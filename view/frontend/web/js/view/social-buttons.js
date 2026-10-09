/**
 * Softaware Social Login: buttons inside Knockout-rendered areas (Luma authentication popup, checkout).
 */
define([
    'uiComponent',
    'Softaware_SocialLogin/js/social-login',
    'mage/translate'
], function (Component, socialLogin, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Softaware_SocialLogin/social-buttons',
            buttons: [],
            heading: '',
            place: 'auth_popup',
            buttonStyle: 'icon_text',
            buttonTheme: 'light',
            popup: false,
            showDivider: false,
            inAppText: '',
            copyText: '',
            copiedText: ''
        },

        initialize: function () {
            this._super();
            this.inAppText = $t('You are using the browser built into another app. Google does not allow sign-in there, and other providers may not work either. Please open this page in your browser, for example with "Open in browser" in the app\'s menu.');
            this.copyText = $t('Copy link');
            this.copiedText = $t('Link copied. Paste it into your browser.');
            socialLogin();
            return this;
        },

        getCssClass: function () {
            return 'sw-social sw-social--' + this.buttonStyle + ' sw-social--' + this.buttonTheme +
                ' sw-social--' + this.place;
        },

        isIconOnly: function () {
            return this.buttonStyle === 'icon';
        }
    });
});
