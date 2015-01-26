<?
/**
 * Base_Dump
 * 
 * @author Romens
 */
namespace BaseConnector;
class Base_Dump {
	
	/**
     * Режим работы класса
     * 
     * Может быть:
     *  'scan' - абсолютное сканирование и создание новой базы
     * 
     * @var string Навзание режима
     */
	public $mode = 'scan';
    
    /**
     * Подключение к базе
     * 
     * @var PDO Подключение к базе
     */
    public $connection = NULL;
    
    /**
     * Название базы
     * 
     * @var string Название базы
     */
    public $base = NULL;
    
    /**
     * Правила конвертации
     * 
     * @var array Правила конвертации
     */
    /*public $convert_rules = array(
        'int'           => '',
        'text'          => '',
        'date'          => '',
        'varchar'       => '',
        'tinyint'       => '',
        'tinytext'      => '',
        'timestamp'     => '',
        'tinyint(1)'    => '',
    );
    */
    
    /**
     * Результат работы
     * 
     * $result['name'] - название базы
     * $result['tables'] - информация о таблицах в базе
     * $result['tables'][$table] - список полей выбранной таблицы с информацией о них
     * $result['tables'][$table][$field] - информация о выбранном поле
     * $result['tables'][$table][$field]['Convert'] - информация о оптимальном типе для конвертации
     */
    private $result = NULL;
    
    /**
     * Начало работы режима
     * 
     * @param string $mode Название режима
     */
    public function __construct($mode = 'scan') {
        $this->mode = $mode;
        return $this;
    }
    
    /**
     * Установка базы для её дампа
     * 
     * @param string $base Установка базы
     */
    public function setBase($base = NULL) {
        $this->base = $base;
        return $this;
    }
    
    /**
     * Установка соединения с базой
     * 
     * @param PDO $pdo Активный PDO
     */
    public function setConnect($pdo) {
        $this->connection = $pdo;
        return $this;
    }
    
    /**
     * Возвращает соединение с базой
     * 
     */
    public function connect() {
        if(is_null($this->connection)){
            try {
                $this->connection = new \PDO('mysql:host=' . BASE_HOST . ';dbname=' . $this->base . ';charset=utf8', BASE_LOGIN, BASE_PASS);
            } catch (PDOException $exc) {
                echo $exc->getTraceAsString();
            }
        }
        return $this->connection;
    }
    
    /**
     * Установка базы для её дампа
     * 
     * @param string $base Установка базы
     */
    public function dump() {
        
        $tables = $this->getTables();
        
        $this->result = array(
            'name' => $this->base,
            'tables_number' => count($tables)
        );
        foreach ($tables as $value) {
            $value = array_shift($value);
            $table = $this->query("EXPLAIN ".$value.";", \PDO::FETCH_ASSOC);
            
            foreach ($table as $field) {
                if(isset($field['Field'])){
                    $name = $field['Field'];
                }
                
                $type_field = $field['Type'];
                $optimal_type = $this->getOptimalTypeAndMaxSize($type_field);
                
                $this->result['tables'][$value][$name] = $field; 
                $this->result['tables'][$value][$name]['Convert'] = $optimal_type['type'];
                
                if(isset($optimal_type['size'])){
                    $this->result['tables'][$value][$name]['Size'] = $optimal_type['size'];
                }
            }
        }
    }
    /**
     * Возвращает таблицы с базами
     * 
     * @return array Таблицы из базы
     */
    private function getTables() {
        $connect = $this->connect();
        return $this->query("SHOW TABLES FROM ".$this->base.";", \PDO::FETCH_NUM);
    }
    
    /**
     * Возвращает результат в заданном формате
     * 
     * @param string $type Тип результата
     *        'array' - обычный массив PHP
     *        'json'  - массов закодированный в JSON
     * @param boolean $pretty Нужен ли читаемый формат для JSON?
     */
    public function result($type = 'array', $pretty = false) {
        if(strtolower($type) == 'json'){
            if((boolean)$pretty){
                if(version_compare(PHP_VERSION, '5.4.0','>=')){
                    return json_encode($this->result,JSON_PRETTY_PRINT);
                }
            }
            return json_encode($this->result);
        }
        return $this->result;
    }
    
    /**
     * Сохраняет результат в файл
     * 
     * @param string $path Путь к файлу
     * @param string $format Тип результата
     *        'array' - обычный массив PHP
     *        'json'  - массов закодированный в JSON
     */
    public function saveToFile($path = DIR, $format = 'json') {
        $fp = fopen($path,"w+");
        $result = $this->result($format,true);
        
        if($format == 'array'){
            $result = serialize($result);
        }
        fwrite($fp, $result);
        fclose($fp);
        
        return file_exists($path);
    }
    
    /**
     * Возвращает оптимальный тип для конвертации 
     * и узнаёт макимально допустимый размер
     * 
     * @param string $mysql_type Тип данный сервером MySQL
     */
    private function getOptimalTypeAndMaxSize($mysql_type) {
        $mysql_type = (string) $mysql_type;
        
        $array = array();
        
        $size = $this->getSize($mysql_type);
        if($size){
            $array['size'] = $size;
        }
        
        if(substr($mysql_type,0,3) == 'int')     {$array['type'] = 'integer';}
        if($mysql_type == 'tinyint(1)')          {return array('type' => 'boolean');}
        if(substr($mysql_type,0,7) == 'tinyint') {$array['type'] = 'integer';}
        if($mysql_type == 'timestamp')           {$array['type'] = 'date';}
        if($mysql_type == 'date')                {$array['type'] = 'date';}
        
        if(!isset($array['type'])){
            $array['type'] = 'string';
        }
        return $array;
    }
    private function getSize($field) {
        $result = explode('(', $field);
        if(isset($result[1])){
            return (int) str_replace(')', '', $result[1]);
        }
        return FALSE;
    }
    
    private function query($SQL,$fetch_mode = \PDO::FETCH_ASSOC) {
        $tablet = $this->connect()->prepare($SQL);
        $tablet->execute();
        $tablet->setFetchMode($fetch_mode);  
        return $tablet->fetchAll();
    }
}
