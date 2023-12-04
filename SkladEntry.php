<?php

namespace admin\components;

class SkladEntry extends BaseComponent{

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
   *  Добавляет складскую проводку в таблицу sklad_entry
   *  @param mixed $from_type - тип источника из таблицы sklad_source_type
   *  @param mixed $from_id - id строки из таблицы источника в зависимоти от from_type
   *  @param mixed $to_type - тип получателя из таблицы sklad_source_type
   *  @param mixed $to_id - id строки из таблицы получателя в зависимоти от from_type
   *  @param mixed $id_acc - id позиции из таблицы uslugi
   *  @param mixed $count - количество
    * @param mixed $comment - комментарий
   *  @param mixed $price - цена
   *  @param mixed $id_order - номер заказа для которого предназначен товар. Только для проводки типа склад --> монтажник
   */
  public function addEntry(
    $from_type,
    $from_id,
    $to_type,
    $to_id,
    $id_acc,
    $count,
    $comment = "",
    $price = "0",
    $id_order = "0"
  ){
    $user_created = $_SESSION["id_user"];
    $data = compact("from_type","from_id","to_type","to_id","id_acc","count","price","user_created","comment","status");
    $this->addOne("sklad_entry",$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет либо восстанавливает проводку в зависимости от delete. Если true то поле статус строки id таблицы sklad_entry устанавливается 1 иначе 0
   *  @param mixed $id - id строки
   *  @param mixed $delete - флаг удаления, если true значит удалить, если false значит восстановить
   */
  public function deleteEntry(
    $id,
    $delete = true
  ){
    $this->changeOne("sklad_entry",$id,[
      "status" => $delete ? "1" : "0"
    ]);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Создает передачу товара id_acc со склада sklad монтажнику id_user в количестве count
   *  @param mixed $id_user - id монтажника из таблицы user
   *  @param mixed $id_acc - id товара
   *  @param mixed $count - количество
   *  @param mixed $id_order - номер заказа для которого предназначен товар
   *  @param mixed $comment - комментарий
   *  @param mixed $sklad - id склада из таблицы sklad_type
   */
  public function skladToMont(
    $id_user,
    $id_acc,
    $count,
    $id_order = "0",
    $comment = "",
    $sklad = "1"
  ){
    $this->addEntry(
      "1",
      $sklad,
      "2",
      $id_user,
      $id_acc,
      $count,
      $comment,
      "0",
      $id_order
    );
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Создает поступление товара id_acc по итогам инвентаризации id_inventory на склад sklad  в количестве count
   *  @param mixed $id_inventory - id инвентаризации из таблицы inventory
   *  @param mixed $id_acc - id товара
   *  @param mixed $count - количество
   *  @param mixed $price - цена. Если не указано ставить 0
   *  @param mixed $comment - комментарий
   *  @param mixed $sklad - id склада из таблицы sklad_type
   */
  public function inventoryToSklad(
    $id_inventory,
    $id_acc,
    $count,
    $price = 0,
    $comment = "",
    $sklad = "1"
  ){
    $this->addEntry(
      "4",
      $id_inventory,
      "1",
      $sklad,
      $id_acc,
      $count,
      $comment,
      $price
    );
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список всех проводок из таблицы sklad_entry
   *  @param mixed $where - блок where запроса
   */
  public function getAllEntry(
    $where = ""
  ){
    $entryList = $this->allGet("sklad_entry",$where);
    $sourceList = $this->allGet("sklad_source_type");
    foreach($entryList as $key => $entry){
      $from_source = $sourceList[$entry["from_type"]];
      $to_source = $sourceList[$entry["to_type"]];
      $from = $this->oneById($from_source["table"],$entry["from_id"]);
      $to = $this->oneById($to_source["table"],$entry["to_id"]);
      $entryList[$key]["from_name"] = "{$from_source["desc"]} : {$from[$from_source["name_field"]]}";
      $entryList[$key]["to_name"] = "{$to_source["desc"]} : {$to[$to_source["name_field"]]}";
    }
    return $entryList;
  }

  /*-----------------------------------------------------------------------*/


}