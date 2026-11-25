"""Zrzuty ekranu do instrukcji (docs/instrukcja/img/).

Uruchamiaj przez ./docs/instrukcja/screenshots.sh, który najpierw wgrywa
dane przykładowe (ManualDemoSeeder) do LOKALNEJ bazy i odpala ten skrypt w
obrazie Playwrighta. Nie kieruj go na produkcję: klika w formularze.
"""

import datetime
import os
import re
import sys

from playwright.sync_api import sync_playwright

BASE = os.environ.get("BASE", "http://localhost:8000")
OUT = os.environ.get("OUT", "/out")
ONLY = set(sys.argv[1:])
TODAY = datetime.date.today()


def day(offset):
    return (TODAY + datetime.timedelta(days=offset)).isoformat()  # opcjonalnie: nazwy zrzutów do zrobienia

os.makedirs(OUT, exist_ok=True)


def wanted(name):
    return not ONLY or name in ONLY


def save(page, name, full=False, locator=None, pad=12):
    if not wanted(name):
        return
    path = f"{OUT}/{name}.png"
    page.wait_for_timeout(250)
    if locator is not None:
        locator.first.scroll_into_view_if_needed()
        box = locator.first.bounding_box()
        page.screenshot(path=path, full_page=True, clip={
            "x": max(box["x"] - pad, 0), "y": max(box["y"] - pad + page.evaluate("window.scrollY"), 0),
            "width": box["width"] + 2 * pad, "height": box["height"] + 2 * pad,
        })
    else:
        page.screenshot(path=path, full_page=full)
    print("zrzut:", name)


def card(page, heading):
    """Biała ramka (karta) zawierająca nagłówek o tym tekście."""
    return page.locator(f"xpath=//*[self::h2 or self::h3][normalize-space()='{heading}']/ancestor::div[contains(@class,'bg-white')][1]")


def item_url(page, name):
    page.goto(f"{BASE}/items?f=1&view=grid&q={name}")
    return page.locator("main a[href*='/items/']", has_text=name).first.get_attribute("href")


def login(page, email="admin@craty.test"):
    page.goto(f"{BASE}/login")
    page.fill("#email", email)
    page.fill("#password", "password")
    page.click("button[type=submit]")
    page.wait_for_url(re.compile(r".*/dashboard"))


with sync_playwright() as p:
    browser = p.chromium.launch(args=[
        "--lang=pl-PL",
        "--use-fake-ui-for-media-stream", "--use-fake-device-for-media-stream",
        f"--use-file-for-fake-video-capture={OUT}/camera/camera.mjpeg",
    ])
    ctx = browser.new_context(viewport={"width": 1280, "height": 860}, color_scheme="light", locale="pl-PL", accept_downloads=True)
    page = ctx.new_page()

    # ---------- bez logowania ----------
    page.goto(f"{BASE}/login")
    save(page, "logowanie")
    page.goto(f"{BASE}/")
    save(page, "pchli-targ", full=True)
    page.click("text=Więcej informacji")
    save(page, "pchli-targ-oferta", full=True)

    # Nieudane logowanie przed właściwym, żeby było co pokazać w dzienniku aktywności.
    page.goto(f"{BASE}/login")
    page.fill("#email", "admin@craty.test")
    page.fill("#password", "zle-haslo")
    page.click("button[type=submit]")
    page.wait_for_load_state()

    login(page)
    save(page, "panel", full=True)

    errors = []

    # ---------- przedmioty ----------
    try:
        page.goto(f"{BASE}/items?clear=1")
        page.goto(f"{BASE}/items?f=1&view=grid")
        save(page, "przedmioty-kafelki")
        page.goto(f"{BASE}/items?f=1&view=list&sort=name&dir=asc")
        save(page, "przedmioty-lista", full=True)
        page.goto(f"{BASE}/items?f=1&view=list&q=bosch&category_id=")
        save(page, "przedmioty-szukaj")
        page.goto(f"{BASE}/items?clear=1")

        page.goto(f"{BASE}/items/create")
        save(page, "przedmiot-nowy", full=True)

        driver = item_url(page, "Wkrętarka akumulatorowa")
        page.goto(driver)
        save(page, "przedmiot-karta", full=True)
        page.click("summary:has-text('Wypożycz')")
        loan = page.locator("form[action$='/loans']")
        loan.locator("select[name=contact_id]").select_option(label="Jan Nowak")
        loan.locator("input[name=due_at]").fill(day(7))
        loan.locator("textarea[name=notes]").fill("Z ładowarką i dwoma akumulatorami.")
        save(page, "wypozycz-formularz", locator=card(page, "Wypożyczenie"))

        page.goto(item_url(page, "Oscyloskop"))
        save(page, "przedmiot-wypozyczony", locator=card(page, "Wypożyczenie"))
        page.click("summary:has-text('Zarejestruj zwrot')")
        page.locator("textarea[name=return_notes]").fill("Bez uwag, komplet z sondami.")
        save(page, "zwrot-formularz", locator=card(page, "Wypożyczenie"))

        page.goto(item_url(page, "Głośnik JBL"))
        save(page, "rezerwacja-trwa", locator=card(page, "Wypożyczenie"))
        page.click("summary:has-text('Zarezerwuj')")
        res = page.locator("form[action$='/reservations']")
        res.locator("select[name=contact_id]").select_option(label="Anna Zielińska")
        res.locator("input[name=starts_on]").fill(day(5))
        res.locator("input[name=ends_on]").fill(day(6))
        res.locator("textarea[name=reservation_notes]").fill("Na festyn w szkole.")
        save(page, "rezerwacja-formularz", locator=card(page, "Wypożyczenie"))

        page.goto(item_url(page, "Wiertarka udarowa"))
        save(page, "historia", locator=card(page, "Historia zmian"))
        sharp = browser.new_context(viewport={"width": 700, "height": 400}, device_scale_factor=2, color_scheme="light", locale="pl-PL")
        sharp.add_cookies(ctx.cookies())
        label = sharp.new_page()
        label.goto(page.url + "/label")
        if wanted("etykieta"):
            right, bottom = label.evaluate("() => { let r = 0, b = 0; document.querySelectorAll('body *').forEach(e => { const x = e.getBoundingClientRect(); if (x.width) { r = Math.max(r, x.right); b = Math.max(b, x.bottom); } }); return [r, b]; }")
            label.screenshot(path=f"{OUT}/etykieta.png", clip={"x": 0, "y": 0, "width": min(right + 24, 700), "height": bottom + 24})
            print("zrzut: etykieta")
        sharp.close()
    except Exception as e:  # noqa: BLE001
        errors.append("przedmioty: " + str(e).splitlines()[0])

    # ---------- struktura ----------
    try:
        page.goto(f"{BASE}/warehouse-structure")
        save(page, "struktura-magazynu", full=True)
        page.goto(f"{BASE}/categories")
        save(page, "kategorie")
    except Exception as e:  # noqa: BLE001
        errors.append("struktura: " + str(e).splitlines()[0])

    # ---------- wypożyczenia, zestawy ----------
    try:
        page.goto(f"{BASE}/loans")
        save(page, "wypozyczenia", full=True)
        page.locator("main a[href*='/loan-groups/']").first.click()
        save(page, "zestaw", full=True)

        page.goto(f"{BASE}/items?clear=1")
        page.goto(f"{BASE}/items?f=1&view=list&category_id=&sort=name&dir=asc")
        for name in ["Drabina aluminiowa", "Pilarka tarczowa", "Wkrętarka akumulatorowa"]:
            page.locator("tr", has_text=name).locator("input[type=checkbox]").check()
        page.evaluate("window.scrollTo(0, 0)")
        save(page, "zaznaczone-na-liscie")
    except Exception as e:  # noqa: BLE001
        errors.append("wypożyczenia, zestawy: " + str(e).splitlines()[0])

    # ---------- skanowanie ----------
    try:
        phone = browser.new_context(viewport={"width": 390, "height": 844}, device_scale_factor=2, is_mobile=True, has_touch=True, color_scheme="light", locale="pl-PL")
        phone.add_cookies(ctx.cookies())
        mpage = phone.new_page()
        mpage.goto(f"{BASE}/dashboard")
        save(mpage, "telefon-panel")
        mpage.goto(f"{BASE}/scan")
        mpage.wait_for_timeout(1500)
        save(mpage, "telefon-skaner")
        mpage.goto(f"{BASE}/scan/quick-add?code=5907654321098")
        save(mpage, "telefon-szybkie-dodanie", full=True)

        page.goto(f"{BASE}/scan/batch")
        page.evaluate("sessionStorage.clear()")
        page.reload()
        page.wait_for_timeout(1200)
        for code in ["DDF482-7731902", "3165140796337", "DWE560-2041188", "601063S00-552"]:
            page.fill("[data-batch-manual]", code)
            page.press("[data-batch-manual]", "Enter")
            page.wait_for_timeout(700)
        save(page, "skanuj-serie", full=True)
        page.evaluate("sessionStorage.clear()")

        page.goto(f"{BASE}/loan-groups/create?items[]=" + "&items[]=".join(
            re.search(r"/items/(\d+)", item_url(page, n)).group(1) for n in ["Wkrętarka akumulatorowa", "Pilarka tarczowa", "Drabina aluminiowa"]))
        save(page, "zestaw-nowy", full=True)
    except Exception as e:  # noqa: BLE001
        errors.append("skanowanie: " + str(e).splitlines()[0])

    # ---------- inwentaryzacja ----------
    try:
        page.goto(f"{BASE}/stocktakes")
        save(page, "inwentaryzacje", full=True)
        page.locator("main a[href*='/stocktakes/']", has_text="Garaż").first.click()
        page.wait_for_timeout(1200)
        page.fill("[data-batch-manual]", "X8L7210993")
        page.press("[data-batch-manual]", "Enter")
        page.wait_for_timeout(900)
        save(page, "inwentaryzacja-skanowanie", full=True)
        page.goto(f"{BASE}/stocktakes")
        page.locator("main a[href*='/stocktakes/']", has_text="Regał").first.click()
        save(page, "inwentaryzacja-raport", full=True)
    except Exception as e:  # noqa: BLE001
        errors.append("inwentaryzacja: " + str(e).splitlines()[0])

    # ---------- materiały ----------
    try:
        page.goto(f"{BASE}/consumables")
        save(page, "materialy", full=True)
        page.locator("main a", has_text="Wkręty do drewna").first.click()
        save(page, "material-karta", full=True)
    except Exception as e:  # noqa: BLE001
        errors.append("materiały: " + str(e).splitlines()[0])

    # ---------- kontakty ----------
    try:
        page.goto(f"{BASE}/contacts")
        save(page, "kontakty")
        page.locator("main a", has_text="Budmex").first.click()
        save(page, "kontakt-karta", full=True)
    except Exception as e:  # noqa: BLE001
        errors.append("kontakty: " + str(e).splitlines()[0])

    # ---------- maszyny i rezerwacje czasu ----------
    try:
        # Rezerwacje z ManualDemoSeeder są liczone od dziś; jutro zawsze trafia w tydzień z większością z nich.
        week = day(1)
        printer = item_url(page, "Drukarka 3D Prusa")
        page.goto(printer + "/edit")
        save(page, "stacjonarny-pole", locator=page.locator("label:has(input[name=stationary])"))
        page.goto(f"{BASE}/bookings?week={week}")
        save(page, "maszyny-grafik", full=True)
        page.goto(printer)
        page.click("summary:has-text('Zarezerwuj czas')")
        booking = page.locator("form[action$='/bookings']")
        booking.locator("input[name=date]").fill(day(2))
        booking.locator("input[name=from]").fill("10:00")
        booking.locator("input[name=to]").fill("12:00")
        booking.locator("textarea[name=description]").fill("Uchwyty do szuflad")
        save(page, "maszyna-karta", locator=card(page, "Rezerwacje maszyny"))

        member = browser.new_context(viewport={"width": 390, "height": 844}, device_scale_factor=2, is_mobile=True, has_touch=True, color_scheme="light", locale="pl-PL")
        mpage = member.new_page()
        mpage.goto(f"{BASE}/login")
        mpage.fill("#email", "uzytkownik@craty.test")
        mpage.fill("#password", "password")
        mpage.click("button[type=submit]")
        mpage.wait_for_url(re.compile(r".*/bookings"))
        mpage.goto(f"{BASE}/bookings?week={week}")
        save(mpage, "maszyny-telefon")
        member.close()
    except Exception as e:  # noqa: BLE001
        errors.append("maszyny: " + str(e).splitlines()[0])

    # ---------- serwis ----------
    try:
        page.goto(f"{BASE}/service")
        save(page, "serwis", full=True)
        page.goto(item_url(page, "Odkurzacz"))
        save(page, "naprawa-w-toku", locator=card(page, "Serwis"))
        page.goto(item_url(page, "Drabina aluminiowa"))
        page.click("summary:has-text('Zapisz przegląd')")
        insp = page.locator("form[action$='/inspections']")
        insp.locator("input[name=performed_at]").fill(day(0))
        insp.locator("textarea[name=inspection_notes]").fill("Stopki wymienione, zawiasy nasmarowane.")
        save(page, "przeglad-formularz", locator=card(page, "Serwis"))
    except Exception as e:  # noqa: BLE001
        errors.append("serwis: " + str(e).splitlines()[0])

    # ---------- sprzedaż ----------
    try:
        page.goto(f"{BASE}/sales")
        save(page, "sprzedaz-przygotowane")
        page.goto(f"{BASE}/sales/wystawione")
        save(page, "sprzedaz-wystawione")
    except Exception as e:  # noqa: BLE001
        errors.append("sprzedaż: " + str(e).splitlines()[0])

    # ---------- dziennik aktywności ----------
    try:
        page.goto(f"{BASE}/ustawienia/dziennik")
        page.check("input[name=activity_log_all_enabled][type=checkbox]")
        page.locator("main form[method=POST] button").first.click()
        page.wait_for_load_state()
        page.goto(item_url(page, "Drabina aluminiowa") + "/edit")
        page.fill("#value", "800")
        page.locator("main form button[type=submit]").last.click()
        page.wait_for_load_state()
        page.goto(f"{BASE}/ustawienia/dziennik")
        save(page, "dziennik", full=True)
    except Exception as e:  # noqa: BLE001
        errors.append("dziennik: " + str(e).splitlines()[0])

    # ---------- ustawienia ----------
    try:
        for path, name in [("ustawienia", "ustawienia"), ("ustawienia/moduly", "ustawienia-moduly"),
                           ("ustawienia/aplikacja", "ustawienia-aplikacja"), ("ustawienia/powiadomienia", "ustawienia-powiadomienia"),
                           ("users", "uzytkownicy"), ("ustawienia/aktualizacje", "ustawienia-aktualizacje")]:
            page.goto(f"{BASE}/{path}")
            save(page, name, full=True)
    except Exception as e:  # noqa: BLE001
        errors.append("ustawienia: " + str(e).splitlines()[0])

    # ---------- protokoły PDF (zamieniane na obrazki przez screenshots.sh) ----------
    try:
        page.goto(f"{BASE}/loans")
        kit = page.locator("main a[href*='/loan-groups/']").first.get_attribute("href")
        kit_id = re.search(r"/loan-groups/(\d+)", kit).group(1)
        for name, url in [("protokol-zestawu", f"/loan-groups/{kit_id}/protokol-wydania.pdf")]:
            if wanted(name):
                open(f"{OUT}/{name}.pdf", "wb").write(ctx.request.get(BASE + url).body())
        page.goto(item_url(page, "Wiertarka udarowa"))
        returned = page.locator("a[href*='protokol-zwrotu.pdf']").first.get_attribute("href")
        if wanted("protokol-zwrotu"):
            open(f"{OUT}/protokol-zwrotu.pdf", "wb").write(ctx.request.get(returned if returned.startswith("http") else BASE + returned).body())
    except Exception as e:  # noqa: BLE001
        errors.append("protokoły PDF (zamieniane na obrazki przez screenshots.sh): " + str(e).splitlines()[0])

    browser.close()
    for e in errors:
        print("BŁĄD", e)
    sys.exit(1 if errors else 0)
