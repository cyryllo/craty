# Instrukcja użytkownika

Krótki, praktyczny przewodnik po głównych funkcjach Craty — bez żargonu
technicznego. Jeśli szukasz instrukcji instalacji/wdrożenia, zobacz
[README.md](README.md); historia zmian jest w [CHANGELOG.md](CHANGELOG.md).

**Pełna instrukcja z przykładami krok po kroku i zrzutami ekranu:**
[cyryllo.github.io/craty/instrukcja](https://cyryllo.github.io/craty/instrukcja/)
(źródło w [docs/instrukcja/](docs/instrukcja/index.html)).

## Role i uprawnienia

Są trzy role: **Administrator**, **Magazynier** i **Użytkownik** (tylko rezerwacje maszyn, opis niżej). Magazynier ma dostęp do
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

**Sortowanie i zapamiętane filtry**: w widoku listy kliknij nagłówek kolumny (nazwa, numer ewidencyjny, kategoria, lokalizacja, wartość, status), żeby posortować; drugie kliknięcie odwraca kolejność, a strzałka pokazuje, po czym jest posortowane. W widoku kafelków sortowanie wybierasz z listy „Sortuj” (najnowsze, najstarsze, nazwa, wartość). Wyszukiwanie, filtry i sortowanie są zapamiętywane: po powrocie na listę przedmiotów (np. z menu) widzisz to samo co ostatnio. Gdy jakiś filtr jest włączony, nad listą widać żółty pasek z przyciskiem „Wyczyść filtry”, który przywraca pełną listę.

**Historia zmian**: każda edycja kluczowych pól (nazwa, wartość, stan,
status, kategoria, lokalizacja) zapisuje się w historii widocznej na karcie
przedmiotu, razem z tym, kto i kiedy ją zrobił.

**Etykiety do druku**: z listy przedmiotów (zaznacz checkboxy) albo z karty
pojedynczego przedmiotu — trzy rozmiary naklejek do wyboru (z kodem QR, z
nazwą, opcjonalnie z ceną), wybór szablonu dzieje się na samym podglądzie
wydruku.

## Wypożyczenia

Wypożyczenia to moduł, domyślnie włączony. Kto ich nie potrzebuje, wyłącza je w Ustawienia → Moduły: znika wtedy zakładka Wypożyczenia, ramka wypożyczenia na karcie przedmiotu, zestawy, rezerwacje na dni, protokoły, liczniki na Panelu i e-mail o przeterminowanych wypożyczeniach. Zapisane wypożyczenia zostają w bazie i wracają po ponownym włączeniu.

Wypożyczasz z karty przedmiotu, w ramce „Wypożyczenie”: kliknij „Wypożycz”, żeby rozwinąć formularz (zwrot rozwija się tak samo, przyciskiem „Zarejestruj zwrot”).

- **Komu**: wybierasz kontakt z listy albo „+ Nowy kontakt” i wpisujesz nazwę (oraz opcjonalnie telefon i e-mail). Nowy kontakt zapisuje się sam, więc następnym razem jest już na liście. Przy wyłączonym module Kontakty pole „komu” to zwykły tekst, jak wcześniej.
- **Termin zwrotu** i **uwagi** (np. „z ładowarką i walizką”) są opcjonalne. Uwagi drukują się na protokole wydania.
- Przedmiot dostaje status „Wypożyczony”, a Craty zapamiętuje jego stan techniczny w chwili wydania i to, kto z obsługi go wydał.

Przy wypożyczonym przedmiocie w tej samej ramce jest link **„Protokół wydania (PDF)”**: dokument do wydrukowania i podpisania przez obie strony, z danymi wypożyczającego (z kontaktu: telefon, e-mail, adres), przedmiotu (numer ewidencyjny, numer seryjny, wartość, stan), datami i uwagami.

**Zwrot**: pod danymi wypożyczenia wybierasz stan przy zwrocie (domyślnie dotychczasowy) i opcjonalnie wpisujesz uwagi, np. uszkodzenia, potem „Zarejestruj zwrot”. Jeśli stan się zmienił, zmienia się też w kartotece przedmiotu (i zapisuje w historii). Na **protokole zwrotu** widać stan przy wydaniu i przy zwrocie obok siebie, z wyraźnym oznaczeniem zmiany.

Ostatnie zakończone wypożyczenia są wymienione pod formularzem, z linkami do obu protokołów. Protokoły generują się na bieżąco z zapisanych danych, nie trzeba ich nigdzie zapisywać.

W historii przedmiotu zapisuje się, komu go wypożyczono (i do kiedy), a przy zwrocie, od kogo wrócił. Przeterminowane wypożyczenia widać na Panelu; opcjonalnie appka wysyła codzienne e-mailowe podsumowanie przeterminowanych wypożyczeń do wszystkich aktywnych kont (Ustawienia → Powiadomienia).

### Zestawy (kilka przedmiotów naraz)

Gdy jedna osoba bierze kilka rzeczy (np. komplet na wyjazd), wypożycz je jako **zestaw**: jeden formularz, jedna osoba, jeden wspólny protokół wydania z listą wszystkich przedmiotów. Przedmioty do zestawu wybierasz na dwa sposoby:
- **Skanuj serię** (Skanuj → „Skanuj serię”, albo przycisk „Wypożycz zestaw” na stronie Wypożyczenia): skanujesz kody po kolei, każdy trafia na listę, potem „Wypożycz jako zestaw”.
- **Lista przedmiotów**: zaznacz przedmioty checkboxami i kliknij „Wypożycz zaznaczone”.

Przedmioty, których nie da się teraz wypożyczyć (już wypożyczone, w naprawie, sprzedane), są oznaczone i pomijane. Strona zestawu pokazuje wszystkie rzeczy; **zwrot** zaznaczasz przy każdej osobno (ze stanem i uwagami), więc można zarejestrować zwrot całości albo tylko tego, co wróciło. Resztę da się zwrócić później, na stronie zestawu albo zwykłym zwrotem z karty przedmiotu. Protokół zwrotu zestawu obejmuje to, co już wróciło. Na karcie przedmiotu z zestawu widać numer zestawu (np. „Z7/2026”) z linkiem.

### Rezerwacje

Na karcie przedmiotu, w ramce „Wypożyczenie”, sekcja **Rezerwacje**: „Zarezerwuj” zapisuje, dla kogo i w jakie dni (od–do) przedmiot jest zaklepany. Dwie rezerwacje tego samego przedmiotu na zachodzące terminy nie przejdą. Gdy rezerwacja trwa, nad formularzem wypożyczenia pojawia się żółta informacja, dla kogo jest zarezerwowany; wypożyczyć komuś innemu nadal się da, decyzja należy do obsługi. Przycisk **„Wydaj teraz”** zamienia trwającą rezerwację w zwykłe wypożyczenie dla tej samej osoby, z terminem zwrotu równym końcowi rezerwacji. Rezerwacje na dziś i jutro widać na Panelu.

### Strona Wypożyczenia

Zakładka **Wypożyczenia** w menu zbiera w jednym miejscu: co jest teraz wypożyczone (z zaznaczeniem po terminie), wypożyczone zestawy (ile rzeczy jeszcze nie wróciło) i nadchodzące rezerwacje.

## Przedmioty stacjonarne i rezerwacje maszyn

**Przedmiot stacjonarny** to maszyna, która zostaje w pracowni (drukarka 3D, ploter laserowy, stanowisko lutownicze). Zaznaczasz go polem „Stacjonarny” w formularzu przedmiotu. Takiego przedmiotu nie da się wypożyczyć, zarezerwować na dni ani dodać do zestawu; na karcie przedmiotu zamiast formularza wypożyczenia jest informacja, że zostaje w pracowni.

**Właściciel albo opiekun**: w formularzu przedmiotu pole „Osoba odpowiedzialna” (przy włączonym module Kontakty) wskazuje osobę z Kontaktów i jej rolę: opiekun albo właściciel, np. członek makerspace'u, który przyniósł swoją drukarkę albo szkoli innych z plotera. Osobę najpierw dodaje się w Kontaktach. Widać ją na karcie przedmiotu (z telefonem i e-mailem), w grafiku maszyn pod nazwą maszyny i na karcie kontaktu; zmiana trafia do historii przedmiotu. W imporcie CSV to kolumny `osoba_odpowiedzialna` (nazwa kontaktu) i `rola_osoby` (opiekun albo wlasciciel).

**Rezerwacje maszyn** (moduł opcjonalny, domyślnie wyłączony, Ustawienia → Moduły) pozwalają rezerwować czas na przedmiotach stacjonarnych, np. w makerspace: kto i kiedy korzysta z drukarki albo plotera. W menu pojawia się zakładka **Maszyny** z grafikiem tygodnia: wiersze to maszyny, kolumny to dni, a każda rezerwacja ma godziny, osobę i kolor rodzaju użycia. Kliknięcie rezerwacji pokazuje jej opis. Na telefonie grafik jest listą dzień po dniu.

- **Nowa rezerwacja**: maszyna, dzień, godziny od–do (co 15 minut), rodzaj użycia (użytek prywatny, zajęcia, warsztaty; obsługa może też zablokować maszynę na konserwację) i opcjonalny opis, np. nazwa projektu. Rezerwacja jest ważna od razu. Nakładające się rezerwacje tej samej maszyny nie przejdą, Craty pokaże, kto ma już ten termin.
- **Karta maszyny** pokazuje najbliższe rezerwacje i pozwala od razu zarezerwować czas.
- **Odwołanie**: każdy odwołuje swoje nadchodzące rezerwacje (lista „Moje rezerwacje”), a obsługa także cudze.
- Na Panelu obsługi jest lista „Dziś na maszynach”.

**Rola Użytkownik** jest dla osób, które tylko rezerwują maszyny (np. członkowie makerspace'u). Konto zakłada administrator w Ustawienia → Użytkownicy. Użytkownik po zalogowaniu widzi wyłącznie grafik maszyn i swój profil: nie widzi magazynu, wypożyczeń, kontaktów ani ustawień. Gdy moduł jest wyłączony, zobaczy tylko informację, że rezerwacje są chwilowo niedostępne.

## Inwentaryzacja

Struktura magazynu → **„Inwentaryzacja”** (albo na liście przedmiotów przefiltrowanej po miejscu przycisk **„Inwentaryzacja tego miejsca”**). Wybierasz miejsce do przeliczenia; magazyn, pomieszczenie, regał albo półka obejmuje wszystko, co jest pod nim. Potem skanujesz serią to, co faktycznie tam leży (aparatem, wpisując kod albo ręcznym czytnikiem kodów). Każdy skan zapisuje się od razu, więc można przerwać i dokończyć później, także na innym telefonie.

Po „Zakończ i pokaż raport” widać:
- **Brakuje**: przedmioty, które według kartoteki powinny tam być, a nie zostały zeskanowane. Craty niczego z nimi sam nie robi, to lista do sprawdzenia.
- **Znalezione tutaj, ale zapisane gdzie indziej**: przy każdym przycisk **„Przenieś tutaj”**, który poprawia lokalizację w kartotece (zmiana trafia do historii przedmiotu).
- **Wypożyczone albo w naprawie**: rzeczy z tego miejsca, które są teraz poza magazynem, więc nie liczą się jako braki.
- **Na miejscu**: wszystko, co się zgadza.

Raporty zostają na liście inwentaryzacji; sprzedane i wycofane przedmioty nie są brane pod uwagę.

## Materiały eksploatacyjne (moduł opcjonalny, domyślnie wyłączony)

Włączasz go w Ustawienia → Moduły; w menu pojawia się zakładka **Materiały**. To osobna lista na rzeczy liczone ilością (śrubki, klej, kabel, taśma), bez numerów ewidencyjnych i wypożyczeń. Każdy materiał ma jednostkę (szt., opak., m, l, kg), lokalizację, kategorię i **stan minimalny**.

Ilość zmieniasz na trzy sposoby: **„Pobierz”** (także szybko z samej listy), **„Uzupełnij”** po zakupie i **„Ustaw policzony stan”** po przeliczeniu na półce (zapisuje się jako korekta). Każda zmiana ma wpis w historii: kiedy, kto, o ile i z jaką uwagą. Edycja materiału nie zmienia ilości, tylko jego dane. Stan nie schodzi poniżej zera.

Gdy ilość spadnie do minimum albo niżej, materiał jest oznaczony na czerwono, filtr „Tylko kończące się” pokazuje wszystkie takie pozycje, a lista kończących się materiałów pojawia się na Panelu.

## Kontakty (moduł opcjonalny)

Zakładka **Kontakty** w menu to lista osób, firm i serwisów, którym wypożyczasz sprzęt. Każdy kontakt ma nazwę, typ (osoba, firma, serwis), telefon, e-mail, adres (drukowany na protokołach) i notatkę. Listę można przeszukiwać po nazwie, telefonie i e-mailu oraz filtrować po typie; przy każdym kontakcie widać liczbę wypożyczeń i to, ile rzeczy ma u siebie teraz.

Karta kontaktu pokazuje całą **historię jego wypożyczeń**: co, kiedy, czy wróciło (albo czy jest po terminie), z linkami do protokołów. Kontakty typu „serwis” przydadzą się w planowanym module Serwis (wysyłka sprzętu do naprawy).

Kontakt może też być właścicielem albo opiekunem przedmiotów (patrz „Przedmioty stacjonarne i rezerwacje maszyn”); jego karta pokazuje wtedy ich listę.

Usunięcie kontaktu nie kasuje jego wypożyczeń: zostają w historii pod tą samą nazwą, a przedmioty, którymi się opiekował, zostają bez osoby odpowiedzialnej. Po aktualizacji do wersji z Kontaktami dotychczasowe wpisy „komu” z wcześniejszych wypożyczeń zamieniły się w kontakty automatycznie (te same imiona i nazwy połączone w jeden kontakt), więc ich historia jest od razu kompletna.

Moduł można wyłączyć w Ustawienia → Moduły: znika wtedy zakładka Kontakty, a „komu” przy wypożyczeniu znów wpisuje się zwykłym tekstem. Protokoły PDF działają w obu przypadkach.

## Serwis (moduł opcjonalny, domyślnie wyłączony)

Włączasz go w Ustawienia → Moduły. Dochodzi wtedy zakładka **Serwis** w menu, ramka „Serwis” na karcie przedmiotu i nowe pola w formularzu przedmiotu.

**Naprawy.** Na karcie przedmiotu: „Wyślij do naprawy”. Wybierasz serwis z Kontaktów (serwisy są na górze listy), wpisujesz inny albo zostawiasz puste, jeśli naprawiasz we własnym warsztacie. Do tego opis usterki, data wysłania i opcjonalnie planowany powrót. Przedmiot dostaje status „W naprawie” i w tym czasie nie da się go wypożyczyć (a wypożyczonego nie da się wysłać do naprawy, najpierw trzeba zarejestrować zwrot). Gdy sprzęt wróci: „Zakończ naprawę”, z datą powrotu, kosztem, opisem tego, co zrobiono, i stanem po naprawie. Przedmiot wraca do statusu sprzed naprawy.

**Przeglądy okresowe.** W formularzu przedmiotu ustawiasz „Przegląd co (miesięcy)” i datę następnego przeglądu, np. dla gaśnic albo elektronarzędzi. Wykonany przegląd zapisujesz na karcie przedmiotu („Zapisz przegląd”: data, wynik pozytywny albo negatywny, uwagi), a następny termin liczy się sam z podanego okresu.

**Gwarancje.** W formularzu przedmiotu pole „Gwarancja do”. Na karcie przedmiotu widać, czy gwarancja się kończy albo już wygasła.

**Co wymaga uwagi.** Strona Serwis (i skrót na Panelu) zbiera w jednym miejscu: przedmioty w naprawie (z zaznaczeniem, gdy minął planowany powrót), przeglądy po terminie i te w ciągu najbliższych 14 dni oraz gwarancje kończące się w ciągu 30 dni (i te, które skończyły się w ostatnich 30 dniach). Sprzedane i wycofane przedmioty są pomijane.

Historia napraw i przeglądów jest na karcie przedmiotu, a na karcie kontaktu typu serwis widać wszystkie zlecone mu naprawy. Wyłączenie modułu chowa to wszystko, ale niczego nie kasuje: po ponownym włączeniu dane są na miejscu.

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
otwiera stronę główną w tej samej karcie, a w górnym pasku panelu (obok
przełącznika motywu, także na telefonie) ikonka koszyka prowadzi w to samo
miejsce.

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

**Skanuj serię** (link na stronie Skanuj) nie otwiera karty po pierwszym kodzie, tylko zbiera kolejne zeskanowane przedmioty na liście; z niej wypożyczasz zestaw albo drukujesz etykiety. Kod można też wpisać ręcznie albo użyć ręcznego czytnika kodów kreskowych podpiętego jak klawiatura. Lista zostaje zapamiętana po odświeżeniu strony.

## Import i eksport

Strona **Ustawienia → Import i eksport** (Administrator i Magazynier). Pliki są w CSV ze średnikiem, które Excel, LibreOffice i Arkusze Google otwierają od razu; po zmianach zapisz plik znowu jako CSV (pliki .xlsx nie są przyjmowane).

- **Eksport przedmiotów**: z wybraną kategorią i statusem (domyślnie wszystko na stanie, można też ze sprzedanymi i wycofanymi). Na końcu pliku są cztery kolumny tylko do wglądu (nazwa kategorii, miejsce, komu wypożyczony, do kiedy).
- **Import przedmiotów**: wiersz bez numeru ewidencyjnego dodaje nowy przedmiot (wymagana tylko nazwa), wiersz z numerem zmienia ten przedmiot, więc eksport, poprawki w arkuszu i ponowny import zmieniają wiele przedmiotów naraz. Pusta komórka i brakująca kolumna zostawiają wartość bez zmian. Kategorię i miejsce podajesz kodem (np. `NAR`, `M1-R3-P2`), status i stan słowami albo kodem, „stacjonarny” jako tak/nie, daty jako 2026-11-25 albo 25.11.2026. Przed zapisem zawsze jest **podgląd** (nowe, zmiany „było → będzie”, bez zmian, błędy), a zapisuje dopiero „Zatwierdź import”; wiersze z błędami są pomijane. Nieznany kod przy nowym przedmiocie to ostrzeżenie (przedmiot powstaje bez tego pola), przy zmianie to błąd. Zmiany trafiają do historii przedmiotu. Do 2000 wierszy w pliku.
- **Kontakty i materiały** (gdy moduły są włączone): eksport i import, dopasowanie po nazwie. Inna ilość materiału zapisuje się jako korekta w historii ruchów, więc import działa też jak spis z natury.
- **Raporty**: historia wypożyczeń (opcjonalnie od daty), raport z zakończonej inwentaryzacji i dziennik aktywności (tylko Administrator; z filtrami przyciskiem CSV na stronie dziennika).

## Ustawienia

Rozdzielnik Ustawień (link w prawym górnym rogu) grupuje wszystko w jednym
miejscu:

- **Kategorie** i **Struktura magazynu** — opisane wyżej (magazyny,
  pomieszczenia, regały, półki i pojemniki w jednym drzewie).
- **Import i eksport**: pliki CSV z przedmiotami, kontaktami i materiałami oraz raporty, opisane wyżej.
- **Ustawienia aplikacji** *(tylko Administrator)*: nazwa, logo i
  favicon (ikonka karty przeglądarki w PNG albo ICO; bez własnej używane
  jest logo) oraz
  domyślny język (PL/EN). Opcja „Użyj logo jako ikony aplikacji na telefonie” robi z logo ikonę aplikacji instalowanej na telefonie (logo na białym kwadracie, z podglądem). Najlepiej wygląda logo kwadratowe albo okrągłe. Bez tej opcji ikoną jest skrzynka Craty. Telefon z już zainstalowaną aplikacją może pokazywać starą ikonę, dopóki aplikacji nie doda się ponownie.
- **Opcje sprzedaży** *(tylko Administrator, przy włączonej Sprzedaży)*:
  opis sprzedaży, kontakt, polityka prywatności i regulamin, opisane wyżej.
- **Użytkownicy** *(tylko Administrator)*: konta, role, aktywacja i
  dezaktywacja. Wyłączone konto nie może się zalogować, a jeśli ktoś był
  akurat zalogowany, zostaje wylogowany przy następnym kliknięciu.
- **Poczta** *(tylko Administrator)* — własne SMTP zamiast domyślnej
  konfiguracji serwera. Przycisk wysłania testowej wiadomości działa tylko
  ze standardowymi portami poczty (25, 465, 587, 2525).
- **Powiadomienia** *(tylko Administrator)* — włącznik samodzielnego
  resetu hasła oraz e-mailowe podsumowanie przeterminowanych wypożyczeń. Przy włączonym module Serwis dochodzi cotygodniowe przypomnienie serwisowe: w każdy poniedziałek rano e-mail z przeglądami po terminie i w ciągu 14 dni, gwarancjami kończącymi się w ciągu 30 dni i naprawami po planowanym powrocie (tylko gdy coś jest na liście). Oba e-maile wymagają, żeby hosting uruchamiał harmonogram zadań (cron); w wersji Docker działa to samo.
- **Kopie zapasowe** *(tylko Administrator)*: jeden plik `.zip` z całą bazą danych i wszystkimi wgranymi plikami (zdjęcia, załączniki, kody QR, logo), robiony przyciskiem „Zrób kopię zapasową” albo automatycznie (codziennie lub co tydzień, wymaga crona; w Dockerze działa samo). Kopie leżą na serwerze w katalogu niedostępnym z internetu i usuwają się po wybranym czasie (7, 14, 30 albo 90 dni, albo bez limitu), ale najnowsza zostaje zawsze. Pobieranie, wgrywanie kopii z komputera (np. z innego serwera) i przywracanie wymagają potwierdzenia hasła. Przywrócenie zastępuje wszystkie dane i pliki zawartością kopii; przed nim Craty sam robi kopię bieżącego stanu (typ „przed przywróceniem”), a potem trzeba się zalogować ponownie kontem z kopii. W każdej kopii jest plik CZYTAJ-MNIE.txt z instrukcją ręcznego odtworzenia (import database.sql np. w phpMyAdmin i skopiowanie plików).
- **Dziennik aktywności** *(tylko Administrator)*: kto, co i kiedy zrobił. Zawsze zapisują się logowania (także nieudane, z wpisanym e-mailem), wylogowania, zakładanie, zmiany i usuwanie kont, zmiany ustawień i modułów oraz wgrane aktualizacje, z adresem IP. Po zaznaczeniu „Zapisuj też zmiany danych” dochodzi dodawanie, zmiany i usuwanie przedmiotów, kategorii, miejsc, kontaktów, wypożyczeń i innych danych. Hasła nigdy nie trafiają do dziennika, tylko informacja, że je zmieniono. Wpisy można filtrować po rodzaju i osobie oraz przeszukiwać, a stare wpisy usuwają się same po czasie wybranym na tej stronie (domyślnie rok; do wyboru od 30 dni do 2 lat albo bez limitu).
- **Moduły** *(tylko Administrator)* — włącz/wyłącz opcjonalne części
  appki (dziś: Wypożyczenia, Sprzedaż, Kontakty, Serwis, Materiały eksploatacyjne i Rezerwacje maszyn).
- **Aktualizacje** *(tylko Administrator)* — samodzielna aktualizacja
  appki przez wgranie paczki `.zip`, bez SSH/Composera. Zawsze rób kopię
  przed wgraniem nowej wersji (Ustawienia → Kopie zapasowe, link jest też na stronie Aktualizacje). Jeśli w trakcie przeglądarka zgłosi błąd sieci, aktualizacja i tak kończy się na serwerze: po minucie odśwież stronę i sprawdź wersję. W wersji z obrazu
  Docker paczek się nie wgrywa: aktualizacja to pobranie nowego obrazu
  (`docker compose pull`, potem `docker compose up -d`), a strona
  Aktualizacje tylko to podpowiada. Dane zostają w wolumenie Dockera, a
  zmiany w bazie wprowadzają się same przy starcie kontenera.

Każdy może też zmienić własny język i hasło w Profilu (menu użytkownika w
prawym górnym rogu), a jasny/ciemny motyw przełącza się przyciskiem w
prawym dolnym rogu ekranu.
