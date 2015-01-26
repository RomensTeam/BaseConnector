<?
/**
 * Base_Ynco
 * 
 * Создаёт пользовательскую библиотеку
 * которая выполняет основные запросы к базе
 * через предоставленный интерфейс
 * 
 * @author Romens
 */
namespace BaseConnector{

    class Base_Ynco {

        /**
         * Режим работы класса
         * 
         * Может быть:
         *  'min' - абсолютное сканирование и создание новой базы
         *  'classic' - Создание обычной легкочитаемой библоитеки
         *  'full' - Создание библиотеки с коментариями к каждой функции
         * 
         * @var string Навзание режима
         */
        public $mode = 'classic';
        
        public $nameClass = '';

        public $class = '';

        public $structure = array();

        public $base = array();


        public function createLib($nameClass = 'BaseController') {
            $nameClass = str_replace('*', $this->base['name'], $nameClass);
            $this->nameClass = $nameClass;
            $this->class .= '<?php
                
class '.$nameClass.' extends BaseControllerCore {'.  NL;
            $this->library_write();
            $this->class .= NL.'}';
        }
        
        public function saveToFile($path) {
            $fp = fopen($path,"w+");
            fwrite($fp, $this->class);
            fclose($fp);

            return file_exists($path);
        }
        
        public function library_write() {
            $this->class .= NL;
            $this->structure_create();
            $this->select_write();
            $this->insert_write();
            $this->update_write();
            $this->delete_write();
        }

        public function setBaseInfo($baseinfo) {
            $this->base = $baseinfo;
        }
        public function setFile($path,$format) {
            $file = file_get_contents($path);
            if($format == 'json'){
                $this->setBaseInfo(json_decode($file, true));
            } else {
                $this->setBaseInfo(unserialize($file));
            }
            print_var($this->base);
        }

        public function select_write() {
            foreach ($this->base['tables'] as $key => $value) {
                
                $this->class .= NL;
                $this->class .= NL.TAB."public  function ".$key."_select(\$columns, \$where = null, \$limit = null) {";
                $this->class .= NL.TAB.TAB."\$columns = \$this->getColumn(\$columns);";
                $this->class .= NL.TAB.TAB."\$where = \$this->getWhere(\$where);";
                $this->class .= NL;
                $this->class .= NL.TAB.TAB."return \$this->".$key."_select_query(\$columns, \$where, \$limit);";
                $this->class .= NL.TAB."}"; 
                $this->class .= NL;
                $this->class .= NL.TAB."private function ".$key."_select_query(\$columns, \$where = null, \$limit = null) {";
                $this->class .= NL.TAB.TAB."\$this->qb->select(\$columns)";
                $this->class .= NL.TAB.TAB.TAB.TAB.TAB."->from('".$key."');";
                $this->class .= NL;
                $this->class .= NL.TAB.TAB."if(!empty(\$where)){ \$this->qb->where(\$where);}";
                $this->class .= NL.TAB.TAB."if(!empty(\$limit)){ \$this->qb->limit(\$limit);}";
                $this->class .= NL;
                $this->class .= NL.TAB.TAB."return \$this->qb->result();";
                $this->class .= NL.TAB."}"; 
            }
        }
        
        public function delete_write() {
            foreach ($this->base['tables'] as $key => $value) {
                
                $this->class .= NL;
                $this->class .= NL.TAB."public function ".$key."_delete(\$where) {";
                $this->class .= NL.TAB.TAB."\$where = \$this->getWhere(\$where);";
                $this->class .= NL;
                $this->class .= NL.TAB.TAB."\$this->qb->delete()";
                $this->class .= NL.TAB.TAB.TAB."  ->from('".$key."')";
                $this->class .= NL.TAB.TAB.TAB."  ->where(\$where);";
                $this->class .= NL;
                $this->class .= NL.TAB.TAB."return (boolean) \$this->qb->result();";
                $this->class .= NL.TAB.'}';
            }
        }
        
        public function insert_write() {
            $this->class .= NL;
            foreach ($this->base['tables'] as $key => $value) {
                $this->class .= NL;
                $primary = $this->primaryFields($key);
                $second  = $this->secondFields($key);
                $array = $this->input_fields($primary, $second);
                $this->class .= NL.TAB."public function ".$key."_insert(".implode(', ',$array).") {";
                $this->class .= NL.TAB.TAB."\$record = array(";
                if(!empty($primary)){
                    foreach ($primary as $field) {
                        $this->class .= NL.TAB.TAB.TAB."'".$field."'  => \$this->type(\$".strtolower($field).",  \$this->structure['".$key."']['".$field."']),";
                    }
                }
                $this->class .= NL.TAB.TAB.");";
                $this->class .= NL;
                if(!empty($second)){
                    foreach ($second as $field) {
                        $miniField = strtolower($field);
                        $this->class .= NL.TAB.TAB."if(!is_null(\$".$miniField.")){";
                        $this->class .= NL.TAB.TAB.TAB."\$record['".$field."'] = \$this->type(\$".$miniField.", \$this->structure['".$key."']['".$field."']);";
                        $this->class .= NL.TAB.TAB."}";
                    }
                }
                $this->class .= NL.TAB.TAB."return \$this->".$key."_insert_query(\$record);";
                $this->class .= NL.TAB."}";
                $this->class .= NL;
                $this->class .= NL.TAB."public function ".$key."_insert_query(\$record) {";
                $this->class .= NL.TAB.TAB."\$this->qb->insert(\$record)";
                $this->class .= NL.TAB.TAB.TAB."  ->from('".$key."');";
                $this->class .= NL.TAB.TAB."return (boolean) \$this->qb->result();";
                $this->class .= NL.TAB."}";
            }
        }
        
        public function update_write() {
            $this->class .= NL;
            foreach ($this->base['tables'] as $key => $value) {
                $this->class .= NL;
                $primary = $this->primaryFields($key);
                $second  = $this->secondFields($key);
                $array = $this->input_fields($primary, $second);
                $this->class .= NL.TAB."public function ".$key."_update(".implode(', ',$array).") {";
                $this->class .= NL.TAB.TAB."\$record = array();";
                $this->class .= NL;
                foreach (array_merge($primary,$second) as $field) {
                    $miniField = strtolower($field);
                    $this->class .= NL.TAB.TAB."if(!is_null(\$".$miniField.")){";
                    $this->class .= NL.TAB.TAB.TAB."\$record['".$field."'] = \$this->type(\$".$miniField.", \$this->structure['".$key."']['".$field."']);";
                    $this->class .= NL.TAB.TAB."}";
                }
                $this->class .= NL.TAB.TAB."return \$this->ads_update_query(\$record);";
                $this->class .= NL.TAB."}";
                $this->class .= NL;
                $this->class .= NL.TAB."public function ".$key."_update_query(\$record,\$where) {";
                $this->class .= NL.TAB.TAB."\$where = \$this->getWhere(\$where);";
                $this->class .= NL;
                $this->class .= NL.TAB.TAB."\$this->qb->update(\$record)";
                $this->class .= NL.TAB.TAB.TAB."  ->from('".$key."')";
                $this->class .= NL.TAB.TAB.TAB."  ->where(\$where);";
                $this->class .= NL.TAB.TAB."return (boolean) \$this->qb->result();";
                $this->class .= NL.TAB."}";
            }
        }
        
        public function primaryFields($table) {
            $array = array();
            
            foreach ($this->base['tables'][$table] as $key => $value) {
                if(is_null($value['Default']) and $value['Null'] == 'NO' and $value['Extra'] != 'auto_increment'){
                    $array[] = $value['Field'];
                }
            }
            return $array;
        }
        public function secondFields($table) {
            $array = array();
            
            foreach ($this->base['tables'][$table] as $key => $value) {
                if(empty($value['Default']) and $value['Null'] == 'YES' and $value['Extra'] != 'auto_increment'){
                    $array[] = $value['Field'];
                }
            }
            return $array;
        }
        public function input_fields($primary,$second) {
            $array1 = array();
            $array2 = array();
            
            foreach ($primary as $value) {
                $array1[] = '$'.strtolower($value);
            }
            foreach ($second as $value) {
                $array2[] = '$'.strtolower($value).' = NULL';
            }
            return array_merge($array1, $array2);
        }
        
        public function structure_create() {
            $this->class .= TAB.'private $structure = array(';
            foreach ($this->base['tables'] as $nameTable => $arrayField) {
               $this->class .= NL.TAB.TAB."'".$nameTable."' => array(";
               foreach ($arrayField as $value) {
                   $this->class .= NL.TAB.TAB.TAB."'".$value['Field']."' => array(";
                   $this->class .= NL.TAB.TAB.TAB.TAB."'Convert' => '".$value['Convert']."',";
                   if(isset($value['Size'])){
                    $this->class .= NL.TAB.TAB.TAB.TAB."'Size' => ".$value['Size']."";
                   }
                   $this->class .= NL.TAB.TAB.TAB."),";
               }
               $this->class .= NL.TAB.TAB."),";
            }
            $this->class .= NL.TAB.');';
        }
    }
}
