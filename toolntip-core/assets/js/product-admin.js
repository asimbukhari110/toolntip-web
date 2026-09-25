(function () {
    'use strict';

    function closestRow(element) {
        return element.closest('.tnt-product-capability-row, .tnt-product-related-row');
    }

    function renumberCapabilities() {
        var rows = document.querySelectorAll('#tnt-product-capabilities-list .tnt-product-capability-row');
        rows.forEach(function (row, index) {
            row.querySelectorAll('[name^="tnt_product_capabilities["]').forEach(function (field) {
                field.name = field.name.replace(/tnt_product_capabilities\[[^\]]+\]/, 'tnt_product_capabilities[' + index + ']');
            });
            var order = row.querySelector('.tnt-capability-order');
            if (order) {
                order.value = String((index + 1) * 10);
            }
        });
    }

    function moveRow(button, direction) {
        var row = closestRow(button);
        if (!row || !row.parentNode) {
            return;
        }
        if (direction < 0 && row.previousElementSibling) {
            row.parentNode.insertBefore(row, row.previousElementSibling);
        } else if (direction > 0 && row.nextElementSibling) {
            row.parentNode.insertBefore(row.nextElementSibling, row);
        }
        renumberCapabilities();
    }

    document.addEventListener('click', function (event) {
        var target = event.target;
        if (!(target instanceof HTMLElement)) {
            return;
        }

        if (target.matches('.tnt-move-up')) {
            event.preventDefault();
            moveRow(target, -1);
        }

        if (target.matches('.tnt-move-down')) {
            event.preventDefault();
            moveRow(target, 1);
        }

        if (target.matches('.tnt-remove-row')) {
            event.preventDefault();
            var row = closestRow(target);
            if (row) {
                row.remove();
                renumberCapabilities();
            }
        }

        if (target.matches('#tnt-product-add-capability')) {
            event.preventDefault();
            var list = document.getElementById('tnt-product-capabilities-list');
            var template = document.getElementById('tmpl-tnt-product-capability-row');
            if (!list || !template) {
                return;
            }
            var index = list.querySelectorAll('.tnt-product-capability-row').length;
            var html = template.innerHTML.replace(/__INDEX__/g, String(index));
            var wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            if (wrapper.firstElementChild) {
                list.appendChild(wrapper.firstElementChild);
                renumberCapabilities();
            }
        }

        if (target.matches('#tnt-product-add-tool')) {
            event.preventDefault();
            var toolSelector = document.getElementById('tnt-product-tool-selector');
            var toolList = document.getElementById('tnt-product-tool-list');
            if (!toolSelector || !toolList || !toolSelector.value) {
                return;
            }
            var toolId = toolSelector.value;
            if (toolList.querySelector('[data-id="' + CSS.escape(toolId) + '"]')) {
                return;
            }
            var toolOption = toolSelector.options[toolSelector.selectedIndex];
            if (toolOption.disabled) {
                return;
            }
            var toolLi = document.createElement('li');
            toolLi.className = 'tnt-product-related-row';
            toolLi.dataset.id = toolId;
            toolLi.innerHTML = '<span class="tnt-product-related-title"></span>' +
                '<input type="hidden" name="tnt_product_tool_ids[]" value="">' +
                '<span class="tnt-product-row-actions">' +
                '<button type="button" class="button-link tnt-move-up">Move Up</button> ' +
                '<button type="button" class="button-link tnt-move-down">Move Down</button> ' +
                '<button type="button" class="button-link-delete tnt-remove-row">Remove</button>' +
                '</span>';
            toolLi.querySelector('.tnt-product-related-title').textContent = toolOption.dataset.title || toolOption.textContent;
            toolLi.querySelector('input').value = toolId;
            toolList.appendChild(toolLi);
            toolSelector.value = '';
        }

        if (target.matches('#tnt-product-add-resource')) {
            event.preventDefault();
            var selector = document.getElementById('tnt-product-resource-selector');
            var resourceList = document.getElementById('tnt-product-resource-list');
            if (!selector || !resourceList || !selector.value) {
                return;
            }
            var id = selector.value;
            if (resourceList.querySelector('[data-id="' + CSS.escape(id) + '"]')) {
                return;
            }
            var option = selector.options[selector.selectedIndex];
            var li = document.createElement('li');
            li.className = 'tnt-product-related-row';
            li.dataset.id = id;
            li.innerHTML = '<span class="tnt-product-related-title"></span>' +
                '<input type="hidden" name="tnt_product_resource_ids[]" value="">' +
                '<span class="tnt-product-row-actions">' +
                '<button type="button" class="button-link tnt-move-up">Move Up</button> ' +
                '<button type="button" class="button-link tnt-move-down">Move Down</button> ' +
                '<button type="button" class="button-link-delete tnt-remove-row">Remove</button>' +
                '</span>';
            li.querySelector('.tnt-product-related-title').textContent = option.dataset.title || option.textContent;
            li.querySelector('input').value = id;
            resourceList.appendChild(li);
            selector.value = '';
        }
    });

    renumberCapabilities();
}());
