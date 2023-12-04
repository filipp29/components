<?php

namespace admin\components;

class ExceptionOrder extends BaseComponent{

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
   *  Возвращает все строки из таблицы exception_order с заказами-исключениями вида [id => row]
   *  @param mixed $table - наименование таблицы (order | order_meneger)
   *  @param mixed $orderAi - ai заказа
   *  @param mixed $type - тип исключения
   *  @return array
   */
  public function getExceptionOrderList(
    $table = null,
    $orderAi = null,
    $type = null
  ){
    $whereList = [];
    // если $type не null, то выбирать строки в которых поле type = $type
    if ($type !== null){
      $type = $this->mysqli->real_escape_string($type);
      $whereBlock[] = "`type` = '{$type}'";
    }
    if ($table !== null){
      $table = $this->mysqli->real_escape_string($table);
      $whereBlock[] = "`table` = '{$table}'";
    }
    // если $orderId не null, то выбирать строки в которых поле id_order = $orderId
    if ($orderAi !== null){
      $orderAi = $this->mysqli->real_escape_string($orderAi);
      $whereList[] = "`ai_order` = '{$orderAi}'";
    }
    if (count($whereList) > 0){
      $whereBlock = " WHERE ". implode(" AND ",$whereList);
    }
    $buf = $this->mysqli->query("SELECT * from `exception_order` {$whereBlock}");
    $result = [];
    while($row = $buf->fetch_assoc()){
      $result[$row["id"]] = $row;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет заказ orderAi из таблицы table в исключения с типом type и причиной reason
   *  @param int|string $table - наименование таблицы (order | order_meneger)
   *  @param int|string $orderAi - ai заказа
   *  @param int $type - тип исключения. По умолчанию 0 - большое расстояние КАД
   *  @param string $reason - причина исключения
   */
  public function addOrderToExceptionList(
    $table,
    $orderAi,
    $type = 0,
    $reason = ""
  ){
    $orderAi = $this->mysqli->real_escape_string($orderAi);
    $type = $this->mysqli->real_escape_string($type);
    $table = $this->mysqli->real_escape_string($table);
    $reason = $this->mysqli->real_escape_string($reason);
    $buf = $this->mysqli->query("SELECT * from `exception_order` WHERE `ai_order` = '{$orderAi}' AND `type` = '{$type}' AND `table` = '{$table}'");
    if ($buf->num_rows > 0){
      $this->mysqli->query("UPDATE `exception_order` SET `reason` = '{$reason}' WHERE `ai_order` = '{$orderAi}' AND `type` = '{$type}' AND `table` = '{$table}'");
    }
    else{
      $this->mysqli->query("INSERT INTO `exception_order` (`table`,`ai_order`,`type`,`reason`) VALUES ('{$table}','{$orderAi}','{$type}','{$reason}')");
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет заказ orderAi и table из исключений с типом type
   *  @param mixed $table - наименование таблицы
   *  @param int|string $orderAi - ai заказа
   *  @param int $type - тип исключения. По умолчанию 0 - большое расстояние КАД
   */
  public function removeOrderFromExceptionList(
    $table,
    $orderAi,
    $type = 0
  ){
    $orderAi = $this->mysqli->real_escape_string($orderAi);
    $type = $this->mysqli->real_escape_string($type);
    $table = $this->mysqli->real_escape_string($table);
    $this->mysqli->query("DELETE from `exception_order` where `ai_order` = '{$orderAi}' AND `type` = '{$type}' AND `table` = '{$table}'");
  }

  /*-----------------------------------------------------------------------*/

}
