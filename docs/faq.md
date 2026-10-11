# Social Login for Magento 2: FAQ

**Which providers are supported?**
Google, Facebook and X (Twitter). Each one can be switched on separately, per website or store view.

**Can I offer Apple, Microsoft, LinkedIn, Amazon or GitHub as well?**
Yes, with the add-on **Social Login Pro**. It adds these five providers to the same configuration section, buttons,
Connected Accounts page and admin tools; everything described here applies to them too.

**What happens when a shopper opens the shop from Instagram or Facebook?**
Those apps open links in their own built-in browser, where Google does not allow sign-in. Social Login recognises
these browsers and shows a note above the buttons asking the shopper to open the page in their browser, with a
"Copy link" button. This works on Luma, the checkout and Hyvä.

**Does it work with Hyvä?**
Yes. Hyvä templates are included (Hyvä 1.3+, tested with 1.5). On the Hyvä checkout the Luma checkout fallback is
used, so the checkout buttons are the Luma ones.

**Does it install third-party SDKs?**
No. OAuth 2.0 and OpenID Connect are implemented on Magento's HTTP client and PHP's OpenSSL extension, so there are
no extra libraries to keep up to date or to conflict with other extensions.

**Where can the buttons appear?**
On the sign-in page, the create-account page, the authentication popup and the checkout sign-in, above the email
field in checkout (guests), anywhere as a CMS widget, and anywhere in your layout as a block.

**What happens if a customer already has an account with the same email?**
The provider is linked to that account. If the provider has verified the address and is responsible for it (for
example Gmail addresses at Google), this happens automatically; otherwise, or if you choose so, the customer enters
their account password once. After that the provider signs them in directly.

**Can I allow only existing customers to sign in with a provider?**
Yes: set **Create New Accounts** to **No**. Shoppers without an account are asked to register first. This is
useful for B2B and trade-only shops.

**Does it work with customer approval?**
Yes, with CommerceGears Customer Approval: social sign-ups get an approval status like any other registration, and
pending or rejected customers are not signed in.

**What if the provider does not give an email address?**
X never returns one unless your app has the email permission. The customer then enters an email address once; an account created from a typed-in address must be confirmed by email before it can be used.

**Can customers remove a connection?**
Yes, under **My Account > Connected Accounts**. The module refuses to remove the last way to sign in (no password
and no other provider). Admins can remove links under **CommerceGears > Social Login > Linked Accounts**.

**Is it GDPR friendly?**
Only the link between the customer and the provider account is stored, no access tokens. Links are deleted with the
customer, and pending sign-in data is deleted after 15 minutes.

**How is it protected against account takeover?**
Every sign-in uses a single-use `state` bound to the browser, PKCE where the provider supports it, and full ID token
validation (signature, issuer, audience, expiry, nonce). Email addresses are only trusted when the provider has
verified them, and accounts from unverified addresses must be confirmed by email first. Return URLs are restricted
to your own store.

**Can I use different provider apps for each domain?**
Yes. All settings, including the credentials, can be set per website and store view. Register one callback URL per
store view URL at the provider.

**Can I add another provider?**
Yes, developers can implement `ProviderInterface` (or extend `AbstractOidcProvider`) and register it in `di.xml`;
icons, settings and self-test checks have extension points too. See the README.

**What happens to links of a provider that is not installed (for example after removing an add-on)?**
They are kept and shown with their provider code in the admin and under Connected Accounts, where they can be
removed. They are not counted as a way to sign in, so customers are never left without one.

**Which Magento and PHP versions are supported?**
Magento Open Source and Adobe Commerce 2.4.7 to 2.4.9, PHP 8.2 to 8.5.
