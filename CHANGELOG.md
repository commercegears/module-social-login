# Changelog

## 2.0.3 (2026-10-15)
- Improvement: complete and consistent German translation.
- Improvement: focus outlines follow your theme's colour.

## 2.0.2 (2026-10-12)
- Improvement: compatible with Magento 2.4.6 (PHP 8.2).

## 2.0.1 (2026-10-12)
- Improvement: user guide screenshots and links updated for the CommerceGears name

## 2.0.0 (2026-10-11)
- Improvement: renamed to CommerceGears (new Composer package, admin menu and settings paths)

## 1.4.2 (2026-10-11)
- Improvement: button labels accept plain text only (up to 60 characters); sort orders 0 to 9999.
- Improvement: the Test Provider settings are shown only in developer mode, the only mode in which they work.
- Improvement: Linked Accounts mass delete says "1 social login link has been removed" instead of "link(s)".
- Improvement: requires CommerceGears Core 1.3, which checks these settings on the server when they are saved.

## 1.4.1 (2026-10-09)
- Improvement: Facebook setup hint and user guide show the current Meta app dashboard path.
- Improvement: clearer documentation headings and version ranges.

## 1.4.0 (2026-10-09)
- New: note above the buttons in in-app browsers (Instagram, Facebook, TikTok and others) with "Copy link".
- Improvement: Facebook Graph API v26.0 by default, with "Use system value" to follow the module default.
- Improvement: Google setup hint uses current Google Cloud console names; docs link to commercegears.com.
- Improvement: documentation screenshots retaken.

## 1.3.0 (2026-10-07)
- New: works with Social Login Pro, adding Apple, Microsoft, LinkedIn, Amazon and GitHub to the same buttons.
- New: extension points for modules that add providers (icons, self-test checks, callback URL comment).
- Improvement: links of providers that are no longer installed are shown and can be removed safely.
- Improvement: self-test covers ES256 ID tokens, issuer checks and links of removed providers.

## 1.2.1 (2026-10-07)
- New: user guide, FAQ and screenshots.
- Fix: documented compatibility corrected to Magento 2.4.7 to 2.4.9.

## 1.2.0 (2026-10-06)
- Improvement: admin menu and permissions moved under "CommerceGears".

## 1.1.0 (2026-10-06)
- Fix: open redirect after sign-in closed; return URLs are now parsed strictly.
- Improvement: accounts from unverified e-mail addresses always need e-mail confirmation before use.
- Improvement: automatic linking by e-mail only for providers responsible for the address (Gmail at Google).
- New: setting Create New Accounts, to let only existing customers sign in with a provider.
- Fix: confirmation e-mails and confirmed state with "Require Emails Confirmation" switched on.

## 1.0.0 (2026-10-06)
- New: sign-in and registration with Google, Facebook and X, built on OAuth 2.0 and OpenID Connect.
- New: buttons on sign-in, create-account, popup, checkout, CMS widget and layout block (Luma and Hyvä).
- New: matching by verified e-mail, short form for missing details, customer group and welcome e-mail.
- New: Connected Accounts in My Account, Linked Accounts grid and customer tab in the admin, CLI commands.
