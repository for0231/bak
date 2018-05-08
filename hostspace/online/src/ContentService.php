<?php

namespace Drupal\online;

use Drupal\Core\Database\Connection;

class ContentService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }
  public function updateStatus($id){
    $row= $this->database->update('online_content_field_data')
      ->fields(array('status'=> true))
      ->condition('id',$id)
      ->execute();
    return $row;
  }
 
  public function insertOnline($name, $email){
    $entity = entity_create('online_content', array(
      'ask_name' => $name,
      'email' => $email,
      'remote_iP' => $_SERVER["REMOTE_ADDR"],
      'status' => '0'
    ));
    $entity->save();
    return $entity;
  }
  
  /**
   * 未接收的人数
   */
  public function selectOnlineBystatus($id) {
    $entity =  entity_load('online_content', $id);
    if($entity->get('status')->value) {
      return 'ok';
    }
    $itmes = entity_load_multiple_by_properties('online_content', array('status' => 0));
    return count($itmes);
  }
  
  public function getRelyContent($askId){
    $data = $this->database->select('online_content_field_data','k')
      ->fields('k')
      ->condition('id',$askId)
      ->execute()
      ->fetchAll();
    return $data;
  }
  
  public function insertContent($sender,$content,$receiver) {
    $entity = entity_create('realy_content',array(
      'sender' => $sender,
      'content' => $content,
      'receiver' => $receiver,
    ));
    $entity->save();
    return $entity;
  }
  public function selectContentByuser($uid) {
    $entitys = entity_load_multiple_by_properties('sender',$uid);
    return $entitys;
  }
  public function selectContentByadmin($receiver) {
    return  entity_load_multiple_by_properties('receiver',$receiver);
  }
}

