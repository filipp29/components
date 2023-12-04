<?php

namespace admin\components;

class User extends BaseComponent{

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
   *  Возвращает все строки из таблицы user 
   *  @param mixed $where - блок where апроса
   */
  public function getAll(
    $where = ""
  ){
    return $this->allGet("user",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы user 
   *  @param mixed $where - блок where апроса
   */
  public function getOne(
    $where = ""
  ){
    return $this->oneGet("user",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список пользователей из таблицы user, если стоит флаг active, то возвращает только активных, инае возвращает всех. В виде массива [id_user => row]
   *  @param bool $active - если true, то возвращает только активных пользователей
   *  @param mixed $priority - id роли из таблицы priority
   *  @return array
   */
  public function getUserList(
    $active = true,
    $priority = null
  ){
    if ($active){
      $whereBlock = " WHERE `delete` = '0'";
    }
    else{
      $whereBlock = "";
    }
    if ($priority !== null){
      $whereBlock .= $whereBlock ? " AND " : "WHERE ";
      $whereBlock .= "`priority` = '{$priority}'";
    }
    $req = $this->mysqli->query("SELECT * from `user` {$whereBlock}");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id_user"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список способов оплаты из таблицы order_income_type для каждого пользователя из таблицы user через таблицу user_order_income_type в виде [id_user => [type_id_1,type_id_2,type_id_3]]
   *  @return array
   */
  public function getUserIncomeTypeList(){
    $req = $this->mysqli->query("SELECT `user`.`id_user`, `income`.`order_income_type_id` as `type` from `user` left join `user_order_income_type` as `income` on `user`.`id_user` = `income`.`id_user`");
    $result = [];
    if ($req){
      while ($row = $req->fetch_assoc()){
        $userId = $row["id_user"];
        $type = $row["type"];
        if (!isset($result[$userId])){
          $result[$userId] = [];
        }
        if ($type && !in_array($type,$result[$userId])){
          $result[$userId][] = $type;
        }
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список всех мастеров в виде массива [id_user => {номер мастера (m1,m2,m3)}]
   *  @return array
   */
  public function getMasterList(){
    $req = $this->mysqli->query("SELECT * FROM `user` WHERE `priority`=1 or `priority`=6");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $buf = explode(" ",$row["name"]);
        $number = $buf[count($buf) - 1];
        $result[$row["id_user"]] = $number;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Проверяет поместится ли позиция кому либо в багажник
   *  @param mixed $height - высота позиции,
   *  @param mixed $width - ширина позиции,
   *  @param mixed $type - тип позиции
   */
  public function checkBagAllUser(
    $height,
    $width,
    $type
  ){
    $userList = $this->getUserList(true);
    foreach($userList as $user){
      if ($type == "setka"){
        if ($user['width_bag_dver'] != 0) {
          $width_bag = $user['width_bag_dver'];
          $height_bag = $user['height_bag_dver'];
        } else {
          $width_bag = $user['width_bag'];
          $height_bag = $user['height_bag'];
        }
      }
      if (in_array($type,["steklo","steklopaket"])){
        $width_bag = $user['width_bag_steklo'];
        $height_bag = $user['height_bag_steklo'];
      }
      if (($width <= $height_bag and $height <= $width_bag) or ($width <= $width_bag and $height <= $height_bag)) {
        return true;
      }
    }
    return false;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возаращает строку из таблицы user по id_user_clientbase
   *  @param mixed $id_user_clientbase - id пользователя из КБ 
   */
  public function getOneByClientbaseUserId(
    $id_user_clientbase
  ){
    $id_user_clientbase = $this->mysqli->real_escape_string($id_user_clientbase);
    return $this->oneGet("user","`id_user_clientbase` = '{$id_user_clientbase}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Вовращает строку из таблицы user по id_user
   *  @param mixed $id_user - id пользователя
   */
  public function getOneById(
    $id_user
  ){
    return $this->oneById("user",$id_user);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Вовращает дату ближайшего рабочего дня пользователя id_user из таблицы staff_work_date больше чем date. Если такой даты нет - возвращает дату ближайшего буднего дня
   *  @param mixed $id_user = id польхователя
   *  @param mixed $date - дата начала в формате YYYY-MM-DD
   */
  public function getClosestWorkDate(
    $id_user,
    $date
  ){
    $id_user = $this->mysqli->real_escape_string($id_user);
    $date = $this->mysqli->real_escape_string($date);
    $req = $this->mysqli->query("SELECT MIN(`date`) as `date` FROM `staff_work_date` WHERE 
        `id_user` = '{$id_user}' 
        and DATE(`date`) > DATE('{$date}') 
    ");
    if ($req->num_rows > 0){
      $row = $req->fetch_assoc();
      if ($row["date"]){
        return $row["date"];
      }
    }
    $date = new \DateTime($date);
    $date->modify(" + 1 day");
    while(in_array($date->format("N"),[6,7])){
      $date->modify("+ 1 day");
    }
    return $date->format("Y-m-d");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает true если в таблице staff_work_date для пользователя id_user есть запись на дату date
   *  @param mixed $id_user - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   */
  public function checkWorkDate(
    $id_user,
    $date
  ){
    $sql = $this->mysqli->query("
			SELECT * FROM `staff_work_date` WHERE `id_user`='$id_user' and `date`='$date'
		");
    $col = $sql->num_rows;
    if ($col > 0) return true;
    else return false;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список id текущих навыков монтажника id_user 
   */
  public function getCurrentSkills(
    $id_user
  ){
    $id_user = $this->mysqli->real_escape_string($id_user);
    $where = "`id_user` = '{$id_user}'";
    $skills = $this->allGet("user_skill",$where);
    $result = [];
    foreach($skills as $skill){
      $result[] = $skill["id_skill"];
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает навыкм skills мастеру id_user, если навыка нет в списке, но он есть в базе, то навык удаляется из базы
   *  @param mixed $id_user - id пользователя
   *  @param mixed $skills - массив id навыков   
   */
  public function setSkills(
    $id_user,
    $skills
  ){
    $id_user = $this->mysqli->real_escape_string($id_user);
    $currentSkills = $this->getCurrentSkills($id_user);
    $same = [];
    foreach($currentSkills as $id_skill){
      if (in_array($id_skill,$skills)){
        $same[] = $id_skill;
      }
      else{
        $this->mysqli->query("DELETE from `user_skill` where `id_skill` = '{$id_skill}' and `id_user` = '{$id_user}'");
      }
    }
    foreach($skills as $key => $id_skill){
      if (in_array($id_skill,$same)){
        unset($skills[$key]);
      }
    }
    foreach($skills as $id_skill){
      $id_skill = $this->mysqli->real_escape_string($id_skill);
      $this->addOne("user_skill",compact("id_user","id_skill"));
    }
  }

  /*-----------------------------------------------------------------------*/

}
