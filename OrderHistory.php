<?php

namespace admin\components;

class OrderHistory extends BaseComponent{

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
   *  Вовращает историю изменения позиций заказа из таблицы order_history по orderId
   *  @param mixed $orderId - id закаа
   */
  public function getProductHistory(
    $orderId
  ){
    $req = $this->mysqli->query("SELECT * from `order` where `id_order` = '$orderId'");
    if ($req){
      $row = $req->fetch_assoc();
      $orderAi = $row["ai"];
    }
    else{
      return [];
    }
    $req = $this->mysqli->query("SELECT * from `order_history` where `id_order_ai` = '{$orderAi}'");
    $result = [];
    if ($req){
      while($row = $req->fetch_array()){
        if (!$row["json_post"]){
          continue;
        }
        $post = json_decode($row["json_post"],true);
        if (!isset($post["type"]) && !isset($post["del"])){
          continue;
        }
        $result[$row["ai"]] = $post;
      }
    }
    return $result;
  }

}
