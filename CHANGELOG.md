# Changelog

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
