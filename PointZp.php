<?php

namespace admin\components;

class PointZp extends BaseComponent{

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
    return $this->allGet("point_zp",$where);
  }

  /*-----------------------------------------------------------------------*/

  public function getOne(
    $where = ""
  ){
    return $this->oneGet("point_zp",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет строку в таблицу point_zp с параметрами params
   *  @param mixed $params - массив полей для вставки
   */
  public function create(
    $params
  ){
    $this->addOne("point_zp",$params);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Одобряет запись id из таблицы point_zp и добавляет запись в таблицу order_mont_zp
   *  @param mixed $id - id записи
   */
  private function accept(
    $id
  ){
    $orderMontZp = new OrderMontZp($this->mysqli);
    $id = $this->mysqli->real_escape_string($id);
    $point = $this->getOne("`id` = '{$id}'");
    if ($point["file"]){
      $comment = "<a href=\"{$point["file"]}\">Ссылка</a>";
    }
    else{
      $comment = $point["comment"];
    }
    $id_mont_zp = $orderMontZp->addPointZp(
      $point["id_user"],
      $point["date"],
      $point["address"],
      $comment
    );
    $this->changeOne("point_zp", $id, [
      "status"=> "1",
      "id_mont_zp" => $id_mont_zp
    ]);

  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Отклоняет запись id из таблицы point_zp и удаляет запись из таблицы order_mont_zp
   *  @param mixed $id - id записи
   */
  private function decline(
    $id
  ){
    $orderMontZp = new OrderMontZp($this->mysqli);
    $id = $this->mysqli->real_escape_string($id);
    $point = $this->getOne("`id` = '{$id}'");
    if(count($point) == 0){
      return;
    }
    if ($point["id_mont_zp"]){
      $orderMontZp->delete($point["id_mont_zp"]);
    }
    $this->changeOne("point_zp", $id, [
      "status"=> "2",
      "id_mont_zp" => "0"
    ]);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет статус записи id из таблицы point_zp на status
   *  @param mixed $id - id строки
   *  @param mixed $status - новый статус
   */
  public function changeStatus(
    $id,
    $status
  ){
    switch ($status) {
      case '1':
        $this->accept($id);
        break;

      case '2':
        $this->decline($id);
        break;
      
      default:
        # code...
        break;
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет запись id из таблицы point_zp
   *  @param mixed $id - id строки
   */
  public function delete(
    $id
  ){
    $orderMontZp = new OrderMontZp($this->mysqli);
    $id = $this->mysqli->real_escape_string($id);
    $point = $this->getOne("`id` = '{$id}'");
    if(count($point) == 0){
      return;
    }
    if ($point["id_mont_zp"]){
      $orderMontZp->delete($point["id_mont_zp"]);
    }
    $this->mysqli->query("DELETE from `point_zp` where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из таблицы point_zp c id_user, date, time_period
   *  @param mixed $id_user - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   *  @param mixed $time_period - временной интервал из графика
   */
  public function findOne(
    $id_user,
    $date,
    $time_period
  ){
    $id_user = $this->mysqli->real_escape_string($id_user);
    $date = $this->mysqli->real_escape_string($date);
    $time_period = $this->mysqli->real_escape_string($time_period);
    return $this->getOne("`id_user` = '{$id_user}' and `date` = '{$date}' and `time_period` = '{$time_period}'");
  }

  /*-----------------------------------------------------------------------*/

  public function getPeriodList(){
    return [
      "9-11",
      "11-13",
      "13-15",
      "15-17",
      "17-19",
      "19-21",
      "21-23"
    ];
  }

  /*-----------------------------------------------------------------------*/

}
