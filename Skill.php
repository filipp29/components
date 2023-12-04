<?php

namespace admin\components;

class Skill extends BaseComponent{

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
   *  Добавляет строку в таблицу skill
   *  @param mixed $name - наименование
   *  @param mixed $parent - id родительского элемента. Если parent = 0, то элемент корневой
   *  @param mixed $is_group - указывает является ли элемент группой
   *  @param mixed $sort - значение сортировки
   */
  public function addSkill(
    $name,
    $parent,
    $is_group,
    $sort = "0"
  ){
    $data = compact(
      "name",
      "parent",
      "sort"
    );
    $data["is_group"] = $is_group ? true : false;
    $result = $this->addOne("skill",$data);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает все строки из таблицы skill
   *  @param mixed $where - строка where 
   */
  public function getAll(
    $where = ""
  ){
    return $this->allGet("skill",$where);
  }

  /*-----------------------------------------------------------------------*/
  
  /**
   *  Возвращает дерево групп и умений из таблицы skill в виде массива
   *  [
   *    group1 => [
   *      element1
   *      group2 => [
   *        element2
   *      ]
   *    ]
   *    element3
   *  ]
   */
  public function getTree(){
    $skillList = $this->getAll();
    $root = [];
    $children = [];
    foreach($skillList as $id => $skill){
      if ($skill["parent"] == "0"){
        $root[$id] = $skill;
      }
      else{
        $id_parent = $skill["parent"];
        $children[$id_parent] = $children[$id_parent] ?? [];
        $children[$id_parent][$id] = $skill;
      }
    }
    $count = 0;
    $makeBranch = function(
      $branch
    )use($children,&$count,&$makeBranch,$skillList){
      if ($count > 20){
        return [];
      }
      $count++;
      $result = [];
      foreach($branch as $id => $row){
        if ($row["is_group"]){
          $result[$id] = isset($children[$row["id"]]) ? $makeBranch($children[$row["id"]]) : [];
        }
        else{
          $result[$id] = $id;
        }
      }
      $sort = function($a,$b)use($skillList){
        $t1 = $skillList[$a];
        $t2 = $skillList[$b];
        if (!$t1["is_group"] && $t2["is_group"]){
          return -1;
        }
        else if ($t1["is_group"] && !$t2["is_group"]){
          return 1;
        }
        else{
          return (int)$t1["sort"] - (int)$t2["sort"];
        }
      };
      uksort($result,$sort);
      return $result;
    };
    $result = $makeBranch($root);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает список навыков в виде массива [row]
   */
  public function getSkillList(){
    $skillTree = $this->getTree();
    $skillList = $this->getAll();
    $result = [];
    $addName = function(
      $branch,
      $level
    )use($skillList,&$result,&$addName){
      foreach($branch as $id => $row){
        $skill = $skillList[$id];
        $skill["level"] = $level;
        $result[] = $skill;
        if ($skill["is_group"] && is_array($row)){
          $addName($row,$level + 1);
        }
      }
    };

    $addName($skillTree,0);
    return $result;
  }

  /*-----------------------------------------------------------------------*/

  /**
   *  Изменяет строку id в таблице skill
   *  @param mixed $id - id строки
   *  @param mixed $data - данные для изменения
   */
  public function changeSkill(
    $id,
    $data
  ){
    $this->changeOne("skill",$id,$data);
  }

  /*-----------------------------------------------------------------------*/

  

}
