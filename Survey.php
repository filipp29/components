<?php

namespace admin\components;

class Survey extends BaseComponent{

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
   *  Возвращает список опросов из таблицы survey вместе с вопросами из таблицы survey_question и ответами из таблицы survey_answer в виде 
   *    [surveyList => [id_survey => survey],
   *     questionList => [id_survey => [id_question => question]],
   *     answerList => [id_question => [id_answer => answer]]]
   */
  public function getSurveyList(){
    $surveyList = $this->allGet("survey");
    $questionList = $this->allGet(("survey_question"));
    usort($questionList,function($a,$b){
      return (int)$a["sort"] - (int)$b["sort"];
    });
    $answerList = $this->allGet("survey_answer");

    $result = [
      "surveyList" => $surveyList,
      "questionList" => [],
      "answerList" => [] 
    ];
    foreach($questionList as $questionId => $row){
      $surveyId = $row["id_survey"];
      if (!isset($result["questionList"][$row["id_survey"]])){
        $result["questionList"][$surveyId] = [];
      }
      $result["questionList"][$surveyId][$questionId] = $row;
      # Если answertype = 2 то значит ответ это оценка от 1 до 5. Нужно сформировать список ответов
      if ($row["answer_type"] == "2"){
        for($i = 1; $i <= 5; $i++){
          $result["answerList"][$questionId][$i] = [
            "text" => $i
          ];
        }
      }
    }
    foreach($answerList as $answerId => $row){
      # Если answertype != 0 то значит не нужно формировать список ответов из таблицы survey_answer
      if ($questionList[$questionId] != "0"){
        continue;
      }
      $questionId = $row["id_question"];
      if (!isset($result["answerList"][$questionId])){
        $result["answerList"][$questionId] = [];
      }
      $result["answerList"][$questionId][$answerId] = $row;
    }

    return $result;

  } 

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список опросов из таблицы survey
   *  @param mixed $where - блок where запроса  
   */
  public function getSurveyAll(
    $where = ""
  ){
    return $this->allGet("survey",$where);
  }

  /*-----------------------------------------------------------------------*/
  
  /**
   *  Возвращает опрос id из таблиц survey, survey_question,survey_answers в виде
   *  [
   *    survey => survey[],
   *    questionList => [
   *      [
   *        question => question,
   *        answerList => [id_answer => answer]
   *      ]
   *    ]
   *  ]
   */
  public function getSurveyById(
    $id
  ){
    $buf = $this->getSurveyList();
    $answerList = $buf["answerList"];
    $survey = $buf["surveyList"][$id];
    $questionList = [];
    foreach($buf["questionList"][$id] as $questionId => $question){
      $questionList[] = [
        "question" => $question,
        "answerList" => isset($answerList[$questionId]) ? $answerList[$questionId] : "none"
      ];
    }
    return [
      "survey" => $survey,
      "questionList" => $questionList
    ];
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет результат опроса в таблицу survey_result в виде ответа на вопрос
   *  @param mixed $surveyId - id опроса
   *  @param mixed $questionId - id вопроса
   *  @param mixed $answer - ответ
   *  @param array $data - прочие данные для вставки в виде ассоциативного массива (id_order,id_user,phone)
   */
  public function addResult(
    $surveyId,
    $questionId,
    $answer,
    $data
  ){
    $buf = $this->allGet(`survey_question`," `id` = '{$questionId}'");
    $type = "1";
    foreach($buf as $question){
      $type = $question["answer_type"];
    }
    if ($type != "0"){
      $data["id_answer"] = "0";
      $data["answer_text"] = $answer;
    }
    else{
      $data["id_answer"] = $answer;
    }
    $data["id_survey"] = $surveyId;
    $data["id_question"] = $questionId;
    $data["created_at"] = date("Y-m-d H:i:s",time());
    $data["id_user"] = "0";
    $id_result = $this->addOne("survey_result",$data);
    $this->setResultUser($id_result);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет текст ответа id_result на text в таблице survey_result
   *  @param mixed $id_result - id ответа
   *  @param mixed $text - новый текст
   */
  public function changeAnswerText(
    $id_result,
    $text
  ){
    $data = [
      "answer_text" => $text
    ];
    $this->changeOne("survey_result",$id_result,$data);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает ответственного пользователя в зависимости от поля user_type таблицы survey_question
   *    0 - не считать в рейтинг
   *    1 - считать в рейтинг менеджеру заказа
   *    2 - считать в рейтинг монтажнику
   *    3 - считать в рейтинг склада
   *    4 - считать в управленческий рейтинг
   *  @param mixed $id_result - id таблицы survey_result
   */
  public function setResultUser(
    $id_result
  ){
    $clientbase = new Clientbase($this->mysqli);
    $orderComponent = new Order($this->mysqli);
    $user = new User($this->mysqli);
    $result = $this->oneById("survey_result",$id_result);
    if (count($result) == 0){
      return;
    }
    $order = $clientbase->getOneByOrderId($result["id_order"]);
    $question = $this->oneById("survey_question",$result["id_question"]);
    switch ($question["user_type"]) {
      case '1':
        //Если в заказе есть доставка, нет замера и нет доп выездов, то добавлять рейтинг для added_user иначе для main_user
        $bufUser = $order["main_user"];
        $bufOrder = $orderComponent->getOneByOrderId($result["id_order"]);
        if (is_array($bufOrder) && (count($bufOrder) > 0)){
          if ($bufOrder["delivery"] && !$bufOrder["col_dop_viezd"] && !$bufOrder["zamer_flag"]){
            $bufUser = $order["added"];
          }
        }

        $main_user = $user->getOneByClientbaseUserId($bufUser);
        $id_user = $main_user["id_user"] ?? 0;
        break;

      case "2":
        $montList = $user->getMasterList();
        $mont = $order["mont"];
        $id_user = "0";
        foreach($montList as $key => $value){
          if ($value == $mont){
            $id_user = $key;
          }
        }
        break;
      
      case "3":
        $id_user = "-1";
        break;

      case "4":
        $id_user = "-2";
        break;

      default:
        $id_user = "0";
        break;
    }
    $this->changeOne("survey_result",$id_result,[
      "id_user" => $id_user
    ]);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список вопросов из таблицы survey_question по id_survey
   */
  public function getQuestionList(
    $surveyId
  ){
    $surveyId = $this->mysqli->real_escape_string($surveyId);
    return $this->allGet("survey_question"," `id_survey` = '{$surveyId}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список вопросов из таблицы survey_question
   *  @param mixed $where - блок where
   */
  public function getQuestionAll(
    $where = ""
  ){
    return $this->allGet("survey_question",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   * Возвращает ссылку на опрос после заказа в зависимости от того был ли самовывоз или работа под ключ
   */
  public function makeOrderSurveyLink(
    $orderId
  ){
    $user = new User($this->mysqli);
    $buf = $user->getMasterList();
    $masterList = [];
    $userList = $user->getUserList(false);
    $managerList = [];
    foreach($userList as $row){
      if ($row["id_user_clientbase"]){
        $managerList[$row["id_user_clientbase"]] = $row["id_user"];
      }
    }
    foreach($buf as $id => $number){
      $masterList[$number] = $id;
    }
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `clientbase` where `id_order` = '{$orderId}'");
    if ($req->num_rows > 0){
      $orderData = $req->fetch_assoc();
    }
    else{
      return "";
    }
    $survey = (in_array($orderData["adr"],["самовывоз","-"])) ? "1" : "2"; 
    if(in_array($orderData["adr"],["самовывоз","-"])){
      $userId = $managerList[$orderData["main_user"]];
    }
    else{
      $userId = $masterList[$orderData["mont"]];
    } 
    return "https://03-okna.ru/run_widget_method.php?widget=survey_widget/survey_form&survey={$survey}&id_order={$orderId}&user={$userId}";
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список результатов опросов из таблиы survey_result в виде
   *  [id_survey => [
   *    survey_uniq => [
   *      results => [
   *        id => result[]
   *      ]
   *      data => [
   *        id_survey
   *        id_order
   *        id_user
   *        phone
   *        grade_sum - сумма оценок ответов с типом answer_type = 2
   *        grade_count - количество ответов с типом answer_type = 2
   *        grade_avarage - средняя оценка, если есть ответы с типом answer_type = 2
   *      ]
   *    ]
   *  ]
   *  @param mixed $typeList - список id опросов
   */
  public function getResultList(
    $where = ""
  ){
    $clientbase = new Clientbase($this->mysqli);
    $user = new User($this->mysqli);
    $result = [];
    $list = $this->allGet("survey_result",$where);
    $questionList = $this->allGet("survey_question");
    $answerList = $this->allGet("survey_answer");
    $buf = $user->getMasterList();
    $montList = [];
    foreach($buf as $key => $value){
      $montList[$value] = $key;
    }
    foreach($list as $row){
      $id_survey = $row["id_survey"];
      $uniq = $row["survey_uniq"];
      $id_question = $row["id_question"];
      $id_order = $row["id_order"];
      $question = $questionList[$row["id_question"]];
      $order = $clientbase->getOneByOrderId($id_order);
      $phone = $row["phone"];
      $id_user = "0";
      if ($question["id_survey"] == "1"){
        $id_user = $user->getOneByClientbaseUserId($order["main_user"])["id_user"] ?? "0";
      }
      if ($question["id_survey"] == "2"){
        $id_user = $montList[$order["mont"]] ?? "0";
      }
      $created_at = $row["created_at"];
      if (!isset($result[$id_survey][$uniq])){
        $result[$id_survey][$uniq] = [
          "data" => [
            "id_survey" => $id_survey,
            "id_order" => $id_order,
            "id_user" => $id_user,
            "phone" => $phone,
            "created_at" => $created_at,
            "grade_sum" => 0,
            "grade_count" => 0,
            "grade_average" => 0
          ],
          "result" => []
        ];
      }
      if ($row["id_answer"] != "0"){
        $row["answer_text"] = $answerList[$row["id_answer"]]["text"];
      }
      $row["question_text"] = $questionList[$row["id_question"]]["text"];
      $result[$id_survey][$uniq]["result"][$row["id"]] = $row;
      if (($questionList[$id_question]["answer_type"] == "2") && ($row["answer_text"])){
        $result[$id_survey][$uniq]["data"]["grade_count"]++;
        $result[$id_survey][$uniq]["data"]["grade_sum"] += (int)$row["answer_text"];
        $result[$id_survey][$uniq]["data"]["grade_average"] = round($result[$id_survey][$uniq]["data"]["grade_sum"] / $result[$id_survey][$uniq]["data"]["grade_count"],2);
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/
  
  /**
   *  Возвращает все строки из таблицы survey_result
   *  @param mixed $where - блок where
   */
  public function getSurveyResultAll(
    $where = ""
  ){
    return $this->allGet("survey_result",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает рейтинг пользователей в виде массива [id_user => rating]
   *    -1  - склад
   *     0  - без рейтинга
   *  @param mixed $id_user - id пользователя. Если null то возвращает рейтинг всех пользователей. 
   */
  public function getUserRating(
    $id_mont = null
  ){
    $where = "";
    $resultList = $this->allGet("survey_result",$where);
    $questionList = $this->getQuestionAll();
    $result = [];
    foreach($resultList as $row){
      $id_user = $row["id_user"];
      if ($questionList[$row["id_question"]]["answer_type"] != "2"){
        continue;
      }
      if (!isset($result[$id_user])){
        $result[$id_user] = [
          "count" => 0,
          "sum" => 0
        ];
      }
      $result[$id_user]["count"]++;
      $result[$id_user]["sum"] += (int)$row["answer_text"];
      $result[$id_user]["grade"] = round($result[$id_user]["sum"] / $result[$id_user]["count"],2);
    }
    if ($id_mont){
      if (isset($result[$id_mont])){
        return $result[$id_mont]["grade"];
      }
      else{
        return -1;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает историю рейтинга пользователя из таблицы survey_result
   *  @param mixed $id_user - id пользователя
   */
  public function getUserRatingHistory(
    $id_user
  ){
    $id_user = $this->mysqli->real_escape_string($id_user);
    $where = "`id_user` = '$id_user'";
    $resultList = $this->allGet("survey_result",$where);
    $questionList = $this->getQuestionAll();
    $result = [];
    foreach($resultList as $row){
      if ($questionList[$row["id_question"]]["answer_type"] != "2"){
        continue;
      }
      $row["question_text"] = $questionList[$row["id_question"]]["text"];
      $result[] = $row; 
    }
    usort($result,function($a,$b){
      $t1 = (int)strtotime($a["created_at"]);
      $t2 = (int)strtotime($b["created_at"]);
      return $t2 - $t1;
    });
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строки из таблицы survey_order_queue
   *  @param mixed $where - блок where
   */
  public function orderQueueGetAll(
    $where = ""
  ){
    return $this->allGet("survey_order_queue",$where);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Устанавливает поле status = 1 в строке id таблицы survey_order_queue
   *  @param mixed $id - id строки
   */
  public function orderQueueSend(
    $id
  ){
    $this->changeOne("survey_order_queue",$id,[
      "status" => "1"
    ]);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет заказ id_order в таблицу survey_order_queue
   *  @param mixed $id_order
   */
  public function orderQueueAdd(
    $id_order
  ){
    $id_order = $this->mysqli->real_escape_string($id_order);
    $row = $this->oneGet("survey_order_queue","`id_order` = 'id_order'");
    if (!$row){
      $this->addOne("survey_order_queue",[
        "id_order" => $id_order
      ]);
    }
  }

  /*-----------------------------------------------------------------------*/

}
