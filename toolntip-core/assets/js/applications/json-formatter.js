(function () {
    'use strict';

    var sample = {
        name: 'ToolNTip',
        purpose: 'Learn. Discover. Build. Govern.',
        active: true,
        topics: ['tools', 'resources', 'technology']
    };

    function init(root) {
        if (root.dataset.tntInitialized === '1') return;
        root.dataset.tntInitialized = '1';

        var input = root.querySelector('[data-role="input"]');
        var output = root.querySelector('[data-role="output"]');
        var message = root.querySelector('[data-role="message"]');
        var feedback = root.querySelector('[data-role="feedback"]');
        var stats = root.querySelector('[data-role="stats"]');
        var indent = root.querySelector('[data-setting="indent"]');
        var copy = root.querySelector('[data-action="copy"]');
        var download = root.querySelector('[data-action="download"]');

        function setState(state, text, parsed) {
            feedback.dataset.state = state;
            message.textContent = text;
            if (parsed !== undefined) {
                var rendered = JSON.stringify(parsed);
                var type = Array.isArray(parsed) ? 'Array' : (parsed === null ? 'Null' : typeof parsed === 'object' ? 'Object' : 'Value');
                stats.textContent = type + ' · ' + rendered.length + ' chars';
                stats.hidden = false;
            } else {
                stats.textContent = '';
                stats.hidden = true;
            }
            var hasOutput = output.value.length > 0;
            copy.disabled = !hasOutput;
            download.disabled = !hasOutput;
        }

        function parseInput() {
            var value = input.value.trim();
            if (!value) throw new Error('Enter JSON to continue.');
            return JSON.parse(value);
        }

        function indentValue() {
            return indent.value === 'tab' ? '\t' : Number(indent.value);
        }

        function run(action) {
            if (action === 'sample') {
                input.value = JSON.stringify(sample, null, 2);
                output.value = '';
                setState('ready', 'Sample JSON loaded.');
                input.focus();
                return;
            }
            if (action === 'clear') {
                input.value = '';
                output.value = '';
                setState('ready', 'Ready');
                input.focus();
                return;
            }

            setState('processing', 'Processing…');
            try {
                var parsed = parseInput();
                if (action === 'validate') {
                    output.value = '';
                    setState('success', 'Valid JSON.', parsed);
                    return;
                }
                output.value = action === 'minify'
                    ? JSON.stringify(parsed)
                    : JSON.stringify(parsed, null, indentValue());
                setState('success', action === 'minify' ? 'JSON minified.' : 'JSON formatted.', parsed);
            } catch (error) {
                output.value = '';
                setState('error', 'Invalid JSON: ' + error.message);
            }
        }

        root.addEventListener('click', function (event) {
            var button = event.target.closest('[data-action]');
            if (!button || button.disabled) return;
            var action = button.dataset.action;

            if (['format', 'minify', 'validate', 'sample', 'clear'].indexOf(action) !== -1) {
                run(action);
                return;
            }
            if (action === 'copy' && output.value) {
                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(output.value).then(function () {
                        setState('success', 'Output copied to clipboard.');
                    }).catch(function () {
                        output.select();
                        document.execCommand('copy');
                        setState('success', 'Output copied to clipboard.');
                    });
                } else {
                    output.select();
                    document.execCommand('copy');
                    setState('success', 'Output copied to clipboard.');
                }
                return;
            }
            if (action === 'download' && output.value) {
                var blob = new Blob([output.value], {type: 'application/json;charset=utf-8'});
                var url = URL.createObjectURL(blob);
                var link = document.createElement('a');
                link.href = url;
                link.download = 'formatted.json';
                document.body.appendChild(link);
                link.click();
                link.remove();
                URL.revokeObjectURL(url);
                setState('success', 'JSON download prepared.');
            }
        });
    }

    function boot() {
        document.querySelectorAll('[data-tnt-runtime="json_formatter"]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}());
