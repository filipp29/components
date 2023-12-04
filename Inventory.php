<?php

namespace admin\components;

class Inventory extends BaseComponent{

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
   *  Возвращает строку из таблицы inventory по id_inventory
   */
  public function getOne(
    $id_inventory
  ){
    $id_inventory = $this->mysqli->real_escape_string($id_inventory);
    return $this->oneGet("inventory","`id_inventory` = '{$id_inventory}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список инвентаризаций где фигурировала химия 
   *  @param mixed $sklad_type - поле type из таблицы sklad, если оставить пустым возвращаются весь список
   */ 
  public function getChemicalList(
    $sklad_type = null
  ){
    $chemicalList = [17,18,19,20,21];
    $whereList = [];
    foreach($chemicalList as $id){
      $whereList[] = "`t1`.id_type_part_window = '{$id}'";
    }
    $whereBlock = "(". implode(" or ",$whereList). ")";
    if ($sklad_type){
      $sklad_type = $this->mysqli->real_escape_string($sklad_type);
      $whereBlock .= " and `t1`.type = '{$sklad_type}'";
    }
    $req = $this->mysqli->query("
    select distinct `t2`.* from (SELECT `inv_acc`.*,`acc`.id_type_part_window, `inv`.`type` as `type` 
    FROM `inventory_acc` as `inv_acc` 
    left join `uslugi` as `acc`
    on `inv_acc`.id_acc = `acc`.id_us
    left join `inventory` as `inv`
    on `inv_acc`.id_inventory = `inv`.`id_inventory`) as `t1`
    left join `inventory` as `t2`
    on `t1`.id_inventory = `t2`.id_inventory
    where   {$whereBlock}
    ");
    $result = [];
    while($row = $req->fetch_assoc()){
      $type = $row["type"];
      if (!isset($result[$type])){
        $result[$type] = [];
      }
      $result[$type][$row["id_inventory"]] = $row;
    }
    if ($sklad_type && isset($result[$sklad_type])){
      return $result[$sklad_type];
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
