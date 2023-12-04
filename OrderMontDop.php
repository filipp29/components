<?php

namespace admin\components;

class OrderMontDop extends BaseComponent{

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
   *  Возвращает все строки из таблицы order_mont_dop
   *  @param mixed $where - блок where запроса
   *  @return  array
   */
  public function getAll(
    $where = null
  ){
    return $this->allGet("order_mont_dop",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет строку в таблицу order_mont_dop
   *  @param mixed $id_order - номер заказа
   *  @param mixed $id_user - id пользователя
   *  @param mixed $viezd_count - количество выездов
   *  @param mixed $percent - процент от зп
   */
  public function addMontDop(
    $id_order,
    $id_user,
    $viezd_count,
    $percent
  ){
    $data = compact("id_order","id_user","viezd_count","percent");
    $data["user_created"] = $_SESSION["id_user"] ?? "0";
    $id_order = $this->mysqli->real_escape_string($id_order);
    $id_user = $this->mysqli->real_escape_string($id_user);
    $where = "`id_user` = '{$id_user}' and `id_order` = '{$id_order}'";
    $row = $this->oneGet("order_mont_dop",$where);
    if (isset($row["id"])){
      return false;
    }
    else{
      $this->addOne("order_mont_dop",$data);
    }
    return true;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет строку id в таблице order_mont_dop
   *  @param mixed $id - id строки
   *  @param mixed $id_order - номер заказа
   *  @param mixed $id_iser - id пользователя
   *  @param mixed $viezd_count - количество выездов
   *  @param mixed $percent - процент от зп
   */
  public function changeMontDop(
    $id,
    $id_order,
    $id_user,
    $viezd_count,
    $percent
  ){
    $data = compact("id_order","id_user","viezd_count","percent");
    $data["user_created"] = $_SESSION["id_user"] ?? "0";
    $id_order = $this->mysqli->real_escape_string($id_order);
    $id_user = $this->mysqli->real_escape_string($id_user);
    $where = "`id_user` = '{$id_user}' and `id_order` = '{$id_order}'";
    $row = $this->oneGet("order_mont_dop",$where);
    if (isset($row["id"])){
      $this->changeOne("order_mont_dop",$row["id"],$data);
    }
    else{
      return false;
    }
    return true;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет строку id из таблицы order_mont_dop
   *  @param mixed $id - id строки
   */
  public function deleteMontDop(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $this->mysqli->query("DELETE FROM `order_mont_dop` where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает количество выездов всех дополнительных монтажников заказа id_order
   *  @param mixed $id_order - номер заказа
   */
  public function getOrderViezdCount(
    $id_order
  ){
    $id_order = $this->mysqli->real_escape_string($id_order);
    $montList = $this->getAll("`id_order` = '{$id_order}'");
    $result = 0;
    foreach($montList as $row){
      $result += (int)$row["viezd_count"];
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  

}
