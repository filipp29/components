<?php

namespace admin\components;

class PlanDay extends BaseComponent{

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
   *  Возврашает список заданий userId из таблицы plan_day за дату date
   *  @param mixed $userId - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   *  @return array
   */
  public function getUserPlanDay(
    $userId,
    $date
  ){
    $buf = explode("-",$date);
    $y = (int)$buf[0];
    $m = (int)$buf[1];
    $d = (int)$buf[2];
    $userId  = $this->mysqli->real_escape_string($userId);
    $req = $this->mysqli->query("SELECT * from `plan_day` where `id_user` = '{$userId}' and `d` = '{$d}' and `m` = '{$m}' and `y` = '{$y}'");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["ai"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает новую дату date для строки ai в таблице plan_day
   *  @param mixed $ai - ai строки
   *  @param mixed $date - новая дата формата YYYY-MM-DD
   *  @param mixed $message - текст добавляемый в конец desc задания
   */
  public function setDate(
    $ai,
    $date,
    $message = ""
  ){
    $buf = explode("-",$date);
    $y = (int)$buf[0];
    $m = (int)$buf[1];
    $d = (int)$buf[2];
    $ai  = $this->mysqli->real_escape_string($ai);
    $req = $this->mysqli->query("SELECT * from `plan_day` where `ai` = '{$ai}'");
    if ($req){
      $row = $req->fetch_assoc();
      $desc = $this->mysqli->real_escape_string($row["desc"]. $message);
    }
    else{
      return;
    }
    $this->mysqli->query("UPDATE `plan_day` SET `y` = '{$y}', `m` = '{$m}', `d` = '{$d}', `desc` = '{$desc}' where `ai` = '{$ai}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы plan_day
   *  @param mixed $where - блок where запроса
   *  @return array
   */
  public function getAll(
    $where
  ){
    
    if ($where){
      $whereBlock = " where {$where} ";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `plan_day` {$whereBlock}");
    
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["ai"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строку из таблицы plan_day по id
   *  @param mixed $id - ai строки
   */
  public function getPlanById(
    $id
  ){
    if ($_SESSION["id_user"] == "100"){
      echo $id;
    }
    $result = $this->getAll("`ai` = '{$id}'");
    foreach($result as $row){
      return $row;
    }
    return [];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Передает задание из таблицы plan_day пользователю id_user путем создания нового задания на дату date и внесением задания ai в таблицу user_plan_transfer
   *  и возвращает id созданной строки
   *  @param mixed $ai - ai задания
   *  @param mixed $id_user - id пользователя которому принадлежит задание
   *  @param mixed $userTransfer - id пользователя, которому нужно передать задание
   *  @param mixed $date - дата для нового задания в формате YYYY-MM-DD
   *  @param mixed $reason - причина передачи
   *  @param mixed $date_get - дата старого задания
   */
  public function transferTask(
    $ai,
    $id_user,
    $userTransfer,
    $date,
    $reason,
    $date_get
  ){
    $plan = $this->getPlanById($ai);
    if ($plan){
      $timestamp = strtotime($date);
      $d = date("d",$timestamp);
      $m = date("m",$timestamp);
      $y = date("Y",$timestamp);
      $newPlan = [
        'date_created'=> date("d.m.Y"), 
				'id_user_created'=> $plan["id_user_created"], 
				'rool'=> "0", 
				'desc'=> $plan["desc"], 
				'id_user'=> $userTransfer, 
				'd'=> $d,
				'm'=> $m,
				'y'=> $y,
				'need_ans'=> $plan["need_ans"],
				'show'=> 1, 
				'limit_date' => $plan["limit_date"]
      ];
      $insertedAi = $this->addPlan($newPlan);
			$req = $this->mysqli->query("SELECT * from `plan_file` where `task_id` = '{$ai}'");
			while($row = $req->fetch_assoc()){
				$this->mysqli->query("INSERT into `plan_file` (`name`,`path`,`task_id`) values ('{$row["name"]}','{$row["path"]}','{$insertedAi}')");
			}
      $userPlanData = [
        "date" => $date_get,
        "ai_plan" => $ai,
        "id_user" => $id_user,
        "user_transfer" => $userTransfer,
        "reason" => $reason,
        "created_at" => date("Y-m-d H:i:s",time())
      ];
      print_r($userPlanData);
      $this->addOne("user_plan_transfer",$userPlanData);
    }
    
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет нову строку в таблицу plan_day с данными data
   *  @param mixed $data - данные для вставки
   */
  public function addPlan(
    $data
  ){
    return $this->addOne("plan_day",$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает true если задание было передано
   *  @param mixed $ai - ai задания
   *  @param mixed $id_user - id пользователя,
   *  @param mixed $date - дата
   */
  public function isTransfered(
    $ai,
    $id_user,
    $date
  ){
    $sql_check = $this->mysqli->query("SELECT * FROM `user_plan_transfer` WHERE `date`='{$date}' and `id_user`= '{$id_user}' and `ai_plan` = '{$ai}'");
    $col_check = $sql_check->num_rows;
    if ($col_check > 0) {
      return true;
    } else {
      return false;
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает true если plan с ai, id_user и date есть в таблице user_plan_day_status со статусом 1 иначе возвращает  false
   *  @param mixed $ai - ai плана
   *  @param mixed $id_user - id пользователя
   *  @param mixed $date - дата
   */
  public function isStatusTable(
    $ai,
    $id_user,
    $date
  ){
    $req = $this->mysqli->query("SELECT * FROM `user_plan_day_status` WHERE `status`=1 and `date`='$date' and `id_user`=" . $id_user . " and `id_plan`=" . $ai);
    return $req->num_rows > 0;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает true если plan с ai, id_user и date есть в таблице user_plan_other_day иначе возвращает  false
   *  @param mixed $ai - ai плана
   *  @param mixed $id_user - id пользователя
   *  @param mixed $date - дата
   */
  public function isOtherDayTable(
    $ai,
    $id_user,
    $date
  ){
    $req = $this->mysqli->query("SELECT * FROM `user_plan_other_day` WHERE `ai_plan`=" . $ai . " and `date`='$date' and `id_user`=" . $id_user);
    return $req->num_rows > 0;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает задание ai,id_user,date в план, удаляя его из таблицы plan_day_status
   *  @param mixed $ai = ai плана
   *  @param mixed $id_user - id пользователя
   *  @param mixed $date - дата
   */
  public function redone(
    $ai,
    $id_user,
    $date
  ){
    $ai = $this->mysqli->real_escape_string($ai);
    $id_user = $this->mysqli->real_escape_string($id_user);
    $date = $this->mysqli->real_escape_string($date);
    $this->mysqli->query("DELETE FROM `user_plan_day_status` WHERE `id_plan`=$ai and `id_user` = '{$id_user}' and `date` = '{$date}'");
    $this->mysqli->query('UPDATE `plan_day` SET `deleted` = 0 WHERE `ai`=' . $ai);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список всех заданий id_user за дату date, включая настраиваемые
   *  @param mixed $id_user - id пользователя
   *  @param mixed $date - дата в формате YYYY-MM-DD
   */
  public function getUserAllPlan(
    $id_user,
    $date
  ){
    global $PER_PLAN;
    $buf = get_plan_one(date("d.m.Y",strtotime($date)),$PER_PLAN,$this->mysqli,0,$id_user);
    $plan = [];
    foreach($buf["array_plan_ret"] as $row){
      $plan[$row["ai"]] = $row;
    }
    return $plan;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Переносит задание ai_plan с даты current_date на дату for_date
   *  @param mixed $ai_plan - ai строки из таблицы plan_date
   *  @param mixed $current_date - текущая дата в формате YYYY-MM-DD
   *  @param mixed $for_date - дата для переноса в формате YYYY-MM-DD
   *  @param mixed $reason - причина переноса
   *  @param mixed $to_user - если указан, то перенести задание для этого пользователя
   */
  public function toOtherDate(
    $ai_plan,
    $current_date,
    $for_date,
    $reason,
    $to_user = null
  ){
		$reason = $this->mysqli->real_escape_string($reason);
		$current_date = date("d.m.Y",strtotime($current_date));
		$sql_check = $this->mysqli->query("SELECT * FROM `plan_day` WHERE `ai`=$ai_plan");
		$res_check = $sql_check->fetch_array();
		
		$desc = $this->mysqli->real_escape_string($res_check['desc']);
		
		$date_now = date('Y-m-d',strtotime($current_date));
		
		if ($date_now <= $for_date) {
			
			if (!$to_user){
        $id_user = $res_check["id_user"] ? $res_check["id_user"] : $_SESSION['id_user'];
      }
			else{
        $id_user = $to_user;
      }
			$id_user_created = $res_check['id_user_created'];
			$limit_date = $res_check['limit_date'];
			$need_ans = $res_check['need_ans'];
			if ($id_user_created == 0) $id_user_created = $id_user;
			$priority = 0;
			
			$array_date = explode('-',$for_date);
			$day = (int) $array_date[2];
			$month = (int) $array_date[1];
			$year = $array_date[0];

//            $isset = (bool) $mysqli->query("
//                select * from `user_plan_other_day` where `ai_plan`=$ai_plan limit 1
//            ")->num_rows;
//
//            if ($isset) {
//                $mysqli->query("delete from `user_plan_other_day` where `ai_plan`=$ai_plan");
//            }
//
//            $mysqli->query("delete from `plan_day` where `ai` = $ai_plan");

			$this->mysqli->query("
				INSERT INTO `user_plan_other_day` SET
				`date`='$current_date',
				`id_user`='$id_user',
				`reason`='$reason',
				`ai_plan`=$ai_plan
			");
			
			$ai_user_plan_other_day = $this->mysqli->insert_id;
			
			
			$this->mysqli->query("
				INSERT INTO `plan_day` SET 
				`desc`='$desc', 
				`limit_date`='$limit_date', 
				`d`='$day', 
				`m`='$month',
				`y`='$year',
				`show`='1',
				`id_user`='$id_user',
				`id_user_created`='$id_user_created',
				`parent_ai`=$ai_plan,
				`need_ans`='$need_ans',
				`ai_user_plan_other_day`=$ai_user_plan_other_day
			");

			// 15.05.23 Изменение Добавил перенос вложенных файлов в новую копию задания
			$taskId = $this->mysqli->insert_id;
			$ai_plan;
			$result = $this->mysqli->query("SELECT * from `plan_file` where `task_id` = '{$ai_plan}'");
			while($row = $result->fetch_assoc()){
				$this->mysqli->query("INSERT into `plan_file` (`name`,`path`,`task_id`) values ('{$row["name"]}','{$row["path"]}','{$taskId}')");
			}
			
			return 1; // Задание успешно перенесено!
		} else {
			return 2; // Нельзя переносить на дату, которая была раньше!
		}
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Переносит все задания пользователя id_user с даты current_date на дату for_date. Не создает повторяющиеся задания
   *  @param mixed $id_user - id пользователя
   *  @param mixed $current_date - текущая дата
   *  @param mixed $for_date - дата для переноса
   */
  public function allPlanToOtherDate(
    $id_user,
    $current_date,
    $for_date
  ){
    $current_plan = $this->getUserAllPlan($id_user,$current_date);
    $for_plan = $this->getUserAllPlan($id_user,$for_date);
    $plan = [];
    $contKeyList = [
      "70102",
      "57339"
    ];
    foreach($current_plan as $current_key => $row){
      if (in_array($current_key,$contKeyList)){
        continue;
      }
      
      if ($row["done"] ?? false){
        continue;
      }
      if (!array_key_exists($current_key,$for_plan)){
        $plan[$current_key] = $row; 
      }
    }
    foreach($plan as $ai_plan => $row){
      echo $this->toOtherDate($ai_plan,$current_date,$for_date,"Выходной день",$id_user). "<br>";
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет задание в план для пользователя id_user на определенную дату, если дата не указана добавляет задание на сегодня
   *  @param mixed $desc - текст задания
   *  @param mixed $id_user - id пользователя
   *  @param mixed $d - день
   *  @param mixed $m - месяц
   *  @param mixed $y - год
   *  @param mixed $param - массив с дополнительными параметрами
   */
  public function sendDatePlan(
    $desc, 
    $id_user, 
    $d = "", 
    $m = "", 
    $y = "", 
    $param = []
  ){
  
      if ($d == "") $d = date('d');
      if ($m == "") $m = date('m');
      if ($y == "") $y = date('Y');
  
      $need_ans = 0;
      $priority = 0;
      $id_user_created = 0;
      $rool = 1;
      $show = 1;
      $desc = $this->mysqli->real_escape_string(trim($desc));
  
      if (isset($param['need_ans'])) $need_ans = $param['need_ans'];
      if (isset($param['id_user_created'])) $id_user_created = $param['id_user_created'];
  
      // if ($id_user_created == $id_user) $need_ans = 0;
  
      if ($desc != "") {
  
          $this->mysqli->query("
          INSERT INTO `plan_day` SET 
          `id_user_created`=$id_user_created, 
          `desc`='$desc', 
          `priority`=$priority, 
          `id_user`=$id_user, 
          `d`='$d', 
          `m`='$m', 
          `y`='$y', 
          `need_ans`='$need_ans', 
          `show`=$show
        ");
  
          
          return $this->mysqli->insert_id;
      }
  
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет строку в таблицу plan_file
   *  @param mixed $task_id - id задания из таблицы plan_day
   *  @param mixed $name - имя файла
   *  @param mixed $path - путь к файлу
   */
  public function addPlanFile(
    $task_id,
    $name,
    $path
  ){
    return $this->addOne("plan_file",compact(
      "task_id",
      "name",
      "path"
    ));
  }

  /*-----------------------------------------------------------------------*/

}
