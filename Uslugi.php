<?php

namespace admin\components;

class Uslugi extends BaseComponent{

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
    return $this->allGet("uslugi",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список товаров и таблицы uslugi сгруппированный по type_window и type_part_window в виде массива и отсортированный по полю sort
   *  [
   *    type_window => [
  *       type_part_window => [
  *         uslugi
  *       ]
   *  ]
   *  @param mixed $where - блок where для запроса к таблице uslugi
   */
  public function getUslugiFullList(
    $where = ""
  ){
    $list = $this->allGet("uslugi",$where);
    $result = [];
    foreach($list as $row){
      if ($row["id_us"] != $row["id_us_spisanie"]){
        continue;
      }
      $id_type_window = $row["id_type_window"];
      $id_type_part_window = $row["id_type_part_window"];
      $result[$id_type_window] = $result[$id_type_window] ?? [];
      $result[$id_type_window][$id_type_part_window] = $result[$id_type_window][$id_type_part_window] ?? [];
      $result[$id_type_window][$id_type_part_window][$row["id_us"]] = $row;
    }
    $typeList = $this->allGet("type_window");
    $typeSort = function($a,$b) use ($typeList){
      $a_v = isset($typeList[$a]["sort"]) ? (int)$typeList[$a]["sort"] : 0;
      $b_v = isset($typeList[$b]["sort"]) ? (int)$typeList[$b]["sort"] : 0;
      return $a_v - $b_v;
    };
    $typePartList = $this->allGet("type_part_window");
    $typePartSort = function($a,$b) use ($typePartList){
      $a_v = isset($typePartList[$a]["sort"]) ? (int)$typePartList[$a]["sort"] : 0;
      $b_v = isset($typePartList[$b]["sort"]) ? (int)$typePartList[$b]["sort"] : 0;
      return $a_v - $b_v;
    };
    foreach($result as $key => $row){
      uksort($result[$key],$typePartSort);
    }
    uksort($result,$typeSort);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список для option для тега select в виде массива [[type, name, value]] где type это label|option 
   *  @param mixed $where - блок where запроса к uslugi
   */
  public function getSelectUslugiList(
    $where = ""
  ){
    $uslugiFullList = $this->getUslugiFullList($where);
    $typeList = $this->allGet("type_window");
    $typePartList = $this->allGet("type_part_window");
    $result = [];
    foreach($uslugiFullList as $typeWindowId => $typePartWindowList){
      $result[] = [
        "type" => "label",
        "name" => $typeList[$typeWindowId]["name"],
        "value" => "",
        "sort" => $typeList[$typeWindowId]["sort"]
      ];
      foreach($typePartWindowList as $typePartWindowId => $uslugiList){
        if (!isset($typePartList[$typePartWindowId]["name"])){
          continue;
        }
        $result[] = [
          "type" => "label",
          "name" => $typePartList[$typePartWindowId]["name"],
          "value" => "",
          "sort" => $typePartList[$typePartWindowId]["sort"]
        ];
        foreach($uslugiList as $uslugi){
          $result[] = [
            "type" => "option",
            "name" => $uslugi["name"],
            "value" => $uslugi["id_us"],
            "sort" => $uslugi["sort"]
          ];
        }
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает поле marketolog_check товара id_us в таблице uslugi
   *  @param mixed $id_us - id товара
   *  @param mixed $check - значение поля true|false
   */
  public function setMarketologCheck(
    $id_us,
    $check = true
  ){
    $this->changeOne("uslugi",$id_us,[
      "marketolog_check" => $check ? "1" : "0"
    ]);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет строку id_us из таблицы uslugi
   *  @param mixed $id_us - id услуги
   *  @param mixed $data - массив с данными для изменения
   */
  public function change(
    $id_us,
    $data
  ){
    $this->changeOne("uslugi",$id_us,$data);
  }

  /*-----------------------------------------------------------------------*/

}
