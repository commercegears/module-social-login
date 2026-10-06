/**
 * Softaware Social Login: runs in the sign-in popup when the flow has finished.
 * Sends the opening window to the next page and closes the popup; falls back to
 * continuing inside the popup when no opening window answers.
 */
(function () {
    'use strict';

    var link = document.getElementById('sw-social-done'),
        target = link ? link.href : '/',
        done = false,
        channel = null;

    function finish() {
        done = true;
        window.close();
        window.setTimeout(function () {
            window.location.replace(target);
        }, 300);
    }

    try {
        if (window.opener && !window.opener.closed && window.opener.location.host === window.location.host) {
            window.opener.location.href = target;
            finish();
            return;
        }
    } catch (e) {
        // Opener is cross-origin or unreachable: use the channel.
    }

    if (typeof window.BroadcastChannel === 'function') {
        channel = new window.BroadcastChannel('sw_social_login');
        channel.onmessage = function (event) {
            if (event.data && event.data.type === 'ack' && !done) {
                channel.close();
                finish();
            }
        };
        channel.postMessage({type: 'done', target: target});
    }

    window.setTimeout(function () {
        if (!done) {
            done = true;
            window.location.replace(target);
        }
    }, 800);
}());
