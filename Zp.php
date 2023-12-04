<?php

namespace admin\components;

class Zp extends BaseComponent{

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
   *  Возвращает бонус за выполнение плана по обычным заказам
   *  @param mixed $percent - процент выполненного плана 
   *  @param mixed $sum - сумма выполненного плана
   */
  public function getOrderPlanReward(
    $percent,
    $sum
  ){
    if ($percent < 40) {
      $result = 0;
    } elseif ($percent >= 40 and $percent < 60) {
      $result = $sum * 0.05 * 0.5;
    } elseif ($percent >= 60 and $percent < 80) {
      $result = $sum * 0.05 * 0.75;
    } elseif ($percent >= 80 and $percent < 110) {
      $result = $sum * 0.05 * 1;
    } elseif ($percent >= 110) {
      $result = $sum * 0.05 * 1.25;
    }

    return round($result);
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает бонус за закаы свыше 100,000
   *  @param mixed $sum - прибыль за заказы
   */
  public function getExpensiveOrderReward(
    $sum
  ){
    return $sum * 0.15;
  }

  /*-----------------------------------------------------------------------*/

}
