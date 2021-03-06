*abstract* Addon
===

Klasa abstrakcyjna będąca podstawą dla klas reprezentujących poszczególne typy dodatków systemu WizyTówka. Wtyczki, typy zawartości i motywy są dodatkami. Klasa dziedzicząca po tej klasie jest abstrakcyjną reprezentacją rodzaju dodatku.

**Konfiguracja klasy dziedziczącej polega na określeniu statycznych i chronionych pól:**

- `$_addonsSubdir` — nazwa podfolderu katalogu `addons` przeznaczonego na dany typ dodatku (np. klasa `Themes` może określać podfolder `themes`);
- `$_defaultConfig` — tablica z domyślnymi ustawieniami nadpisywanymi przez ustawienia w pliku konfiguracyjnym `addon.conf` dodatku.

Klasa implementuje metody `__get()` i `__isset()`, umożliwiając operowanie na opcjach konfiguracyjnych dodatku jak na polach obiektu, oraz interfejs `IteratorAggregate`, pozwalając na iterowanie po opcjach w pętli. Udostępnia też metodę `__debugInfo()` dla debugowania przy użyciu funkcji `var_dump()`.

## *private* `__construct()`

Konstruktor jest prywatny — nie można tworzyć nowych dodatków z wnętrza kodu. Aby pobrać instancję klasy konkretnego typu dodatku należy użyć metody statycznej `getByName()` lub `getAll()`.

## *private* `__clone()`

Nie można klonować obiektu dodatku — jest to pozbawione sensu, bowiem dodatek nie ma wielu kopii w systemie plików.

## `getName() : string`

Zwraca nazwę dodatku (dokładniej: nazwę podfolderu danego dodatku znajdującego się w katalogu określonym w `$_addonsSubdir`).

## `getPath() : string`

Zwraca pełną bezwzględną ścieżkę do katalogu dodatku, z uwzględnieniem źródła dodatku (z systemu bądź użytkownika).

## `getURL() : string`

Zwraca adres URL — ścieżkę do katalogu dodatku, z uwzględnieniem źródła dodatku (z systemu bądź użytkownika).

## `isFromSystem() : bool`

Zwraca prawdę, jeśli dodatek jest integralnym elementem systemu WizyTówka i znajduje się w folderze systemu (domyślnie `system`; pełna ścieżka to `(katalog systemu)/addons/(folder typu dodatków)/(folder dodatku)`, na przykład `system/addons/themes/exampletheme`).

Zwraca fałsz, jeśli dodatek nie jest elementem systemu WizyTówka i znajduje się w folderze danych witryny (domyślnie `data`; pełna ścieżka to `(katalog danych)/addons/(folder typu dodatków)/(folder dodatku)`, na przykład `data/addons/themes/exampletheme`).

## `isFromUser() : bool`

Zwraca odwrotność wartości z metody `isFromSystem()`.

## *static* `getByName($name) : ?Addon`

Zwraca dodatek (instancję klasy) o nazwie `$name` bądź `null`, jeśli dodatek o takiej nazwie nie istnieje.

Nazwa dodatku określona w argumencie `$name` jest w rzeczywistości nazwą podfolderu znajdującego się w folderze danego typu dodatków (ten folder określony jest w polu `$_addonsSubdir`).

Jeżeli dodatek o tej samej nazwie i typie znajduje się jednocześnie i w katalogu systemu (domyślnie `system`), i w katalogu danych witryny (domyślnie `data`), dodatek z katalogu danych witryny jest zwracany. Innymi słowy: dodatki z katalogu danych witryny nadpisują systemowe dodatki.

## *static* `getAll() : array`

Zwraca tablicę zawierającą wszystkie dodatki (instancje klasy) danego typu. Jeśli dodatków danego typu brak, zwracana jest pusta tablica.

Dodatki są posortowane alfabetycznie według nazwy, pierwsze w tablicy są dodatki z katalogu danych witryny, później systemowe. Dodatki z katalogu danych witryny nadpisują dodatki systemowe o tej samej nazwie.