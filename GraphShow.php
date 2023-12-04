<?php

namespace admin\components;

class GraphShow extends BaseComponent{

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
   *  Делает доступным показ графика для userId на дату date в таблице graph_show
   *  @param mixed $userId - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   */
  public function show(
    $userId,
    $date
  ){
    $date = $this->mysqli->real_escape_string($date);
    $userId = $this->mysqli->real_escape_string($userId);
    $req = $this->mysqli->query("SELECT * from `graph_show` where `id_user` = '{$userId}' and `date` = '{$date}'");
    if ($req && ($req->num_rows > 0)){
      $data = [
        "show" => "1"
      ];
      $row = $req->fetch_assoc();
      $id = $row["id"];
      $this->changeOne("graph_show",$id,$data);
    }
    else{
      $data = [
        "id_user" => $userId,
        "date" => $date,
        "show" => "1"
      ];
      $this->addOne("graph_show",$data);
    }
  }

  /*-----------------------------------------------------------------------*/
  /**
   *  Делает не доступным показ графика для userId на дату date в таблице graph_show
   *  @param mixed $userId - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   */
  public function hide(
    $userId,
    $date
  ){
    $date = $this->mysqli->real_escape_string($date);
    $userId = $this->mysqli->real_escape_string($userId);
    $req = $this->mysqli->query("SELECT * from `graph_show` where `id_user` = '{$userId}' and `date` = '{$date}'");
    if ($req && ($req->num_rows > 0)){
      $data = [
        "show" => "0"
      ];
      $row = $req->fetch_assoc();
      $id = $row["id"];
      $this->changeOne("graph_show",$id,$data);
    }
  }
  
  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает true если пользователь userId может видеть график на дату date иначе возвращает false
   *  @param mixed $userId - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   */
  public function check(
    $userId,
    $date
  ){
    $date = $this->mysqli->real_escape_string($date);
    $userId = $this->mysqli->real_escape_string($userId);
    $req = $this->mysqli->query("SELECT * from `graph_show` where `id_user` = '{$userId}' and `date` = '{$date}'");
    if ($req){
      $row = $req->fetch_assoc();
      if ($row["show"] == "1"){
        return true;
      }
    }
    return false;
  }

  /*-----------------------------------------------------------------------*/

}
