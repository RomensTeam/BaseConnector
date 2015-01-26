# BaseConnector > Base_Dump
Класс создающий файл-дамп структуры БД.

Основные возможности:

- Создаёт дамп
- Сохраняет дамп в JSON или в файл с сериализацией данных

Поддерживаемые базы данных:

- MySQL

Пример работы: 

    $dumper = new BaseConnector\Base_Dump();
    $dumper->setBase('mysql_base');
    $dumper->dump();
    $result = $dumper->result();
	var_dump($result);


## Основные методы класса ##

**setBase**

    BaseConnector\Base_Dump $dumper->setBase( string $base = NULL)

Выбирает базу для работы над ней.
Если название базы выбрано неправильно будет вызвано исключение *PDOException*

Возвращает сам класс.

----------

**dump**

    void $dumper->dump( void )

Производит дамп структуры БД
Если при дамп'е будет ошибка, то будет вызвано исключение PDOException

----------

**result**

    mixed $dumper->result(string $type = 'array', boolean $pretty = false)

Возвращает дамп в определенном $type формате.

Принимает:

- **$type** - может быть *"array"* или *"json"* - задаёт возвращаемый формат данных
- **$pretty** - если истина, то возвратит *"json"* формат в удобно-читаемой форме.

----------

**saveToFile**

    boolean $dumper->saveToFile( string $path, string $format = 'json')

Сохраняет дамп в файл возвращает результат сохранения.

Принимает:

- **$path** - Абсолютный путь к файлу в котором надо сохранить дамп. (Желательно писать с расширением файла) 
- **$type** - может быть *"array"* или *"json"* - задаёт формат данных для сохранения

----------
