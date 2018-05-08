<?php
/**
 * @file
 * 操作part实体表
 */
namespace Drupal\part;

use Drupal\Core\Database\Connection;

class PartService {

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
   * part entity table save
   */
  public function save($entity, $update = TRUE) {
    $part_id = 0;
    if($update) {
      $type = $entity->getEntityTypeId();
      $ppid = $entity->id();
      $arr_entity =  entity_load_multiple_by_properties('part', array(
        'ppid' => $ppid,
        'type' => $type
      ));
      $part_entity = current($arr_entity);
      $part_entity->set('brand', $entity->get('brand')->value);
      $part_entity->set('model', $entity->get('model')->value);
      $part_entity->set('standard', $entity->get('standard')->value);
      $stock = $part_entity->get('stock')->value +  $entity->get('stock')->value;
      $part_entity->set('stock', $stock);
      $part_entity->save();
      $part_id = $part_entity->id();
    } else {
      $part_entity = entity_create('part', array(
        'type' => $entity->getEntityTypeId(),
        'brand' => $entity->get('brand')->value,
        'model' => $entity->get('model')->value,
        'standard' => $entity->get('standard')->value,
        'stock' => $entity->get('stock')->value,
        'stock_used_rent' => 0,
        'stock_used_free' => 0,
        'stock_fault' => 0,
        'ppid' => $entity->id(),
      ));
      $part_entity->save();
      $part_id = $part_entity->id();
    }
    //保存采购明细
    $number = $entity->get('stock')->value;
    $this->savePurchaseDetail($part_id, $number);
  }

  /*
   * 保存采购名细
   */
  public function savePurchaseDetail($part_id, $stock) {
    if(!$stock) {
      return;
    }
    $this->database->insert('part_purchase_detail')
      ->fields(array(
        'pid' => $part_id,
        'stock' => $stock,
        'uid' => \Drupal::currentUser()->id(),
        'created' => REQUEST_TIME
      ))
      ->execute();
  }

  /**
   * 查询采购数据
   */
  public function getUrchaseDetail($condition = array(), $header = array()) {
    $query = $this->database->select('part_purchase_detail', 'd')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->innerJoin('part', 'p', 'd.pid =p.pid');
    $query->fields('p', array('type', 'ppid', 'brand', 'model', 'standard'));
    $query->fields('d', array('stock', 'uid', 'created', 'did', 'pid'));

    foreach($condition as $key => $value) {
      if($key == 'keyword') {
        if(!empty($value)) {
          $value = db_like($value);
          $query->condition($query->orConditionGroup()
            ->condition('p.brand', $value . '%', 'LIKE')
            ->condition('p.model', $value . '%', 'LIKE')
            ->condition('p.standard', $value . '%', 'LIKE')
          );
        }
        continue;
      }
      if(is_array($value)) {
        $query->condition($value['field'], $value['value'], $value['op']);
      } else {
        $query->condition($key, $value);
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header);
    return $query->execute()->fetchAll();
  }
}
