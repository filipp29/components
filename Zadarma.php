<?php

namespace admin\components;

class Zadarma extends BaseComponent{

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
   *  Возвращает список звонков из таблицы zadarma_stat сгруппированных по номеру телефона и отсортированных по убыванию даты звонка в виде массива [caller_id => [call_start,duration,internal]]
   */
  public function getCallByPhone(){
    $req = $this->mysqli->query("SELECT `caller_id`,`call_start`,`duration`,`internal` from `zadarma_stat` ORDER BY `call_start` DESC");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $phone = $row["caller_id"];
        if (!isset($result[$phone])){
          $result[$phone] = [];
        }
        $result[$phone][] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список звонков из таблицы zadarma_stat за дату $date
   *  @param mixed $date - дата в формате YYYY-MM-DD
   */
  public function getCallByDate(
    $date
  ){
    $date = $this->mysqli->real_escape_string($date);
    $req = $this->mysqli->query("SELECT * from `zadarma_stat` where DATE(`call_start`) = DATE('{$date}')");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["ai"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
