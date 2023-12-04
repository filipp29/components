<?php

namespace admin\components;

class Sms extends BaseComponent{

  private $api_logn = "ex15565185363";
  private $api_password = "609971";

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
   *  Отправляет смс с текстом $text на номер $phone
   *  @param mixed $phone - номер телефона
   *  @param mixed $text - текст сообщения
   */
  public function sendMessage(
    $phone,
    $text
  ){
    $phone = urlencode($phone);
    $text = urlencode($text);
    $url = "http://{$this->api_logn}:{$this->api_password}@apisms.expecto.me/send/?phone={$phone}&text={$text}";
    $result = file_get_contents($url);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Отправляет все сообщения из таблицы sms_queue с status = 0 через метод sendMessage. Изменяет значение поля status на 1
   */
  public function sendAllMessages(){
    $req = $this->mysqli->query("SELECT * from `sms_queue` where `status` = '0'");
    $result = [];
    if ($req){
      while ($row = $req->fetch_assoc()){
        $result[$row["id"]] = $this->sendMessage($row["phone"],$row["message"]);
        sleep(20);
        $this->mysqli->query("UPDATE `sms_queue` SET `status` = '1' WHERE `id` = {$row["id"]}");
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
