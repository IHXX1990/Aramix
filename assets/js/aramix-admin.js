(function ($) {
    const productOptions = window.ARAMIX_ADMIN_PRODUCTS || [];
    const storageField = $('#aramix-products-field');
    const repeater = $('#aramix-repeater');

    function toSelectedArray() {
        try {
            const parsed = JSON.parse(storageField.val() || '[]');
            if (!Array.isArray(parsed)) {
                return [];
            }
            return parsed.filter(function (value) {
                return value !== '' && value !== null && value !== undefined;
            });
        } catch (e) {
            return [];
        }
    }

    function syncHiddenValue() {
        const ids = [];
        repeater.find('.aramix-product-value').each(function () {
            const val = $(this).val();
            if (val && val !== '') {
                ids.push(Number(val));
            }
        });
        storageField.val(JSON.stringify(ids));
    }

    function getProductById(id) {
        return productOptions.find(function (product) {
            return Number(product.id) === Number(id);
        });
    }

    function renderOptionsList(listEl, selectedId) {
        listEl.empty();
        const filtered = productOptions.filter(function (product) {
            return product.title && product.title.toLowerCase().indexOf((listEl.data('search') || '').toLowerCase()) !== -1;
        });

        if (!filtered.length) {
            listEl.append('<div class="aramix-no-result">محصولی پیدا نشد</div>');
            return;
        }

        filtered.forEach(function (product) {
            const item = $('<button type="button" class="aramix-product-option"></button>');
            const label = product.title + (product.price ? ' — ' + Number(product.price).toLocaleString('fa-IR') + ' تومان' : '');
            item.text(label);
            item.attr('data-id', product.id);
            if (Number(selectedId) === Number(product.id)) {
                item.addClass('selected');
            }
            item.on('click', function () {
                const parentRow = $(this).closest('.aramix-row');
                const valueInput = parentRow.find('.aramix-product-value');
                const labelBox = parentRow.find('.aramix-product-label');
                valueInput.val(product.id);
                labelBox.text(label);
                parentRow.find('.aramix-product-menu').removeClass('open');
                syncHiddenValue();
            });
            listEl.append(item);
        });
    }

    function renderRow(selectedId) {
        const row = $('<div class="aramix-row"></div>');
        const wrap = $('<div class="aramix-product-wrap"></div>');
        const valueInput = $('<input type="hidden" class="aramix-product-value" value="' + (selectedId || '') + '">');
        const label = getProductById(selectedId);
        const labelBox = $('<button type="button" class="aramix-product-label"></button>');
        const menu = $('<div class="aramix-product-menu"></div>');
        const search = $('<input type="text" class="aramix-product-search" placeholder="جستجو و انتخاب محصول..." aria-label="جستجو محصول">');
        const list = $('<div class="aramix-product-list"></div>');
        const removeBtn = $('<button type="button" class="aramix-remove-row" aria-label="حذف">×</button>');

        labelBox.text(label ? (label.title + (label.price ? ' — ' + Number(label.price).toLocaleString('fa-IR') + ' تومان' : '')) : 'محصول را انتخاب کنید');

        menu.append(search).append(list);
        wrap.append(valueInput).append(labelBox).append(menu);
        row.append(wrap).append(removeBtn);

        renderOptionsList(list, selectedId);
        list.data('search', '');

        labelBox.on('click', function () {
            menu.toggleClass('open');
            if (menu.hasClass('open')) {
                search.focus();
            }
        });

        search.on('input', function () {
            list.data('search', $(this).val());
            renderOptionsList(list, valueInput.val());
        });

        $(document).on('click.aramix', function (event) {
            if (!$(event.target).closest('.aramix-product-wrap').length) {
                menu.removeClass('open');
            }
        });

        removeBtn.on('click', function () {
            if (repeater.find('.aramix-row').length > 1) {
                row.remove();
                syncHiddenValue();
            }
        });

        return row;
    }

    function renderRows() {
        repeater.empty();
        const selected = toSelectedArray();
        const rows = selected.length ? selected : [''];

        rows.forEach(function (productId) {
            repeater.append(renderRow(productId));
        });
    }

    $(document).ready(function () {
        renderRows();
        syncHiddenValue();

        $('#aramix-add-row').on('click', function () {
            repeater.append(renderRow(''));
            syncHiddenValue();
        });
    });
})(jQuery);
