<?php

namespace admin\components;

class OrderMontZp extends BaseComponent{

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
   *  Возвращает все строки из таблицы order_mont_zp
   *  @param mixed $where - блок where запроса
   *  @return  array
   */
  public function getAll(
    $where = null
  ){
    return $this->allGet("order_mont_zp",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы order_mont_zp сгруппированные по order в виде [id_order => [ai => row]]
   *  @param mixed $where - блок where запроса
   */
  public function getAllByOrder(
    $where = null
  ){
    $list = $this->getAll($where);
    $result = [];
    foreach($list as $row){
      $orderId = $row["id_order"];
      if (!isset($result[$orderId])){
        $result[$orderId] = [];
      }
      $result[$orderId][$row["ai"]] = $row;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет запись в таблицу order_mont_zp для пользователя id_user о посещении точки address. Возвращает id строки
   *  @param mixed $id_user - id пользователя,
   *  @param mixed $date - дата
   *  @param mixed $address - адрес
   *  @param mixed $comment - комментарий к записи
   */
  public function addPointZp(
    $id_user,
    $date,
    $address,
    $comment = ""
  ){
    $systemPrice = new SystemPrice($this->mysqli);
    $user = User::slf()->getOneById($id_user);
    $company_car = $user["company_car"] ?? false;
    $price = $company_car ? "365" : $systemPrice->getPriceByName("del_zp")["price"];
    $comment = "ЗП за посещение {$address}. {$comment}";
    $data = [
      "id_user" => $id_user,
      "date" => date("Y-m-d",strtotime($date)),
      "comment" => $comment,
      "id_user_created" => $_SESSION["id_user"],
      "price" => $price
    ];
    $result = $this->addOne("order_mont_zp",$data);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет запись ai из таблицы order_mont_zp
   */
  public function delete(
    $ai
  ){
    $ai = $this->mysqli->real_escape_string($ai);
    $this->mysqli->query("DELETE from `order_mont_zp` where `ai` = '{$ai}'");
  }

  /*-----------------------------------------------------------------------*/


  /**
   *  Добавляет строку в таблицу order_mont_zp
   *  @param mixed $data - данные для вставки
   */
  public function create(
    $data
  ){
    return $this->addOne("order_mont_zp",$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет строку id в таблице order_mont_zp
   *  @param mixed $id - id строки
   *  @param mixed $data - данные для изменения
   */
  public function change(
    $id,
    $data
  ){
    $this->changeOne("order_mont_zp",$id,$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Создает стрки в таблице order_mont_zp для каждого мастера из mont_list
   *  @param mixed $id_order - номер заказа
   *  @param mixed $zp_list - список ЗП в виде массива [[price => сумма, comment => комментарий к зп]]
   *  @param mixed $mont_list - список мастеров в виде массива [id_user => [price => сумма, comment => комментарий]]
   */
  public function addMontListZp(
    $id_order,
    $mont_list
  ){
    $orderComponent = new Order($this->mysqli);
    $ai_order = $orderComponent->getOneByOrderId($id_order)["ai"] ?? "0";
    $mont_zp_list = $this->getAll("`ai_order` = '{$ai_order}' and `comment` not like '%Заказ передан другому мастеру%'");
    $current_list = [];
    foreach($mont_zp_list as $row){
      if (key_exists($row["id_user"],$mont_list) && ($mont_list[$row["id_user"]]["price"] > 0)){
        $current_list[$row["id_user"]] = $row;
      }
      else if(!$row["date_payment"]){
        $this->delete($row["ai"]);
      }
    }
    
    foreach($mont_list as $id_user => $row){
      if ($row["price"] == 0){
        continue;
      }
      $data = [
        "id_order" => $id_order,
        "ai_order" => $ai_order,
        "id_user" => $id_user,
        "price" => $row["price"],
        "comment" => $row["comment"]
      ];
      if (!key_exists($id_user,$current_list)){
        $this->create($data);
      }
      else if(!$current_list[$id_user]["date_payment"]){
        $this->change($current_list[$id_user]["ai"],$data);
      }
    }

  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список мастеров с распределенной между ними зп за заказ id_order в виде массива [id_user => [price => сумма, comment => комментарий]]
   *  @param mixed $id_order - номер заказа
   *  @param mixed $zp_list - список ЗП в виде массива [[price => сумма, comment => комментарий к зп]]
   *  @param mixed $mont_dop_list - список дополнительных мастеров в виде массива [id_user => row]
   */
  public function makeMontListZp(
    $id_order,
    $zp_list,
    $mont_dop_list = []
  ){
    $orderComponent = new Order($this->mysqli);
    $userComponent = new User($this->mysqli);
    $order_user_id = $orderComponent->getOneByOrderId($id_order)["id_user"] ?? "0";
    $order_user = $userComponent->getOneById($order_user_id);
    if ($order_user_id && !in_array($order_user["priority"],[1,6])){
      $order_user_id = 0;
    }


    $result = [];
    $percent_all = 0;
    foreach($mont_dop_list as $row){
      $percent_all += floor($row["percent"]);
      $result[$row["id_user"]] = $result[$row["id_user"]] ?? [
        "price" => 0,
        "comment" => "",
        "percent" => $row["percent"]
      ];
    }
    foreach($zp_list as $zp){
      $zp["percent_all"] = 0;
      foreach($mont_dop_list as $row){
        $id_user = $row["id_user"];
        $percent = $row["percent"];
        
        $user_price = floor($zp["price"] * $percent / 100);
        $result[$id_user]["price"] += $user_price;
        $result[$id_user]["comment"] .= "{$user_price} - {$zp["comment"]}";
      }
    }


    $percent = 100 - $percent_all;
    $result[$order_user_id] = [
      "price" => 0,
      "comment" => "",
      "percent" => $percent
    ];
    foreach($zp_list as $zp){
        $user_price = floor($zp["price"] * $percent / 100);
        $result[$order_user_id]["price"] += $user_price;
        $result[$order_user_id]["comment"] .= "{$user_price} - {$zp["comment"]}";
    }
    

    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет начисление за выезд на замер
   *  @param mixed $id_order - номер заказа
   *  @param mixed $zp_list - список ЗП в виде [[price,comment]]
   */
  public function addZamerZp(
    $id_order,
    $zp_list
  ){
    $orderComponent = new Order($this->mysqli);
    $userComponent = new User($this->mysqli);
    $order_user_id = $orderComponent->getOneByOrderId($id_order)["id_user"] ?? "0";
    $order_user = $userComponent->getOneById($order_user_id);
    if ($order_user_id && !in_array($order_user["priority"],[1,6])){
      return;
    }
    $price = 0;
    $comment = "";
    foreach($zp_list as $row){
        $price += (int)$row["price"];
        $comment .= "{$row["price"]} - {$row["comment"]}";
    }
    $comment = "Выплата за выезд на замер<br>{$comment}";

    $data = [
      "id_user" => $order_user_id,
      "date" => date("d.m.Y H:i",time()),
      "id_order" => $id_order,
      "comment" => $comment,
      "id_user_created" => "0",
      "price" => $price
    ];
    $this->create($data);

  }

  /*-----------------------------------------------------------------------*/

}
