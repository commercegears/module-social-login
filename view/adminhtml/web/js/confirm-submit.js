/**
 * Softaware Social Login: asks for confirmation before submitting a form (admin).
 */
define(['Magento_Ui/js/modal/confirm'], function (confirm) {
    'use strict';

    return function (config, form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.confirmed === '1') {
                return;
            }
            event.preventDefault();
            confirm({
                title: config.title || '',
                content: config.message || '',
                actions: {
                    confirm: function () {
                        form.dataset.confirmed = '1';
                        form.submit();
                    }
                }
            });
        });
    };
});
