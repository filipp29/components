<?php

namespace admin\components;

class Operation extends BaseComponent{

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /*-----------------------------------------------------------------------*/

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список операций из таблицы operations.
   *  @param array $params - параметры запроса: where - строка блока where
   *  @return array
   */
  public function getOperationList(
    $params = []
  ){
    if (isset($params["where"]) && $params["where"]){
      $whereBlock = "WHERE {$params["where"]}";
    }
    else{
      $whereBlock = "";
    }
    if (isset($params["order"]) && $params["order"]){
      $orderBlock = "ORDER BY {$params["order"]}";
    }
    else {
      $orderBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `operations` {$whereBlock} {$orderBlock}");
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
   *  Обновляет поле id_order в таблице operations
   *  @param mixed $id  id строки
   *  @param mixed $orderId - новый номер заказа
   */
  public function setOrderId(
    $id,
    $orderId
  ){
    if(($orderId === null) || ($orderId === "")){
      $orderIdBlock = "NULL";
    }
    else{
      $orderIdBlock = "'".$this->mysqli->real_escape_string($orderId)."'";
    }
    $orderId = $this->mysqli->real_escape_string($orderId);
    $userProcessed = $this->mysqli->real_escape_string($_SESSION["id_user"]);
    $dateProcessed = date("Y-m-d H:i:s",time());
    $this->mysqli->query("UPDATE `operations` SET `id_order` = {$orderIdBlock}, `user_processed` = '{$userProcessed}', `date_processed` = '{$dateProcessed}' where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает одну запись из таблицы operations по id
   *  @param mixed $id - id строки
   *  @return array
   */
  public function getOne(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $req = $this->mysqli->query("SELECT * from `operations` where `id` = '{$id}'");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        return $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
