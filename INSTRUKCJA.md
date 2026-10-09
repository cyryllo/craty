# Instrukcja użytkownika

Krótki, praktyczny przewodnik po głównych funkcjach Craty — bez żargonu
technicznego. Jeśli szukasz instrukcji instalacji/wdrożenia, zobacz
[README.md](README.md); historia zmian jest w [CHANGELOG.md](CHANGELOG.md).

## Role i uprawnienia

Są dwie role: **Administrator** i **Magazynier**. Magazynier ma dostęp do
całej codziennej pracy z ewidencją (przedmioty, wypożyczenia, sprzedaż,
struktura magazynowa) — różnica względem Administratora to tylko dostęp do
"Ustawień zaawansowanych" (użytkownicy, poczta, powiadomienia, moduły,
aktualizacje appki). Konta zakłada Administrator w Ustawienia → Użytkownicy
— nie ma samodzielnej rejestracji.

## Struktura magazynowa: Kategorie i Struktura magazynu

To jest część, która najczęściej budzi pytania, więc tłumaczymy dokładnie,
jak się to wszystko do siebie ma i co jest naprawdę wymagane.

**Kategoria** (Ustawienia → Kategorie) to typ przedmiotu (np. "Narzędzia",
"Elektronika") — decyduje o pierwszym segmencie numeru ewidencyjnego (np.
`NAR-...`). Kategorie mogą mieć podkategorie.

**Struktura magazynu** (Ustawienia → Struktura magazynu) to jedno miejsce,
w którym widać i układa się całe „gdzie co leży”, jako drzewo:

- **Magazyn** — fizyczne miejsce (hala, budynek, garaż), z nazwą i krótkim
  kodem (np. "M1"). Na stronie to ciemny, szeroki nagłówek. Nowy magazyn
  dodajesz przyciskiem "+ Nowy magazyn" u góry strony, a edytujesz (i
  usuwasz) ołówkiem w jego nagłówku. **Od razu po założeniu magazynu można
  go wybrać przy dodawaniu przedmiotu** jako "cały magazyn" — nic więcej
  nie trzeba konfigurować. Jeśli to Ci wystarcza (mały warsztat, jeden
  magazyn), na tym możesz skończyć.
- **Pomieszczenie** — *opcjonalny* podział magazynu (np. "Hala warsztatowa"
  i "Strych" w tym samym budynku). Dodajesz je przyciskiem
  "+ Pomieszczenie" w nagłówku magazynu; na stronie to karty, po dwie w
  rzędzie. Pomieszczenie też od razu jest wybieralne na formularzu
  przedmiotu jako "całe pomieszczenie".
- **Regał → półka → pojemnik** — najbardziej szczegółowe miejsca, w obrębie
  pomieszczenia. Regał dodajesz przyciskiem "+ Regał w: …" na dole karty
  pomieszczenia, półkę przyciskiem "+ Półka" pod regałem, a pojemnik
  przyciskiem "+ Pojemnik" przy półce — formularz otwiera się już z
  wpisanym magazynem, pomieszczeniem, regałem i półką, zostaje tylko
  uzupełnić numer. Regały można zwijać (strzałka przy nazwie albo "Zwiń
  wszystko" u góry), a pojemniki wyświetlają się jako małe "chipy" (np.
  `K1 · 2`).

**Liczby przy każdym miejscu** to przedmioty, które tam leżą (bez
sprzedanych i wycofanych), łącznie ze wszystkim, co niżej — regał liczy
też swoje półki i pojemniki. **Po najechaniu myszką** na liczbę albo na
pojemnik pokazuje się dymek z nazwami tych przedmiotów (i notatką miejsca,
jeśli ją dodano). **Kliknięcie** otwiera listę przedmiotów z tego miejsca;
nad nią jest przycisk "← Struktura magazynu", który wraca prosto do tego
magazynu.

**Usuwanie niczego nie gubi.** Usunięcie pomieszczenia przenosi wszystkie
jego przedmioty (także z regałów, półek i pojemników) do samego magazynu.
Usunięcie regału, półki albo pojemnika przenosi przedmioty poziom wyżej —
do "całego pomieszczenia" (albo do samego magazynu, jeśli regał nie był w
żadnym pomieszczeniu). Każde takie przeniesienie widać w historii
przedmiotu. Przycisk usuwania jest na dole formularza edycji (ołówek).
Magazynu, w którym coś leży, nie da się usunąć — najpierw przenieś
przedmioty.

**Jedna lokalizacja może trzymać wiele przedmiotów naraz** — nie trzeba
zakładać nowego pojemnika dla każdego kolejnego przedmiotu. Próba
założenia miejsca z już istniejącym regałem/półką/pojemnikiem w tym samym
magazynie zwróci błąd z podpowiedzią, którego użyć zamiast tworzyć
duplikat.

## Przedmioty

**Dodawanie przedmiotu** (Przedmioty → "Dodaj przedmiot"): nazwa, numer
seryjny/EAN (opcjonalne, osobne od numeru ewidencyjnego), kategoria,
lokalizacja, wartość, data zakupu, stan (nowy/używany/uszkodzony), status
(dostępny/wypożyczony/w naprawie/do sprzedaży/sprzedany/wycofany), opis,
zdjęcia i załączniki (np. faktura, instrukcja). Załącznikiem może być
plik PDF, dokument biurowy (Word, Excel, LibreOffice, RTF), obraz, plik
tekstowy lub CSV albo archiwum ZIP; inne typy plików (np. skrypty czy
strony HTML) są odrzucane ze względów bezpieczeństwa.

**Numer ewidencyjny i kod QR** generują się automatycznie przy zapisie, w
formacie `KATEGORIA-LOKALIZACJA-ROK-NUMER` (np. `NAR-M1-2026-00042`).
Dlatego kody kategorii, magazynów i pomieszczeń mogą zawierać tylko
litery, cyfry, myślnik i podkreślnik, a nazwy regałów, półek i
pojemników dodatkowo spację (bez znaków typu `/` czy `.`). Jeśli
dodasz przedmiot bez kategorii lub lokalizacji, numer dostaje w tym miejscu
"GEN"/"BRAK" — jeśli uzupełnisz te dane później, na karcie przedmiotu obok
kodu QR jest przycisk odświeżenia, który przelicza numer i QR na nowo na
podstawie aktualnych danych. Wymaga potwierdzenia, bo **jeśli etykieta była
już wydrukowana i przyklejona, przestanie pasować** — trzeba wydrukować
nową.

**Zdjęcia**: pierwsze zdjęcie na liście jest zawsze okładką widoczną na
liście przedmiotów i karcie przedmiotu. Kolejność da się zmieniać
strzałkami na formularzu edycji — przesunięcie innego zdjęcia na pierwsze
miejsce automatycznie robi z niego nową okładkę.

**Sprzedane i wycofane przedmioty znikają z magazynu.** Gdy przedmiot
dostanie status "Sprzedany" albo "Wycofany", przestaje być widoczny na
liście przedmiotów (także w wyszukiwaniu) i nie liczy się do liczby ani
wartości przedmiotów na Panelu — domyślna opcja filtra statusu to
"wszystkie (w magazynie)". Żeby je zobaczyć, wybierz w filtrze statusu
"Sprzedany" albo "Wycofany". Nic nie jest kasowane: przedmiot zostaje w
bazie razem z historią, a jego karta (i kod QR z naklejki) dalej działa.

**Historia zmian**: każda edycja kluczowych pól (nazwa, wartość, stan,
status, kategoria, lokalizacja) zapisuje się w historii widocznej na karcie
przedmiotu, razem z tym, kto i kiedy ją zrobił.

**Etykiety do druku**: z listy przedmiotów (zaznacz checkboxy) albo z karty
pojedynczego przedmiotu — trzy rozmiary naklejek do wyboru (z kodem QR, z
nazwą, opcjonalnie z ceną), wybór szablonu dzieje się na samym podglądzie
wydruku.

## Wypożyczenia

Z karty przedmiotu — "Wypożycz", z **obowiązkowym** polem komu
(imię/nazwisko albo osoba spoza systemu) i opcjonalnym terminem zwrotu. W
historii przedmiotu zapisuje się, komu go wypożyczono (i do kiedy), a przy
zwrocie — od kogo wrócił. Przeterminowane wypożyczenia widać
na Panelu; opcjonalnie appka wysyła codzienne e-mailowe podsumowanie
przeterminowanych wypożyczeń do wszystkich aktywnych kont (Ustawienia →
Powiadomienia).

## Sprzedaż (moduł opcjonalny)

Można wyłączyć w Ustawienia → Moduły, jeśli nie sprzedajecie sprzętu.

Ścieżka: z karty przedmiotu "Przygotuj ofertę sprzedaży" → zakładka
"Przygotowane" (Sprzedaż) → eksport do CSV (opcja wyeksportowania swoich
przedmiotów przeznaczonych do sprzedaży — gotowe tytuły i opisy, do
dalszego wykorzystania jak Ci wygodnie; tekst zaczynający się od `=`,
`+`, `-` lub `@` dostaje w pliku apostrof na początku, żeby arkusz nie
potraktował go jak formuły) albo ręczne "wystaw" przy
pojedynczej ofercie → oferta trafia do zakładki "Wystawione", gdzie
oznaczasz ją jako "sprzedane" albo "wycofaj".

**Formularz oferty**: tytuł, opis (pole rośnie razem z tekstem, można je
też rozciągnąć w dół uchwytem w rogu), cena i opcjonalny **link do tej
samej oferty na OLX, Allegro, Allegro Lokalnie albo Vinted**. Serwis
rozpoznawany jest z samego linku i pokazywany w tabeli ofert oraz na
przycisku „Zobacz na …” na pchlim targu, więc nie trzeba go nigdzie
osobno wybierać. Zapisaną ofertę poprawiasz przyciskiem "edytuj" na obu
zakładkach, "Przygotowane" i "Wystawione".

**Link do oferty zwykle powstaje dopiero po jej wystawieniu**, więc można
go dopisać albo zmienić także później: kliknij wiersz oferty na zakładce
"Przygotowane" lub "Wystawione", a w okienku podglądu na dole jest pole na
link. Oferty, które mają link, mają w tabeli ikonkę 🔗.

**Pchli targ**: publiczna strona (bez logowania) z wystawionymi ofertami.
**To strona główna aplikacji** (sam adres, np. `https://twoja-domena.pl/`)
i działa zawsze wtedy, gdy włączony jest moduł Sprzedaż, bez osobnego
przełącznika. Adres możesz wysłać znajomym, żeby pokazać, co aktualnie
sprzedajesz: bez koszyka i bez typowego sklepu, kontakt tylko przez podany
e-mail lub telefon. Gdy moduł Sprzedaż jest wyłączony, strona główna
pokazuje tylko informację, że nic nie jest wystawione. W prawym górnym
rogu jest **kłódka** prowadząca do logowania (dla zalogowanych przycisk
"Panel"), a zainstalowana na telefonie aplikacja i tak otwiera się od razu
na panelu. Obok zakładek Sprzedaży jest odnośnik "🛒 Pchli targ", który
otwiera stronę główną w tej samej karcie.

Na liście pchlego targu każda oferta to tylko zdjęcie (z liczbą zdjęć,
jeśli jest ich więcej), nazwa, cena i stan techniczny, plus przycisk
"Więcej informacji". Po kliknięciu otwiera się **strona oferty** z
wszystkimi zdjęciami, pełnym opisem, przyciskiem "Zobacz na OLX / Allegro
/ Vinted" (jeśli podano link) i kontaktem; e-mail otwiera się od razu z
tytułem oferty w temacie. Kliknięcie zdjęcia otwiera je w okienku na
ciemnym tle: zdjęcia przełącza się strzałkami (także klawiszami ← →), a
okienko zamyka krzyżykiem, klawiszem Esc albo kliknięciem w tło.

**Opcje sprzedaży** (Ustawienia, tylko Administrator, widoczne przy
włączonym module Sprzedaż) to wszystko, co dotyczy strony głównej:
- **opis sprzedaży**, widoczny na stronie głównej w ramce kontaktu, nad
  e-mailem i telefonem (np. kim jesteś, jak odebrać rzecz, płatność),
- **e-mail i telefon kontaktowy**,
- **polityka prywatności** i **regulamin**: linki do nich pojawiają się w
  stopce strony głównej i stron ofert tylko wtedy, gdy tekst jest wpisany,
  a sam tekst otwiera się w okienku.

Teksty wpisujesz prostym Markdownem: pusta linia zaczyna nowy akapit,
`**pogrubienie**`, `## nagłówek`, `- punkt listy`, `[link](https://…)`.
Zwykły HTML wklejony do tych pól jest pomijany.

## Skanowanie kamerą i aplikacja mobilna (PWA)

Craty można "zainstalować" jak aplikację na telefonie/tablecie (przycisk w
przeglądarce — Chrome/Android pokazuje to sam, wymaga HTTPS). Strona
"Skanuj" używa aparatu do odczytu:
- **własnego kodu QR** z wydrukowanej etykiety Craty — od razu otwiera
  kartę przedmiotu,
- **kodu kreskowego producenta** (EAN/UPC/Code 39/93/Codabar/ITF) — szuka
  dopasowania po numerze seryjnym/EAN; jeśli nic nie znajdzie, proponuje
  "szybkie dodanie" nowego przedmiotu (zdjęcie z aparatu + nazwa, reszta do
  uzupełnienia później — przedmiot dostaje wtedy ikonkę 📱 na liście, jako
  przypomnienie, że warto go jeszcze dokończyć).

## Ustawienia

Rozdzielnik Ustawień (link w prawym górnym rogu) grupuje wszystko w jednym
miejscu:

- **Kategorie** i **Struktura magazynu** — opisane wyżej (magazyny,
  pomieszczenia, regały, półki i pojemniki w jednym drzewie).
- **Ustawienia aplikacji** *(tylko Administrator)*: nazwa, logo i
  favicon (ikonka karty przeglądarki w PNG albo ICO; bez własnej używane
  jest logo) oraz
  domyślny język (PL/EN).
- **Opcje sprzedaży** *(tylko Administrator, przy włączonej Sprzedaży)*:
  opis sprzedaży, kontakt, polityka prywatności i regulamin, opisane wyżej.
- **Użytkownicy** *(tylko Administrator)*: konta, role, aktywacja i
  dezaktywacja. Wyłączone konto nie może się zalogować, a jeśli ktoś był
  akurat zalogowany, zostaje wylogowany przy następnym kliknięciu.
- **Poczta** *(tylko Administrator)* — własne SMTP zamiast domyślnej
  konfiguracji serwera.
- **Powiadomienia** *(tylko Administrator)* — włącznik samodzielnego
  resetu hasła oraz e-mailowe podsumowanie przeterminowanych wypożyczeń.
- **Moduły** *(tylko Administrator)* — włącz/wyłącz opcjonalne części
  appki (dziś: Sprzedaż).
- **Aktualizacje** *(tylko Administrator)* — samodzielna aktualizacja
  appki przez wgranie paczki `.zip`, bez SSH/Composera. Zawsze rób kopię
  plików i bazy danych przed wgraniem nowej wersji.

Każdy może też zmienić własny język i hasło w Profilu (menu użytkownika w
prawym górnym rogu), a jasny/ciemny motyw przełącza się przyciskiem w
prawym dolnym rogu ekranu.
