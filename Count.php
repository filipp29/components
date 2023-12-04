<?php

namespace admin\components;

class Count extends BaseComponent{

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
   *  Возвращает все строки из таблицы count в виде [id_order => [row1,row2]]
   *  @param mixed $where - блок where
   */
  public function getAll(
    $where = ""
  ){
    return $this->allGet("count",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает одну строку из таблицы count_plan 
   *  @param mixed $where - блок where
   */
  public function getOneCountPlan(
    $where = ""
  ){
    return $this->oneGet("count_plan",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список типов прихода в виде массива [id => name]
   */
  public function getArticlePrihodList(){
    $list = $this->allGet("article_prihod");
    $result = [];
    foreach($list as $key => $row){
      $result[$row["id"]] = $row["name"];
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет строку в таблицу count_plan соответствующую строке id_count таблицы count
   *  @param mixed $id_count - id строки таблицы count
   */
  public function addCountPlan(
    $id_count
  ){
    $count = $this->oneById("count",$id_count);
    if (!$count["in"]){
      return;
    }
    if (in_array($count["article_prihod"],[2,3,4,6])){  //если article_prihod маркетплейс то начисляем бонус маркетологу
      $user = $this->oneGet("user","`priority` = '10' and `delete` = '0'");
      if (!$user){
        return;
      }
      $current = $this->getOneCountPlan("`id_count` = '{$id_count}'");
      $data = [
        "id_count" => $id_count,
        "id_user" => $user["id_user"],
        "sum" => $count["in"],
        "id_article_prihod" => $count["article_prihod"],
        "datetime" => date("Y-m-d H:i:s",strtotime($count["date"]))
      ];
      if (isset($current["id"])){

      }
      else{
        $this->addOne("count_plan",$data);
        dd($data,false);
      }
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает план пользователей относительно приходов из таблицы count_plan и count
   *  в виде массива [
   *    id_user => [
   *      sum => sum
   *      dsteList => [
   *        date => sum
   *      ]
   *    ]
   *  ]
   *  @param mixed $where - блок where для запроса к clientbase_prihod_plan
   */
  public function getUserPlanList(
    $where = ""
  ){
    $planList = $this->allGet("count_plan",$where);
    $result = [];
    foreach($planList as $plan){
      $id_user = $plan["id_user"];
      $id_count = $plan["id_count"];
      $count = $this->oneById("count",$id_count);
      if (!$count || !$count["in"]){
        continue;
      }
      $sum = (int)$count["in"];
      $date = date("Y-m-d",strtotime($plan["datetime"]));
      $result[$id_user] = $result[$id_user] ?? [
        "sum" => 0,
        "dateList" => []
      ];
      $result[$id_user]["dateList"][$date] = $result[$id_user]["dateList"][$date] ?? 0;
      $result[$id_user]["sum"] += $sum;
      $result[$id_user]["dateList"][$date] += $sum;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает план пользователей относительно таблицы count_plan а так же план за предыдущий месяц
   *  @param mixed $year - год
   *  @param mixed $month - месяц  
   */
  public function getMonthPlanList(
    $year,
    $month
  ){
    $result = [];
    $start = date("Y-m-d H:i:s",strtotime("{$year}-{$month}-01"));
    $end = date("Y-m-t H:i:s",strtotime("{$year}-{$month}-01"));
    $where = "DATE(`datetime`) >= DATE('{$start}') and DATE(`datetime`) <= DATE('{$end}')";
    $result["current"] = $this->getUserPlanList($where);
    if ($month == 1) {
        $month = 12;
        $year = $year - 1;
    } else {
        $month = $month - 1;
    }
    $start = date("Y-m-d H:i:s",strtotime("{$year}-{$month}-01"));
    $end = date("Y-m-t H:i:s",strtotime("{$year}-{$month}-01"));
    $where = "DATE(`datetime`) >= DATE('{$start}') and DATE(`datetime`) <= DATE('{$end}')";
    $result["previous"] = $this->getUserPlanList($where);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает историю начислений менеджерам из таблиц count_plan
   *  в виде массива отсортированного по дате начисления
   *  [
   *    user => [plan1,plan2]
   *  ]
   *  
   * ---------------------------------------------------------------------
   *  @param mixed $where - блок where для запроса к таблице count_plan
   */  
  public function getPlanHistory(
    $where = ""
  ){
    $planList = $this->allGet("count_plan",$where);
    $result = [];
    foreach($planList as $plan){
      $id_user = $plan["id_user"];
      $id_count = $plan["id_count"];
      $count = $this->oneById("count",$id_count);
      if (!$count || !$count["in"]){
        continue;
      }
      $plan["sum"] = (int)$count["in"];
      $result[$id_user] = $result[$id_user] ?? [];
      $result[$id_user][] = $plan;
    }
    foreach($result as $key => $row){
      usort($result[$key],function($a,$b){
        $t1 = strtotime($a["datetime"]);
        $t2 = strtotime($b["datetime"]);
        return $t1 - $t2;
      });
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
