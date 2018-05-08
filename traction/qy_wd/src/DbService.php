<?php

namespace Drupal\qy_wd;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class DbService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  //=============策略===================
  /**
   * 查询t_policy表数据
   */
  public function load_policy(array $conditions, $order = null) {
    $query = $this->database->select('t_policy', 't')
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
    if(!empty($order)) {
      $query->orderBy($order, 'DESC');
    }
    return $query->limit(20)
      ->execute()->fetchAll();
  }

  /**
   * 查询t_policy表数据
   */
  public function load_policy_nopage(array $conditions, $order1 = null, $order2 = null) {
    $query = $this->database->select('t_policy', 't')
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
    if(!empty($order1)) {
      $query->orderBy($order1, 'DESC');
    }
    if(!empty($order2)) {
      $query->orderBy($order2, 'DESC');
    }
    return $query->execute()
      ->fetchAll();
  }

  /**
   * 通过Id查询policy对象
   */
  public function load_policyById($id) {
    return $this->database->select('t_policy', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 增加t_policy表数据
   */
  public function add_policy(array $values) {
    return $this->database->insert('t_policy')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改t_policy表数据
   */
  public function update_policy(array $values, $id) {
    $this->database->update('t_policy')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 按线路修改
   */
  public function update_policyByRoute(array $values, $route_id) {
    return $this->database->update('t_policy')
      ->fields($values)
      ->condition('routeid', $route_id)
      ->execute();
  }

  /**
   * 删除t_policy表数据
   */
  public function del_policy($id) {
    $this->database->delete('t_policy')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除IP段
   */
  public function del_policySegment($ip, $xx) {
    return $this->database->delete('t_policy')
      ->condition('ip', $ip)
      ->condition('xx', $xx)
      ->execute();
  }

  //==============牵引=================
  /**
   * 查询t_qy表数据
   */
  public function load_qyById($id) {
    return $this->database->select('t_qy', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 查询t_qy表数据
   */
  public function load_qy(array $conditions = array(), $ordertype = 'DESC') {
    $query = $this->database->select('t_qy', 't')
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
    return $query->orderBy('id', $ordertype)
       ->execute()
       ->fetchAll();
  }

  /**
   * 查询t_qy所有Alarm
   */
  public function load_qy_alarm() {
    return $this->database->select('t_qy', 't')
      ->fields('t')
      ->condition('type', 'Alarm')
      ->condition('gjft', 1)
      ->execute()
      ->fetchAll();
  }

  /**
   * 增加t_qy表数据
   */
  public function add_qy(array $values) {
    $this->database->insert('t_qy')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改t_qy表数据
   */
  public function update_qy(array $values, $id) {
    $this->database->update('t_qy')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除t_qy表数据
   */
  public function del_qy($qy) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->delete('t_qy')
        ->condition('id', $qy->id)
        ->execute();
      //增加日志
      $route = $this->load_routeById($qy->net_type);
      $route_name = '';
      if($route) {
        $route_name = $route->routename;
      }
      $log = array(
        'ip' => $qy->ip,
        'routename' => $route_name,
        'bps' => $qy->bps,
        'pps' => $qy->pps,
        'start' => $qy->start,
        'end' => time(),
        'type' => $qy->type,
        'note' => $qy->note,
        'opter' => $qy->opter,
        'log' => 1
      );
      if($qy->gjft == 2)  {
        $log['log'] = 2;
      }
      $this->add_logs($log);
    } catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 删除此线路的牵引
   * @param unknown $routeid
   */
  public function del_qyByRoute($route_id) {
    $qys = $this->load_qy(array('net_type' => $route_id));
    foreach ($qys as $qy) {
      $this->del_qy($qy);
    }
  }

  /**
   * 按gift字段统计
   */
  public function statistics_gift($user=0) {
    $sql = 'select gjft, count(*) as total  from t_qy GROUP BY gjft';
    if($user > 0) {
       $sql = 'select gjft, count(*) as total  from t_qy where uid = '. $user .' GROUP BY gjft';
    }
    return $this->database->query($sql)->fetchAll();
  }

  /**
   * 查询指定线路要牵引的IP
   */
  public function loadRouteqy($route_id) {
    $sql = 'select id,ip from t_qy where net_type = '. $route_id .' or state = 1 order by id desc';
    return $this->database->query($sql)->fetchAll();
  }

  //----------日志----------------
  public function load_logs(array $conditions) {
    $query = $this->database->select('t_logs', 't')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(empty($value)) {
        $query->condition($key, NULL, 'IS NULL');
        continue;
      }
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
      ->orderBy('t.id', 'DESC')
      ->execute()->fetchAll();
  }
  /**
   * 日志无分页
   */
  public function load_noPageLogs(array $conditions) {
    $query = $this->database->select('t_logs', 't')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(empty($value)) {
        $query->condition($key, NULL, 'IS NULL');
        continue;
      }
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
    return $query->orderBy('t.id', 'DESC')
      ->execute()
      ->fetchAll(); 
  }
  /**
   * 增加t_logs表数据
   */
  public function add_logs(array $values) {
    return $this->database->insert('t_logs')
      ->fields($values)
      ->execute();
  }

  /**
   * 删除日志
   */
  public function del_logs(array $conditions) {
    $query = $this->database->delete('t_logs');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        $query->condition($key, $value['value'], $value['op']);
      } else {
        $query->condition($key, $value);
      }
    }
    $query->execute();
  }

  //=========t_route线路表=========
  /**
   * 增加t_route表数据
   */
  public function add_route(array $values) {
    return $this->database->insert('t_route')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改t_route表数据
   */
  public function update_route(array $values, $id) {
    $this->database->update('t_route')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除t_route表数据
   */
  public function del_route($id) {
    $transaction = $this->database->startTransaction();
    try {
      $this->del_qyByRoute($id);

      $this->database->delete('t_route')
        ->condition('id', $id)
        ->execute();

      $this->database->delete('t_policy')
        ->condition('routeid', $id)
        ->execute();
    } catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 查询t_route表数据
   */
  public function load_route(array $conditions = array(), $is_key = true) {
    $query = $this->database->select('t_route', 't');
    $query->fields('t');
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
    $datas = $query->execute()->fetchAll();
    if($is_key) {
      $items = array();
      foreach($datas as $data) {
        $items[$data->id] = $data;
      }
      return $items;
    }
    return $datas;
  }

  /**
   * 以线路单无为key的数组
   */
  public function load_routeUnit(array $conditions = array()) {
    $query = $this->database->select('t_route', 't');
    $query->fields('t');
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
    $routes = $query->execute()->fetchAll();
    $items = array();
    foreach($routes as $route) {
      $str_unit = $route->firewall_unit;
      if(empty($str_unit) || $route->status == 0) {
        continue;
      }
      $units = explode(',', $str_unit);
      foreach($units as $unit) {
        $items[$unit] = $route->routename;
      }
    }
    return $items;
  }

  /**
   * 通过Id查询t_route对象
   */
  public function load_routeById($id) {
    return $this->database->select('t_route', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  //==========t_alarm单元流量警告设置表============
  public function add_alarm($values) {
    return $this->database->insert('t_alarm')
      ->fields($values)
      ->execute();
  }

  public function load_alarm(array $conditions = array()) {
    $query = $this->database->select('t_alarm', 't');
    $query->fields('t');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        $query->condition($key, $value['value'], $value['op']);
      } else {
        $query->condition($key, $value);
      }
    }
    $data = array();
    $items = $query->execute()->fetchAll();
    foreach($items as $item) {
      $data[$item->id] = $item;
    }
    return $data;
  }

  public function load_alarmById($id) {
    return $this->database->select('t_alarm', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  public function update_alarm($values, $id) {
    $this->database->update('t_alarm')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  //======t_unit_flow单元信息情况表============
  public function add_unit_flow($values) {
    return $this->database->insert('t_unit_flow')
      ->fields($values)
      ->execute();
  }

  public function update_unit_flow($values, $id) {
    $this->database->update('t_unit_flow')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 加载指定单元信息
   */
  public function load_unit_flow(array $units) {
    return $this->database->select('t_unit_flow', 't')
      ->fields('t')
      ->condition('id', $units, 'IN')
      ->execute()
      ->fetchAll();
  }
}
