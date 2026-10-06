/**
 * Softaware Social Login: buttons inside Knockout-rendered areas (Luma authentication popup, checkout).
 */
define([
    'uiComponent',
    'Softaware_SocialLogin/js/social-login'
], function (Component, socialLogin) {
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
            showDivider: false
        },

        initialize: function () {
            this._super();
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
