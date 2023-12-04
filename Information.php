<?php

namespace admin\components;

class Information extends BaseComponent{

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
   *  Вставляет в таблицу information блок со значениями title и text
   *  @param string $title - заголовок блока
   *  @param string $text - текст блока
   */
  public function addInfoBlock(
    $title,
    $text
  ){
    $title = $this->mysqli->real_escape_string($title);
    $text = $this->mysqli->real_escape_string($text);
    $id_user = $_SESSION["id_user"];
    return $this->mysqli->query("INSERT INTO `information` (`title`,`text`,`id_user`) VALUES ('{$title}','{$text}','{$id_user}')");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет в таблице information блок id значениями title и text
   *  @param mixed $id - id блока
   *  @param string $title - заголовок блока
   *  @param string $text - текст блока
   */
  public function updateInfoBlock(
    $id,
    $title,
    $text
  ){
    $title = $this->mysqli->real_escape_string($title);
    $text = $this->mysqli->real_escape_string($text);
    $id_user = $_SESSION["id_user"];
    return $this->mysqli->query("UPDATE `information` SET `text` = '{$text}', `title` = '{$title}', `id_user` = '{$id_user}' WHERE `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Взовращает строку из таблицы information по id
   *  @param mixed $id - id строки
   *  @return array
   */
  public function getBlockById(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $buf = $this->mysqli->query("SELECT * FROM `information` WHERE `id` = '{$id}'");
    if ($buf->num_rows > 0){
      $row = $buf->fetch_assoc();
      return $row;
    }
    else{
      return [];
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы information
   *  @return array
   */
  public function getAllBlocks(){
    $buf = $this->mysqli->query("SELECT * FROM `information`");
    $result = [];
    while($row = $buf->fetch_assoc()){
      $result[] = $row;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет строку из таблицы information по id
   *  @param mixed $id - id блока
   */
  public function deleteBlock(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    return $this->mysqli->query("DELETE FROM `information` WHERE `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

}
