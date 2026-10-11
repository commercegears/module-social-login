/**
 * CommerceGears Social Login: button behaviour for Luma (RequireJS module) and Hyvä (plain script).
 * No jQuery, no Knockout.
 *
 * - Adds the current page as "return" URL to the sign-in link.
 * - Popup mode: opens the provider in a popup; the popup tells this window where to go next
 *   (via window.opener, or a BroadcastChannel when the provider cut the opener link).
 * - Widgets marked data-sw-social-guest-only hide themselves for signed-in customers.
 * - In the built-in browsers of Instagram, Facebook and other apps (where Google refuses OAuth sign-in), shows the
 *   "open this page in your browser" note rendered with the buttons, with a "Copy link" button.
 */
(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['Magento_Customer/js/customer-data'], factory);
    } else {
        window.CommerceGearsSocialLogin = factory(null);
        window.CommerceGearsSocialLogin();
    }
}(function (customerData) {
    'use strict';

    var CHANNEL = 'sw_social_login',
        // In-app browsers (WebViews): Facebook, Messenger, Instagram, LinkedIn, Snapchat, Pinterest, TikTok,
        // WeChat, LINE, and Android WebViews in general ("; wv)").
        IN_APP = /FBAN|FBAV|FB_IAB|FBIOS|Instagram|LinkedInApp|Snapchat|Pinterest|TikTok|musical_ly|BytedanceWebview|MicroMessenger|\bLine\/|; wv\)/;

    function isInAppBrowser() {
        return IN_APP.test(window.navigator.userAgent || '');
    }

    function showInAppHints() {
        // Luma: the class shows the notes through CSS (also those Knockout renders later). Hyvä: unhide them.
        document.documentElement.classList.add('sw-social-inapp');
        Array.prototype.forEach.call(document.querySelectorAll('[data-sw-social-inapp][hidden]'), function (node) {
            node.hidden = false;
        });
    }

    function copyLink(button) {
        var url = window.location.href,
            done = function () {
                button.textContent = button.getAttribute('data-copied') || button.textContent;
            },
            fallback = function () {
                // No clipboard access: show the address so it can be selected and copied by hand.
                var target = button.parentNode && button.parentNode.querySelector('[data-sw-social-url]');
                if (target) {
                    target.textContent = url;
                    target.hidden = false;
                }
            };
        if (window.navigator.clipboard && window.navigator.clipboard.writeText) {
            window.navigator.clipboard.writeText(url).then(done, fallback);
        } else {
            fallback();
        }
    }

    function withParams(href, popup) {
        var url = href + (href.indexOf('?') === -1 ? '?' : '&') + 'return=' + encodeURIComponent(window.location.href);
        return popup ? url + '&popup=1' : url;
    }

    function listenForPopup() {
        if (window.swSocialChannel || typeof window.BroadcastChannel !== 'function') {
            return;
        }
        window.swSocialChannel = new window.BroadcastChannel(CHANNEL);
        window.swSocialChannel.onmessage = function (event) {
            var data = event.data || {};
            if (data.type === 'done' && typeof data.target === 'string') {
                window.swSocialChannel.postMessage({type: 'ack'});
                window.location.href = data.target;
            }
        };
    }

    function openPopup(url) {
        var width = 520,
            height = 680,
            left = Math.max(0, (window.screenX || 0) + (window.outerWidth - width) / 2),
            top = Math.max(0, (window.screenY || 0) + (window.outerHeight - height) / 2),
            popup = window.open(
                url,
                'swSocialLogin',
                'width=' + width + ',height=' + height + ',left=' + left + ',top=' + top + ',resizable=yes,scrollbars=yes'
            );
        if (!popup) {
            return false;
        }
        listenForPopup();
        if (popup.focus) {
            popup.focus();
        }
        return true;
    }

    function onClick(event) {
        var copy = event.target && event.target.closest ? event.target.closest('[data-sw-social-copy]') : null;
        if (copy) {
            event.preventDefault();
            copyLink(copy);
            return;
        }
        var link = event.target && event.target.closest ? event.target.closest('a[data-sw-social]') : null;
        if (!link || event.defaultPrevented || event.button > 0 || event.metaKey || event.ctrlKey || event.shiftKey) {
            return;
        }
        event.preventDefault();
        var href = link.getAttribute('href');
        if (link.getAttribute('data-sw-social-popup') === '1' && openPopup(withParams(href, true))) {
            return;
        }
        window.location.href = withParams(href, false);
    }

    function hideForCustomer(isLoggedIn) {
        var nodes = document.querySelectorAll('[data-sw-social-guest-only]');
        Array.prototype.forEach.call(nodes, function (node) {
            node.hidden = !!isLoggedIn;
        });
    }

    function watchCustomer() {
        if (customerData) {
            var customer = customerData.get('customer');
            hideForCustomer(customer() && customer().firstname);
            customer.subscribe(function (data) {
                hideForCustomer(data && data.firstname);
            });
        } else {
            window.addEventListener('private-content-loaded', function (event) {
                var data = event.detail && event.detail.data;
                hideForCustomer(data && data.customer && data.customer.firstname);
            });
        }
    }

    return function init() {
        if (window.swSocialLoginReady) {
            return;
        }
        window.swSocialLoginReady = true;
        document.addEventListener('click', onClick);
        if (isInAppBrowser()) {
            showInAppHints();
        }
        watchCustomer();
    };
}));
