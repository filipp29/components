<?php

namespace admin\components;
use yii\db\Query;

class OrderProduct extends BaseComponent{

  private $groupList;
  private $addList;
  private $typeList;
  private $tissueList;
  private $profilList;

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }


  /*-----------------------------------------------------------------------*/

  public function __construct(
    $mysqli
  ){
    parent::__construct($mysqli);
    $this->initNameList();
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает строки из таблицы order_product по orderId
   *  @param mixed $orderId - id заказа
   *  @return array
   */
  public function getOrderProductList(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `order_product` where `id_order` = '{$orderId}'");
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
   *  Сохраняет файлы подтверждения коэффициента и добавляет соответствующие записи в таблицу order_koef_file
   *  @param mixed $tableName - наименование таблицы позиции
   *  @param mixed $ai - ai позиции
   *  @param mixed $file - массив с данными файлов из $_FILE
   */
  public function saveKoefFile(
    $tableName,
    $ai,
    $file
  ){
    for ($f = 0; $f < count($file['name']); $f++) {

      if ($file['name'][$f] == "") continue;

      $file_type = $file['type'][$f];
      $file_tmp_name = $file['tmp_name'][$f];
      $file_size = $file['size'][$f];
      $file_name = $file['name'][$f];
      $ext = explode(".", $file_name);
      $ext = trim($ext[count($ext) - 1]);

      if ($ext != "png" and $ext != "jpg" and $ext != "jpeg" and $ext != "gif" and $ext != "pdf" and $ext != "doc" and $ext != "docx") {
          $mist = $mist . "Загружать можно только изображения формата png, gif, jpg, jpeg или документы формата .pdf, .doc, .docx";
          continue;
      }

      $newFilePath = "uploaded/" . uniqid() . "." . $ext;
      $createdAt = date("Y-m-d H:i:s",time());
      $userId = $_SESSION["id_user"];

      if (move_uploaded_file($file_tmp_name, $newFilePath)) {
          $this->mysqli->query("INSERT INTO `order_koef_file` SET `tablename` = '{$tableName}', `ai` = '{$ai}', `file` = '{$newFilePath}', `created_at` = '{$createdAt}', `id_user` = '{$userId}' ");
      }
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список файлов подтверждения коэффициента из таблицы order_koef_file
   *  @param mixed $tablename - наименование таблицы
   *  @param mixed $ai - ai позиции
   */
  public function getKoefFileList(
    $tablename,
    $ai
  ){
    $tablename = $this->mysqli->real_escape_string($tablename);
    $ai = $this->mysqli->real_escape_string($ai);
    $req = $this->mysqli->query("SELECT * from `order_koef_file` where `tablename` = '{$tablename}' and `ai` = '{$ai}'");
    $result = [];
    if ($req){
      while($row = $req->fetch_assoc()){
        $result[$row["id"]] = $row;
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Удаляет строку id и таблицы order_koef_file
   *  @param mixed $id - id строки
   */
  public function deleteKoefFile(
    $id
  ){
    $id = $this->mysqli->real_escape_string($id);
    $this->mysqli->query("DELETE from `order_koef_file` where `id` = '{$id}'");
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Сохраняет комментарий в таблицу order_koef_comment
   *  @param mixed $tablename - наименование таблицы
   *  @param mixed $ai - ai позиции
   *  @param mixed $text - текст комментария
   */
  public function saveKoefComment(
    $tablename,
    $ai,
    $text,
    $mont_time = "0"
  ){
    
    $tablename = $this->mysqli->real_escape_string($tablename);
    $ai = $this->mysqli->real_escape_string($ai);
    $text = $this->mysqli->real_escape_string($text);
    $req = $this->mysqli->query("SELECT * from `order_koef_comment` where `tablename` = '{$tablename}' and `ai` = '{$ai}'");
    if ($req){
      $row = $req->fetch_assoc();
      if ($row){
        $id = $row["id"];
        $this->mysqli->query("UPDATE `order_koef_comment` SET `text` = '{$text}', `mont_time` = '{$mont_time}' where `id` = '{$id}'");
      }
      else{
        $createdAt = date("Y-m-d H:i:s",time());
        $userId = $_SESSION["id_user"];
        $this->mysqli->query("INSERT into `order_koef_comment` (`tablename`,`ai`,`text`,`mont_time`,`created_at`,`id_user`) VALUES ('{$tablename}','{$ai}','{$text}','{$mont_time}','{$createdAt}','{$userId}')");
      }
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает комментарий из таблицы order_koef_comment
   *  @param mixed $tablename - наименование таблицы
   *  @param mixed $ai - ai позиции
   */
  public function getKoefComment(
    $tablename,
    $ai
  ){
    $tablename = $this->mysqli->real_escape_string($tablename);
    $ai = $this->mysqli->real_escape_string($ai);
    $req = $this->mysqli->query("SELECT * from `order_koef_comment` where `tablename` = '{$tablename}' and `ai` = '{$ai}'");
    if ($req){
      $row = $req->fetch_assoc();
      return $row["text"];
    }
    else{
      return "";
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает время на монтаж из таблицы order_koef_comment
   *  @param mixed $tablename - наименование таблицы
   *  @param mixed $ai - ai позиции
   */
  public function getMontTime(
    $tablename,
    $ai
  ){
    $tablename = $this->mysqli->real_escape_string($tablename);
    $ai = $this->mysqli->real_escape_string($ai);
    $req = $this->mysqli->query("SELECT * from `order_koef_comment` where `tablename` = '{$tablename}' and `ai` = '{$ai}'");
    if ($req){
      $row = $req->fetch_assoc();
      return $row["mont_time"];
    }
    else{
      return "";
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список сеток заказа orderId
   *  @param mixed $orderId - id заказа
   */
  public function getSetkaList(
    $orderId
  ){
    $orderId = $this->mysqli->real_escape_string($orderId);
    $req = $this->mysqli->query("SELECT * from `order_product` where `id_order` = '{$orderId}'");
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
   *  Инициализирует массивы с наименованиями
   */
  private function initNameList(){
    $req = $this->mysqli->query("SELECT * from `group`");
    while($row = $req->fetch_assoc()){
      $this->groupList[$row["id_group"]] = $row["name"];
    }
    $req = $this->mysqli->query("SELECT * from `add`");
    while($row = $req->fetch_assoc()){
      $this->addList[$row["id_add"]] = $row["name"];
    }
    $req = $this->mysqli->query("SELECT * from `type`");
    while($row = $req->fetch_assoc()){
      $this->typeList[$row["id_type"]] = $row["name"];
    }
    $req = $this->mysqli->query("SELECT * from `tissue`");
    while($row = $req->fetch_assoc()){
      $this->tissueList[$row["id_tissue"]] = $row["name"];
    }
    $req = $this->mysqli->query(("SELECT * from `profil`"));
    while($row = $req->fetch_assoc()){
      $this->profilList[$row["id_profil"]] = $row["name_db"];
    }
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает текстовое представление строки ai из таблицы order_product
   *  @param mixed $ai - ai строки
   */
  public function getOrderProductName(
    $ai
  ){
    
    $ai = $this->mysqli->real_escape_string($ai);
    $req = $this->mysqli->query("SELECT * from `order_product` where `ai` = '{$ai}'");
    $result = [];
    if ($req){
      $row = $req->fetch_assoc();
      for($i = 1; $i <= 6; $i++){
        $key = "group_{$i}";
        if ($row[$key]){
          $result[$key] = [
            "name" => $this->groupList[$i],
            "value" => $this->addList[$row[$key]]
          ];
        }
      }
      $paramList = [
        "col" => "Количество",
        "height" => "Высота",
        "width" => "Ширина",
        "price_total" => "Сумма"
      ];
      foreach($paramList as $key => $value){
        $result[$key] = [
          "name" => $value,
          "value" => $row[$key]
        ];
      }
      $paramList = [
        "type" => "typeList",
        "tissue" => "tissueList",
        "profil" => "profilList"
      ];
      $nameList = [
        "type" => "Тип",
        "tissue" => "Полотно",
        "profil" => "Профиль"
      ];
      foreach($paramList as $key => $value){
        $result[$key] = [
          "name" => $nameList[$key],
          "value" => $this->{$value}[$row[$key]]
        ];
      }
    }
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает текстовое представление позиции ai из таблицы tableName
   *  @param mixed $tableName - наименование таблицы
   *  @param mixed $ai - ai позиции
   */
  public function getProductName(
    $tableName,
    $ai
  ){
    
    $methodName = "get". str_replace("_","",mb_convert_case($tableName,2)). "Name";
    return $this->{$methodName}($ai);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает расчетное время на монтаж
   *  @param mixed $tablename - наименование таблицы
   *  @param mixed $ai - ai позиции
   */
  public function getCalculatedMontTime(
    $tablename,
    $id_order_ai
  ){  
    $mysqli = $this->mysqli;
    $res_product = $this->oneById($tablename, $id_order_ai);
    if (!$res_product){
      return "0";
    }

    // Проходим по сеткам
    if (in_array($tablename,["order_product","order_product_meneger"])){

        $col = $res_product['col'];
        $group_2 = $res_product['group_2'];
        $type = $res_product['type'];

        $res_type_add_price = get_type_add_price($type, $group_2, $mysqli);

        return $col * $res_type_add_price['time_mont'];
    }
    


    // Проходим по всем аксессуарам
    if (in_array($tablename,["order_product_acc","order_product_acc_meneger"])){

        $col = $res_product['col'];
        $id_acc = $res_product['id_acc'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];
        if ($koef == 0) $koef = 1;

        $res_us = get_uslugi($id_acc, $mysqli);
        return $col * $koef * $res_us['time_mont'];

    }

    // Надо проставить время на монтаж по жалюзи. Где это сделать?
    // 20 мин изделие - монтаж
    // 5 мин изделние - замер 
    if (in_array($tablename,["order_product_blinds","order_product_blinds_meneger"])){

        $col = $res_product['col'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];
        $mont_type = $res_product['mont_type'];

        return $col * ($mont_type == 1 ? 50 : 20);


    }


    // Проходим по всем стеклопакетам
    // Замер стеклопакета или стекла в окне ПВХ	10 мин
    // Замер стеклопакета или стекла в окне Алюминька	10 мин
    // Замер стеклопакета в дереве	Не известно, наши мастера не умеют
    // Установка стеклопакета или стекла в ПВХ, с учетом демонтажа старого стеклопакета.	20 мин
    // Установка стеклопакета или стекла в Алюминьке, с учетом демонтажа старого стеклопакета.	20 мин
    // Замер замены стекла на стеклопакет	20 мин
    // Работа по замене стекла на стеклопакет	20 мин
    if (in_array($tablename,["order_product_steklopaket_podogrevom_meneger","order_product_steklopaket_podogrevom","order_product_steklopaket","order_product_steklopaket_meneger"])){

        $col = $res_product['col'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];


        return $koef * $col * 20;

    }


    // Проходим по всем стеклам
    // Замер стеклопакета или стекла в окне ПВХ	10 мин
    // Замер стеклопакета или стекла в окне Алюминька	10 мин
    // Замер стеклопакета в дереве	Не известно, наши мастера не умеют
    // Установка стеклопакета или стекла в ПВХ, с учетом демонтажа старого стеклопакета.	20 мин
    // Установка стеклопакета или стекла в Алюминьке, с учетом демонтажа старого стеклопакета.	20 мин
    if (in_array($tablename,["order_product_steklo","order_product_steklo_meneger"])){

        $col = $res_product['col'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];


        return $koef * $col * 20;

    }


    // Проходим по всем пленкам
    // Замер одной секции.	4 мин
    // Установка всех пленок, кроме бронирующей при размерах до 1 м.кв.	25
    // Установка всех пленок, кроме бронирующей при размерах от 1 до 2 м.кв.	30
    // Установка бронепленки 100 мкрм при размерах до 1 м.кв.	35
    // Установка бронепленки 100 мкрм при размерах от 1 до 2 м.кв.	40
    // Установка бронепленки 200 мкрм при размерах до 1 м.кв.	35
    // Установка бронепленки 200 мкрм при размерах от 1 до 2 м.кв.	40
    // Установка бронепленки 300 мкрм при размерах до 1 м.кв.	40
    // Установка бронепленки 300 мкрм при размерах от 1 до 2 м.кв.	45
    if (in_array($tablename,["order_product_plenka","order_product_plenka_meneger"])){

        $col = $res_product['col'];
        $width = $res_product['width'];
        $height = $res_product['height'];
        $mont = $res_product['mont'];
        $plenka = $res_product['plenka'];
        $id_type_vid = $res_product['id_type_vid'];
        $koef = $res_product['koef'];

        $m2 = $width * $height / 1000000;



        if ($plenka == 17) { // Бронирующая пленка

            if ($id_type_vid == 12) {

                // 200 мкм
                if ($m2 <= 1) return $col * $koef * 35;
                else return $col * $koef * 40;

            } elseif ($id_type_vid == 20) {
                // 300 мкм
                if ($m2 <= 1) return $col * $koef * 40;
                else return $col * $koef * 45;

            } else {
                // Осальные воспринимаем как 100мкм
                if ($m2 <= 1) return $col * $koef * 35;
                else return $col * $koef * 40;

            }

        } else {

            if ($m2 <= 1) return $col * $koef * 25;
            else return $col * $koef * 30;

        }


    }


    // Проходим по всем подоконникам
    // Замер подоконника.	10 мин
    // Демонтаж старого подоконника. 1 шт	30 мин
    // Установка подоконника * на коэффициент сложности – 1 шт.	60 мин
    if (in_array($tablename,["order_product_podokonnik","order_product_podokonnik_meneger"])){

        $col = $res_product['col'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];
        $demont = $res_product['demont'];


        $time = $col * $koef * 60;

        if ($demont == 1) {
            $time = $time + $col * 30;
        }
        return $time;

    }


    // Проходим по всем накладкам
    // Замер накладки	10 мин
    // Установка накладки на подоконник * на коэффициент сложности – 1 шт.	60 мин
    if (in_array($tablename,["order_product_podokonnik_nakladka_new","order_product_podokonnik_nakladka_new_meneger"])){

        $col = $res_product['col'];
        $koef = $res_product['koef'];
        $mont = $res_product['mont'];

        return $col * $koef * 60;

    }


    // Проходим по всем откосам
    // Замер	5 мин
    // Установка одной панели * коэф.сложности	60 мин
    if (in_array($tablename,["order_product_otkos","order_product_otkos_meneger"])){

        $col = $res_product['col'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];


        return $col * $koef * 60;

    }

    // Проходим по всем отливам
    // Замер отлива	10 мин
    // Установка отлива * коэф. сложности	30 мин
    // Демонтаж старого отлива.	15 мин
    if (in_array($tablename,["order_product_otliv","order_product_otliv_meneger"])){
  
        $buf = get_uslugi("841",$mysqli);
        $time_mont = (int)$buf["time_mont"];
        if (!$time_mont){
            $time_mont = 30;
        }
        $col = $res_product['col'];
        $mont = $res_product['mont'];
        $koef = $res_product['koef'];
        $demont = $res_product['demont'];

        $time = $col * $koef * $time_mont;

        if ($demont == 1) {
            $time = $time + $col * 15;
        }
        return $time;

    }
  }

  /*-----------------------------------------------------------------------*/

}
