<?php

namespace admin\components;
use yii\db\Query;

class ProductIncome extends BaseComponent{

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
   *  Возвращает все строки из таблицы product_income
   *  @param mixed $where - условия выборки
   */
  public function getAll(
    $where = null
  ){
    if ($where){
      $whereBlock = "where {$where} ";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `product_income` {$whereBlock}");
    $result = [];
    if ($req){
      while ($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет строку в таблицу product_income
   *  @param mixed $postavshik_number - номер поставщика
   *  @param mixed $postavshik_name - наименование поставщика
   *  @param mixed $count - количество
   *  @param mixed $id_order - номер заказа
   *  @param mixed $warehouse - заказ на складе
   */
  public function add(
    $postavshik_number,
    $postavshik_name,
    $count,
    $id_order,
    $warehouse
  ){
    $warehouse = $warehouse ? "1" : "0";
    $data = compact([
      "postavshik_name",
      "postavshik_number",
      "count",
      "id_order",
      "warehouse"
    ]);
    $data["created_at"] = date("Y-m-d H:i:s");
    $data["user_created"] = isset($_SESSION["id_user"]) ? $_SESSION["id_user"] : "0";
    return $this->addOne("product_income",$data);
  }

  /*-----------------------------------------------------------------------*/

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет строку id в таблице product_income
   *  @param mixed $id - id строки
   *  @param mixed $postavshik_number - номер поставщика
   *  @param mixed $postavshik_name - наименование поставщика
   *  @param mixed $count - количество
   *  @param mixed $id_order - номер заказа
   *  @param mixed $warehouse - заказ на складе
   */
  public function change(
    $id,
    $postavshik_number,
    $postavshik_name,
    $count,
    $id_order,
    $warehouse
  ){
    $warehouse = $warehouse ? "1" : "0";
    $data = compact([
      "postavshik_name",
      "postavshik_number",
      "count",
      "id_order",
      "warehouse"
    ]);
    $id = $this->mysqli->real_escape_string($id);
    $req = $this->mysqli->query("SELECT * from `product_income` where `id` = '{$id}'");
    $row = $req->fetch_assoc();
    $changed = false;
    foreach($data as $key => $value){
      if ($row[$key] != $value){
        $changed = true;
      }
    }
    if ($changed){
      $this->setReaded($id,false);
    }
    $setList = [];
    foreach($data as $key => $value){
      $value = $this->mysqli->real_escape_string($value);
      $setList[] = "`{$key}` = '{$value}'";
    }
    $setBlock = implode(",",$setList);
    $this->mysqli->query("UPDATE `product_income` SET {$setBlock} where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет строку из таблицы product_income
   *  @param mixed $id - id строки
   */
  public function delete(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $this->mysqli->query("DELETE from `product_income` where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает свойство readed в строке id таблицы product_income
   *  @param mixed $id - id строки
   *  @param mixed $readed - Свойство readed (true | false)
   */
  public function setReaded(
    $id,
    $readed
  ){
    $data = [
      "readed" => $readed ? "1" : "0"
    ];
    $this->changeOne("product_income",$id,$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает значение поля checked строки id в таблице product_income
   *  @param mixed $id - id строки
   *  @param mixed $checked - значение поля checked
   */
  public function setChecked(
    $id,
    $checked = true
  ){
    $this->changeOne("product_income",$id,[
      "checked" => $checked ? "1" : "0"
    ]);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает количество непрочитанных строк из таблицы product_income для userId
   */
  public function unreadedCount(
    $userId
  ){
    $userId = $this->mysqli->real_escape_string($userId);
    $list = $this->getAll(" `user_created` = '{$userId}' and `readed` = '0' ");
    return count($list);
  }

  /*-----------------------------------------------------------------------*/

}
