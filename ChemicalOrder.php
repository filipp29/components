<?php

namespace admin\components;

class ChemicalOrder extends BaseComponent{

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
   *  Добавляет в таблицу chemical_order новый заказ id_order если в таблице еще нет строки с id_order
   *  @param mixed $id_order - id заказа
   */
  public function addNewOrder(
    $id_order
  ){
    $id_order = $this->mysqli->real_escape_string($id_order);
    $row = $this->oneGet("chemical_order","`id_order` = '{$id_order}'");
    if (!$row){
      $this->addOne("chemical_order",[
        "id_order" => $id_order,
        "user_created" => $_SESSION["id_user"],
        "created_at" => date("Y-m-d H:i:s")
      ]);
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет договор из таблицы chemical_order
   *  @param mixed $id_order - номер договора
   */
  public function deleteOrder(
    $id_order
  ){
    $id_order = $this->mysqli->real_escape_string($id_order);
    $this->mysqli->query("DELETE from `chemical_order` where `id_order` = '{$id_order}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы chemical_order
   *  @param mixed $where - блок where
   */
  public function getAll(
    $where = ""
  ){
    return $this->allGet("chemical_order",$where);
  }

  /*-----------------------------------------------------------------------*/  


}