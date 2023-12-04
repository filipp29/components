<?php

namespace admin\components;

class Price extends BaseComponent{

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /*-----------------------------------------------------------------------*/

  private function getGridPrice($colorId, $gridId, $height, $mountId, $mountLengthBottom, $mountLengthTop, $profileId, $tissueId, $width, $amount, $handleId, $cornerId, $only_cost_price = 0, $impost = 0)
  {

    if (!is_numeric($mountLengthBottom)) $mountLengthBottom = 0; 
    if (!is_numeric($mountLengthTop)) $mountLengthTop = 0; 

    if ($mountId == 18) $mountId = 50;
    else if ($mountId == 22) $mountId = 0;
    else if ($mountId == 47) $mountId = 18;
    else if ($mountId == 50) $mountId = 49;

    if ($cornerId == 48) $cornerId = 46;
    else if ($cornerId == 49) $cornerId = 47;

    $add_mp_1 = $mountLengthTop > $mountLengthBottom ? $mountLengthTop : $mountLengthBottom;

    $is_dealer = "1";

    $data = [
      'id_type' => $gridId,
      'id_tissue' => $tissueId,
      'id_profil' => $profileId,
      'group_1' => $mountId,
      'group_3' => $colorId,
      'group_4' => $handleId,
      'group_5' => $cornerId,
      'group_6' => 0,
      'width' => $width,
      'height' => $height,
      'add_mp_1' => $add_mp_1,
      'only_cost_price' => $only_cost_price,
      'impost' => $impost,
      'is_dealer' => $is_dealer,
    ];

    $result = calculateTypeTissueProfil($data,$this->mysqli);

    if (isset($result['price'])) $result['price'] *= $amount;

    return $result['price'];

  }

  /*-----------------------------------------------------------------------*/

  public function gridPrice($id_type, $id_tissue, $id_profil, $group_1, $group_3, $impost = 0){
      
    $group_4 = 42;
    $group_5 = 0;

    $price = 0;

    if ($id_profil == 3) $group_4 = 0;
    if ($id_profil == 1) $group_5 = 48;

    $itog_price = $this->getGridPrice(
      $group_3,
      $id_type,
      1000, 
      $group_1,
      0, 
      0, 
      $id_profil, 
      $id_tissue, 
      1000, 
      1,
      $group_4,
      $group_5,
      0,
      $impost
    );


    if ($itog_price !== null) $price = $itog_price;

    /*if ($price == 0) {
        $price = self::getPrice03okna($id_type, $id_tissue, $id_profil, $group_1, $group_3);
    }*/

    return $price == 0 ? '-' : $price;

  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает стоимость комплекта с учетом наценки
   *  @param mixed $id_add - id позиции
   */
  public function getAddPriceComplect(
    $id_add
  ){
    global $mysqli_nelson;
    $req = $mysqli_nelson->query("SELECT * from `add` where `id_add` = '{$id_add}'");
    if ($req->num_rows == 0){
      return "-";
    }
    $add = $req->fetch_assoc();
    $formula = $add["formula"];
    preg_match_all("/\[(.+?)\]/", $formula, $matches);
    foreach ($matches[1] as $id_position) {
      $req = $this->mysqli->query("SELECT * from `uslugi` where `id_us` = '{$id_position}'");
      if ($req->num_rows > 0) {
        $usluga = $req->fetch_assoc();
        $sale_pr = $usluga["sale_pr"];
      }
      else{
        $sale_pr = 0;
      }
      $req = $this->mysqli->query("SELECT * from `sklad` where `type` = '1' and `id_acc` = '{$id_position}'");
      if ($req->num_rows > 0) {
        $sklad = $req->fetch_assoc();
        $price = $sklad["price"] + ($sklad["price"] * $sale_pr / 100);
        $formula = str_replace("[$id_position]", $price, $formula);
      }
    }
    $price_cost = eval("return $formula;");
    return ceil($price_cost);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает стоимость услуги с учетом наценки
   *  @param mixed $id_us - id услуги
   */
  public function getUslugiSalePrice(
    $id_us
  ){
    $req = $this->mysqli->query("SELECT * from `sklad` where `type` = '1' and `id_acc` = '{$id_us}'");
    if ($req->num_rows > 0) {
      $sklad = $req->fetch_assoc();
      $price = $sklad["price"];
    }
    else{
      return "-";
    }
    $req = $this->mysqli->query("SELECT * from `uslugi` where `id_us` = '{$id_us}'");
    if ($req->num_rows > 0) {
      $usluga = $req->fetch_assoc();
      $sale_pr = $usluga["sale_pr"];
    }
    else{
      $sale_pr = 0;
    }
    return round($price + ($price * $sale_pr / 100),1);
  }

  /*-----------------------------------------------------------------------*/


}