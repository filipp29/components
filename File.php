<?php

namespace admin\components;

class File extends BaseComponent{

  /*-----------------------------------------------------------------------*/

  /**
   *  Возвращает экземпляр класса
   */
  public static function slf(){
    global $mysqli;
    return new static($mysqli);
  }

  /**
   *  Сохраняет файл из $_FILE на диск и возвращает ссылку на него
   *  @param mixed $fileName - имя файла в массиве $_FILE
   *  @param mixed $index - индекс файла если передан массив файлов fileName
   */
  public function saveFile(
    $fileName,
    $index = null
  ){
    if (!isset($_FILES[$fileName])){
      return "";
    }
    $file_tmp_name = $index ? $_FILES[$fileName]['tmp_name'][$index] : $_FILES[$fileName]['tmp_name'];
    $file_name = $index ? $_FILES[$fileName]['name'][$index] : $_FILES[$fileName]['name'];
    $ext = explode(".", $file_name);
    $ext = trim($ext[count($ext) - 1]);

    $newFilePath = "uploaded/" . uniqid() . ".$ext";

    move_uploaded_file($file_tmp_name, $newFilePath);
    return $newFilePath;
  }

}
