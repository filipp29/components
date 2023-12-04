<?php

namespace admin\components;

class Postavshiki extends BaseComponent{

  private $whereList = [];

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /*-----------------------------------------------------------------------*/

  public function getAll(
    $where = ""
  ){
    return $this->allGet("postavshiki",$where);
  }

  /*-----------------------------------------------------------------------*/

  public function getOne(
    $where = ""
  ){
    return $this->oneGet("postavshiki",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Проверяет есть ли в строке address название любого из поставщиков
   */
  public function checkAddress(
    $address
  ){
    $postavshikiList = $this->getAll();
    $address = mb_strtolower($address);
    $pattern = "/";
    $buf = [];
    foreach($postavshikiList as $postavshik){
      $buf[] = "({$postavshik["name"]})";
    }
    $pattern .= implode("|",$buf);
    $pattern .= "/";
    $pattern = mb_strtolower($pattern);
    return preg_match($pattern,$address);
  }

  /*-----------------------------------------------------------------------*/

}
