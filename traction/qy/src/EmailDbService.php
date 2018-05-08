<?php

namespace Drupal\qy;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class EmailDbService {
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
   * 增加邮件地址
   */
  public function add_email(array $values) {
    return $this->database->insert('qy_email')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改邮件地址
   */
  public function update_email(array $values, $id) {
    $this->database->update('qy_email')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除邮箱
   */
  public function del_email($id) {
    $this->database->delete('qy_email')
      ->condition('id', $id)
      ->execute();
  }
  
  /**
   * 获取指定邮箱
   */
  public function load_emailById($id) {
     return $this->database->select('qy_email', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 查询
   */
  public function load_email(array $conditions = array()) {
    $query = $this->database->select('qy_email', 't')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        if($value['op'] == 'like') {
          $query->condition($key, $value['value'] . '%', 'like');
        } else if($value['op'] == 'or') {
           $orCondition = $query->orConditionGroup();
           foreach($value['or_field'] as $or_item) {
             if(isset($or_item['value'])) {
               $orCondition->condition($or_item['name'], $or_item['value'], $or_item['op']);
             } else {
               $orCondition->condition($or_item['name'], NULL, 'IS NULL');
             }
           }
           $query->condition($orCondition);
        } else {
          $query->condition($key, $value['value'], $value['op']);
        }
      } else {
        $query->condition($key, $value);
      }
    }
    return $query->limit(20)
      ->execute()->fetchAll();
  }

  /**
   * 查询没有分页
   */
  public function load_email_nopage(array $conditions = array(), $order = null) {
    $query = $this->database->select('qy_email', 't')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        if($value['op'] == 'like') {
          $query->condition($key, $value['value'] . '%', 'like');
        } else if($value['op'] == 'or') {
           $orCondition = $query->orConditionGroup();
           foreach($value['or_field'] as $or_item) {
             if(isset($or_item['value'])) {
               $orCondition->condition($or_item['name'], $or_item['value'], $or_item['op']);
             } else {
               $orCondition->condition($or_item['name'], NULL, 'IS NULL');
             }
           }
           $query->condition($orCondition);
        } else {
          $query->condition($key, $value['value'], $value['op']);
        }
      } else {
        $query->condition($key, $value);
      }
    }
    if(!empty($order)) {
      $query->orderBy($order, 'DESC');
    }
    return $query->execute()->fetchAll();
  }
}
