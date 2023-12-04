<?php

namespace admin\components;

class Invoice extends BaseComponent{

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
   *  Возвращает все строки из таблицы invoice, если стоит флаг clientbasePrihodStatus
   *  @return array
   */
  public function getInvoiceList(){
    $req = $this->mysqli->query("SELECT * from `invoice` ORDER BY `date_created` desc");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row;
      }
    }
    return $result;
  }

}