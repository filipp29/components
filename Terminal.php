<?php

namespace admin\components;

class Terminal extends BaseComponent{

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
   *  Возвращает список терминалов из таблицы terminal
   *  @return array
   */
  public function getTerminalList(){
    $req = $this->mysqli->query("SELECT * from `terminal`");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет в таблице terminal строки с id_user = userId на id_user = -1
   *  @param mixed $userId - id пользователя
   */
  public function removeTerminalByUser(
    $userId
  ){  
    $userId = $this->mysqli->real_escape_string($userId);
    $this->mysqli->query("UPDATE terminal SET `id_user` = '-1' where `id_user` = '{$userId}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из таблиы terminal по userId
   *  @param mixed $userId - id пользователя
   */
  public function getTerminalByUser(
    $userId
  ){
    $userId = $this->mysqli->real_escape_string($userId);
    $req = $this->mysqli->query("SELECT * from `terminal`");
    $result = [];
    if ($req){
      $row = $req->fetch_assoc();
      return $row;
    }
    return [];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет в таблицу terminal новую запись
   *  @param mixed $serial - Серийный номер терминала
   *  @param mixed $userId = id пользователя терминала, если '0' то терминал офисный
   */
  public function addTerminal(
    $serial,
    $userId
  ){
    $serial = $this->mysqli->real_escape_string($serial);
    $userId = $this->mysqli->real_escape_string($userId);
    $this->mysqli->query("INSERT INTO `terminal` (`serial`,`id_user`) VALUES ('{$serial}','{$userId}')");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Обновляет запись в таблице terminal
   *  @param mixed $id - id строки,
   *  @param mixed $serial - серийный номер терминала,
   *  @param mixed $userId - id пользователя терминала, если '0' то терминал офисный
   */
  public function updateTerminal(
    $id,
    $serial = null,
    $userId = null
  ){
    $setList = [];
    if ($serial){
      $serial = $this->mysqli->real_escape_string($serial);
      $setList[] = "`serial` = '{$serial}'"; 
    }
    if ($userId){
      $userId = $this->mysqli->real_escape_string($userId);
      $setList[] = "`id_user` = '{$userId}'"; 
    }
    if ($setList){
      $setBlock = implode(",",$setList);
      $id = $this->mysqli->real_escape_string($id);
      $this->mysqli->query("UPDATE `terminal` SET {$setBlock} where `id` = '{$id}'");
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет запись из таблицы terminal
   *  @param mixed $id - id строки
   */
  public function deleteTerminal(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $this->mysqli->query("DELETE FROM `terminal` where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

}
