<?php

namespace admin\components;

class ShortLink extends BaseComponent{

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

  /**
   *  Генерирует случайную буквенно-цифровую строку из 8 символов на основании текущего времени
   */
  private function generateKey(){
    $text = md5(microtime()). random_int(0,10000);
    return substr($text,0,8);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Сохраняет ссылку в таблицу short_link и возвращает ее короткое представление в базе (поле short)
   *  @param mixed $link - ссылка для сохранения
   *  @param mixed $one_off - если true, то создается одноразовая ссылка
   */
  public function saveLink(
    $link,
    $one_off = false
  ){
    $short = $this->generateKey();
    $link = $this->mysqli->real_escape_string($link);
    $this->addOne("short_link",[
      "short" => $short,
      "link" => $link,
      "one_off" => $one_off ? "1" : "0"
    ]);
    return $short;
  }

  /*-----------------------------------------------------------------------*/

  /**
   * Возвращает строку из таблицы short_link по значению short. 
   * @param mixed $short - короткая ссылка
   * @param mixed $delete - если true, то удаляет короткую ссылку из базы. Использовать если короткая ссылка одноразовая
   */
  public function getLink(
    $short
  ){
    $short = $this->mysqli->real_escape_string($short);
    $link = $this->oneGet("short_link","`short` = '{$short}'");
    if ($link){
      if ($link["one_off"]){
        $this->mysqli->query("DELETE from `short_link` where `short` = '{$short}'");
      }
      else{
        $count = (int)$link["open_count"] + 1;
        $this->mysqli->query("UPDATE `short_link` SET `open_count` = '{$count}' where `id` = '{$link["id"]}'");
      }
    }
    return $link;
  }

  /*-----------------------------------------------------------------------*/

}
