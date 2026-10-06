/**
 * Softaware Social Login: button behaviour for Luma (RequireJS module) and Hyvä (plain script).
 * No jQuery, no Knockout.
 *
 * - Adds the current page as "return" URL to the sign-in link.
 * - Popup mode: opens the provider in a popup; the popup tells this window where to go next
 *   (via window.opener, or a BroadcastChannel when the provider cut the opener link).
 * - Widgets marked data-sw-social-guest-only hide themselves for signed-in customers.
 */
(function (factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['Magento_Customer/js/customer-data'], factory);
    } else {
        window.SoftawareSocialLogin = factory(null);
        window.SoftawareSocialLogin();
    }
}(function (customerData) {
    'use strict';

    var CHANNEL = 'sw_social_login';

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
        watchCustomer();
    };
}));
