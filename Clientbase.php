<?php

namespace admin\components;

class Clientbase extends BaseComponent{

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из client_base по id_order
   *  @param mixed $orderId - номер заказа КБ
   *  @return array
   */
  public function getOneByOrderId(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $buf = $this->mysqli->query("SELECT * from `clientbase` WHERE `id_order` = '{$orderId}'");
    if (!$buf){
      return [];
    }
    while($row = $buf->fetch_assoc()){
      return $row;
    }
    return [];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из client_base по параметру line
   *  @param mixed $line - параметр line заказа КБ
   *  @return array
   */
  public function getOneByLine(
    $line
  ){
    $line = $this->mysqli->real_escape_string($line);
    $req = $this->mysqli->query("SELECT * from `clientbase` where `line` = '{$line}'");
    if (!$req){
      return [];
    }
    while($row = $req->fetch_assoc()){
      return $row;
    }
    return [];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы clientbase
   *  @param mixed $where - блок where запроса
   *  @param mixed $fieldList - список полей для запроса в виде массива
   *  @return  array
   */
  public function getAll(
    $where = null,
    $fieldList = []
  ){
    if ($where){
      $whereBlock = " where {$where} ";
    }
    else{
      $whereBlock = "";
    }
    $fieldBlock = "";
    if ($fieldList){
      foreach($fieldList as $key => $value){
        $fieldList[$key] = "`". $this->mysqli->real_escape_string($value). "`";
      }
      $fieldBlock = implode(" , ",$fieldList);
    }
    else{
      $fieldBlock = "*";
    }
    $req = $this->mysqli->query("SELECT {$fieldBlock} from `clientbase` {$whereBlock}");
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
   *  Возвращает id_user из таблицы user связанный с main_user из таблицы clientbase с orderId
   *  @param mixed $orderId - id заказа
   */
  public function getMainUser(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `clientbase` where `id_order` = '{$orderId}'");
    $mainUser = "0";
    if ($req){
      $row = $req->fetch_assoc();
      $mainUser = $row["main_user"];
    } 
    $req = $this->mysqli->query("SELECT * from `user` where `id_user_clientbase` = '{$mainUser}'");
    if ($req){
      $row = $req->fetch_assoc();
      return $row["id_user"];
    }
    return null;   
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает id_user из таблицы user связанный с mont из таблицы clientbase с orderId
   *  @param mixed $orderId - id заказа
   */
  public function getMontUser(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `clientbase` where `id_order` = '{$orderId}'");
    $mainUser = "0";
    if ($req){
      $row = $req->fetch_assoc();
      $mont = $row["mont"];
    } 
    $req = $this->mysqli->query("SELECT * from `user` WHERE (`priority`='1' or `priority`='6') and `name` LIKE '%{$mont}%' ");
    if ($req){
      $row = $req->fetch_assoc();
      return $row["id_user"];
    }
    return null;   
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Взвращает ссылку на заказ в КБ по orderId
   *  @param mixed $orderId - id заказа
   */
  public function getCbLink(
    $orderId,
    $http = true
  ){
    $orderData = $this->getOneByOrderId($orderId);
    if ($http){
      $prefix = "https:";
    }
    else{
      $prefix = "";
    }
    if (isset($orderData["line"]) && $orderData["line"]){
      return $prefix. "//oknapomoshch.clientbase.ru/view_line2.php?table=311&page=1&line={$orderData["line"]}";
    }
    else{
      return "";
    }
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

  /**
   *  Проверяет существование заказа id_order в таблице clientbase и возвращает true|false
   *  @param mixed $id_order - номер заказа
   */
  public function issetOrder(
    $id_order
  ){
    $id_order = $this->mysqli->real_escape_string($id_order);
    $req = $this->mysqli->query("SELECT * from `clientbase` where `id_order` = '{$id_order}'");
    if ($req && ($req->num_rows > 0)){
      return true;
    }
    else{
      return false;
    }
  } 

  /*-----------------------------------------------------------------------*/

}
