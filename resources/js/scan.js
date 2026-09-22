// Skaner QR/kodów kreskowych kamerą — ładowany tylko na stronie /scan (osobny
// entry point Vite, patrz vite.config.js), żeby @zxing nie obciążało reszty
// appki. Zwykły JS, nie Alpine — kolejność ładowania modułów Vite (ten plik
// obok app.js, który sam startuje Alpine.start() od razu po zaimportowaniu)
// nie gwarantuje, że nasłuch na "alpine:init" zdąży się zarejestrować przed
// tym, jak Alpine już wystartuje, więc prościej i pewniej operować wprost na
// DOM-ie niż walczyć z tym wyścigiem dla jednej, w gruncie rzeczy imperatywnej
// strony (kamera, nie reaktywny stan formularza).
//
// Patrz TODO.md "PWA": własny QR z etykiety koduje pełny URL karty przedmiotu
// (obsługa czysto po stronie klienta, zwykły redirect), a goły kod kreskowy
// (EAN/UPC) idzie do /scan/lookup po dopasowanie po stronie serwera.
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
    const lookupUrl = root.dataset.lookupUrl;
    const quickAddUrl = root.dataset.quickAddUrl;
    const messages = {
        noCamera: root.dataset.errNoCamera,
        permission: root.dataset.errPermission,
        generic: root.dataset.errGeneric,
    };

    let controls = null;
    let handled = false;

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

    async function handleResult(text) {
        if (handled) {
            return; // pierwszy trafiony kod wygrywa, ignorujemy kolejne klatki
        }
        handled = true;
        show('redirecting');
        controls?.stop();

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
            fail(messages.generic);
        }
    }

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
                    handleResult(result.getText());
                }
            });
        } catch (e) {
            fail(e?.name === 'NotAllowedError' ? messages.permission : messages.generic);
        }
    })();

    window.addEventListener('beforeunload', () => controls?.stop());
});
