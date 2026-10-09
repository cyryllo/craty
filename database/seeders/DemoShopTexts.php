<?php

namespace Database\Seeders;

/**
 * Przykładowe teksty Ustawień sklepu (opis, polityka prywatności, regulamin)
 * dla danych demo. Pisane z punktu widzenia osoby prywatnej, która sprzedaje
 * własne rzeczy (nie sklep ani firma), i oznaczone jako przykład: na
 * prawdziwej instalacji trzeba je zastąpić własnymi.
 */
class DemoShopTexts
{
    public const DESCRIPTION = <<<'MD'
**Sprzedaję prywatnie swoje rzeczy**, których już nie używam: sprzęt, narzędzia i meble. To nie jest sklep ani firma, tylko wyprzedaż z mojego warsztatu.

Odbiór osobisty po wcześniejszym umówieniu się, płatność gotówką albo przelewem przy odbiorze. Napisz albo zadzwoń, odpowiem najszybciej, jak dam radę.
MD;

    public const PRIVACY_POLICY = <<<'MD'
*To jest przykładowy tekst z danych demo. Zastąp go własnym w Ustawieniach sklepu.*

## Kto przetwarza dane

Tę stronę prowadzę prywatnie, jako osoba fizyczna sprzedająca własne rzeczy. Nie jestem sklepem ani firmą. Kontakt ze mną: adres e-mail podany na stronie głównej.

## Jakie dane dostaję

Strona nie wymaga zakładania konta ani wypełniania formularzy. Twoje dane (imię, e-mail, numer telefonu) poznaję tylko wtedy, gdy sam do mnie napiszesz albo zadzwonisz w sprawie którejś rzeczy.

## Po co

- żeby odpowiedzieć na Twoje pytanie,
- żeby umówić się na odbiór.

Nie przekazuję Twoich danych nikomu i nie wysyłam żadnych reklam.

## Jak długo

Wiadomości trzymam tylko tak długo, jak są potrzebne do załatwienia sprawy, potem je usuwam.

## Twoje prawa

W każdej chwili możesz poprosić o informację, jakie Twoje dane mam, o ich poprawienie albo usunięcie. Masz też prawo złożyć skargę do Prezesa Urzędu Ochrony Danych Osobowych.

## Pliki cookies

Strona używa tylko technicznych plików cookies potrzebnych do działania (np. zapamiętania wybranego widoku listy). Żadnych cookies reklamowych ani statystyk odwiedzin.
MD;

    public const TERMS = <<<'MD'
*To jest przykładowy tekst z danych demo. Zastąp go własnym w Ustawieniach sklepu.*

## Czym jest ta strona

1. To prywatna wyprzedaż rzeczy, które należą do mnie i których już nie potrzebuję. Sprzedaję jako osoba prywatna, nie prowadzę sklepu ani działalności gospodarczej.
2. Nie da się tu nic zamówić ani zapłacić online. Opisy rzeczy to zaproszenie do kontaktu, a nie oferta w rozumieniu Kodeksu cywilnego.

## Jak kupić

1. Napisz albo zadzwoń, korzystając z danych na stronie głównej.
2. Umawiamy się na odbiór osobisty. Przed zakupem możesz rzecz obejrzeć i sprawdzić.
3. Rzeczy są używane, chyba że w opisie napisałem inaczej. Stan techniczny podaję w opisie każdej z nich, zgodnie z tym, co o niej wiem.
4. Ponieważ to sprzedaż między osobami prywatnymi, nie obowiązuje tu prawo do zwrotu towaru kupionego na odległość, przewidziane dla zakupów w sklepach internetowych.

## Rezerwacje

Rzecz może zostać sprzedana, zanim zniknie ze strony. Rezerwację zawsze potwierdzam w odpowiedzi na Twoją wiadomość.

## Kontakt

Wszystkie pytania kieruj na e-mail albo numer telefonu podany na stronie głównej.
MD;
}
