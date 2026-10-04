(function () {
    'use strict';

    function copyText(text, button) {
        var original = button.textContent;

        function done() {
            button.textContent = 'Copied';
            window.setTimeout(function () {
                button.textContent = original;
            }, 1600);
        }

        function fallback() {
            var area = document.createElement('textarea');
            var copied = false;

            area.value = text;
            area.setAttribute('readonly', 'readonly');
            area.style.position = 'fixed';
            area.style.opacity = '0';
            area.style.pointerEvents = 'none';
            document.body.appendChild(area);
            area.focus();
            area.select();
            area.setSelectionRange(0, area.value.length);

            try {
                copied = document.execCommand('copy');
            } catch (error) {
                copied = false;
            }

            document.body.removeChild(area);
            if (copied) {
                done();
            }
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(done).catch(fallback);
            return;
        }

        fallback();
    }

    document.addEventListener('click', function (event) {
        var button = event.target.closest('.tnt-product-copy');
        var text;

        if (!button) {
            return;
        }

        text = button.getAttribute('data-copy') || '';
        if (!text) {
            return;
        }

        copyText(text, button);
    });

    function initDownloadPreparation(root) {
        var resolutionUrl = root.getAttribute('data-resolution-url') || '';
        var title = root.querySelector('[data-tnt-download-title]');
        var message = root.querySelector('[data-tnt-download-message]');
        var start = root.querySelector('[data-tnt-download-start]');
        var retry = root.querySelector('[data-tnt-download-retry]');
        var back = root.querySelector('[data-tnt-download-back]');
        var requestInFlight = false;
        var navigationTimer = null;

        if (!resolutionUrl || !title || !message || !start || !retry || !back) {
            return;
        }

        function setState(state, heading, detail) {
            root.setAttribute('data-state', state);
            title.textContent = heading;
            message.textContent = detail;
        }

        function resolveDownload() {
            if (requestInFlight) {
                return;
            }

            requestInFlight = true;
            start.hidden = true;
            retry.hidden = true;
            back.hidden = true;
            setState('preparing', 'Preparing your download…', 'Creating a secure download link. This may take a few seconds.');

            window.fetch(resolutionUrl, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                cache: 'no-store'
            }).then(function (response) {
                return response.json().then(function (payload) {
                    return { response: response, payload: payload };
                });
            }).then(function (result) {
                var payload = result.payload || {};
                var data = payload.data || {};
                var destination = data.url || '';

                if (!result.response.ok || !payload.success || !destination) {
                    throw new Error(data.message || 'Unable to prepare download.');
                }

                requestInFlight = false;
                start.href = destination;
                start.hidden = false;
                setState('ready', 'Your download is ready', 'Your download is starting…');

                navigationTimer = window.setTimeout(function () {
                    window.location.assign(destination);
                }, 650);
            }).catch(function () {
                requestInFlight = false;
                if (navigationTimer) {
                    window.clearTimeout(navigationTimer);
                    navigationTimer = null;
                }
                retry.hidden = false;
                back.hidden = false;
                setState('error', 'Unable to prepare your download', 'We could not create a download link right now. Please try again.');
            });
        }

        retry.addEventListener('click', resolveDownload);
        resolveDownload();
    }

    var preparation = document.querySelector('[data-tnt-download-preparation]');
    if (preparation) {
        initDownloadPreparation(preparation);
    }
}());
