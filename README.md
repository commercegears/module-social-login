# Softaware Social Login for Magento 2

Lets customers sign in and register with **Google, Apple, Facebook, Microsoft, GitHub, LinkedIn, Amazon and X**.
OAuth 2.0 and OpenID Connect are implemented directly on Magento's HTTP client and PHP's OpenSSL extension;
no provider SDKs or JWT libraries are installed. Works with Luma-based themes and Hyvä.

Package `softaware/module-social-login`, module `Softaware_SocialLogin`.

## Features

- **Providers**: Google (OpenID Connect + PKCE), Sign in with Apple (client secret JWT from the .p8 key,
  `response_mode=form_post`), Facebook (Graph API with `appsecret_proof`), Microsoft Entra ID (v2.0, `common`
  tenant by default, PKCE), GitHub (verified email from `/user/emails`), LinkedIn (OpenID Connect), Amazon
  (Login with Amazon, PKCE), X / Twitter (OAuth 2.0 with PKCE). Each: enable, client ID, secret (stored
  encrypted), button label, sort order. The exact callback URL for the selected store view is shown under each
  provider in the configuration.
- **Where**: sign-in page, create-account page, Luma authentication popup ("Checkout using your account"), the
  "Sign In" link on the checkout page, the checkout email step (optional), Hyvä authentication popup, CMS widget
  **Softaware Social Login Buttons**, and a layout block (`Softaware\SocialLogin\Block\Buttons`).
- **Look**: icon and text, or icon only; light or dark. Sign-in in a **popup window** or a **full-page redirect**.
- **Security**
  - `state` (random, single use, stored server-side, bound to the browser by a cookie, 15-minute lifetime),
    `nonce` (OIDC) and PKCE S256 (Google, Microsoft, GitHub, Amazon, X).
  - ID tokens of Google, Microsoft, Apple and LinkedIn are validated against the provider's JWKS (RS256/ES256,
    `alg` whitelist, issuer, audience, expiry, nonce). Keys are cached and refreshed on rotation.
  - Email addresses are only treated as verified when the provider says so (`email_verified`, GitHub verified
    flag, Microsoft only for personal accounts or `xms_edov`). Amazon and typed-in addresses never count as
    verified.
  - Callback is CSRF-safe through the state; all customer-side state changes use POST with form key.
  - Return URLs are restricted to this installation (no open redirect). Secrets, codes and tokens are never
    logged.
- **Account matching**
  - An identity that is already linked signs in its customer.
  - An existing customer with the same, **verified** email is linked automatically, or (configurable) only after
    entering the account password. Unverified emails always require the password.
  - New customers are created with name and email from the provider, in a configurable customer group, with an
    optional welcome email ("Welcome Email (without password)" template). If the store requires email
    confirmation and the email is not verified, the normal confirmation email is sent instead.
  - When the provider returns no email (X; Apple when the customer hides it on a later sign-in) or no name, the
    customer completes a short form.
- **My Account > Connected Accounts**: see linked providers, connect another, disconnect. Disconnecting is refused
  when it would leave the customer without a way to sign in (no password and no other provider).
- **Admin**: Softaware > Social Login > Linked Accounts (grid with filters, remove links); "Social Login" tab on
  the customer edit page; ACL for viewing, removing and configuration.
- **GDPR**: links are deleted together with the customer (foreign key `ON DELETE CASCADE`). Pending sign-in data is
  deleted after 15 minutes (cron).
- **Test provider** (development only): a local identity provider to try the whole flow without provider apps.
  Disabled by default, works only in developer mode, and its addresses are never treated as verified.

## Configuration

Stores > Configuration > Softaware > Social Login (`softaware_sociallogin/*`), also reachable from
Softaware > Social Login > Configuration.

| Group | Settings |
| --- | --- |
| General | Enabled, sign-in window (redirect/popup), button style, button colour |
| Where to Show the Buttons | Sign-in page, create-account page, authentication popup and checkout sign-in, checkout email step |
| Accounts | Existing account with the same email (link automatically / ask for password), welcome email, customer group, after sign-in (stay on page / My Account / custom URL) |
| Google … X | Enabled (with the callback URL), client ID, client secret, button label, sort order; Apple: Services ID, Team ID, Key ID, private key; Microsoft: tenant; Facebook: Graph API version; X: request email |
| Test Provider | Enabled (developer mode only) |

Scope: default, website and store view (credentials can differ per domain). Provider codes (config group and
callback URL segment): `google`, `apple`, `facebook`, `microsoft`, `github`, `linkedin`, `amazon`, `twitter` (X), `test`.

### Provider setup in short

Register the callback URL shown in the configuration exactly as displayed (one per store view URL):

- **Google**: Google Cloud Console > APIs & Services > Credentials > OAuth client ID (Web application).
- **Apple**: Services ID with "Sign in with Apple" (domain + return URL), a key with "Sign in with Apple";
  enter Services ID, Team ID, Key ID and paste the .p8 file.
- **Facebook**: Facebook Login product, "Valid OAuth Redirect URIs", `email` permission.
- **Microsoft**: App registration for "any organisational directory and personal Microsoft accounts", Web
  redirect URI, client secret.
- **GitHub**: OAuth App, "Authorization callback URL".
- **LinkedIn**: product "Sign In with LinkedIn using OpenID Connect", authorised redirect URL.
- **Amazon**: Login with Amazon security profile, "Allowed Return URLs".
- **X**: User authentication settings, OAuth 2.0, type Web App, callback URL. Switch on "Request email" only if
  the app has the email permission.

### Widget and layout block

Content > Widgets > Add Widget > "Softaware Social Login Buttons" (heading, style, colour). In layout XML:

```xml
<block class="Softaware\SocialLogin\Block\Buttons" name="my.social.buttons">
    <arguments>
        <argument name="place" xsi:type="string">widget</argument>
        <argument name="title" xsi:type="string">Sign in with</argument>
    </arguments>
</block>
```

The block picks the Luma or Hyvä template automatically and hides itself for signed-in customers in the
browser, so it can be used on full-page-cached pages.

### Adding a provider

Implement `Softaware\SocialLogin\Model\Provider\ProviderInterface` (or extend `AbstractProvider` /
`AbstractOidcProvider`) and add it to the `providers` argument of `Softaware\SocialLogin\Model\Provider\ProviderPool`
in `di.xml`. Its configuration lives in `softaware_sociallogin/<code>/...`.

## CLI

```
bin/magento softaware:social-login:links [--customer=<id|email>] [--provider=google] [--limit=50]
bin/magento softaware:social-login:unlink <link_id>
bin/magento softaware:social-login:self-test [--accounts]
```

`self-test` checks PKCE (RFC 7636 test vector), ID token validation incl. tampering, wrong issuer/audience/nonce,
expired tokens and `alg` confusion, the Apple ES256 client secret, the state store and, with `--accounts`, the
account matching rules using temporary "Test SocialLogin" customers that are deleted afterwards. It makes no
calls to providers.

## Compatibility

- Magento Open Source / Adobe Commerce 2.4.6 – 2.4.9, PHP 8.2 – 8.5, ext-openssl.
- Luma, Blank and themes based on them; Hyvä 1.3+ (tested with Hyvä 1.5). The Hyvä checkout uses the Luma
  checkout fallback, so the checkout buttons are the Luma ones.
- Requires `softaware/module-base` ^1.2.

## Limitations

- Real provider sign-ins could not be tested on the development server (no provider apps); the protocol pieces
  are covered by the self-test and the full flow by the test provider.
- Facebook only returns confirmed email addresses, so they are treated as verified. Amazon does not say, so
  Amazon addresses are treated as unverified (existing customers confirm with their password once).
- One identity per provider per customer.
- Microsoft: given/family name are only returned when configured as optional claims; otherwise the display
  name is split.
- Callback URLs are per store view URL; with store codes in URLs, register one per store view.
- Customers created through social sign-in have no password until they set one with "Forgot Your Password?".

## Log

`var/log/softaware_sociallogin.log` (failed sign-ins with the technical reason; no secrets or tokens).
