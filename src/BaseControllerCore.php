<?php

/**
 * Класс для работы с БД
 */
class BaseControllerCore {
    
    public $pdo;
    public $qb;
    
    private $structure = array();
            
    function __construct($pdo) {
        $this->pdo = $pdo;
        $this->qb = new QueryBuilder($pdo);
    }

    protected function type($subject, $adress) {
        switch ($adress['Convert']) {
            case 'integer':
                $subject = $this->resize($subject, $adress);
                return (integer) $subject;
                break;

            default:
                $subject = $this->resize($subject, $adress);
                return (string) $subject;
                break;
        }
    }
    protected function resize($subject,$adress) {
        if(isset($adress['Size'])){
            $subject = substr($subject, 0, (int) $adress['Size']);
        }
        return $subject;
    }
    
    protected function getColumn($columns) {
        if(is_string($columns)){
            $columns = explode(',', $columns);
        }
        
        return (array) $columns;
    }
    
    protected function getWhere($where) {
        if(is_array($where)){
            if(count($where) == 2){
                $where = ' '.$where[0].' = '.$where[1].' ';
            } elseif (count($where) == 3) {
                $where = ' '.$where[0].' '.$where[1].' '.$where[2].' ';
            } else {
                throw new RemusException('Wrong where parametr');
            }
        }
        
        return (string) $where;
    }
    
    protected function correctColumn($column) {
        $column = str_replace(array('"',"'",'`'), "", $column);
        $column = explode('.', $column);
        
        $first = array_shift($column);
        $first = $this->inQuote($first);
        
        if(!empty($column)){
            foreach ($column as $value) {
                $first .= '.'.$this->inQuote($value);
            }
        }
        return ' '.$first.' ';
    }
    protected function inQuote($string) {
        return '`'.$string.'`';
    }
}
