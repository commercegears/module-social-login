<?php
/**
 * Softaware Social Login
 *
 * @copyright Copyright (c) Softaware Commerce (https://www.softawarecommerce.co.uk/)
 */
declare(strict_types=1);

namespace Softaware\SocialLogin\Console\Command;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;
use Softaware\SocialLogin\Api\Data\LinkInterfaceFactory;
use Softaware\SocialLogin\Api\LinkRepositoryInterface;
use Softaware\SocialLogin\Model\Account\AccountMatcher;
use Softaware\SocialLogin\Model\Account\Decision;
use Softaware\SocialLogin\Model\Account\SocialAccountService;
use Softaware\SocialLogin\Model\AuthRequest;
use Softaware\SocialLogin\Model\Config;
use Softaware\SocialLogin\Model\Jwt\Base64Url;
use Softaware\SocialLogin\Model\Jwt\Der;
use Softaware\SocialLogin\Model\Jwt\Es256Signer;
use Softaware\SocialLogin\Model\Jwt\JwtException;
use Softaware\SocialLogin\Model\Jwt\JwtValidator;
use Softaware\SocialLogin\Model\Profile;
use Softaware\SocialLogin\Model\Provider\AbstractProvider;
use Softaware\SocialLogin\Model\StateStore;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks the security-relevant building blocks without contacting any provider:
 * PKCE, JWT/JWKS validation (RS256, ES256) incl. tampering, the Apple client secret,
 * the state store and (with --accounts) the account matching rules.
 */
class SelfTestCommand extends Command
{
    private int $passed = 0;
    private int $failed = 0;
    private ?OutputInterface $output = null;

    public function __construct(
        private readonly JwtValidator $jwtValidator,
        private readonly Es256Signer $es256Signer,
        private readonly StateStore $stateStore,
        private readonly ResourceConnection $resource,
        private readonly AccountMatcher $accountMatcher,
        private readonly SocialAccountService $accountService,
        private readonly LinkRepositoryInterface $linkRepository,
        private readonly LinkInterfaceFactory $linkFactory,
        private readonly CustomerRepositoryInterface $customerRepository,
        private readonly CustomerInterfaceFactory $customerFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly Registry $registry,
        private readonly AppState $appState
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('softaware:social-login:self-test')
            ->setDescription('Tests PKCE, ID token validation, the Apple client secret, the state store and account matching (no provider calls).')
            ->addOption('accounts', null, InputOption::VALUE_NONE, 'Also test account matching with temporary "Test SocialLogin" customers (removed afterwards).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->output = $output;
        try {
            $this->appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (\Exception $e) {
            // Area already set.
        }

        $output->writeln('<info>PKCE and encoding</info>');
        $this->check('RFC 7636 appendix B challenge', AbstractProvider::codeChallenge('dBjftJeZ4CVP-mB92K27uhbUJU1p1r_wW1gFWFOEjXk') === 'E9Melhoa2OwvFrEMTJguCHaoeK1t8URWbuGJSstw-cM');
        $token = StateStore::randomToken(48);
        $this->check('verifier length 43-128 and unreserved characters', strlen($token) >= 43 && strlen($token) <= 128 && preg_match('/^[A-Za-z0-9_\-]+$/', $token) === 1);
        $this->check('base64url round trip', Base64Url::decode(Base64Url::encode("\x00\xff\xfe binary")) === "\x00\xff\xfe binary");

        $this->testRs256();
        $this->testEs256AndApple();
        $this->testStateStore();
        if ($input->getOption('accounts')) {
            $this->testAccounts();
        }

        $output->writeln(sprintf('%s%d passed, %d failed%s', $this->failed ? '<error>' : '<info>', $this->passed, $this->failed, $this->failed ? '</error>' : '</info>'));
        return $this->failed ? Command::FAILURE : Command::SUCCESS;
    }

    private function testRs256(): void
    {
        $this->output->writeln('<info>ID token validation (RS256 / JWKS)</info>');
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
        $details = openssl_pkey_get_details($key);
        $jwks = ['keys' => [
            ['kty' => 'RSA', 'kid' => 'other', 'use' => 'sig', 'n' => Base64Url::encode(random_bytes(256)), 'e' => 'AQAB'],
            ['kty' => 'RSA', 'kid' => 'k1', 'use' => 'sig', 'alg' => 'RS256', 'n' => Base64Url::encode($details['rsa']['n']), 'e' => Base64Url::encode($details['rsa']['e'])],
        ]];
        $now = time();
        $claims = ['iss' => 'https://issuer.example', 'aud' => 'client-1', 'sub' => 'user-1', 'iat' => $now, 'exp' => $now + 300, 'nonce' => 'n-123', 'email' => 'a@example.com', 'email_verified' => true];
        $expect = ['issuer' => 'https://issuer.example', 'audience' => 'client-1', 'nonce' => 'n-123', 'nonce_required' => true];
        $sign = static function (array $header, array $payload) use ($key): string {
            $input = Base64Url::encode(json_encode($header)) . '.' . Base64Url::encode(json_encode($payload));
            openssl_sign($input, $signature, $key, OPENSSL_ALGO_SHA256);
            return $input . '.' . Base64Url::encode($signature);
        };
        $valid = $sign(['alg' => 'RS256', 'kid' => 'k1'], $claims);

        $this->expectValid('valid token accepted', $valid, $jwks, $expect);
        $parts = explode('.', $valid);
        $tampered = $parts[0] . '.' . Base64Url::encode(json_encode(['sub' => 'admin'] + $claims)) . '.' . $parts[2];
        $this->expectInvalid('tampered payload rejected', $tampered, $jwks, $expect);
        $this->expectInvalid('wrong audience rejected', $valid, $jwks, ['audience' => 'client-2'] + $expect);
        $this->expectInvalid('wrong issuer rejected', $valid, $jwks, ['issuer' => 'https://evil.example'] + $expect);
        $this->expectInvalid('wrong nonce rejected', $valid, $jwks, ['nonce' => 'n-999'] + $expect);
        $this->expectInvalid('missing nonce rejected when required', $sign(['alg' => 'RS256', 'kid' => 'k1'], array_diff_key($claims, ['nonce' => 1])), $jwks, $expect);
        $this->expectValid('missing nonce accepted when optional', $sign(['alg' => 'RS256', 'kid' => 'k1'], array_diff_key($claims, ['nonce' => 1])), $jwks, ['nonce_required' => false] + $expect);
        $this->expectInvalid('expired token rejected', $sign(['alg' => 'RS256', 'kid' => 'k1'], ['exp' => $now - 600] + $claims), $jwks, $expect);
        $this->expectInvalid('unknown kid rejected', $sign(['alg' => 'RS256', 'kid' => 'nope'], $claims), $jwks, $expect);
        $this->expectInvalid('signature by the wrong key rejected', $sign(['alg' => 'RS256', 'kid' => 'other'], $claims), $jwks, $expect);
        $none = Base64Url::encode(json_encode(['alg' => 'none'])) . '.' . Base64Url::encode(json_encode($claims)) . '.';
        $this->expectInvalid('alg "none" rejected', $none, $jwks, $expect);
        $this->expectInvalid('alg HS256 rejected', $sign(['alg' => 'HS256', 'kid' => 'k1'], $claims), $jwks, $expect);
        $this->expectValid('issuer pattern (Microsoft style)', $sign(['alg' => 'RS256', 'kid' => 'k1'], ['iss' => 'https://login.microsoftonline.com/9188040d-6c67-4c5b-b112-36a304b66dad/v2.0'] + $claims), $jwks, ['issuer_pattern' => '#^https://login\.microsoftonline\.com/[0-9a-f\-]{36}/v2\.0$#'] + array_diff_key($expect, ['issuer' => 1]));
    }

    private function testEs256AndApple(): void
    {
        $this->output->writeln('<info>ES256 and Sign in with Apple client secret</info>');
        $key = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
        openssl_pkey_export($key, $pem);
        $details = openssl_pkey_get_details($key);
        $jwks = ['keys' => [[
            'kty' => 'EC', 'crv' => 'P-256', 'kid' => 'ABC123DEFG',
            'x' => Base64Url::encode($details['ec']['x']), 'y' => Base64Url::encode($details['ec']['y']),
        ]]];
        // .p8 pasted into a password field loses its line breaks: rebuild and sign.
        $flattened = str_replace("\n", ' ', $pem);
        $now = time();
        try {
            $secret = $this->es256Signer->sign(
                ['iss' => 'TEAMID1234', 'iat' => $now, 'exp' => $now + 300, 'aud' => 'https://appleid.apple.com', 'sub' => 'com.example.signin'],
                $flattened,
                ['kid' => 'ABC123DEFG']
            );
            [$header] = $this->jwtValidator->decodeUnverified($secret);
            $this->check('client secret header has alg ES256 and kid', $header['alg'] === 'ES256' && $header['kid'] === 'ABC123DEFG');
            $this->check('raw R||S signature is 64 bytes', strlen(Base64Url::decode(explode('.', $secret)[2])) === 64);
            $claims = $this->jwtValidator->validate($secret, $jwks, [
                'issuer' => 'TEAMID1234', 'audience' => 'https://appleid.apple.com', 'algorithms' => ['ES256'],
            ]);
            $this->check('client secret verifies with the public key', $claims['sub'] === 'com.example.signin');
        } catch (\Throwable $e) {
            $this->check('client secret: ' . $e->getMessage(), false);
        }
        $raw = random_bytes(64);
        $this->check('DER <-> raw signature conversion', Der::derToRawSignature(Der::rawToDerSignature($raw)) === $raw);
        try {
            $this->es256Signer->sign(['a' => 1], 'not a key');
            $this->check('invalid .p8 key rejected', false);
        } catch (JwtException $e) {
            $this->check('invalid .p8 key rejected', true);
        }
    }

    private function testStateStore(): void
    {
        $this->output->writeln('<info>State store (state, browser binding, single use, expiry)</info>');
        $state = StateStore::randomToken();
        $binding = StateStore::randomToken();
        $request = new AuthRequest('google', $state, 'nonce', 'verifier', 'https://example.com/cb', 1, AuthRequest::INTENT_LOGIN, '', false, 0, 'sid');
        $this->stateStore->save($request, $binding);
        $this->check('wrong browser binding rejected', $this->stateStore->peek($state, StateStore::randomToken()) === null);
        $peeked = $this->stateStore->peek($state, $binding);
        $this->check('peek returns the stored request', $peeked !== null && $peeked->codeVerifier === 'verifier' && $peeked->sessionId === 'sid');
        $this->stateStore->save($request->withCallbackParams(['code' => 'abc']), $binding);
        $consumed = $this->stateStore->consume($state, $binding);
        $this->check('consume returns stored callback parameters', $consumed !== null && $consumed->callbackParams === ['code' => 'abc']);
        $this->check('state cannot be used twice', $this->stateStore->consume($state, $binding) === null);
        $this->check('unknown state rejected', $this->stateStore->consume(StateStore::randomToken(), $binding) === null);

        $old = StateStore::randomToken();
        $this->stateStore->save(new AuthRequest('google', $old, 'n', 'v', 'u', 1), $binding);
        $this->resource->getConnection()->update(
            $this->resource->getTableName(StateStore::TABLE),
            ['created_at' => gmdate('Y-m-d H:i:s', time() - StateStore::TTL - 60)],
            ['state_hash = ?' => hash('sha256', $old)]
        );
        $this->check('expired state rejected', $this->stateStore->consume($old, $binding) === null);
        $this->stateStore->deleteExpired();
    }

    private function testAccounts(): void
    {
        $this->output->writeln('<info>Account matching (temporary customers)</info>');
        $store = $this->storeManager->getDefaultStoreView();
        $websiteId = (int)$store->getWebsiteId();
        $suffix = bin2hex(random_bytes(4));
        $email = 'test.sociallogin.' . $suffix . '@example.com';
        $customer = $this->customerFactory->create()
            ->setEmail($email)->setFirstname('Test')->setLastname('SocialLogin')
            ->setWebsiteId($websiteId)->setStoreId((int)$store->getId());
        $customer = $this->customerRepository->save($customer);
        $customerId = (int)$customer->getId();
        try {
            $verified = new Profile('google', 'g-' . $suffix, $email, true, 'Test', 'SocialLogin');
            $unverified = new Profile('amazon', 'a-' . $suffix, $email, false, 'Test', 'SocialLogin');
            $noEmail = new Profile('twitter', 'x-' . $suffix, null, false, 'Test', 'SocialLogin');
            $newPerson = new Profile('google', 'g2-' . $suffix, 'new.' . $email, true, 'New', 'Person');
            $noName = new Profile('apple', 'ap-' . $suffix, 'new2.' . $email, true, '', '');

            $this->check('verified email + auto-link => link existing', $this->accountMatcher->decide($verified, $websiteId, Config::LINK_AUTO)->type === Decision::LINK_EXISTING);
            $this->check('verified email + "ask password" => ask password', $this->accountMatcher->decide($verified, $websiteId, Config::LINK_PASSWORD)->type === Decision::ASK_PASSWORD);
            $this->check('unverified email => ask password', $this->accountMatcher->decide($unverified, $websiteId, Config::LINK_AUTO)->type === Decision::ASK_PASSWORD);
            $this->check('no email => ask for details', $this->accountMatcher->decide($noEmail, $websiteId, Config::LINK_AUTO)->type === Decision::ASK_DETAILS);
            $this->check('unknown email => create', $this->accountMatcher->decide($newPerson, $websiteId, Config::LINK_AUTO)->type === Decision::CREATE);
            $this->check('unknown email without name => ask for details', $this->accountMatcher->decide($noName, $websiteId, Config::LINK_AUTO)->type === Decision::ASK_DETAILS);

            $link = $this->linkFactory->create()
                ->setCustomerId($customerId)->setWebsiteId($websiteId)
                ->setProvider('twitter')->setProviderUserId('x-' . $suffix);
            $this->linkRepository->save($link);
            $decision = $this->accountMatcher->decide($noEmail, $websiteId, Config::LINK_AUTO);
            $this->check('linked identity => sign in linked customer', $decision->type === Decision::LOGIN_LINKED && $decision->customerId === $customerId);
            $this->check('unlink refused: only method and no password', !$this->accountService->canUnlink($customerId, 'twitter', false));
            $this->check('unlink allowed with a password', $this->accountService->canUnlink($customerId, 'twitter', true));
            $second = $this->linkFactory->create()
                ->setCustomerId($customerId)->setWebsiteId($websiteId)
                ->setProvider('google')->setProviderUserId('g-' . $suffix);
            $this->linkRepository->save($second);
            $this->check('unlink allowed when another provider remains', $this->accountService->canUnlink($customerId, 'twitter', false));
            try {
                $this->linkRepository->save(
                    $this->linkFactory->create()->setCustomerId($customerId)->setWebsiteId($websiteId)
                        ->setProvider('google')->setProviderUserId('g-other-' . $suffix)
                );
                $this->check('second identity for the same provider refused', false);
            } catch (\Exception $e) {
                $this->check('second identity for the same provider refused', true);
            }
        } finally {
            $this->registry->register('isSecureArea', true, true);
            $this->customerRepository->deleteById($customerId);
            $this->registry->unregister('isSecureArea');
            $this->check('links deleted together with the customer (GDPR)', $this->linkRepository->getListByCustomerId($customerId) === []);
        }
    }

    private function expectValid(string $label, string $jwt, array $jwks, array $expect): void
    {
        try {
            $this->jwtValidator->validate($jwt, $jwks, $expect);
            $this->check($label, true);
        } catch (JwtException $e) {
            $this->check($label . ' (' . $e->getMessage() . ')', false);
        }
    }

    private function expectInvalid(string $label, string $jwt, array $jwks, array $expect): void
    {
        try {
            $this->jwtValidator->validate($jwt, $jwks, $expect);
            $this->check($label, false);
        } catch (JwtException | \InvalidArgumentException $e) {
            $this->check($label, true);
        }
    }

    private function check(string $label, bool $ok): void
    {
        $ok ? $this->passed++ : $this->failed++;
        $this->output->writeln(sprintf('  %s %s', $ok ? '<info>PASS</info>' : '<error>FAIL</error>', $label));
    }
}
