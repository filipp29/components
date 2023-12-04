<?php

namespace admin\components;

class OrderManager extends BaseComponent{
  
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
   *  Возвращает все строки из таблицы order_meneger
   *  @param mixed $where - блок where запроса
   *  @return  array
   */
  public function getAll(
    $where = null
  ){
    if ($where){
      $whereBlock = " where {$where} ";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `order_meneger` {$whereBlock}");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["ai"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
