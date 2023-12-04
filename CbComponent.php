<?php

namespace admin\components;

class CbComponent extends BaseComponent{
  
  private $authKey;
  private $loginCb;
  private $accessId;

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /*-----------------------------------------------------------------------*/

  /*-----------------------------------------------------------------------*/

  /**
   *  Конструктор
   *  @param mixed $useDefaultLogin - если true то использовать login и authkey к КБ по умолчанию
   */
  public function __construct(
    \mysqli $mysqli,
    $useDefaultLogin = false
  ){
    parent::__construct($mysqli);
    $res_user = \get_user($_SESSION['id_user'], $mysqli);
    $this->authKey = $res_user['api_key'];
    $this->loginCb = $res_user['login_cb'];
    if ($useDefaultLogin){
      $this->accessId = auth_clientbase();
    }
    else{
      $this->accessId = auth_clientbase($this->authKey,$this->loginCb);
    }
    
  }

  /*-----------------------------------------------------------------------*/

  /**
  *   Получение заказа из КБ по line
  *   @param $line - поле line из таблицы clientbase 
  */

  public function getOrderFromKbByLine(
    $line
  ){
    $access_id = $this->accessId;

    $command_data = [
      "access_id" => $access_id,
      "table_id" => 311,
      "cals" => true,
      "filter" => [
        'row' => [
          'id' => [
            'term' => '=',
            'value' => $line,
            'union' => 'AND',
          ],
        ],
      ],
      "fields" => [
        // "row" => [
        //   "f5641",
        //   "f7431"
        // ],
      ],
      "start" => 0,
      "limit" => 100000,
    ];
    

    $result = send_command_server('https://oknapomoshch.clientbase.ru/api/data/read/', $command_data);
    if (isset($result['data'][$line]['row'])){
        return $result['data'][$line]['row'];
    }
    else{
        return [];
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
  *   Получение всех заказов из КБ
  *   @param $row - массив полей 
  */

  public function getAllOrdersFromKbBy(
    $row,
    $filter = []
  ){
    $access_id = $this->accessId;

    $command_data = [
        "access_id" => $access_id,
        "table_id" => 311,
        "cals" => true,
        "filter" => [],
        "fields" => [
            "row" => $row,
        ],
        "start" => 0,
        "limit" => 100000,
    ];

    $result = send_command_server('https://oknapomoshch.clientbase.ru/api/data/read/', $command_data);
    if (isset($result['data'])){
        return $result['data'];
    }
    else{
        return null;
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
  *   Получение всех сеток из КБ
  *   @param $row - массив полей 
  */
  public function getAllSetka(
    $row
  ){
    $access_id = $this->accessId;

    $command_data = [
        "access_id" => $access_id,
        "table_id" => 351,
        "cals" => true,
        "filter" => [
          
        ],
        "fields" => [
            "row" => $row,
        ],
        "start" => 0,
        "limit" => 100000,
    ];

    $result = send_command_server('https://oknapomoshch.clientbase.ru/api/data/read/', $command_data);
    if (isset($result['data'])){
        return $result['data'];
    }
    else{
        return null;
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Вовращает true если в КБ установлен ответственный, иначе возвращает false
   *  @param mixed $orderId - id заказа
   *  @return bool
   */
  public function mainUserAdded(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `clientbase` where `id_order` = '{$orderId}'");
    if ($req){
      $row = $req->fetch_assoc();
      $cbData = $this->getOrderFromKbByLine($row["line"]);
      if (isset($cbData["f8391"]) && $cbData["f8391"]){
        return true;
      }
    }
    
    return false;
  }

  /*-----------------------------------------------------------------------*/

  public function getOrderFieldList(){
    $command_data_read = [
        "access_id" => $this->accessId,
        "id" => 311,
    ];
    $result_data_read = send_command_server('https://oknapomoshch.clientbase.ru/api/table/info/', $command_data_read);
    return $result_data_read;
  }

  /*-----------------------------------------------------------------------*/

}
