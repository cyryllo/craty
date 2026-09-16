# Historia zmian

Krótki, chronologiczny zapis tego, co się zmieniło w projekcie. Szerszy
opis funkcji — [README.md](README.md).

## 2026-09-17

- Dodano wspólną stopkę (wersja aplikacji + link do repozytorium) na
  wszystkich stronach.

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
