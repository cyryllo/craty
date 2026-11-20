// Skaner QR/kodów kreskowych kamerą — ładowany tylko na stronach skanowania
// (osobny entry point Vite, patrz vite.config.js), żeby @zxing nie obciążało
// reszty appki. Zwykły JS, nie Alpine — kolejność ładowania modułów Vite (ten
// plik obok app.js, który sam startuje Alpine.start() od razu po
// zaimportowaniu) nie gwarantuje, że nasłuch na "alpine:init" zdąży się
// zarejestrować przed tym, jak Alpine już wystartuje, więc prościej i pewniej
// operować wprost na DOM-ie.
//
// Dwa tryby (data-mode na #scanner):
// - "single" (domyślny, /scan): pierwszy trafiony kod otwiera kartę
//   przedmiotu. Własny QR z etykiety koduje pełny URL karty (zwykły redirect),
//   a goły kod kreskowy (EAN/UPC) idzie do /scan/lookup.
// - "batch" (/scan/batch, inwentaryzacja): kamera działa dalej, a kolejne
//   kody trafiają na listę (rozpoznawane przez /scan/resolve). Lista żyje w
//   sessionStorage, więc przeżywa odświeżenie strony. Z data-record-url każdy
//   rozpoznany przedmiot jest od razu zapisywany na serwerze (inwentaryzacja).
import { BrowserMultiFormatReader } from '@zxing/browser';
import { BarcodeFormat, DecodeHintType } from '@zxing/library';

document.addEventListener('DOMContentLoaded', () => {
    const root = document.getElementById('scanner');
    if (! root) {
        return;
    }

    const video = root.querySelector('video');
    // "scanning" nie ma własnego panelu — to po prostu stan, w którym
    // wszystkie nakładki są ukryte i widać czysty podgląd z kamery.
    const panels = {
        starting: root.querySelector('[data-panel="starting"]'),
        error: root.querySelector('[data-panel="error"]'),
        redirecting: root.querySelector('[data-panel="redirecting"]'),
    };
    const errorText = root.querySelector('[data-error-text]');
    const messages = {
        noCamera: root.dataset.errNoCamera,
        permission: root.dataset.errPermission,
        generic: root.dataset.errGeneric,
    };

    let controls = null;

    function show(panel) {
        Object.values(panels).forEach((el) => el?.classList.add('hidden'));
        panels[panel]?.classList.remove('hidden'); // 'scanning' → brak wpisu, wszystko zostaje ukryte
    }

    function fail(message) {
        show('error');
        if (errorText) {
            errorText.textContent = message;
        }
    }

    const onResult = root.dataset.mode === 'batch' ? batchMode(root) : singleMode(root);

    (async () => {
        show('starting');

        const hints = new Map();
        hints.set(DecodeHintType.POSSIBLE_FORMATS, [
            BarcodeFormat.QR_CODE,
            BarcodeFormat.EAN_13,
            BarcodeFormat.EAN_8,
            BarcodeFormat.UPC_A,
            BarcodeFormat.UPC_E,
            BarcodeFormat.CODE_128,
            // Zwykłe kody kreskowe 1D spotykane na etykietach producenta/SN
            // poza EAN/UPC/Code128 — np. Code 39 to częsty wybór na
            // naklejkach z numerem seryjnym sprzętu.
            BarcodeFormat.CODE_39,
            BarcodeFormat.CODE_93,
            BarcodeFormat.CODABAR,
            BarcodeFormat.ITF,
        ]);
        const reader = new BrowserMultiFormatReader(hints);

        try {
            const devices = await BrowserMultiFormatReader.listVideoInputDevices();
            if (devices.length === 0) {
                fail(messages.noCamera);

                return;
            }

            // Wolimy tylną kamerę na telefonie ("environment"), jeśli da się ją
            // rozpoznać po etykiecie — inaczej pierwsza dostępna wystarczy.
            const back = devices.find((d) => /back|rear|environment/i.test(d.label));
            const deviceId = (back ?? devices[0]).deviceId;

            show('scanning');
            controls = await reader.decodeFromVideoDevice(deviceId, video, (result) => {
                if (result) {
                    onResult(result.getText(), { stop: () => controls?.stop(), show, fail });
                }
            });
        } catch (e) {
            fail(e?.name === 'NotAllowedError' ? messages.permission : messages.generic);
        }
    })();

    window.addEventListener('beforeunload', () => controls?.stop());
});

/** Zwykły skan: pierwszy kod otwiera kartę przedmiotu (albo szybkie dodawanie). */
function singleMode(root) {
    const lookupUrl = root.dataset.lookupUrl;
    const quickAddUrl = root.dataset.quickAddUrl;
    let handled = false;

    return async (text, { stop, show, fail }) => {
        if (handled) {
            return; // pierwszy trafiony kod wygrywa, ignorujemy kolejne klatki
        }
        handled = true;
        show('redirecting');
        stop();

        if (/^https?:\/\//i.test(text)) {
            window.location.href = text;

            return;
        }

        try {
            const response = await fetch(`${lookupUrl}?code=${encodeURIComponent(text)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();

            window.location.href = data.found
                ? data.url
                : `${quickAddUrl}?code=${encodeURIComponent(text)}`;
        } catch (e) {
            fail(root.dataset.errGeneric);
        }
    };
}

/** Seria skanów: kolejne kody na listę, kamera działa dalej. */
function batchMode(root) {
    const resolveUrl = root.dataset.resolveUrl;
    const recordUrl = root.dataset.recordUrl;
    const storageKey = root.dataset.storageKey || 'craty-batch';
    const list = document.querySelector('[data-batch-list]');
    const empty = document.querySelector('[data-batch-empty]');
    const counter = document.querySelectorAll('[data-batch-count]');
    const flash = document.querySelector('[data-batch-flash]');
    const forms = document.querySelectorAll('[data-batch-form]');
    const text = {
        added: root.dataset.msgAdded,
        duplicate: root.dataset.msgDuplicate,
        unknown: root.dataset.msgUnknown,
        remove: root.dataset.msgRemove,
    };

    let items = [];
    try {
        items = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
    } catch (e) {
        items = [];
    }

    let lastCode = null;
    let lastAt = 0;
    let busy = false;

    function save() {
        try {
            sessionStorage.setItem(storageKey, JSON.stringify(items));
        } catch (e) {
            // tryb prywatny / brak miejsca: lista działa dalej, tylko bez zapamiętania
        }
    }

    function say(message, kind = 'ok') {
        if (! flash) {
            return;
        }
        flash.textContent = message;
        flash.dataset.kind = kind;
        flash.classList.remove('hidden');
    }

    function render() {
        if (list) {
            list.replaceChildren(...items.map((item) => {
                const li = document.createElement('li');
                li.className = 'py-2 flex items-start justify-between gap-3 text-sm';

                const info = document.createElement('div');
                info.className = 'min-w-0';
                const link = document.createElement('a');
                link.href = item.url;
                link.textContent = item.name;
                link.className = 'font-medium text-gray-900 hover:underline dark:text-gray-100';
                const meta = document.createElement('span');
                meta.className = 'block text-xs text-gray-500 dark:text-gray-400';
                meta.textContent = [item.inventory_no, item.location, item.status].filter(Boolean).join(' · ');
                info.append(link, meta);
                if (item.unavailable) {
                    const warn = document.createElement('span');
                    warn.className = 'block text-xs text-red-600 dark:text-red-400';
                    warn.textContent = item.unavailable;
                    info.append(warn);
                }

                const remove = document.createElement('button');
                remove.type = 'button';
                remove.textContent = '×';
                remove.title = text.remove;
                remove.className = 'shrink-0 w-7 h-7 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-700';
                remove.addEventListener('click', () => {
                    items = items.filter((other) => other.id !== item.id);
                    save();
                    render();
                });

                li.append(info, remove);

                return li;
            }));
        }

        empty?.classList.toggle('hidden', items.length > 0);
        counter.forEach((el) => { el.textContent = items.length; });
        forms.forEach((form) => {
            form.querySelectorAll('input[data-batch-item]').forEach((input) => input.remove());
            items.forEach((item) => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = item.id;
                input.dataset.batchItem = '';
                form.append(input);
            });
            form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = items.length === 0; });
        });
    }

    async function record(item) {
        const response = await fetch(recordUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ item_id: item.id }),
        });

        return response.json();
    }

    async function handle(code) {
        code = code.trim();
        const now = Date.now();
        // Kamera widzi ten sam kod w wielu klatkach z rzędu: ten sam kod w ciągu
        // 2,5 s to wciąż ten sam skan.
        if (! code || busy || (code === lastCode && now - lastAt < 2500)) {
            return;
        }
        lastCode = code;
        lastAt = now;
        busy = true;

        try {
            const response = await fetch(`${resolveUrl}?code=${encodeURIComponent(code)}`, { headers: { Accept: 'application/json' } });
            const data = await response.json();

            if (! data.found) {
                say(`${text.unknown}: ${code}`, 'error');

                return;
            }
            if (items.some((item) => item.id === data.item.id)) {
                say(`${text.duplicate}: ${data.item.name}`, 'warn');

                return;
            }

            let note = '';
            if (recordUrl) {
                const result = await record(data.item);
                note = result.message ? ` (${result.message})` : '';
                data.item.status = result.label ?? data.item.status;
            }

            items = [data.item, ...items];
            save();
            render();
            say(`${text.added}: ${data.item.name}${note}`);
            navigator.vibrate?.(80);
        } catch (e) {
            say(root.dataset.errGeneric, 'error');
        } finally {
            busy = false;
        }
    }

    // Ręczne wpisanie kodu — także czytnik kodów podpięty jak klawiatura
    // (wpisuje kod i naciska Enter).
    const manual = document.querySelector('[data-batch-manual]');
    manual?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            lastCode = null; // ręcznie można świadomie wpisać ten sam kod drugi raz
            handle(manual.value);
            manual.value = '';
        }
    });

    document.querySelector('[data-batch-clear]')?.addEventListener('click', () => {
        items = [];
        save();
        render();
        flash?.classList.add('hidden');
    });

    render();

    return (code) => handle(code);
}
