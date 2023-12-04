<?php

namespace admin\components;

class ClientbasePrihod extends BaseComponent{

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
   *  Возвращает список приходов из таблицы clientbase_prihod в виде массива [id_order => [summ1,summ2]].
   *  @param mixed $onlySum - если true то вернет только суммы в виде [id_order => [summ1,summ2]], если false то вернет все поля в виде [id_order => [row1,row2]]
   *  @param mixed $where - блок where запроса
   *  @return array
   */
  public function getPrihodSumList(
    $onlySum = true,
    $where = ""
  ){
    if ($where){
      $whereBlock = " WHERE {$where}";
    }
    else{
      $whereBlock = "";
    }
    $req = $this->mysqli->query("SELECT * from `clientbase_prihod` {$whereBlock}");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        if(!isset($result[$row["id_order"]])){
          $result[$row["id_order"]] = [];
        };
        $result[$row["id_order"]][] = $onlySum ? $row["summ"] : $row;
      }
    }
    return $result;
  }
  
  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы clientbase_prihod
   *  @param mixed $where - блок where
   */
  public function getAll(
    $where = ""
  ){
    return $this->allGet("clientbase_prihod",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы clientbase_prihod_plan
   *  @param mixed $where - блок where запроса
   */
  public function getPrihodPlanAll(
    $where = ""
  ){
    return $this->allGet("clientbase_prihod_plan",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет строку с id_prihod в таблицу clientbase_prihod_plan если строки с id_prihod в ней нет
   *  @param mixed $id_prihod - ai строки с приходом из таблицы clientbase_prihod
   *  @param mixed $id_order - номер заказа
   *  @param mixed $main_user - поле main_user из таблицы clientbase
   *  @param mixed $main_pr - процент для main_user
   *  @param mixed $added_user - поле added из таблицы clientbase
   *  @param mixed $added_pr - процент для added_user
   *  @param mixed $prihod_date - дата прихода в формате YYYY-mm-dd HH:ii:ss
   */
  public function addPrihodPlan(
    $id_prihod,
    $id_order,
    $main_user,
    $main_pr,
    $added_user,
    $added_pr,
    $prihod_date
  ){
    $planData = $this->oneGet("clientbase_prihod_plan","`id_prihod` = '{$id_prihod}'");
    if ($planData){
      return;
    }
    $prihod = $this->oneById("clientbase_prihod",$id_prihod);
    if (!$prihod){
      return;
    }
    $sum = $prihod["summ"];
    $data = compact("id_prihod","main_user","main_pr","added_user","added_pr","sum","prihod_date","id_order");
    $this->addOne("clientbase_prihod_plan",$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список приходов из таблицы clientbase_prihod для которых нет строк в таблице clientbaase_prihod_plan, начиная с даты 2022-01-01
   */
  public function getNewPrihodList(){
    $req = $this->mysqli->query("
      SELECT `prihod`.* FROM `clientbase_prihod` as `prihod`
      left join `clientbase_prihod_plan` as `plan`
        on `prihod`.`ai` = `plan`.`id_prihod`
      WHERE DATE(`prihod`.`datetime`) >= DATE('2022-01-01')
      AND `plan`.`id` is null
    ");
    $result = [];
    while($row = $req->fetch_assoc()){
      $result[$row["ai"]] = $row;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает план пользователей относительно приходов из таблицы clientbase_prihod и clientbase_prihod_plan  
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
  public function getUserPrihodPlanList(
    $where = ""
  ){
    $prihodPlanList = $this->getPrihodPlanAll($where);
    $result = [];
    foreach($prihodPlanList as $plan){
      $id_prihod = $plan["id_prihod"];
      $prihod = $this->oneById("clientbase_prihod", $id_prihod);
      if (!$prihod){
        continue;
      }
      $id_order = $prihod["id_order"];
      if ($id_order <= 0){
        continue;
      }
      $sum = $prihod["summ"];
      $main_pr = $this->getPlanPercent("main",$plan)["percent"];
      $added_pr = $this->getPlanPercent("added",$plan)["percent"];
      $date = date("Y-m-d",strtotime($plan["prihod_date"]));
      $main_sum = (int)($sum * $main_pr / 100);
      $added_sum = (int)($sum * $added_pr / 100);
      $added_user = $plan["added_user"];
      $main_user = $plan["main_user"];
      $result[$main_user] = $result[$main_user] ?? [
        "sum" => 0,
        "dateList" => []
      ];
      $result[$added_user] = $result[$added_user] ?? [
        "sum" => 0,
        "dateList" => []
      ];
      $result[$main_user]["sum"] += $main_sum;
      $result[$main_user]["dateList"][$date] = $result[$main_user]["dateList"][$date] ?? 0;
      $result[$main_user]["dateList"][$date] += $main_sum;
      $result[$added_user]["sum"] += $added_sum;
      $result[$added_user]["dateList"][$date] = $result[$added_user]["dateList"][$date] ?? 0;
      $result[$added_user]["dateList"][$date] += $added_sum;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает план пользователей относительно приходов из таблицы clientbase_prihod и clientbase_prihod_plan а так же план за предыдущий месяц
   *  @param mixed $year - год
   *  @param mixed $month - месяц  
   */
  public function getMonthPrihodPlanList(
    $year,
    $month
  ){
    $userPrihodPlanList = [];
    $start = date("Y-m-d H:i:s",strtotime("{$year}-{$month}-01"));
    $end = date("Y-m-t H:i:s",strtotime("{$year}-{$month}-01"));
    $where = "DATE(`prihod_date`) >= DATE('{$start}') and DATE(`prihod_date`) <= DATE('{$end}')";
    $userPrihodPlanList["current"] = $this->getUserPrihodPlanList($where);
    if ($month == 1) {
        $month = 12;
        $year = $year - 1;
    } else {
        $month = $month - 1;
    }
    $start = date("Y-m-d H:i:s",strtotime("{$year}-{$month}-01"));
    $end = date("Y-m-t H:i:s",strtotime("{$year}-{$month}-01"));
    $where = "DATE(`prihod_date`) >= DATE('{$start}') and DATE(`prihod_date`) <= DATE('{$end}')";
    $userPrihodPlanList["previous"] = $this->getUserPrihodPlanList($where);
    return $userPrihodPlanList;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает план пользователей относительно приходов из таблицы clientbase_prihod и clientbase_prihod_plan  
   *  в виде массива [
   *    id_user => [
   *      id_order => sum
   *    ]
   *  ]
   *  @param mixed $where - блок where для запроса к clientbase_prihod_plan
   */
  public function getUserPrihodPlanByOrder(
    $where = ""
  ){
    $prihodPlanList = $this->getPrihodPlanAll($where);
    $result = [];
    foreach($prihodPlanList as $plan){
      $id_prihod = $plan["id_prihod"];
      $prihod = $this->oneById("clientbase_prihod", $id_prihod);
      if (!$prihod){
        continue;
      }
      $id_order = $prihod["id_order"];
      if ($id_order <= 0){
        continue;
      }
      $sum = $prihod["summ"];
      $main_pr = $this->getPlanPercent("main",$plan)["percent"];
      $added_pr = $this->getPlanPercent("added",$plan)["percent"];
      $date = date("Y-m-d",strtotime($plan["prihod_date"]));
      $main_sum = round($sum * $$main_pr / 100);
      $added_sum = round($sum * $added_pr / 100);
      $added_user = $plan["added_user"];
      $main_user = $plan["main_user"];
      $result[$main_user] = $result[$main_user] ?? [];
      $result[$added_user] = $result[$added_user] ?? [];
      $result[$main_user][$id_order] = $result[$main_user][$id_order] ?? 0;
      $result[$added_user][$id_order] = $result[$added_user][$id_order] ?? 0;
      $result[$main_user][$id_order] += $main_sum;
      $result[$added_user][$id_order] += $added_sum;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает историю начислений менеджерам из таблиц clientbase_prihod_plan, clientbae_prihod, clientbase_prihod_sum_history
   *  в виде массива отсортированного по дате начисления
   *  [
   *    user => [
   *      [дата начисления, дата прихода, номер заказа, сумма прихода, процент начисления, сумма начисления, причина начисления]
   *    ]
   *  ]
   *  В сумме начисления записывается разница между значением предыдущего начисления по ДАННОМУ приходу и значением текущего.
   *  Например 12.07 был создан приход на сумму 1000 по заказу 18333 с процентом начисления 20. Потом 14.07 сумма прихода изменилась и стала 1100.
   *  В истории будут две записис по данному приходу первая 
   *  12.07 | 12.07 | 18333 | 1000  | 20  | +200  | добавлен приход
   *  14.07 | 12.07 | 18333 | 1100  | 20  | +20   | изменилась сумма
   * ---------------------------------------------------------------------
   *  @param mixed $where - блок where для запроса к таблице clientbase_prihod_plan
   */
  public function getUserPlanHistory(
    $where = ""
  ){
    $planList = $this->getPrihodPlanAll($where);
    $result = [];
    foreach($planList as $plan){
      $id_order = $plan["id_order"];
      $id_prihod = $plan["id_prihod"];
      $main_user = $plan["main_user"];
      $added_user = $plan["added_user"];
      $prihod_date = $plan["prihod_date"];
      $buf = $this->getPlanPercent("main",$plan);
      $main_pr = $buf["percent"];
      $main_type = $buf["type"];
      $buf = $this->getPlanPercent("added",$plan);
      $added_pr = $buf["percent"];
      $added_type = $buf["type"];
      $getRow = function($date,$prihod_sum,$percent,$sum,$reason) use ($prihod_date,$id_order){
        return [
          "date" => $date,
          "prihod_date" => $prihod_date,
          "id_order" => $id_order,
          "prihod_sum" => $prihod_sum,
          "percent" => $percent,
          "sum" => $sum,
          "reason" => $reason,
        ];
      };
      $historyList = $this->allGet("clientbase_prihod_sum_history","`id_prihod` = '{$id_prihod}'");
      $result[$main_user] = $result[$main_user] ?? [];
      $sum = round($plan["sum"] * $main_pr / 100);
      $result[$main_user][] = $getRow($prihod_date,$plan["sum"],$main_pr, $sum,"insert");
      if (($added_pr > 0) || ($added_type == "expensive_order")){
        $result[$added_user] = $result[$added_user] ?? [];
        $sum = (int)($plan["sum"] * $added_pr / 100);
        $result[$added_user][] = $getRow($prihod_date,$plan["sum"],$added_pr,$sum,"insert");
      }
      foreach($historyList as $row){
        $new_sum = $row["new_sum"];
        $old_sum = $row["old_sum"];
        $delta = (int)$new_sum - (int)$old_sum;
        $date = $row["created_at"];
        $sum = round($delta * $main_pr / 100);
        $result[$main_user][] = $getRow($date,$new_sum,$main_pr,$sum,$row["reason"]);
        if (($added_pr > 0) || ($added_type == "expensive_order")){
          $sum = round($delta * $added_pr / 100);
          $result[$added_user][] = $getRow($date,$new_sum,$added_pr,$sum,$row["reason"]);
        }
      }
    }
    foreach($result as $key => $row){
      usort($result[$key],function($a,$b){
        $a_t = strtotime($a["date"]);
        $b_t = strtotime($b["date"]);
        return $a_t - $b_t;
      });
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает процент начисления и тип начисления в виде массива [percent,type]
   *  @param mixed $type - тип пользователя (main,added)
   *  @param mixed $plan - масиив с данными из таблицы clientbase_prihod_plan
   */
  private function getPlanPercent(
    $type,
    $plan
  ){
    $clientbase = new Clientbase($this->mysqli);
    $order = $clientbase->getOneByOrderId($plan["id_order"]);
    if (isset($order["price_all_order"]) && (int)$order["price_all_order"] > 100000){
      $added_type = ($plan["main_user"] == $plan["added_user"]) ? "normal" : "expensive_order";
      return [
        "main" => [
          "percent" => 0,
          "type" => "expensive_order"
        ],
        "added" => [
          "percent" => 0,
          "type" => $added_type
        ]
      ][$type];
    }
    if ($plan["main_user"] == $plan["added_user"]){
      return [
        "main" => [
          "percent" => (int)$plan["main_pr"] + (int)$plan["added_pr"],
          "type" => "main_added_same"
        ],
        "added" => [
          "percent" => 0,
          "type" => "main_added_same"
        ]
      ][$type];
    }
    return [
      "main" => [
        "percent" => (int)$plan["main_pr"],
        "type" => "normal"
      ],
      "added" => [
        "percent" => (int)$plan["added_pr"],
        "type" => "normal"
      ]
    ][$type];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список заказов больше 100,000 в виде таблицы
   *  @param mixed $start - дата начала в формате YYYY-MM-DD
   *  @param mixed $end - дата конца в формате YYYY-MM-DD
   */
  public function getExpensiveOrderList(
    $start,
    $end
  ){
    $req = $this->mysqli->query(" SELECT 
      `cb_pl`.*, `cb_pr`.`sum_prihod`,`cb_pr`.`all_sum`, `cb_pr`.`max_date` as `max_date`, 
      `cb_pr`.`price_all_order` , `cb_pr`.`rashod_order`, (`cb_pr`.`price_all_order` - `cb_pr`.`rashod_order`) as `pribil`, 
      (`cb_pl`.`main_pr` * (`cb_pr`.`price_all_order` - `cb_pr`.`rashod_order`) / 100) as `main_sum`,
      (`cb_pl`.`added_pr` * (`cb_pr`.`price_all_order` - `cb_pr`.`rashod_order`) / 100) as `added_sum`
      from `clientbase_prihod_plan` as `cb_pl`
      join (
        select `t1`.*, `t2`.`sum` as `all_sum`, `t3`.`max_date` as `max_date` from 
        (select `cb_p`.`ai` as `id_prihod`,`cb_p`.`summ` as `sum_prihod`, `cb`.`id_order` as `id_order`, `cb`.`price_all_order` as `price_all_order`, `cb`.`rashod_order` as `rashod_order` from `clientbase_prihod` as `cb_p`
        join `clientbase` as `cb`
          on `cb_p`.`id_order` = `cb`.`id_order`
        where `cb`.`price_all_order` > 100000
          and `cb`.`status` = 'Выполнен') as `t1`
          left join (
            select `cp`.`id_order`, SUM(`cp`.`summ`) as `sum` from `clientbase_prihod` as `cp`
              group by `cp`.`id_order`
          ) as `t2`
          on `t1`.`id_order` = `t2`.`id_order`
          left join (
            select `cpl`.`id_order`, MAX(`cpl`.`prihod_date`) as `max_date` from `clientbase_prihod_plan` as `cpl`
              group by `cpl`.`id_order`
          ) as `t3`
          on `t1`.`id_order` = `t3`.`id_order`
      ) as `cb_pr`
        on `cb_pl`.`id_prihod` = `cb_pr`.`id_prihod`
      where DATE(`cb_pl`.`prihod_date`) >= DATE('{$start}') 
      and DATE(`cb_pl`.`prihod_date`) <= DATE('{$end}')
      and (`price_all_order` * 0.99) < `all_sum`
      and `prihod_date` = `max_date`
    ");
    $result = [];
    while($row = $req->fetch_assoc()){
      $result[] = $row;
    } 
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает выполненный план заказов больше 100,000 в виде массива [user => сумма прибыли выполненных заказов] либо [user => [sum => сумма, orderList => список заказов]]
   *  @param mixed $year - год
   *  @param mixed $month - месяц
   *  @param mixed $orderList - если true то возвращает также и список заказов если false то только сумму
   */
  public function getExpensiveOrderPlan(
    $year,
    $month,
    $orderList = false
  ){
    $timestamp = strtotime("{$year}-{$month}-01");
    $start = date("Y-m-01",$timestamp);
    $end = date("Y-m-t",$timestamp);
    
    $planList = $this->getExpensiveOrderList($start,$end);
    if ($orderList){
      $result = [];
      foreach($planList as $plan){
        $main_user = $plan["main_user"];
        $added_user = $plan["added_user"];
        $main_sum = $plan["main_sum"];
        $added_sum = $plan["added_sum"];
        $result[$main_user] = $result[$main_user] ?? [
          "sum" => 0,
          "orderList" => []
        ];
        $result[$added_user] = $result[$added_user] ?? [
          "sum" => 0,
          "orderList" => []
        ];
        $result[$added_user]["sum"] += round($added_sum);
        $result[$main_user]["sum"] += round($main_sum);
        $result[$added_user]["orderList"][] = $plan["id_order"];
        $result[$main_user]["orderList"][] = $plan["id_order"];
      }
    }
    else{
      $result = [];
      foreach($planList as $plan){
        $main_user = $plan["main_user"];
        $added_user = $plan["added_user"];
        $main_sum = $plan["main_sum"];
        $added_sum = $plan["added_sum"];
        $result[$main_user] = $result[$main_user] ?? 0;
        $result[$added_user] = $result[$added_user] ?? 0;
        $result[$added_user] += round($added_sum);
        $result[$main_user] += round($main_sum);
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

}
