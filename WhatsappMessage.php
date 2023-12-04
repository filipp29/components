<?php

namespace admin\components;

class WhatsappMessage extends BaseComponent{

  private $url = "https://api.1msg.io/THO731123669/";
  private $token = "9KZSpVRNZffLDuC7orJuGECmGblLnPeM";
  private $namespace = "b0336a53_9b1b_442c_b267_39e7dffa3531";
  private $templateList = [
    "opros_kachestvo" => [
      [
        "type" => "body",
        "parameters" => [
          [
          "type" => "text",
          "text" => "name"
          ]
        ]
      ],
      [
        "type" =>"button",
        "sub_type" => "url",
        "parameters" => [
          [
          "type" => "text",
          "text" => "short_link"
          ]
        ]
      ]
    ],
    "uvedomlenie_opros" => [
      [
        "type" => "body",
        "parameters" => [
          [
            "type" => "text",
            "text" => "name"
          ],
          [
            "type" => "text",
            "text" => "order"
          ],
          [
            "type" => "text",
            "text" => "sum"
          ]
        ]
      ],
      [
        "type" =>"button",
        "sub_type" => "url",
        "parameters" => [
          [
          "type" => "text",
          "text" => "short_link"
          ]
        ]
      ]
    ]
  ];

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
   *  Добавляет сообщение в таблицу whatsapp_message
   *  @param mixed $text - текст сообщения
   */
  public function addMessage(
    $text
  ){
    $text = $this->mysqli->real_escape_string($text);
    $this->mysqli->query("INSERT INTO `whatsapp_message` (`text`) VALUES ('{$text}')");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Создает массив из шаблона template  с параметрами params
   *  @param mixed $templateName - ключ шаблона из массива templateList
   *  @param mixed $params - массив с параметрами шаблона. Ключи должны соответствовать ключам из шаблона
   */
  private function getTemplate(
    $templateName,
    $params = []
  ){
    $template = $this->templateList[$templateName] ?? null;
    if (!$template){
      return false;
    }
    foreach($template as $key => $row){
      foreach($row["parameters"] as $pKey => $param){
        if (isset($param["text"])){
          $template[$key]["parameters"][$pKey]["text"] = $params[$param["text"]] ?? "";
        }
      }
      
    }
    return $template;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Отправляет шаблонное сообщение templateName на номер phone
   *  @param mixed $templateName - наименование шаблона
   *  @param mixed $phone - 11-значный номер телефона без плюса - 79523331122
   *  @param mixed $params - параметры для вставки в шаблон
   */
  public function sendTemplateToPhone(
    $templateName,
    $phone,
    $params = []
  ){
    $template = $this->getTemplate($templateName,$params);
    if (!$template){
      return;
    }
    $data = [
      "namespace" => $this->namespace,
      "template" => $templateName,
      "language" => [
        "policy" => "deterministic",
        "code" => "ru"
      ],
      "params" => $template,
      "phone" => $phone
    ];
    $json = json_encode($data,JSON_UNESCAPED_UNICODE);
    $response = $this->sendPost("sendTemplate",$json);
    $this->addHistory($phone,"1",$json,$response);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Добавляет сообщение в историю в таблицу whatsapp_history 
   *  @param mixed $phone - номер телефона
   *  @param mixed $type - тип сообщения (0 - прочее, 1 - опрос качества обслуживания)
   *  @param mixed $data - json строка отправленного сообщения
   *  @param mixed $response - json строка ответа сервиса
   */
  private function addHistory(
    $phone,
    $type,
    $data,
    $response
  ){
    $this->addOne("whatsapp_history",compact(["phone","type","data","response"]));
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список сообщений отправленных на номер phone из таблицы whatsapp_history
   *  @param mixed $phone - номер телефона
   */
  public function getPhoneHistory(
    $phone
  ){
    $phone = $this->mysqli->real_escape_string($phone);
    $list = $this->allGet("whatsapp_history","`phone` = '{$phone}'");
    $result = [];
    foreach($list as $row){
      $row["data"] = json_decode($row["data"],true);
      $row["response"] = json_decode($row["response"],true);
      $result[] = $row;
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Отправляет post запрос на this->url/action телом body 
   *  @param mixed $action - метод api
   *  @param mixed $body - тело запроса в виде json строки
   */
  private function sendPost(
    $action,
    $body
  ){
    $url = "{$this->url}{$action}?token={$this->token}";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json',
      'Content-Length: ' . strlen($body))
    );
    // curl_setopt($ch, CURLOPT_URL, $url);
    // curl_setopt($ch, CURLOPT_POST, 1);
    // curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
  }
  
  /*-----------------------------------------------------------------------*/

  // /**
  //  *  Отправляет сообщение text на dialogId
  //  *  @param mixed $dialogd - dialogId для отправки
  //  *  @param mixed $text - текст сообщения
  //  */
  // private function sendMessageToDialog(
  //   $dialogId,
  //   $text
  // ){
  //   $params = [
  //     "dialogId" => $dialogId,
  //     "text" => $text
  //   ];
  //   return $this->sendPost("message/send",$params);
  // }

  /*-----------------------------------------------------------------------*/

  // /**
  //  *  Создает диалог с phone возвращает ассоциативный массив
  //  *  [
  //  *    status : true|false
  //  *    data : [
  //  *            id : id диалога
  //  *            url : ссылка на диалог в teletype 
  //  *           ]
  //  *    errors : []
  //  *    errorsType : null
  //  *  ]
  //  * 
  //  *  @param mixed $phone - 11-значный номер телефона без плюса - 77777777777
  //  */
  // public function dialogCreate(
  //   $phone
  // ){
  //   $params = [
  //     "channelId" => $this->channelId,
  //     "clientPhone" => $phone
  //   ];
  //   $request = json_decode($this->sendPost("dialog/create",$params),true);
  //   return $request;
  // }

  /*-----------------------------------------------------------------------*/

}
