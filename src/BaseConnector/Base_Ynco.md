# BaseConnector > Base_Ynco
Класс создающий библиотеку для взаимодействия с БД

Основные возможности:

- Создаёт библиотеку для взаимодействия с БД

**!ВНИМАНИЕ! Нужен класс QueryBuilder и BaseControllerCore**

Поддерживаемые базы данных:

- MySQL

Пример работы: 

    $lib = new Connector\Base_Ynco;
    $lib->setFile(DIR.'base.json','json');
    $lib->createLib('*_Controller');
    $lib->saveToFile(DIR_LIB.$lib->nameClass.'.php');


## Основные методы класса ##

**setFile**

    BaseConnector\Base_Ynco $lib->setFile( string $path, string $format)

Выбирает файл с дампом БД в заданом `$format` формате.

Возвращает сам класс.

----------

**createLib**

    BaseConnector\Base_Ynco $lib->createLib($nameClass = 'BaseController')

Создаёт библиотеку под названием `$nameClass`
Если в названии есть `*` то она заменится на название БД.

Результат работы находится в `$lib->class`

Возвращает сам класс.

----------

**saveToFile**

    boolean $dumper->saveToFile( string $path)

Сохраняет класс в файл возвращает результат сохранения.

Возвращает результат проверки существование фала.

----------
