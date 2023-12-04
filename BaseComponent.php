<?php

namespace admin\components;

include_once ($_SERVER["DOCUMENT_ROOT"]. "/admin/common_function.php");

abstract class BaseComponent{



  /**
   *  @var \mysqli
   */
  protected $mysqli;

  /*-----------------------------------------------------------------------*/

  /**
   *  Конструктор
   *  @param \mysqli $mysqli - объект бд
   */
  public function __construct(
    \mysqli $mysqli
  ){
    $this->mysqli = $mysqli;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы order_meneger
   *  @param mixed $table - наименование таблицы
   *  @param mixed $where - блок where запроса
   *  @return  array
   */
  public function allGet(
    $table,
    $where = null
  ){
    if ($where){
      $whereBlock = " where {$where} ";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `{$table}` {$whereBlock}");
    $result = [];
    $primary = $this->getPrimaryKey($table);
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row[$primary]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает одну строку из таблицы table по where
   *  @param mixed $table - наименование таблицы
   *  @param mixed $where - блок where
   */
  public function oneGet(
    $table,
    $where
  ){
    if ($where){
      $whereBlock = " where {$where} ";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `{$table}` {$whereBlock}");
    if ($req){
      if($row = $req->fetch_assoc()){
        return $row;
      }
      else{
        return [];
      }
    }
    return [];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает одну строку из таблицы table по id
   *  @param mixed $table - наименование таблицы
   *  @param mixed $id - первичный ключ
   */
  public function oneById(
    $table,
    $id
  ){
    $primary = $this->getPrimaryKey($table);
    $id = $this->mysqli->real_escape_string($id);
    $req = $this->mysqli->query("SELECT * from `{$table}` WHERE `{$primary}` = '{$id}'");
    if ($req){
      if($row = $req->fetch_assoc()){
        return $row;
      }
      else{
        return [];
      }
    }
    return [];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Вставляет строку с данными data в таблицу tableName и возвращает первичный ключ новой строки  или false
   *  @param mixed $tableName - наименование таблицы
   *  @param mixed $data - массив с данными для вставки
   */
  public function addOne(
    $tableName,
    $data
  ){
    $fieldList = $this->getFieldList($tableName);
    $query = "INSERT into `{$tableName}` SET %s";
    $setList = [];
    foreach($data as $key => $value){
      if (in_array($key,$fieldList)){
        $key = $this->mysqli->real_escape_string($key);
        $value = $this->mysqli->real_escape_string($value);
        $setList[] = "`{$key}` = '{$value}'";
      }
    }
    if ($setList){
      $setBlock = implode(",",$setList);
      $this->mysqli->query(sprintf($query,$setBlock));
      return $this->mysqli->insert_id;
    }
    return false;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет строку id в таблице tableName
   *  @param mixed $tableName - наименование таблицы
   *  @param mixed $id - первичный ключ
   *  @param mixed $data - данные для изменения в виде ассоциативного массива
   */
  public function changeOne(
    $tableName,
    $id,
    $data
  ){
    $fieldList = $this->getFieldList($tableName);
    $primaryKey = $this->getPrimaryKey($tableName);
    $query = "UPDATE `{$tableName}` SET %s WHERE `{$primaryKey}` = '{$id}'";
    $setList = [];
    foreach($data as $key => $value){
      if (in_array($key,$fieldList)){
        $key = $this->mysqli->real_escape_string($key);
        $value = $this->mysqli->real_escape_string($value);
        $setList[] = "`{$key}` = '{$value}'";
      }
    }
    
    if ($setList){
      $setBlock = implode(",",$setList);
      $this->mysqli->query(sprintf($query,$setBlock));
      return true;
    }
    return false;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список полей таблицы
   *  @param mixed $tableName - наименование таблицы
   *  
   */
  private function getFieldList(
    $tableName
  ){
    $req = $this->mysqli->query("SELECT `COLUMN_NAME` FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='a0087939_wor00'  AND `TABLE_NAME`='{$tableName}'");
    $fieldList = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $fieldList[] = $row["COLUMN_NAME"];
      }
    }
    return $fieldList;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает первичный ключ таблицы tableName
   *  @param mixed $tableName
   */
  private function getPrimaryKey(
    $tableName
  ){
    $req = $this->mysqli->query("SELECT * FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`='a0087939_wor00'  AND `TABLE_NAME`='{$tableName}'");
    $fieldList = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        if ($row["COLUMN_KEY"] == "PRI"){
          return $row["COLUMN_NAME"];
        }
      }
    }
    return null;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /*-----------------------------------------------------------------------*/

  

}
