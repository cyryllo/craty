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

## Struktura magazynowa: Kategorie, Magazyny, Pomieszczenia, Lokalizacje

To jest część, która najczęściej budzi pytania, więc tłumaczymy dokładnie,
jak się to wszystko do siebie ma i co jest naprawdę wymagane.

**Kategoria** (Ustawienia → Kategorie) to typ przedmiotu (np. "Narzędzia",
"Elektronika") — decyduje o pierwszym segmencie numeru ewidencyjnego (np.
`NAR-...`). Kategorie mogą mieć podkategorie.

**Magazyn** (Ustawienia → Magazyny) to fizyczne miejsce — hala, budynek,
pomieszczenie gospodarcze. Ma nazwę i krótki kod (np. "M1"). **Od razu po
założeniu magazynu można go wybrać przy dodawaniu przedmiotu** — nie trzeba
nic więcej konfigurować. Dzieje się tak dlatego, że każdy nowy magazyn
automatycznie dostaje "bazową" lokalizację reprezentującą po prostu cały
magazyn, bez podziału na regały/półki. Jeśli to Ci wystarcza (mały warsztat,
jeden magazyn, nie chce się Wam rozpisywać każdego regału) — nic więcej nie
trzeba robić.

**Pomieszczenie** (Ustawienia → Pomieszczenia) to *opcjonalny* poziom
między magazynem a regałem — przydatny, gdy jeden magazyn ma więcej niż
jedno pomieszczenie/halę (np. "Hala produkcyjna" i "Magazyn narzędzi" w tym
samym budynku). Pomieszczenie zawsze należy do jednego magazynu i — tak
samo jak magazyn — od razu po założeniu jest wybieralne na formularzu
przedmiotu (dostaje własną bazową lokalizację). Jeśli nie potrzebujesz
takiego podziału, po prostu nie zakładaj żadnych pomieszczeń — wszystko
działa tak samo, jak gdyby ich nie było.

**Lokalizacja** (Ustawienia → Lokalizacje) to najbardziej szczegółowy
poziom: regał / półka / pojemnik w obrębie magazynu (i opcjonalnie
pomieszczenia). Wszystkie trzy pola (regał, półka, pojemnik) są opcjonalne
— możesz wypełnić tylko regał, albo regał + półkę, albo wszystkie trzy.
Zostawienie ich pustych przy zakładaniu nowej lokalizacji oznacza po prostu
"cały magazyn" (albo "całe pomieszczenie", jeśli je wybrałeś) — to
dokładnie ta sama bazowa lokalizacja, którą dostajesz automatycznie przy
zakładaniu magazynu/pomieszczenia.

**Podsumowując — trzy poziomy szczegółowości do wyboru, wszystkie
działające od razu bez dodatkowej konfiguracji:**
1. Sam magazyn (nic więcej nie trzeba zakładać).
2. Magazyn + pomieszczenie (jeśli magazyn ma kilka hal/pomieszczeń).
3. Magazyn (+ pomieszczenie) + konkretny regał/półka/pojemnik (jeśli
   zależy Wam na dokładnej ewidencji "gdzie leży co").

**Jedna lokalizacja może trzymać wiele przedmiotów naraz** — nie trzeba
zakładać nowej lokalizacji dla każdego kolejnego przedmiotu w tym samym
pojemniku. Próba założenia lokalizacji z już istniejącą kombinacją
regał/półka/pojemnik w tym samym magazynie zwróci błąd z podpowiedzią,
której lokalizacji użyć zamiast tworzyć duplikat.

## Przedmioty

**Dodawanie przedmiotu** (Przedmioty → "Dodaj przedmiot"): nazwa, numer
seryjny/EAN (opcjonalne, osobne od numeru ewidencyjnego), kategoria,
lokalizacja, wartość, data zakupu, stan (nowy/używany/uszkodzony), status
(dostępny/wypożyczony/w naprawie/do sprzedaży/sprzedany/wycofany), opis,
zdjęcia i załączniki (np. faktura, instrukcja).

**Numer ewidencyjny i kod QR** generują się automatycznie przy zapisie, w
formacie `KATEGORIA-LOKALIZACJA-ROK-NUMER` (np. `NAR-M1-2026-00042`). Jeśli
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

**Historia zmian**: każda edycja kluczowych pól (nazwa, wartość, stan,
status, kategoria, lokalizacja) zapisuje się w historii widocznej na karcie
przedmiotu, razem z tym, kto i kiedy ją zrobił.

**Etykiety do druku**: z listy przedmiotów (zaznacz checkboxy) albo z karty
pojedynczego przedmiotu — trzy rozmiary naklejek do wyboru (z kodem QR, z
nazwą, opcjonalnie z ceną), wybór szablonu dzieje się na samym podglądzie
wydruku.

## Wypożyczenia

Z karty przedmiotu — "Wypożycz", z komu (imię/nazwisko albo osoba spoza
systemu) i opcjonalnym terminem zwrotu. Przeterminowane wypożyczenia widać
na Panelu; opcjonalnie appka wysyła codzienne e-mailowe podsumowanie
przeterminowanych wypożyczeń do wszystkich aktywnych kont (Ustawienia →
Powiadomienia).

## Sprzedaż (moduł opcjonalny)

Można wyłączyć w Ustawienia → Moduły, jeśli nie sprzedajecie sprzętu.

Ścieżka: z karty przedmiotu "Przygotuj ofertę sprzedaży" → zakładka
"Przygotowane" (Sprzedaż) → eksport do CSV (zawiera gotowe tytuły/opisy do
wklejenia na OLX albo wgrania w narzędziu typu BaseLinker — OLX nie
udostępnia publicznego API do masowego wystawiania dla zwykłych kont) →
oferta trafia do zakładki "Wystawione", gdzie oznaczasz ją jako "sprzedane"
albo "wycofaj".

**Pchli targ** — opcjonalna publiczna strona (bez logowania) pokazująca
wystawione oferty, do włączenia w Ustawienia → Ustawienia aplikacji. Dwa
niezależne przełączniki muszą być włączone naraz: moduł Sprzedaży i sam
"Pchli targ" — jeśli wyłączysz moduł Sprzedaży, publiczna strona znika
automatycznie, nawet gdy jej własny przełącznik zostaje włączony.

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

- **Kategorie / Magazyny / Pomieszczenia / Lokalizacje** — struktura
  magazynowa, opisana wyżej.
- **Ustawienia aplikacji** *(tylko Administrator)* — nazwa i logo appki,
  domyślny język (PL/EN), dane kontaktowe i przełącznik Pchlego targu.
- **Użytkownicy** *(tylko Administrator)* — konta, role, aktywacja/
  dezaktywacja.
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
