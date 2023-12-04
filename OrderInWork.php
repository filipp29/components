<?php

namespace admin\components;

class OrderInWork extends BaseComponent{

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
   *  Создает запись в таблице order_in_work с orderId и userId с текущим временем
   *  @param mixed $orderId - номер заказа
   *  @param mixed $userId - id пользователя
   *  @param mixed $status - статус заказа в clientbase 
   */
  public function start(
    $orderId,
    $userId,
    $status = "unknown"
  ){
    $check = $this->getCurrent($userId);
    if ($check){
      return false;
    }
    $data = [
      "id_order" => $orderId,
      "id_user" => $userId,
      "start" => date("Y-m-d H:i:s"),
      "status" => $status
    ];
    return $this->addOne("order_in_work",$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет в строке с orderId и userId и end=null поле end на текущее время
   *  @param mixed $orderId - номер заказа
   *  @param mixed $userId - id пользователя
   */
  public function end(
    $orderId,
    $userId
  ){
    $buf = $this->getCurrent($userId);
    if ($buf){
      $currentOrder = $buf["id_order"];
      if ($orderId != $currentOrder){
        return false;
      }
      $currentId = $buf["id"];
      $data = [
        "end" => date("Y-m-d H:i:s")
      ];
      $this->changeOne("order_in_work",$currentId,$data);
      return true;
    }
    return false;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Проверяет есть ли в таблице order_in_work строка с userId у которой поле end равно null и возвращает ее либо возвращает null
   *  @param mixed $userId - id пользователя
   */
  public function getCurrent(
    $userId
  ){
    $userId = $this->mysqli->real_escape_string($userId);
    $req = $this->mysqli->query("SELECT * from `order_in_work` where `id_user` = '{$userId}' and `end` IS NULL");
    if ($req){
      $row = $req->fetch_assoc();
      return $row;
    }
    return null;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает историю выполнения заказа из таблиы order_in_work по orderId
   *  @param mixed $orderId - id заказа
   */
  public function getOrderHistory(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $history = $this->allGet("order_in_work"," `id_order` = '{$orderId}'");
    usort($history,function($a,$b){
      $t1 = strtotime($a["start"]);
      $t2 = strtotime($b["start"]);
      return (int)$t2 - (int)$t1;
    });
    return $history;
  }

  /*-----------------------------------------------------------------------*/

}
