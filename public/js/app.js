document.addEventListener('DOMContentLoaded', () => {
    // Mobile menu (staff area)
    document.querySelectorAll('[data-menu-toggle]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            document.body.classList.toggle('menu-open');
        });
    });
    document.addEventListener('click', (e) => {
        if (document.body.classList.contains('menu-open') && !e.target.closest('.sidebar')) {
            document.body.classList.remove('menu-open');
        }
    });

    // Year -> class dependent select
    document.querySelectorAll('[data-year-select]').forEach((yearSelect) => {
        const classSelect = document.getElementById(yearSelect.dataset.yearSelect);
        if (!classSelect) return;
        const options = Array.from(classSelect.querySelectorAll('option[data-year]'));
        const placeholder = classSelect.querySelector('option:not([data-year])');

        const refresh = () => {
            const year = yearSelect.value;
            options.forEach((opt) => {
                const match = !year || opt.dataset.year === year;
                opt.hidden = !match;
                opt.disabled = !match;
            });
            if (classSelect.selectedOptions[0]?.disabled) classSelect.value = '';
            const visible = options.filter((o) => !o.disabled);
            if (visible.length === 1 && year) classSelect.value = visible[0].value;
            classSelect.disabled = !year && yearSelect.required;
            if (placeholder) placeholder.textContent = year ? 'Selecione a turma' : 'Escolha primeiro a sala/ano';
        };
        yearSelect.addEventListener('change', refresh);
        refresh();
    });

    // Meeting form: checking a year selects all of its classes (RB18); classes can be unchecked (RB19)
    document.querySelectorAll('[data-year-toggle]').forEach((yearBox) => {
        const block = yearBox.closest('.year-block');
        const classBoxes = block.querySelectorAll('[data-class-box]');
        const sync = () => {
            const checked = Array.from(classBoxes).filter((c) => c.checked).length;
            yearBox.checked = checked > 0;
            yearBox.indeterminate = checked > 0 && checked < classBoxes.length;
        };
        yearBox.addEventListener('change', () => {
            classBoxes.forEach((c) => { c.checked = yearBox.checked; });
            yearBox.indeterminate = false;
        });
        classBoxes.forEach((c) => c.addEventListener('change', sync));
        sync();
    });

    // Confirmation for destructive actions
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (!window.confirm(form.dataset.confirm)) e.preventDefault();
        });
    });

    // Prevent double submit
    document.querySelectorAll('form[data-once]').forEach((form) => {
        form.addEventListener('submit', (e) => {
            if (form.dataset.submitted) {
                e.preventDefault();
                return;
            }
            form.dataset.submitted = '1';
            // Deferred so the clicked button's name/value is still included in the submission
            setTimeout(() => {
                form.querySelectorAll('button[type=submit]').forEach((b) => {
                    b.disabled = true;
                    if (b.dataset.loading) b.textContent = b.dataset.loading;
                });
            }, 0);
        });
    });

    // "Salvar no celular": installs the site on the home screen
    const installBtn = document.querySelector('[data-install-app]');
    const installHelp = document.querySelector('[data-install-help]');
    const standalone = window.matchMedia('(display-mode: standalone)').matches || navigator.standalone;
    const swUrl = document.querySelector('script[data-sw]')?.dataset.sw;
    if (swUrl && 'serviceWorker' in navigator) navigator.serviceWorker.register(swUrl).catch(() => {});
    if (installBtn && !standalone) {
        let deferred = null;
        const ua = navigator.userAgent;
        const platform = /iphone|ipad|ipod/i.test(ua) || (/macintosh/i.test(ua) && navigator.maxTouchPoints > 1)
            ? 'ios' : (/android/i.test(ua) ? 'android' : 'desktop');
        installBtn.hidden = false;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferred = e;
        });
        installBtn.addEventListener('click', async () => {
            if (deferred) {
                deferred.prompt();
                await deferred.userChoice;
                deferred = null;
                return;
            }
            // No direct install available: show the steps for this device.
            installHelp.querySelectorAll('[data-platform]').forEach((el) => { el.hidden = el.dataset.platform !== platform; });
            installHelp.hidden = !installHelp.hidden;
        });
        window.addEventListener('appinstalled', () => { installBtn.hidden = true; installHelp.hidden = true; });
    }

    // Auto-submit filter selects
    document.querySelectorAll('form[data-autosubmit] select').forEach((sel) => {
        sel.addEventListener('change', () => sel.form.submit());
    });
});
