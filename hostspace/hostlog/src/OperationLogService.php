<?php

namespace Drupal\hostlog;

use Drupal\Core\Database\Connection;

/**
 * 操作日志数据操作类
 */
class OperationLogService {
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
   * 记录操作日志
   */
  public function log($context) {
    $this->database
      ->insert('operation_log')
      ->fields(array(
        'uid' => \Drupal::currentUser()->id(),
        'timestamp' => REQUEST_TIME,
      ) + $context)
      ->execute();
  }

  /**
   * 获取所有的操作记录
   */
  public function getAllLogData($condition = array()) {
    $query = $this->database->select('operation_log','log')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('log');
    foreach($condition as $key => $value) {
      $query->condition($key, '%'. $value .'%', 'LIKE');
    }  
    $query->limit(PER_PAGE_COUNT)
      ->orderBy('timestamp', 'DESC');
     return $query->execute()->fetchAll();
  }

  /**
   *  获取一条操作记录
   */
  public function getLogByLid($lid) {
    $query = $this->database->select('operation_log','log')
      ->fields('log')
      ->condition('lid', $lid);
     return $query->execute()->fetchObject();
  }


  /**
   * 根据条件获取上一条操作记录
   */
  public function getLogDataByCondition($condition = array()) {
    $query = $this->database->select('operation_log','log')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('log');
    if(!empty($condition)) {
      foreach($condition as $con) {
        $query ->condition($con['field'], $con['data'], $con['op']);
      }
    }
    $query->limit(1)->orderBy('lid', 'DESC');
    return $query->execute()->fetchObject();
  }

}
