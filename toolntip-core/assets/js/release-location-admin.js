(function () {
    'use strict';

    var config = window.tntReleaseLocationAdmin || {};

    if (!config.ajaxUrl || !config.ajaxAction || !window.fetch || !window.FormData) {
        return;
    }

    function getSection() {
        return document.querySelector('[data-tnt-storage-replicas]');
    }

    function getEditorForm(section) {
        if (!section) {
            return null;
        }

        var actionInput = section.querySelector('form[data-tnt-storage-form] input[name="tnt_release_location_action"][value="save_location"]');
        return actionInput ? actionInput.form : null;
    }

    function setBusy(section, busy) {
        if (!section) {
            return;
        }

        section.setAttribute('aria-busy', busy ? 'true' : 'false');
        section.querySelectorAll('button, input[type="submit"]').forEach(function (control) {
            control.disabled = busy;
        });
    }

    function showClientError(section, message) {
        var notice = section ? section.querySelector('[data-tnt-storage-notice]') : null;
        if (!notice) {
            return;
        }

        notice.innerHTML = '';
        var box = document.createElement('div');
        box.className = 'notice notice-error inline';
        var paragraph = document.createElement('p');
        paragraph.textContent = message || config.errorText || 'The storage replica operation could not be completed.';
        box.appendChild(paragraph);
        notice.appendChild(box);
    }

    function replaceSection(html) {
        var current = getSection();
        if (!current || !html) {
            return null;
        }

        var template = document.createElement('template');
        template.innerHTML = String(html).trim();
        var replacement = template.content.querySelector('[data-tnt-storage-replicas]');

        if (!replacement) {
            return null;
        }

        current.replaceWith(replacement);
        return replacement;
    }

    function updateReplicaCounts(data) {
        if (!data || !data.releaseId) {
            return;
        }

        var total = document.querySelector('[data-tnt-replica-count="' + data.releaseId + '"]');
        if (total && typeof data.replicaCount !== 'undefined') {
            total.textContent = String(data.replicaCount);
        }

        var enabled = document.querySelector('[data-tnt-enabled-replica-count="' + data.releaseId + '"]');
        if (enabled && typeof data.enabledReplicaCount !== 'undefined') {
            enabled.textContent = String(data.enabledReplicaCount);
        }
    }

    function resetEditor(section) {
        var form = getEditorForm(section);
        if (!form) {
            return;
        }

        var locationId = form.querySelector('input[name="location_id"]');
        var provider = form.querySelector('[name="provider"]');
        var reference = form.querySelector('[name="provider_file_id"]');
        var enabled = form.querySelector('[name="enabled"]');
        var priority = form.querySelector('[name="priority"]');
        var weight = form.querySelector('[name="weight"]');
        var heading = section.querySelector('[data-tnt-storage-form-heading]');
        var submit = form.querySelector('input[type="submit"], button[type="submit"]');
        var cancel = form.querySelector('[data-tnt-storage-cancel]');

        if (locationId) { locationId.value = '0'; }
        if (provider) { provider.value = 'google_drive'; }
        if (reference) { reference.value = ''; }
        if (enabled) { enabled.checked = true; }
        if (priority) { priority.value = '100'; }
        if (weight) { weight.value = '100'; }
        if (heading) { heading.textContent = config.addHeading || 'Add Storage Replica'; }
        if (submit) { submit.value = config.addButton || 'Add Storage Replica'; submit.textContent = config.addButton || 'Add Storage Replica'; }
        if (cancel) { cancel.hidden = true; }
    }

    function enterEditMode(link) {
        var section = getSection();
        var form = getEditorForm(section);
        if (!section || !form) {
            return false;
        }

        var locationId = form.querySelector('input[name="location_id"]');
        var provider = form.querySelector('[name="provider"]');
        var reference = form.querySelector('[name="provider_file_id"]');
        var enabled = form.querySelector('[name="enabled"]');
        var priority = form.querySelector('[name="priority"]');
        var weight = form.querySelector('[name="weight"]');
        var heading = section.querySelector('[data-tnt-storage-form-heading]');
        var submit = form.querySelector('input[type="submit"], button[type="submit"]');
        var cancel = form.querySelector('[data-tnt-storage-cancel]');

        if (locationId) { locationId.value = link.dataset.locationId || '0'; }
        if (provider) { provider.value = link.dataset.provider || 'google_drive'; }
        if (reference) { reference.value = link.dataset.fileReference || ''; }
        if (enabled) { enabled.checked = link.dataset.enabled === '1'; }
        if (priority) { priority.value = link.dataset.priority || '100'; }
        if (weight) { weight.value = link.dataset.weight || '100'; }
        if (heading) { heading.textContent = config.editHeading || 'Edit Storage Replica'; }
        if (submit) { submit.value = config.updateButton || 'Update Storage Replica'; submit.textContent = config.updateButton || 'Update Storage Replica'; }
        if (cancel) { cancel.hidden = false; }

        if (reference) {
            reference.focus({ preventScroll: true });
        }

        return true;
    }

    document.addEventListener('click', function (event) {
        var edit = event.target.closest('[data-tnt-storage-edit]');
        if (edit) {
            if (enterEditMode(edit)) {
                event.preventDefault();
            }
            return;
        }

        var cancel = event.target.closest('[data-tnt-storage-cancel]');
        if (cancel) {
            var section = getSection();
            if (section) {
                event.preventDefault();
                resetEditor(section);
            }
        }
    });

    document.addEventListener('submit', function (event) {
        var form = event.target.closest('form[data-tnt-storage-form]');
        if (!form || event.defaultPrevented) {
            return;
        }

        event.preventDefault();

        var section = getSection();
        if (!section || section.getAttribute('aria-busy') === 'true') {
            return;
        }

        var formData = new FormData(form);
        formData.append('action', config.ajaxAction);

        setBusy(section, true);

        fetch(config.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
            .then(function (response) {
                return response.json().catch(function () {
                    throw new Error(config.errorText || 'Invalid server response.');
                });
            })
            .then(function (payload) {
                var data = payload && payload.data ? payload.data : {};
                var replacement = data.html ? replaceSection(data.html) : null;

                if (!replacement) {
                    throw new Error(data.message || config.errorText || 'The storage replica operation could not be completed.');
                }

                replacement.setAttribute('aria-busy', 'false');
                updateReplicaCounts(data);

                var notice = replacement.querySelector('[data-tnt-storage-notice]');
                if (notice && notice.textContent.trim()) {
                    notice.setAttribute('tabindex', '-1');
                    notice.focus({ preventScroll: true });
                }
            })
            .catch(function (error) {
                var activeSection = getSection();
                setBusy(activeSection, false);
                showClientError(activeSection, error && error.message ? error.message : config.errorText);
            });
    });
})();
