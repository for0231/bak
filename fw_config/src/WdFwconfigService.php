<?php
/**
 * @file
 *  工单数据查询服务类
 */
 
namespace Drupal\fw_config;

use Drupal\Core\Database\Connection;

class WdFwconfigService {
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }
  public function selectAll(){
    $data = $this->database->select('fw_config_hostpolicy','c')
      ->fields('c')
      ->execute()
      ->fetchAll();
    return $data;
  }
  public function addlist($datalist) {
    $result = array();
    foreach ($datalist as $items){
      $policy = trim($items[2]);
      if($policy=='屏蔽'){
        $policy= 1;
      }elseif($policy=="忽略"){
        $policy=2;
      }elseif($policy=="宽松(4096)"){
        $policy=4096;
      }
      $result =  $this->database->insert('fw_config_hostpolicy')
      ->fields(array(
        'id'=> intval($items[0]),
        'ip'=> trim($items[1]),
        'policy'=>$policy,
        'value'=> substr(trim($items[5]),2,strlen(trim($items[5])))
      ))
      ->execute();
    }
    return $result;
  }
  public function add($datalist) {
    return $this->database->insert('fw_config_hostpolicy')
      ->fields($datalist)
      ->execute();
  }
  public function update($fields,$ip){
    return $this->database->update('fw_config_hostpolicy')
        ->fields($fields)
        ->condition('ip',$ip)
        ->execute();
  }
  public function delete($value) {
    return $this->database->delete('fw_config_hostpolicy')
      ->condition('value', $value)
      ->execute();
  }
  public function deleteAll(){
    return $this->database->delete('fw_config_hostpolicy')
    ->execute();
  }
}