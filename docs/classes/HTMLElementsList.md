HTMLElementsList
===

Generator kodu HTML list elementów (np. listy utworzonych stron, listy przesłanych plików, listy zainstalowanych typów zawartości bądź motywów), zaprojektowany głównie z myślą o panelu administracyjnym systemu. Umożliwia dodanie do poszczególnych pozycji menu nawigacyjnego oraz odnośnika bądź pola wyboru radio. Klasa dziedziczy po klasie `HTMLTag`.

Lista umieszczana jest w znaczniku `<ul>`, a poszczególne jej elementy w znacznikach `<li>`. Należy określić kolekcję danych (tablicę elementów), po której generator kodu HTML będzie iterował w celu wygenerowania listy. Właściwości poszczególnych elementów listy (tytuł, odnośnik, pole radio, menu nawigacyjne) określane są za pomocą callbacków, które jako argument otrzymują pojedynczy element tablicy kolekcji danych. Należy również określić komunikat generowany, gdy kolekcja danych jest pusta.

Jeśli nie wskazano inaczej, każda metoda zwraca `$this`, umożliwiając stworzenie łańcucha poleceń.

## `collection(array &$collection) : HTMLElementsList`

Służy do określenia kolekcji danych. Kolekcja danych test tablicą zawierającą poszczególne elementy, po których generator będzie iterował. Callbacki określone przy użyciu opisanych niżej metod będą otrzymywać każdy z elementów kolekcji danych jako argument.

Określenie kolekcji danych jest obowiązkowe. Argument przyjmowany jest jako referencja.

## `title(callable $callback) : HTMLElementsList`

Umożliwia określenie w argumencie `$callback` callbacka, który jako argument otrzyma element kolekcji danych, a zwrócić ma tytuł danego elementu listy.

Określenie callbacka tytułów elementów jest obowiązkowe.

Uwaga: tytuł elementu listy nie jest escapowany — niepożądane znaczniki HTML muszą zostać usunięte ręcznie.

## `link(callable $callback, array $HTMLAttributes = []) : HTMLElementsList`

Dodaje do tytułów elementów odnośnik. W argumencie `$callback` należy wskazać callback, który jako argument otrzyma element kolekcji danych, a zwróci adres URL odnośnika.

Opcjonalny argument `$HTMLAttributes` umożliwia określenie dodatkowych atrybutów dla znacznika `<a>`. Należy podać go jako tablicę — jej klucze zostaną nazwami atrybutów, a wartości ich wartościami.

Nie można dla tytułu elementu określić jednocześnie pola radio i odnośnika. W takim wypadku zostanie rzucony wyjątek `HTMLElementsListException` #2.

## `radio(string $name, callable $fieldValueCallback, $currentValue, array $HTMLAttributes = []) : HTMLElementsList`

Dodaje do tytułów elementów pole wyboru radio.

Argument `$name` określa atrybut `name` znacznika `<input>`. W argumencie `$fieldValueCallback` należy wskazać callback, który jako argument otrzyma element kolekcji danych, a zwróci wartość pola radio (atrybut `value` znacznika `<input>`). Argument `$currentValue` określa bieżącą wartość przełącznika — jeżeli wartość pola (wartość zwrócona przez wywołanie zwrotne `$fieldValueCallback`) będzie równa `$currentValue`, pole zostanie zaznaczone.

Opcjonalny argument `$HTMLAttributes` umożliwia określenie dodatkowych atrybutów dla znacznika `<input>`. Należy podać go jako tablicę — jej klucze zostaną nazwami atrybutów, a wartości ich wartościami.

Nie można dla tytułu elementu określić jednocześnie pola radio i odnośnika. W takim wypadku zostanie rzucony wyjątek `HTMLElementsListException` #2.

## `option(...)`

Alias dla metody `radio()`.

## `menu(callable $callback) : HTMLElementsList`

Dodaje do elementów listy menu nawigacyjne. Argument `$callback` służy do określenia callbacka, który jako argument otrzyma element kolekcji danych, a zwróci tablicę z elementami menu.

Menu generowane jest w formie listy `<ul>`.

Tablica elementów menu zawierać powinna tablice zagnieżdżone określające właściwości poszczególnych pozycji menu nawigacyjnego, podane w następującej kolejności:

* etykieta pozycji menu, wymagane,
* adres URL odnośnika menu, wymagane,
* klasa CSS dla elementu menu (znacznika `<li>`), opcjonalnie.

## `emptyMessage(string $text) : HTMLElementsList`

Służy do określenia w argumencie `$text` komunikatu generowanego zamiast listy elementów, jeśli kolekcja danych jest pusta.

## `output() : void`

Generuje kod HTML listy elementów. Jeśli kolekcja nie jest pusta, generowana jest lista `<ul>`. Jeżeli jednak kolekcja danych jest pusta, a określono komunikat poprzez `emptyMessage()`, generowany jest akapit `<p>` z dodatkową klasą CSS `emptyMessage` i określonym komunikatem.

Zostanie rzucony wyjątek `HTMLElementsListException` #1, jeśli nie zostanie określony callback tytułów bądź kolekcja danych.

Metoda nie zwraca wartości.