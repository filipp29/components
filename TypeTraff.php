<?php

namespace admin\components;

class TypeTraff extends BaseComponent{

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
   *  Возвращает список типов трафика из КБ
   */
  public function getTraffList(){
    $cbComponent = new CbComponent($this->mysqli);
    $buf = $cbComponent->getOrderFieldList();
    $fieldList = explode("\n",$buf["fields"]["row"]["f7981"]["value"]);
    foreach($fieldList as $key => $value){
      $fieldList[$key] = trim($value);
    }
    return $fieldList;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет текущие данные в таблицу user_type_traff 
   */
  public function createCurrent(){
    $userComponent = new User($this->mysqli);
    $userList = $userComponent->getUserList(true);
    $date = date("Y-m-01");
    foreach($userList as $user){
      if (!$user["type_traff"]){
        continue;
      }
      $currentTraf = $this->mysqli->query("SELECT * from `user_type_traff` where `id_user` = '{$user["id_user"]}' and `date` = '{$date}'");
      if ($currentTraf->num_rows > 0){
        continue;
      }
      // $this->mysqli->query("DELETE from `user_type_traff` where `id_user` = '{$user["id_user"]}' and `date` = '{$date}'");
      $traffList = explode(";",$user["type_traff"]);
      foreach($traffList as $traff){
        $this->addOne("user_type_traff",[
          "id_user" => $user["id_user"],
          "date" => $date,
          "type_traff" => $traff
        ]);
      }
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает данные по типам трафика за месяц в виде массива
   *  [
   *    traff_list - список всех типов трафика в месяце
   *    user_list - список пользователей с трафиков id_type_traff
   *  ]
   *  @param mixed $year - год
   *  @param mixed $month - месяц
   *  @param mixed $id_type_traff - Тип трафика из КБ
   */
  public function getMonthData(
    $year,
    $month,
    $type_traff
  ){
    $userComponent = new User($this->mysqli);
    $result = [
      "traff_list" => [],
      "user_list" => []
    ];
    $year = $this->mysqli->real_escape_string($year);
    $month = $this->mysqli->real_escape_string($month);
    $date = "{$year}-{$month}-01";
    $trafList = $this->allGet("user_type_traff","`date` = '{$date}'");
    foreach($trafList as $row){
      if (!in_array($row["type_traff"],$result["traff_list"])){
        $result["traff_list"][] = $row["type_traff"];
      }
      if ($row["type_traff"] == $type_traff){
        if (!key_exists($row["id_user"],$result["user_list"])){
          $user = $userComponent->getOneById($row["id_user"]);
          $result["user_list"][] = $user;
        }
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
