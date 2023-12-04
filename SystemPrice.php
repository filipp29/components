<?php

namespace admin\components;

class SystemPrice extends BaseComponent{

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
   *  Возвращает все строки из таблицы system_category
   *  @return array 
   */
  public function getCategoryList(){
    $buf = $this->mysqli->query("SELECT * from `system_category`");
    $result = [];
    while($row = $buf->fetch_assoc()){
      $result[$row["id"]] = $row;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из таблицы system_price по $id
   *  @param mixed $id - id строки system_price
   *  @return array
   */
  public function getPriceById(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $buf = $this->mysqli->query(("SELECT * from `system_price` where `ai` = '{$id}'"));
    $row = $buf->fetch_assoc();
    return $row;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из таблицы system_price по $name
   *  @param mixed $name - name строки system_price
   *  @return array
   */
  public function getPriceByName(
    $name
  ){
    $name = $this->mysqli->real_escape_string($name);
    $buf = $this->mysqli->query(("SELECT * from `system_price` where `name` = '{$name}'"));
    $row = $buf->fetch_assoc();
    return $row;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список категорий из system_category связанных с ценой $priceId через таблицу system_price_category в виде массива [id => category_id]
   *  @param mixed $priceId - id строки system_price
   *  @return mixed array
   */
  public function getPriceCategoryList(
    $priceId
  ){
    $priceId = $this->mysqli->real_escape_string($priceId);
    $req = $this->mysqli->query("SELECT * from `system_price_category` where `price_id` = '{$priceId}'");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row["category_id"];
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Обновляет список категорий в system_category связанных с ценой $priceId через таблицу system_price_category 
   *  @param mixed $priceId - id цены из system_price
   *  @param mixed $$categoryList - список катенорий для $priceId в виде массива [categoryId => 0|1]
   */
  public function updatePriceCategory(
    $priceId,
    $categoryList
  ){
    $priceId = $this->mysqli->real_escape_string($priceId);
    $priceCategoryList = $this->getPriceCategoryList($priceId);
    foreach($categoryList as $categoryId => $value){
      $categoryId = $this->mysqli->real_escape_string($categoryId);
      if ($value && !in_array($categoryId,$priceCategoryList)){
        $this->mysqli->query("INSERT INTO `system_price_category` (`price_id`,`category_id`) VALUES ('{$priceId}','{$categoryId}')");
      }
      if (!$value && in_array($categoryId,$priceCategoryList)){
        $this->mysqli->query("DELETE from `system_price_category` where `price_id` = '{$priceId}' and `category_id` = '{$categoryId}'");
      }
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список цен из system_price связанных с категорией $categoryId через system_price_category
   *  @param mixed $categoryId - id категории
   *  @return array
   */
  public function getCategoryPriceList(
    $categoryId
  ){
    $categoryId = $this->mysqli->real_escape_string($categoryId);
    
    $req = $this->mysqli->query("SELECT `sp`.* from `system_price` as `sp` INNER JOIN `system_price_category` as `spc` on `sp`.`ai` = `spc`.`price_id` where `spc`.`category_id` = '{$categoryId}'");
    $result = [];
    if ($req){
      while ($row = $req->fetch_assoc()){
        $result[$row["ai"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Обновляет price в таблице system_price
   *  @param mixed $id - id строки
   *  @param mixed $price - цена
   */
  public function updatePrice(
    $id,
    $price
  ){
    $id = $this->mysqli->real_escape_string($id);
    $price = $this->mysqli->real_escape_string($price);
    $this->mysqli->query("UPDATE `system_price` SET `price` = '{$price}' WHERE `ai` = '{$id}'");
    
  }

  /*-----------------------------------------------------------------------*/

}