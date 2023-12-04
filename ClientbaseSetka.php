<?php

namespace admin\components;

class ClientbaseSetka extends BaseComponent{

  private $whereList = [];

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все позиции из таблицы clientbase_setka в виде массива [ai => row].
   *  @param mixed $where - блок where запроса
   *  @return array
   */
  public function getAllSetkaList(
    $where = ""
  ){
    if ($where){
      $whereBlock = " WHERE {$where}";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `clientbase_setka` {$whereBlock}");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["ai"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает параметр double_check в строке ai в таблице clientbase_setka равный checked
   *  @param mixed $ai - ai строки
   *  @param mixed $checked - новое значение поля double_check
   */
  public function setDoubleCheck(
    $ai,
    $checked
  ){
    $ai = $this->mysqli->real_escape_string($ai);
    if ($checked){
      $value = "1";
    }
    else{
      $value = "0";
    }
    $this->mysqli->query("UPDATE `clientbase_setka` SET `double_check` = '{$value}' WHERE `ai` = '{$ai}'");
  }

  /*-----------------------------------------------------------------------*/

}
