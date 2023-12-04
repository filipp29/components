<?php

namespace admin\components;

class OrderChangeHistory extends BaseComponent{

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
   *  Возвращает историю изменения заказа orderId из таблицы order_change_history
   *  @param mixed $orderId - id заказа
   *  @return array
   */
  public function getOrderHistory(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `order_change_history` where `id_order` = '{$orderId}'");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет запись в историю извменения заказа orderId в таблицу order_change_history
   *  @param mixed $orderId - id заказа
   *  @param mixed $message - комментарий к записи
   */
  public function addHistory(
    $orderId,
    $message
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $message = $this->mysqli->real_escape_string($message);
    $userId = $this->mysqli->real_escape_string($_SESSION["id_user"]);
    $createdAt = date("Y-m-d H:i:s",time());
    $this->mysqli->query("INSERT INTO `order_change_history` (`id_order`,`message`,`id_user`,`created_at`) VALUES ('{$orderId}','{$message}','{$userId}','{$createdAt}')");
  }

  /*-----------------------------------------------------------------------*/

}