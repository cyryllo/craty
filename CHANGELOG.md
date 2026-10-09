# Historia zmian

Krótki, chronologiczny zapis tego, co się zmieniło w projekcie. Szerszy
opis funkcji — [README.md](README.md).

## 2026-10-09 (1.2.0)

- Dodano Ustawienia sklepu (kafelek widoczny przy włączonej Sprzedaży):
  opis sklepu w ramce kontaktu na stronie głównej, przeniesione dane
  kontaktowe, polityka prywatności i regulamin jako linki w stopce
  otwierane w okienku. Teksty w prostym Markdownie, bez surowego HTML.
- Pchli targ działa zawsze przy włączonym module Sprzedaż; usunięto osobny
  przełącznik "Publikuj publiczną stronę pchli targ".
- Dodano własny favicon w Ustawieniach aplikacji; bez niego favicon to
  wgrane logo, a bez logo domyślna ikona aplikacji.
- Zdjęcia na stronie oferty otwierają się w okienku z przełączaniem
  (strzałki, klawisze ← →, miniatury) zamiast w nowej karcie.
- Odnośniki do pchlego targu w panelu otwierają się w tej samej karcie.
- Dane demo zawierają przykładowe teksty Ustawień sklepu (sprzedaż
  prywatna).

## 2026-10-09 (1.1.17)

- Pchli targ jest teraz stroną główną aplikacji (`/`); przy wyłączonym
  pokazuje informację, że nic nie jest wystawione. Logowanie przez kłódkę w
  prawym górnym rogu, dla zalogowanych przycisk "Panel". Oferty pod
  `/offer/{id}`, stary adres `/flea-market` usunięty.
- Dodano Strukturę magazynu (Ustawienia): magazyny, pomieszczenia, regały,
  półki i pojemniki w jednym drzewie, z liczbą przedmiotów, dymkiem z
  nazwami po najechaniu, linkiem do przefiltrowanej listy przedmiotów i
  przyciskami "+" otwierającymi wypełnione formularze. Zastępuje osobne
  listy magazynów, pomieszczeń i lokalizacji.
- Usunięcie pomieszczenia/regału/półki/pojemnika przenosi przedmioty poziom
  wyżej (z wpisem w historii) zamiast blokować usunięcie.
- Usunięto moduł "Rozszerzony magazyn" — pomieszczenia i lokalizacje są
  zawsze dostępne.
- Przedmioty i oferty bez zdjęcia pokazują grafikę "Brak zdjęcia".
- W historii przedmiotu widać, komu go wypożyczono i od kogo wrócił.
- Uzupełniono brakującą lokalizację "cały magazyn" tam, gdzie jej nie było.

## 2026-10-09 (1.1.16)

- Sprzedane i wycofane przedmioty znikają z listy przedmiotów (także z
  wyszukiwania) i ze statystyk Panelu — widać je tylko po wybraniu filtra
  statusu "Sprzedany"/"Wycofany"; dane zostają w bazie.
- Poprawiono linki kategorii na pchlim targu — "Wszystkie" nie zostawia już
  pustego "?" w adresie, a kliknięcie kategorii nie kończy się błędem 502
  na hostingu z HTTPS przed serwerem.
- Dodano zrzut ekranu aplikacji do README.

## 2026-10-04

- Dodano opcjonalny link do oferty na OLX / Allegro / Vinted — przy
  tworzeniu oferty albo później z okienka podglądu na zakładkach
  "Przygotowane"/"Wystawione"; na pchlim targu pokazuje się jako przycisk.
- Dodano Vinted jako platformę sprzedaży obok OLX i Allegro.
- Poprawiono "edytuj" przy ofertach sprzedaży — edytuje istniejącą ofertę
  (łącznie z platformą) zamiast tworzyć nową; dostępne na obu zakładkach.
- Dodano odnośnik do pchlego targu przy zakładkach Sprzedaży, gdy pchli
  targ jest włączony.
- Dodano moduł "Rozszerzony magazyn" (Ustawienia → Moduły) — pomieszczenia
  i szczegółowe lokalizacje można ukryć; dane nie są kasowane.
- Dodano stronę pojedynczej oferty na pchlim targu (wszystkie zdjęcia,
  pełny opis, przycisk OLX/Allegro/Vinted, kontakt); lista pokazuje już
  tylko zdjęcie, nazwę, cenę i stan oraz przycisk "Więcej informacji".
- Poprawiono pionowe zdjęcia na pchlim targu — są przycinane i nie
  rozpychają już kafelków.
- Wyrównano wysokość kafelków w siatce przedmiotów.
- Pole opisu oferty sprzedaży rośnie razem z treścią i da się je
  rozciągać w dół.

## 2026-09-22

- Dodano możliwość ponownego wygenerowania numeru ewidencyjnego i kodu QR
  na karcie przedmiotu (przydaje się, gdy przedmiot dodano bez kategorii/
  lokalizacji) — z potwierdzeniem, bo unieważnia już wydrukowaną etykietę.
- Rozszerzono skanowanie kamerą o dodatkowe formaty kodów kreskowych
  (Code 39, Code 93, Codabar, ITF), obok dotychczasowych EAN/UPC/Code128.
- Dodano zmianę kolejności zdjęć przy edycji przedmiotu (strzałkami) —
  pierwsze zdjęcie jest zawsze okładką.
- Poprawiono baner powiadomień o zapisie/zmianach — nie nachodzi już na
  nagłówek podstrony i znika sam po 10 sekundach.
- Magazyn dostaje teraz automatycznie "bazową" lokalizację (bez regału/
  półki/pojemnika), więc da się go wybrać na formularzu przedmiotu bez
  konieczności najpierw ręcznie definiować szczegółową lokalizację.
- Dodano pomieszczenia (Ustawienia → Pomieszczenia) jako opcjonalny poziom
  między magazynem a regałem/półką/pojemnikiem — przydaje się, gdy magazyn
  ma więcej niż jedno pomieszczenie. Tak jak magazyn, pomieszczenie od razu
  dostaje własną "bazową" lokalizację, więc jest wybieralne na formularzu
  przedmiotu bez rozpisywania regału/półki/pojemnika.
- Doprecyzowano komunikat o zduplikowanej lokalizacji (regał/półka/
  pojemnik) — teraz podaje kod już istniejącej lokalizacji i przypomina,
  że jedna lokalizacja i tak może trzymać kilka przedmiotów naraz, więc
  wystarczy wybrać ją na formularzu przedmiotu zamiast zakładać duplikat.

## 2026-09-17

- Dodano wspólną stopkę (wersja aplikacji + link do repozytorium) na
  wszystkich stronach.
- Przebudowano pulpit — kafelki liczb kierują teraz do odpowiednich list,
  "Ostatnio dodane" pokazuje też osobno przedmioty dodane szybko z telefonu.
- Sprzedaż stała się modułem, który można włączyć/wyłączyć w Ustawieniach
  → Moduły (pierwszy z planowanych kolejnych modułów opcjonalnych).
- Dodano Ustawienia → Powiadomienia: włącznik samodzielnego resetu hasła
  oraz e-mailowe podsumowanie przeterminowanych wypożyczeń.
- Dodano szybki link powrotu do Ustawień na wszystkich podstronach
  dostępnych z tego rozdzielnika.

## 2026-09-16

- Uproszczono strukturę projektu — jeden, spłaszczony układ katalogów
  wszędzie (środowisko deweloperskie, hosting, paczki aktualizacji).
- Usunięto moduł kopii zapasowych.
- Usunięto funkcję cofania aktualizacji.
- Backup przed aktualizacją nie jest już wymagany automatycznie, tylko
  zalecany.
- Naprawiono błędy przy wgrywaniu aktualizacji przez panel administratora.

## 2026-09-15/16

- Webowy kreator instalacji działa teraz na zwykłym hostingu (bez Dockera).
- Dodano tryb ciemny.
- Dodano druk etykiet (trzy rozmiary naklejek, opcjonalna cena, druk
  zbiorczy).
- Dodano masowy import przedmiotów z pliku CSV.
- Dodano instalowalność jako PWA i skanowanie kodów QR/kreskowych kamerą.
- Rozbudowano publiczną stronę "Pchli targ" o galerię zdjęć.
- Uproszczono role użytkowników i ustawienia aplikacji.

## 2026-09-15

- Dodano wymagania co do siły hasła i potwierdzenie hasła w formularzu
  użytkownika.
- Naprawiono brakujące pole hasła w formularzu użytkownika.

## 2026-09-14

- Dodano moduł samo-aktualizacji przez panel administratora.
- Dodano webowy kreator instalacji.
- Dodano publiczną stronę "Pchli targ" dla wystawionych ofert sprzedaży.
- Dodano ustawienia poczty/SMTP i moduł kopii zapasowych.
- Zmieniono nazwę projektu na "Craty" (wcześniej "Graty") przed wydaniem
  jako open source.

## 2026-09-10

- Pierwsza wersja aplikacji: ewidencja przedmiotów, kategorie, lokalizacje,
  kody QR, wypożyczenia, eksport ofert sprzedaży do CSV.
- Dodano wielojęzyczność (EN/PL).
- Dodano zakładkę "Sprzedaż" z podziałem na oferty przygotowane i
  wystawione.
- Dodano numer seryjny i kod EAN przedmiotu.
- Dodano widok listy dla przedmiotów.
- Dodano ustawienia aplikacji (nazwa, logo) i ochronę konta głównego
  administratora.
