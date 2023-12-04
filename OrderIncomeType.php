<?php

namespace admin\components;

class OrderIncomeType extends BaseComponent{

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
   *  Возвращает все строки из таблицы order_income_type в виде массива [id => name]
   *  @return array
   */
  public function getTypeList(){
    $req = $this->mysqli->query("SELECT * from `order_income_type`");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row["name"];
      }
    }
    return $result;
  }

}