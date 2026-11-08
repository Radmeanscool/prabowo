/*
 * DEEPNEST — perilaku antarmuka ringan tanpa framework.
 * Tidak ada skrip inline di Blade agar Content-Security-Policy tetap ketat.
 */

function onReady(fn) {
    if (document.readyState !== 'loading') {
        fn();
    } else {
        document.addEventListener('DOMContentLoaded', fn);
    }
}

function countWords(text) {
    const trimmed = (text || '').trim();
    return trimmed === '' ? 0 : trimmed.split(/\s+/u).length;
}

onReady(() => {
    // Toggle menu/sidebar: <button data-toggle="#target">
    // Opsional data-toggle-class="is-open" untuk elemen yang selalu tampil di layar lebar.
    document.querySelectorAll('[data-toggle]').forEach((button) => {
        const target = document.querySelector(button.dataset.toggle);
        if (!target) return;
        const cls = button.dataset.toggleClass || 'is-hidden';
        button.addEventListener('click', () => {
            const toggled = target.classList.toggle(cls);
            const expanded = cls === 'is-hidden' ? !toggled : toggled;
            document.querySelectorAll(`[data-toggle="${button.dataset.toggle}"]`).forEach((b) => b.setAttribute('aria-expanded', expanded ? 'true' : 'false'));
        });
    });

    // Tutup <details data-dropdown> saat klik di luar.
    document.addEventListener('click', (event) => {
        document.querySelectorAll('details[data-dropdown][open]').forEach((details) => {
            if (!details.contains(event.target)) details.removeAttribute('open');
        });
    });

    // Konfirmasi sebelum mengirim formulir: <form data-confirm="...">
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) {
                event.preventDefault();
            }
        });
    });

    // Konfirmasi pada tombol tertentu: <button data-confirm-click="...">
    document.querySelectorAll('[data-confirm-click]').forEach((button) => {
        button.addEventListener('click', (event) => {
            if (!window.confirm(button.dataset.confirmClick)) {
                event.preventDefault();
            }
        });
    });

    // Penghitung kata: <textarea data-word-limit="300">
    document.querySelectorAll('textarea[data-word-limit]').forEach((textarea) => {
        const limit = parseInt(textarea.dataset.wordLimit, 10);
        const counter = document.createElement('p');
        counter.className = 'word-counter';
        counter.setAttribute('aria-live', 'polite');
        textarea.insertAdjacentElement('afterend', counter);
        const update = () => {
            const words = countWords(textarea.value);
            counter.textContent = `${words} / ${limit} kata`;
            counter.classList.toggle('is-over', words > limit);
        };
        textarea.addEventListener('input', update);
        update();
    });

    // Pilih tema → tampilkan tantangan yang relevan (validasi tetap di server).
    const themeInputs = document.querySelectorAll('input[name="theme_id"][data-theme-filter]');
    if (themeInputs.length) {
        const cards = document.querySelectorAll('[data-challenge-themes]');
        const emptyNote = document.querySelector('[data-challenge-empty]');
        const prompt = document.querySelector('[data-challenge-prompt]');
        const apply = () => {
            const selected = document.querySelector('input[name="theme_id"][data-theme-filter]:checked');
            let visible = 0;
            cards.forEach((card) => {
                const themes = (card.dataset.challengeThemes || '').split(',');
                const show = selected ? themes.includes(selected.value) : false;
                card.classList.toggle('is-hidden', !show);
                if (!show) {
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio) radio.checked = false;
                } else {
                    visible++;
                }
            });
            if (emptyNote) emptyNote.classList.toggle('is-hidden', !selected || visible > 0);
            if (prompt) prompt.classList.toggle('is-hidden', !!selected);
        };
        themeInputs.forEach((input) => input.addEventListener('change', apply));
        apply();
    }

    // Salin teks: <button data-copy="teks">
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
                const original = button.textContent;
                button.textContent = 'Tersalin';
                setTimeout(() => (button.textContent = original), 1600);
            } catch (e) {
                window.prompt('Salin kode berikut:', button.dataset.copy);
            }
        });
    });

    // Pilih semua checkbox: <input type="checkbox" data-check-all="name[]">
    document.querySelectorAll('[data-check-all]').forEach((master) => {
        master.addEventListener('change', () => {
            document.querySelectorAll(`input[type="checkbox"][name="${master.dataset.checkAll}"]`).forEach((box) => {
                box.checked = master.checked;
            });
        });
    });

    // Kirim otomatis saat filter berubah: <select data-autosubmit>
    document.querySelectorAll('[data-autosubmit]').forEach((field) => {
        field.addEventListener('change', () => field.form && field.form.submit());
    });
});
