(function () {
    const config = window.ARAMIX_FRONTEND || {};
    const products = Array.isArray(config.products) ? config.products : [];
    const weights = Array.isArray(config.weights) && config.weights.length ? config.weights : [250, 500, 750, 1000, 1500, 2000];
    const presets = Array.isArray(config.presets) && config.presets.length ? config.presets : [
        { id: 'balanced', title: 'متعادل', desc: 'شکلاتی و آجیل' },
        { id: 'mild', title: 'ملایم', desc: 'تلخ و دودی' },
        { id: 'fruity', title: 'میوه‌ای و مرکباتی', desc: 'روشن و ترش' }
    ];
    const grindOptions = config.grindOptions || {
        home: 'آسیاب خانگی',
        frenchpress: 'آسیاب فرنچ پرس',
        moka: 'آسیاب موکاپات',
        filterpaper: 'آسیاب فیلتر کاغذی (فرانسه)',
        filterfixed: 'آسیاب فیلتر ثابت (فرانسه)',
        pump: 'آسیاب دستگاه پمپی (مسافرتی)',
        industrial: 'آسیاب دستگاه صنعتی',
        semiindustrial: 'آسیاب دستگاه نیمه صنعتی',
        capsule: 'آسیاب برای کپسول شارژی'
    };
    const customer = config.customer || {};

    const state = {
        currentScreen: 'landing',
        preset: presets[0] ? presets[0].id : 'balanced',
        grind: Object.keys(grindOptions)[0] || 'home',
        weight: 250,
        blend: {}
    };

    function formatPrice(value) {
        return Number(value || 0).toLocaleString('fa-IR') + ' تومان';
    }

    function formatNumber(value) {
        return Number(value || 0).toLocaleString('fa-IR');
    }

    function totalBlendPct() {
        return Object.values(state.blend).reduce(function (sum, value) {
            return sum + Number(value || 0);
        }, 0);
    }

    function averagePrice() {
        if (!products.length) {
            return 0;
        }
        return products.reduce(function (sum, item) {
            return sum + Number(item.price || 0);
        }, 0) / products.length;
    }

    function getProductById(id) {
        return products.find(function (product) {
            return Number(product.id) === Number(id);
        }) || null;
    }

    function setScreen(name) {
        state.currentScreen = name;
        document.querySelectorAll('.aramix-screen').forEach(function (screen) {
            screen.classList.toggle('active', screen.dataset.screen === name);
        });

        const topbar = document.getElementById('aramix-topbar');
        const progress = document.getElementById('aramix-progress-track');
        const stepLabel = document.getElementById('aramix-step-label');
        const fill = document.getElementById('aramix-progress-fill');

        if (!topbar || !progress || !stepLabel || !fill) {
            return;
        }

        const order = ['landing', 'builder', 'grind', 'weight', 'checkout'];
        const index = order.indexOf(name);
        const show = name !== 'landing';

        topbar.style.display = show ? 'flex' : 'none';
        progress.style.display = show ? 'block' : 'none';

        if (show) {
            const pct = index > 0 ? ((index) / (order.length - 1)) * 100 : 0;
            fill.style.width = pct + '%';
            const labels = {
                builder: 'ترکیب · ۱ از ۴',
                grind: 'آسیاب · ۲ از ۴',
                weight: 'وزن · ۳ از ۴',
                checkout: 'ثبت سفارش · ۴ از ۴'
            };
            stepLabel.textContent = labels[name] || '';
        } else {
            fill.style.width = '0%';
            stepLabel.textContent = '';
        }
    }

    function defaultBlendDistribution() {
        const result = {};
        if (!products.length) {
            return result;
        }

        const ratios = {
            balanced: [40, 35, 25],
            mild: [35, 40, 25],
            fruity: [25, 30, 45]
        };
        const preset = ratios[state.preset] || ratios.balanced;

        products.forEach(function (product, index) {
            const ratio = preset[index % preset.length] || 25;
            result[String(product.id)] = ratio;
        });

        const total = Object.values(result).reduce(function (sum, val) {
            return sum + Number(val || 0);
        }, 0);

        if (total > 0) {
            Object.keys(result).forEach(function (key) {
                result[key] = Math.round((result[key] / total) * 100);
            });
        }

        const remaining = 100 - Object.values(result).reduce(function (sum, val) {
            return sum + Number(val || 0);
        }, 0);
        if (remaining !== 0 && Object.keys(result).length) {
            const firstKey = Object.keys(result)[0];
            result[firstKey] = Number(result[firstKey] || 0) + remaining;
        }

        state.blend = result;
    }

    function renderPresetButtons() {
        const row = document.getElementById('aramix-preset-row');
        if (!row) {
            return;
        }

        row.innerHTML = presets.map(function (preset) {
            const active = preset.id === state.preset ? 'active' : '';
            return '<button type="button" class="preset-chip ' + active + '" data-preset="' + preset.id + '"><span class="preset-name">' + preset.title + '</span><span class="preset-sub">' + preset.desc + '</span></button>';
        }).join('');

        row.querySelectorAll('.preset-chip').forEach(function (button) {
            button.addEventListener('click', function () {
                state.preset = button.dataset.preset;
                defaultBlendDistribution();
                renderPresetButtons();
                renderBlendRows();
                renderPrice();
            });
        });
    }

    function toggleProductSelected(id) {
        if (!products.length) {
            return;
        }

        state.preset = null;
        const selected = Object.keys(state.blend);
        if (selected.includes(String(id))) {
            delete state.blend[String(id)];
            if (!Object.keys(state.blend).length) {
                defaultBlendDistribution();
            }
        } else {
            const total = Object.values(state.blend).reduce(function (sum, value) {
                return sum + Number(value || 0);
            }, 0);
            const rest = Math.max(0, 100 - total);
            state.blend[String(id)] = rest >= 50 ? 50 : Math.max(rest, 10);
        }

        normalizeBlend();
        renderBlendRows();
        renderProductGrid();
        renderPrice();
        renderPresetButtons();
    }

    function renderProductGrid() {
        const grid = document.getElementById('aramix-origin-grid');
        if (!grid) {
            return;
        }

        if (!products.length) {
            grid.innerHTML = '<div class="blend-empty">هیچ محصولی از پنل آرامیکس انتخاب نشده است.</div>';
            return;
        }

        grid.innerHTML = products.map(function (product) {
            const active = Object.prototype.hasOwnProperty.call(state.blend, String(product.id));
            const productLabel = product.name || product.title || 'محصول';
            const priceLabel = product.price ? formatNumber(product.price) + ' تومان / کیلوگرم' : 'قیمت ندارد';
            return '<button type="button" class="origin-card ' + (active ? 'active' : '') + '" data-product-id="' + product.id + '"><span class="origin-flag">☕</span><span class="origin-name">' + productLabel + '<br><span class="origin-price">' + priceLabel + '</span></span><span class="origin-check">' + (active ? '✓' : '') + '</span></button>';
        }).join('');

        grid.querySelectorAll('.origin-card').forEach(function (button) {
            button.addEventListener('click', function () {
                toggleProductSelected(button.dataset.productId);
            });
        });
    }

    function updateBlendValue(id, delta) {
        state.preset = null;
        const key = String(id);
        const ids = Object.keys(state.blend);
        const index = ids.indexOf(key);
        if (index === -1) {
            return;
        }

        const current = Number(state.blend[key] || 0);
        const next = Math.max(0, current + Number(delta || 0));
        const maxAllowed = ids.length > 1 && index < ids.length - 1
            ? Math.max(0, 100 - ids.filter(function (item, itemIndex) {
                return itemIndex !== index && itemIndex !== ids.length - 1;
            }).reduce(function (sum, item) {
                return sum + Number(state.blend[item] || 0);
            }, 0))
            : 100;

        state.blend[key] = Math.min(next, maxAllowed);

        if (ids.length > 1) {
            const lastKey = ids[ids.length - 1];
            const sumBeforeLast = ids.slice(0, -1).reduce(function (sum, item) {
                return sum + Number(state.blend[item] || 0);
            }, 0);
            state.blend[lastKey] = Math.max(0, 100 - sumBeforeLast);
        }

        renderPresetButtons();
        renderBlendRows();
        renderPrice();
    }

    function setBlendPercent(id, newValue) {
        state.preset = null;
        const key = String(id);
        const ids = Object.keys(state.blend);
        const index = ids.indexOf(key);
        if (index === -1) {
            return;
        }

        const safeValue = Math.max(0, Math.min(100, Number(newValue) || 0));
        const maxAllowed = ids.length > 1 && index < ids.length - 1
            ? Math.max(0, 100 - ids.filter(function (item, itemIndex) {
                return itemIndex !== index && itemIndex !== ids.length - 1;
            }).reduce(function (sum, item) {
                return sum + Number(state.blend[item] || 0);
            }, 0))
            : 100;

        state.blend[key] = Math.min(safeValue, maxAllowed);

        if (ids.length > 1) {
            const lastKey = ids[ids.length - 1];
            const sumBeforeLast = ids.slice(0, -1).reduce(function (sum, item) {
                return sum + Number(state.blend[item] || 0);
            }, 0);
            state.blend[lastKey] = Math.max(0, 100 - sumBeforeLast);
        }

        renderPresetButtons();
        renderBlendRows();
        renderPrice();
    }

    function normalizeBlend() {
        const ids = Object.keys(state.blend);
        if (!ids.length) {
            return;
        }

        if (ids.length > 1) {
            const lastKey = ids[ids.length - 1];
            const sumBeforeLast = ids.slice(0, -1).reduce(function (sum, item) {
                return sum + Number(state.blend[item] || 0);
            }, 0);
            state.blend[lastKey] = Math.max(0, 100 - sumBeforeLast);
        }
    }

    function updateNextButtonState() {
        const button = document.getElementById('aramix-next-builder');
        if (!button) {
            return;
        }

        const enabled = totalBlendPct() === 100;
        button.disabled = !enabled;
        button.style.opacity = enabled ? '1' : '0.45';
        button.style.cursor = enabled ? 'pointer' : 'not-allowed';
    }

    function renderBlendRows() {
        const container = document.getElementById('aramix-blend-rows');
        if (!container) {
            return;
        }

        const keys = Object.keys(state.blend);
        if (!keys.length) {
            container.innerHTML = '<div class="blend-empty">هنوز چیزی انتخاب نکردید</div>';
            document.getElementById('aramix-blend-status').innerHTML = '';
            updateNextButtonState();
            return;
        }

        normalizeBlend();

        container.innerHTML = keys.map(function (id, index) {
            const product = getProductById(id);
            const pct = Number(state.blend[id] || 0);
            const label = product ? (product.name || product.title) : 'محصول انتخابی';
            const isLast = index === keys.length - 1;
            const sliderDisabled = isLast ? 'disabled' : '';
            return '<div class="blend-row" data-product-id="' + id + '"><div class="blend-row-top"><span class="blend-name">☕ ' + label + '</span><span class="stepper"><button class="step-btn" type="button" data-adjust="-1">−</button><span class="step-val">' + formatNumber(pct) + '٪</span><button class="step-btn" type="button" data-adjust="1">+</button></span></div><div class="share-wrap"><input class="share-slider" type="range" min="0" max="100" step="1" value="' + pct + '" data-product-id="' + id + '" ' + sliderDisabled + '><div class="share-track"><div class="share-fill" style="width:' + pct + '%"></div><div class="handle" style="right:' + pct + '%"></div></div></div></div>';
        }).join('');

        container.querySelectorAll('.step-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const row = button.closest('.blend-row');
                const id = row && row.dataset.productId;
                if (!id) {
                    return;
                }
                const val = Number(button.dataset.adjust || 0);
                updateBlendValue(id, val);
            });
        });

        container.querySelectorAll('.share-slider').forEach(function (slider) {
            slider.addEventListener('input', function () {
                const id = slider.dataset.productId;
                if (!id) {
                    return;
                }
                setBlendPercent(id, slider.value);
            });
        });

        const total = totalBlendPct();
        document.getElementById('aramix-blend-status').innerHTML = total === 100 ? '<span class="ok">✓ جمع ترکیب ۱۰۰٪ شد</span>' : '<span class="warn">' + formatNumber(100 - total) + '٪ هنوز باقی مانده</span>';
        updateNextButtonState();
    }

    function renderPrice() {
        const banner = document.getElementById('aramix-price-banner');
        const value = document.getElementById('aramix-price-value');
        if (!banner || !value) {
            return;
        }

        const keys = Object.keys(state.blend);
        if (!keys.length || totalBlendPct() !== 100) {
            banner.style.display = 'none';
            return;
        }

        let total = 0;
        keys.forEach(function (id) {
            const product = getProductById(id);
            const pct = Number(state.blend[id] || 0) / 100;
            if (product) {
                total += Number(product.price || 0) * pct;
            }
        });

        banner.style.display = 'flex';
        value.textContent = formatPrice(total) + ' / کیلوگرم';
    }

    function renderWeightControls() {
        const range = document.getElementById('aramix-weight-range');
        const input = document.getElementById('aramix-weight-input');
        const scale = document.getElementById('aramix-scale');
        if (!range || !input || !scale) {
            return;
        }

        range.min = Math.min.apply(null, weights);
        range.max = Math.max.apply(null, weights);
        range.step = 50;
        range.value = state.weight;
        input.value = state.weight;

        scale.innerHTML = weights.map(function (weight) {
            return '<span>' + formatNumber(weight) + '</span>';
        }).join('');

        range.oninput = function () {
            state.weight = Number(range.value);
            input.value = state.weight;
            renderWeightSummary();
        };

        input.oninput = function () {
            const val = Number(input.value) || 250;
            const clamped = Math.min(Math.max(val, Math.min.apply(null, weights)), Math.max.apply(null, weights));
            state.weight = clamped;
            range.value = state.weight;
            input.value = state.weight;
            renderWeightSummary();
        };
    }

    function renderWeightSummary() {
        const box = document.getElementById('aramix-weight-summary');
        if (!box) {
            return;
        }

        const estimate = averagePrice() * (state.weight / 1000);
        box.innerHTML = '<div class="summary-row"><span class="k">وزن انتخابی</span><span class="v">' + formatNumber(state.weight) + ' گرم</span></div><div class="summary-row"><span class="k">قیمت تقریبی</span><span class="v">' + formatPrice(estimate) + '</span></div>';
    }

    function fillCustomerData() {
        const nameField = document.getElementById('aramix-name');
        const phoneField = document.getElementById('aramix-phone');
        if (nameField && customer.name) {
            nameField.value = customer.name;
        }
        if (phoneField && customer.phone) {
            phoneField.value = customer.phone;
        }
    }

    function renderCheckoutSummary() {
        const target = document.getElementById('aramix-summary-box');
        if (!target) {
            return;
        }

        const keys = Object.keys(state.blend);
        const rows = keys.map(function (id) {
            const product = getProductById(id);
            const pct = Number(state.blend[id] || 0);
            return '<div class="summary-row"><span class="k">' + (product ? (product.name || product.title) : 'محصول') + '</span><span class="v">' + formatNumber(pct) + '%</span></div>';
        }).join('');

        const estimate = averagePrice() * (state.weight / 1000);
        target.innerHTML = rows + '<div class="summary-row"><span class="k">آسیاب</span><span class="v">' + (grindOptions[state.grind] || '-') + '</span></div><div class="summary-row"><span class="k">وزن</span><span class="v">' + formatNumber(state.weight) + ' گرم</span></div><div class="summary-row"><span class="k">قیمت تقریبی</span><span class="v">' + formatPrice(estimate) + '</span></div>';
    }

    function bindButtons() {
        document.querySelectorAll('[data-flow]').forEach(function (button) {
            button.addEventListener('click', function () {
                const flow = button.dataset.flow;
                if (flow === 'builder') {
                    defaultBlendDistribution();
                    renderPresetButtons();
                    renderProductGrid();
                    renderBlendRows();
                    renderPrice();
                    setScreen('builder');
                } else if (flow === 'taste') {
                    alert('این بخش در نسخه‌ی بعدی به فرم سلیقه‌یاب وصل می‌شود.');
                } else if (flow === 'help') {
                    alert('درخواست راهنمایی ثبت شد و همکاران ما با شما تماس می‌گیرند.');
                }
            });
        });

        document.querySelectorAll('[data-back="true"]').forEach(function (button) {
            button.addEventListener('click', function () {
                const current = state.currentScreen;
                if (current === 'grind') {
                    setScreen('builder');
                } else if (current === 'weight') {
                    setScreen('grind');
                } else if (current === 'checkout') {
                    setScreen('weight');
                } else if (current === 'builder') {
                    setScreen('landing');
                }
            });
        });

        document.getElementById('aramix-next-builder')?.addEventListener('click', function () {
            if (totalBlendPct() !== 100) {
                alert('ترکیب باید مجموعاً ۱۰۰٪ باشد.');
                return;
            }
            setScreen('grind');
        });

        document.getElementById('aramix-next-grind')?.addEventListener('click', function () {
            setScreen('weight');
        });

        document.getElementById('aramix-next-weight')?.addEventListener('click', function () {
            renderCheckoutSummary();
            setScreen('checkout');
            fillCustomerData();
        });

        document.getElementById('aramix-submit-order')?.addEventListener('click', function () {
            const name = document.getElementById('aramix-name')?.value.trim() || '';
            const phone = document.getElementById('aramix-phone')?.value.trim() || '';
            if (!name || !/^09\d{9}$/.test(phone)) {
                alert('لطفاً نام و شماره موبایل معتبر وارد کنید.');
                return;
            }
            alert('سفارش شما ثبت شد. همکاران ما در اولین فرصت با شما تماس می‌گیرند.');
        });
    }

    function initializeGrindGrid() {
        const grid = document.getElementById('aramix-grind-grid');
        if (!grid) {
            return;
        }

        const entries = Object.entries(grindOptions);
        grid.innerHTML = entries.map(function ([key, label]) {
            const active = key === state.grind ? 'active' : '';
            return '<div class="choice-card ' + active + '" data-grind="' + key + '"><div class="choice-label">' + label + '</div></div>';
        }).join('');

        grid.querySelectorAll('.choice-card').forEach(function (card) {
            card.addEventListener('click', function () {
                state.grind = card.dataset.grind;
                initializeGrindGrid();
            });
        });
    }

    function initialize() {
        if (!products.length) {
            const grid = document.getElementById('aramix-origin-grid');
            if (grid) {
                grid.innerHTML = '<div class="blend-empty">هیچ محصولی در آرامیکس انتخاب نشده است.</div>';
            }
            return;
        }

        defaultBlendDistribution();
        renderPresetButtons();
        renderProductGrid();
        renderBlendRows();
        renderPrice();
        renderWeightControls();
        renderWeightSummary();
        initializeGrindGrid();
        bindButtons();
        setScreen('landing');

        const nameField = document.getElementById('aramix-name');
        const phoneField = document.getElementById('aramix-phone');
        if (nameField && customer.name) nameField.value = customer.name;
        if (phoneField && customer.phone) phoneField.value = customer.phone;
    }

    document.addEventListener('DOMContentLoaded', initialize);
})();
