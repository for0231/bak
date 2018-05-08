<?php
namespace Drupal\referer_statist;

use Drupal\Core\Database\Connection;

class RefererStatistService {
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
   * 增加
   */
  public function add(array $values) {
    return $this->database->insert('referer_statistics')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改
   */
  public function update(array $values, $id) {
    $this->database->update('referer_statistics')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 查询指定id的数据
   */
  public function loadById($id) {
    return $this->database->select('referer_statistics', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 按条件查询
   */
  public function load(array $conditions = array(), $order = null) {
    $query = $this->database->select('referer_statistics', 't')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        if($value['op'] == 'like') {
          $query->condition($key, $value['value'] . '%', 'like');
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
    return $query->execute()
      ->fetchAll();
  }

  public function loadView(array $conditions = array()) {
    $query = $this->database->select('referer_statistics', 't')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        if($value['op'] == 'like') {
          $query->condition($key, $value['value'] . '%', 'like');
        } else {
          $query->condition($key, $value['value'], $value['op']);
        }
      } else {
        $query->condition($key, $value);
      }
    }
    $query->orderBy('ip');
    $query->orderBy('id', 'DESC');
    return $query->limit(10)->execute()
      ->fetchAll();

  }

  public function loadCharts() {
    $query = $this->database->query('select referer_site,ip, sum(case when user_name = \'\' then 0 else 1 end) as reg from referer_statistics group by referer_site, ip');
    $data = array();
    $rows = $query->fetchAll();
    foreach($rows as $row) {
      $key = $row->referer_site;
      if(empty($key)) {
        $key = '直达';
      }
      if(array_key_exists($key, $data)) {
        $total = $data[$key]['total'];
        $data[$key]['total'] = $total + 1;
        if($row->reg) {
          $reg = $data[$key]['reg'];
          $data[$key]['reg'] = $reg + 1;
        } else {
          $noreg = $data[$key]['noreg'];
          $data[$key]['noreg'] = $noreg + 1;
        }
      } else {
        $data[$key]['total'] = 1;
        $data[$key]['reg'] = 0;
        $data[$key]['noreg'] = 0;
        if($row->reg) {
          $data[$key]['reg'] = 1;
        } else {
          $data[$key]['noreg'] = 1;
        }
      }
    }
    return $data;
  }
}
