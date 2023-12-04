<?php

namespace admin\components;

class OrderIncome extends BaseComponent{

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
   *  Возвращает сумму приходов из таблицы order_income за определенную дату $date в виде [id_user => [incomeType1 => sum1, incomeType2 => sum2]]. Если указан параметр userId то возвращает данные только для пользователя userId
   *  @param mixed $date - дата 
   *  @param mixed $userId - id пользователя
   *  @return array
   */
  public function getUserIncomeList(
    $date,
    $userId = null
  ){
    if ($date == null){
      $date = date("Y-m-d");
    }
    if ($userId !== null){
      $userId = $this->mysqli->real_escape_string($userId);
      $whereUser = " and `id_user` = '{$userId}' ";
    }
    else{
      $whereUser = "";
    }
    $date = $this->mysqli->real_escape_string($date);
    $req = $this->mysqli->query("SELECT `id_user`,`order_income_type_id`,SUM(`amount`) as `amount` from `order_income` where DATE(`date_created`) = DATE('{$date}') {$whereUser} GROUP BY `id_user`,`order_income_type_id`");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        if (!isset($result[$row["id_user"]])){
          $result[$row["id_user"]] = [];
        }
        $result[$row["id_user"]][$row["order_income_type_id"]] = $row["amount"];
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список поступлений из таблицы order_income по orderId, если есть параметр $date то отображать поступления только за эту дату
   *  @param mixed $orderId - id заказа
   *  @param mixed $date - дата (не обязательно)
   *  @return array
   */
  public function getOrderIncome(
    $orderId,
    $date = null
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    if ($date){
      $date = $this->mysqli->real_escape_string($date);
      $whereBlock = "and DATE(`date_created`) = DATE('{$date}')";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `order_income` where `id_order` = '{$orderId}' {$whereBlock}");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает сумму всех поступлений из таблицы order_income по заказу $orderId
   *  @param mixed $orderId - id заказа
   *  @return int|string
   */
  public function getOrderIncomeSum(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT 'id_order', SUM(`amount`) as `amount` from `order_income` where `id_order` = '{$orderId}' ORDER BY `id_order`");
    $result = 0;
    if($req){
      while($row = $req->fetch_assoc()){
        $result += (int)$row["amount"];
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}