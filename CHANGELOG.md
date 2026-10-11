# Changelog

## 1.4.2 (2026-10-11)
- Button labels accept plain text only (up to 60 characters); sort orders must be whole numbers from 0 to 9999.
- The Test Provider settings group is shown only in developer mode, the only mode in which it works.
- Linked Accounts mass delete message: "1 social login link has been removed" instead of "link(s)".
- Requires Softaware Core 1.3, which checks these settings on the server when they are saved.

## 1.4.1 (2026-10-09)
- Facebook setup hint and user guide show the current Meta app dashboard path.
- Docs: no dashes as punctuation in headings and version ranges.

## 1.4.0 (2026-10-09)
- Note above the buttons in in-app browsers (Instagram, Facebook, TikTok and others) with a "Copy link" button.
- Facebook Graph API v26.0 by default, with "Use system value" to follow the module default.
- Google setup hint uses the current Google Cloud console names; docs link to softawarecommerce.com.
- Doc screenshots retaken.

## 1.3.0 (2026-10-07)
- Works with Social Login Pro: Apple, Microsoft, LinkedIn, Amazon and GitHub in the same settings and buttons.
- Extension points for modules that add providers (icons, self-test checks, callback URL comment).
- Links of providers that are no longer installed are shown and can be removed safely.
- Self-test covers ES256 ID tokens, issuer checks and links of removed providers.

## 1.2.1 (2026-10-07)
- User guide, FAQ and screenshots; README compatibility corrected to Magento 2.4.7 to 2.4.9.

## 1.2.0 (2026-10-06)
- Requires Softaware Core instead of the retired Softaware Base; admin menu and ACL under "Softaware".

## 1.1.0 (2026-10-06)
- Security: open redirect after sign-in fixed; return URLs are parsed strictly.
- Security: accounts from unverified email addresses always need email confirmation before use.
- Automatic linking by email only for providers responsible for the address (Gmail at Google).
- New setting Create New Accounts to let only existing customers sign in with a provider.
- Fixed confirmation emails and confirmed state with "Require Emails Confirmation" switched on.

## 1.0.0 (2026-10-06)
- First release: sign-in and registration with Google, Facebook and X, built on OAuth 2.0 and OpenID Connect.
- Buttons on sign-in, create-account, authentication popup, checkout, CMS widget and layout block (Luma and Hyvä).
- Account matching by verified email, short form for a missing email or name, customer group and welcome email.
- Connected Accounts in My Account, Linked Accounts grid and customer tab in the admin, CLI commands.
