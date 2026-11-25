# Historia zmian

Krótki, chronologiczny zapis tego, co się zmieniło w projekcie. Szerszy
opis funkcji — [README.md](README.md).

## 2026-11-26 (1.7.0)

- Osoba odpowiedzialna za przedmiot: właściciel albo opiekun wybierany z
  Kontaktów (np. członek makerspace'u, który przyniósł swoją maszynę).
  Widoczna na karcie przedmiotu z telefonem i e-mailem, w grafiku maszyn,
  na karcie kontaktu i w historii przedmiotu; kolumny
  `osoba_odpowiedzialna` i `rola_osoby` w imporcie i eksporcie CSV.

## 2026-11-25 (1.6.0)

- Import i eksport CSV na nowej stronie Ustawienia → Import i eksport
  (zamiast przycisku nad listą przedmiotów): eksport przedmiotów z wyborem
  kategorii i statusu, import z podglądem przed zapisem, zmiana wielu
  przedmiotów naraz po numerze ewidencyjnym, nowe kolumny (data zakupu,
  gwarancja, przeglądy, stacjonarny), statusy i stany wpisywane słowami.
- Eksport i import kontaktów i materiałów (dopasowanie po nazwie, korekta
  stanu materiału w historii ruchów).
- Raporty CSV: historia wypożyczeń, raport z inwentaryzacji, dziennik
  aktywności (także z filtrami ze strony dziennika).
- Instrukcja na stronie: rozdziały o kopiach zapasowych i imporcie oraz
  eksporcie, zrzuty z wersji 1.6.0.

## 2026-11-25 (1.5.5)

- Poprawki bezpieczeństwa w bibliotekach (wszystkie zgłoszenia z composer
  audit): Laravel 12.74, Symfony 7.4.24 (http-foundation, http-kernel,
  routing, mime), league/commonmark 2.10.8 i drobniejsze zależności, w
  obrębie tych samych wersji głównych i nadal z PHP 8.3.

## 2026-11-25 (1.5.4)

- Mniejsze paczki aktualizacji (około 13 MB zamiast 26 MB): bez narzędzi
  programistycznych i zbędnych tłumaczeń Laravela w vendor/.
- Po wgraniu aktualizacji Craty czyści pamięć OPcache, więc nowe strony nie
  zwracają przez chwilę błędów 500/404 (działa od następnej aktualizacji po
  tej).

## 2026-11-25 (1.5.3)

- Kopie zapasowe (Ustawienia → Kopie zapasowe, tylko administrator): baza
  danych i wszystkie wgrane pliki w jednym .zip, w czystym PHP (bez
  mysqldump). Ręcznie albo automatycznie codziennie lub co tydzień,
  przechowywanie od 7 do 90 dni albo bez limitu. Pobieranie, wgrywanie kopii
  z komputera i przywracanie po potwierdzeniu hasła; przed przywróceniem
  powstaje kopia bieżącego stanu.
- Dziennik aktywności: wybór czasu przechowywania wpisów (od 30 dni do 2 lat
  albo bez limitu).
- Aktualizacja i przywracanie kopii kończą się na serwerze nawet wtedy, gdy
  przeglądarka zerwie połączenie (np. komunikat o zmianie sieci).

## 2026-11-25 (1.5.2)

- Dziennik aktywności (Ustawienia → Dziennik aktywności, tylko
  administrator): logowania (także nieudane i blokady), wylogowania,
  zmiany kont, ustawień i modułów oraz aktualizacje, z adresem IP.
  Opcjonalnie także dodawanie, zmiany i usuwanie danych. Hasła i inne
  sekrety nigdy nie trafiają do dziennika. Filtry po rodzaju i osobie,
  wyszukiwanie, wpisy starsze niż rok usuwają się same.

## 2026-11-24 (1.5.1)

- Kategorie jako drzewo, jak Struktura magazynu: kategorie główne to karty,
  podkategorie rozwijane w środku, liczba przedmiotów z sumą razem z
  podkategoriami, przycisk „+ Podkategoria” z wybraną kategorią nadrzędną,
  usuwanie na dole formularza edycji.
- Poprawka: kategorii nie da się już przenieść pod jej własną podkategorię
  (powstawała pętla i gałąź znikała z listy).
- Wypożyczenia jako moduł do wyłączenia w Ustawienia → Moduły (domyślnie
  włączony): po wyłączeniu znikają wypożyczenia, zestawy, rezerwacje na dni,
  protokoły i powiadomienia o przeterminowanych, dane zostają.

## 2026-11-22 (1.5.0)

- Przedmioty stacjonarne: pole „Stacjonarny” w formularzu przedmiotu; takiej
  maszyny nie da się wypożyczyć, zarezerwować na dni ani dodać do zestawu.
- Moduł Rezerwacje maszyn (domyślnie wyłączony): tygodniowy grafik
  przedmiotów stacjonarnych, rezerwacje godzinowe (co 15 minut) z rodzajem
  użycia i opisem, blokada nakładających się terminów, „Dziś na maszynach”
  na Panelu.
- Nowa rola Użytkownik: widzi tylko grafik maszyn i swój profil, rezerwuje
  i odwołuje swoje rezerwacje.
- Menu przy wielu włączonych modułach: poniżej 1280 px rząd ikon i menu
  rozwijane zamiast nachodzących na siebie napisów; brakujące ikony
  Wypożyczeń i Materiałów.
- Instrukcja użytkownika z przykładami i zrzutami ekranu na stronie
  projektu (cyryllo.github.io/craty/instrukcja).

## 2026-11-20 (1.4.1)

- Poprawka bezpieczeństwa: w formularzach wypożyczenia, rezerwacji, naprawy
  i zestawu wartość pola kontaktu po błędzie walidacji nie może już zostać
  wykonana jako kod na stronie.
- Testy bezpieczeństwa dla funkcji z wersji 1.3 i 1.4 (kontakty, serwis,
  zestawy, rezerwacje, inwentaryzacja, materiały, seria skanów);
  `security-check` sprawdza też nowe strony.

## 2026-11-20 (1.4.0)

Zbiorczo wersje 1.3.3 i 1.4.0:

- Lista przedmiotów: sortowanie kliknięciem w nagłówek kolumny (w kafelkach
  z listy „Sortuj”), ostatnie wyszukiwanie, filtry i sortowanie
  zapamiętywane, z paskiem „Wyczyść filtry”.
- Seria skanów: kolejne kody na listę (kamera, wpisany kod albo ręczny
  czytnik), z listy wypożyczenie zestawu albo druk etykiet.
- Zestawy: kilka przedmiotów dla jednej osoby, jeden protokół PDF, zwrot
  całości albo części; „Wypożycz zaznaczone” na liście przedmiotów.
- Rezerwacje na przyszły termin, z ostrzeżeniem przy wypożyczeniu i
  przyciskiem „Wydaj teraz”; nowa zakładka Wypożyczenia.
- Inwentaryzacja miejsca: skanowanie z zapisem na bieżąco, raport braków i
  rzeczy nie na swoim miejscu z „Przenieś tutaj”.
- Moduł Materiały eksploatacyjne (domyślnie wyłączony): ilości w
  jednostkach, stan minimalny, historia ruchów, ostrzeżenie na pulpicie.
- Cotygodniowe przypomnienie e-mailem z modułu Serwis.
- Opcja ikony aplikacji z własnego logo; manifest aplikacji generowany
  przez Craty pod `/manifest.webmanifest`.
- Poprawiona instalacja aplikacji na telefonie i odświeżanie ikony.

## 2026-11-05 (1.3.2)

Zbiorczo wersje 1.3.0, 1.3.1 i 1.3.2:

- Moduł Kontakty (domyślnie włączony): osoby, firmy i serwisy z historią
  wypożyczeń; przy wypożyczeniu wybór kontaktu albo dodanie nowego w
  locie; dotychczasowe wpisy „komu” zamienione w kontakty automatycznie.
- Protokoły wydania i zwrotu w PDF, z danymi wypożyczającego i
  przedmiotu, porównaniem stanu przy wydaniu i zwrocie oraz miejscami na
  podpisy.
- Zwrot ze stanem technicznym i uwagami; zmieniony stan trafia do
  kartoteki przedmiotu.
- Moduł Serwis (domyślnie wyłączony): naprawy z kosztami, przeglądy
  okresowe z automatycznym następnym terminem, daty gwarancji;
  przypomnienia na stronie Serwis i na pulpicie.
- Nowa ikona aplikacji, favicon i logo: skrzynka na ciemnym tle.
- Formularze wypożyczenia i zwrotu zwinięte na karcie przedmiotu.

## 2026-11-05 (1.2.6)

- Gotowy obraz Docker `ghcr.io/cyryllo/craty`: aplikacja i baza MariaDB w
  jednym kontenerze, wszystkie dane w jednym wolumenie, uruchomienie
  jednym poleceniem. Instalator w tej wersji nie pyta o bazę, a
  aktualizacja to pobranie nowego obrazu (migracje wykonują się same przy
  starcie). Obraz dla x86 i ARM, publikowany automatycznie przy tagach.
- Czcionka Figtree serwowana z własnego serwera zamiast z
  `fonts.bunny.net`.
- Nagłówek HSTS przy połączeniu HTTPS (także za serwerem pośredniczącym).
- Test poczty w Ustawieniach działa tylko ze standardowymi portami
  (25, 465, 587, 2525).
- Formularz oferty bez pola platformy: serwis (OLX, Allegro, Allegro
  Lokalnie, Vinted) rozpoznawany z linku do ogłoszenia; nowe rozpoznawanie
  linków Allegro Lokalnie.
- Strona oferty: przycisk do ogłoszenia i kontakt zaraz pod stanem
  technicznym, opis na końcu.
- Ikona koszyka w górnym pasku panelu prowadzi na pchli targ (przy
  włączonym module Sprzedaż); uzupełnione brakujące tłumaczenia.

## 2026-10-09 (1.2.3)

Poprawki po lokalnym teście penetracyjnym (ręczne ataki, OWASP ZAP,
sqlmap):

- Kody kategorii, magazynów i pomieszczeń oraz nazwy regałów, półek i
  pojemników przyjmują tylko bezpieczne znaki; nazwa pliku z kodem QR jest
  dodatkowo czyszczona (wcześniej regał w rodzaju `/../../x` zapisywał plik
  QR poza katalogiem `qr/`).
- Eksport CSV neutralizuje formuły arkusza (wartości zaczynające się od
  `=`, `+`, `-`, `@` dostają apostrof na początku).
- Reset hasła odpowiada tak samo dla istniejącego i nieistniejącego
  konta, więc nie zdradza, kto ma konto.
- Link "Wróć" na stronie oferty nie prowadzi już na obce domeny.
- Nagłówki bezpieczeństwa także na stronach 404, dodatkowo
  Cross-Origin-Opener-Policy.

## 2026-10-09 (1.2.2)

- Poprawki bezpieczeństwa: załączniki przyjmują tylko dozwolone typy
  plików (wcześniej każdy, także skrypty .php), wyłączone konto nie może
  się zalogować i jest wylogowywane z otwartej sesji, favicon i logo bez
  SVG.
- Nagłówki bezpieczeństwa wysyłane przez aplikację; `.htaccess` na stałe
  wyłącza listing katalogów, w `/storage/` udostępnia tylko zdjęcia, kody
  QR i logo, blokuje skrypty w `/storage/` oraz ukryte pliki i pliki .md.
- Migracja usuwa stare, prywatne katalogi, które mogły trafić do
  publicznego `/storage/` (m.in. migawki kodu z dawnej funkcji cofania
  aktualizacji).
- Wyłączono nieużywane trasy Laravela do serwowania plików prywatnych.
- Dodano testy bezpieczeństwa (mapa uprawnień wszystkich tras, wgrywanie
  plików, XSS, role, wyłączone konta, blokada logowania) i skrypt
  `security-check` do sprawdzania działającej instalacji z zewnątrz.
- "Ustawienia sklepu" nazywają się teraz "Opcje sprzedaży" (1.2.1).
- README: założenia projektu (program magazynowy, nie sklep), plany,
  sekcje "Jak pomóc" i "Bezpieczeństwo"; strona projektu na GitHub Pages
  i formularze zgłoszeń błędów oraz pomysłów.

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
