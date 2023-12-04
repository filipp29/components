<?php

namespace admin\components;

class Order extends BaseComponent{

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
   *  Возвращает строку из таблицы order по orderId
   *  @param mixed $orderId - id_order заказа
   *  @return array
   */
  public function getOneByOrderId(
    $orderId
  ){
    $req = $this->mysqli->query("SELECT * from `order` where `id_order` = '{$orderId}'");
    if ($req){
      $row = $req->fetch_assoc();
      return $row;
    }
    else{
      return [];
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список заказов клиента nelson из таблицы order
   *  @param mixed $customerId - customer_id клиента
   *  @param mixed $done - если true возвращает только выполненные заказы
   */
  public function getCustomerOrderList(
    $customerId,
    $done = false
  ){
    $customerId = $this->mysqli->real_escape_string($customerId);
    $whereBlock = "WHERE `customer_id` = '{$customerId}'";
    if ($done){
      $done = $this->mysqli->real_escape_string(($done));
      $whereBlock .= " AND `status_done` = '1'";
    }
    $req = $this->mysqli->query("SELECT * from `order` {$whereBlock} ORDER BY `date_created` DESC");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id_order"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы order
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
    $req = $this->mysqli->query("SELECT * from `order` {$whereBlock}");
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
   *  Возвращает ссылку на заказ gj id_order
   */
  public function getOrderLink(
    $id_order
  ){
    $order = $this->getOneByOrderId($id_order);
    if ($order){
      return "/master.php?order={$order["ai"]}";
    }
    else{
      return "";
    }
    
  }

  /*-----------------------------------------------------------------------*/

}
