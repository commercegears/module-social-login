# Changelog

## 1.2.0 (2026-10-06)

### Changed

- Requires `softaware/module-core` instead of `softaware/module-base`. The admin menu and ACL now sit under `Softaware_Core::core` ("Softaware"); roles that had access keep it (migrated by module-core). After updating all SoftAware modules, `softaware/module-base` can be removed.

## 1.1.0 (2026-10-06)

### Security
- Open redirect after sign-in fixed: return URLs such as `https:///evil.example/` or
  `https://evil.example\@your-shop/` passed the "own host" check (PHP and browsers read them differently) and
  sent the customer to another site after signing in. Return URLs are now parsed strictly (no backslashes,
  control characters, user info or empty hosts) before the host check.
- Pre-account hijacking: an account created from an email address the provider has not verified (typed in by
  the shopper, Amazon, the test provider) now always needs Magento's email confirmation before it can be used,
  whether or not the store requires confirmation. Before, anybody could create an account in someone else's name
  through a provider without email (e.g. X) and keep access through the linked provider after the real owner
  had reset the password.
- Linking to an existing account by email is limited to providers that are responsible for the address: Google
  only for Gmail and Google Workspace addresses (Google's own guidance), Facebook no longer (the Graph API has no
  verification flag). Everything else asks for the account password once, as before for unverified addresses.
  The same rule applies when an unconfirmed account is confirmed through a provider.

### Added
- Accounts > **Create New Accounts** (default Yes). Set to No to let only existing customers sign in with a
  provider; shoppers without an account are asked to register first.
- Hint on the "Connect your account" password step for customers who signed up with another provider and have no
  password.
- Self-test: return URL (open redirect) checks and email trust rules per provider.

### Fixed
- With "Require Emails Confirmation" switched on, a new social customer with an unverified address got the
  "welcome (no password)" email without a confirmation link and could never activate the account. The
  confirmation email is now sent.
- With "Require Emails Confirmation" switched on, accounts created from a verified address stayed unconfirmed in
  the database (password sign-in later said "not confirmed"). They are now confirmed.
- When another module refused the sign-in (e.g. Softaware Customer Approval: account waiting for approval), the
  customer saw two messages; now only the other module's explanation is shown. A refused sign-in is also detected
  when the session ends up with a different customer.

## 1.0.0

First release.

- Sign-in and registration with Google, Sign in with Apple, Facebook, Microsoft (Entra ID), GitHub, LinkedIn,
  Amazon and X (Twitter), implemented directly on OAuth 2.0 / OpenID Connect (no provider SDKs).
- State, nonce and PKCE (S256) where supported; ID token signature validation via JWKS (RS256/ES256) for Google,
  Microsoft, Apple and LinkedIn; browser-bound, single-use state stored server-side.
- Apple: ES256 client secret from the .p8 key, `form_post` callback with session restore.
- Buttons on the sign-in and create-account pages, the Luma authentication popup, the checkout sign-in and
  (optional) checkout email step, the Hyvä authentication popup, a CMS widget and a layout block. Icon+text or
  icon-only, light or dark, popup or full-page redirect.
- Account matching: verified email links automatically (or asks for the password, configurable); missing email
  or name is asked for on a short form; new customers get a configurable group and optional welcome email.
- My Account > Connected Accounts (connect, disconnect with lock-out protection).
- Admin grid Softaware > Social Login > Linked Accounts, "Social Login" tab on the customer edit page.
- CLI: `softaware:social-login:links`, `softaware:social-login:unlink`, `softaware:social-login:self-test`.
- Luma and Hyvä templates; en_US and de_DE translations.
- Test provider for development (developer mode only, disabled by default).
