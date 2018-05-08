<?php

namespace Drupal\utils;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class NetworkConfigService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * 增加路由器
   */
  public function insertLog($value) {
    return $this->database->insert('network_config_log')
      ->fields($value)
      ->execute();
  }
  
  public function updateLog($value, $id) {
    return $this->database->update('network_config_log')
      ->fields($value)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 日志列表
   */
  public function logList($conditions = array()) {
    $query = $this->database->select('network_config_log', 't')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if($key == 'begin_time') {
        $query->condition('created', $value, '>');
      } else if($key == 'end_time') {
        $query->condition('created', $value, '<');
      } else if($key == 'keyword') {
        $query->condition('command', '%' . $value. '%', 'LIKE');
      } else {
        $query->condition($key, $value);
      }
    }
    $query->orderBy('id', 'DESC');
    return $query->limit(20)
      ->execute()
      ->fetchAll();
  }
}
