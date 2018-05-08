<?php

namespace Drupal\qy_jd;

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
   * 查询jd_policy表数据
   */
  public function load_policy(array $conditions, $order = null) {
    $query = $this->database->select('jd_policy', 't')
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
    $query = $this->database->select('jd_policy', 't')
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
    return $this->database->select('jd_policy', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 增加t_policy表数据
   */
  public function add_policy(array $values) {
     return $this->database->insert('jd_policy')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改t_policy表数据
   */
  public function update_policy(array $values, $id) {
    $this->database->update('jd_policy')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 按线路修改
   */
  public function update_policyByRoute(array $values, $route_id) {
    return $this->database->update('jd_policy')
      ->fields($values)
      ->condition('routeid', $route_id)
      ->execute();
  }

  /**
   * 删除t_policy表数据
   */
  public function del_policy($id) {
    $this->database->delete('jd_policy')
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除IP段
   */
  public function del_policySegment($ip, $xx) {
    return $this->database->delete('jd_policy')
      ->condition('ip', $ip)
      ->condition('xx', $xx)
      ->execute();
  }
  //==============牵引=================
  /**
   * 查询jd_qy表数据
   */
  public function load_qyById($id) {
    return $this->database->select('jd_qy', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 查询jd_qy表数据
   */
  public function load_qy(array $conditions = array(), $ordertype = 'DESC') {
    $query = $this->database->select('jd_qy', 't')
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
   * 查询jd_qy所有Alarm
   */
  public function load_qy_alarm() {
    return $this->database->select('jd_qy', 't')
      ->fields('t')
      ->condition('type', 'Alarm')
      ->condition('gjft', 1)
      ->execute()
      ->fetchAll();
  }

  /**
   * 增加jd_qy表数据
   */
  public function add_qy(array $values) {
    $this->database->insert('jd_qy')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改jd_qy表数据
   */
  public function update_qy(array $values, $id) {
    $this->database->update('jd_qy')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除jd_qy表数据
   */
  public function del_qy($qy) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->delete('jd_qy')
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
  public function del_qyByRoute($routeid) {
  	$qys = $this->load_qy(array('net_type' => $routeid));
  	foreach ($qys as $qy) {
  		$this->del_qy($qy);
  	}
  }

  /**
   * 按gift字段统计
   */
  public function statistics_gift($user = 0) {
    $sql = 'select gjft, count(*) as total  from jd_qy GROUP BY gjft';
    if($user > 0) {
       $sql = 'select gjft, count(*) as total  from jd_qy where uid = '. $user .' GROUP BY gjft';
    }
    return $this->database->query($sql)->fetchAll();
  }

  /**
   * 查询指定线路要牵引的IP
   */
  public function loadRouteqy($route_id) {
    $sql = 'select id,ip from jd_qy where net_type = '. $route_id .' or state = 1 order by id desc';
    return $this->database->query($sql)->fetchAll();
  }

  //----------日志----------------
  public function load_logs(array $conditions) {
    $query = $this->database->select('jd_logs', 't')
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
    $query = $this->database->select('jd_logs', 't')
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
    return $this->database->insert('jd_logs')
      ->fields($values)
      ->execute();
  }

  /**
   * 删除日志
   */
  public function del_logs(array $conditions) {
    $query = $this->database->delete('jd_logs');
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
    return $this->database->insert('jd_route')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改t_route表数据
   */
  public function update_route(array $values, $id) {
    $this->database->update('jd_route')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 删除t_netcom表数据
   */
  public function del_route($id) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->delete('jd_route')
        ->condition('id', $id)
        ->execute();

      $this->database->delete('jd_netcom')
        ->condition('type', $id)
        ->execute();

      $this->del_qyByRoute($id);

      $this->database->delete('jd_policy')
        ->condition('routeid', $id)
        ->execute();
    }catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 查询t_route表数据
   */
  public function load_route(array $conditions = array(), $is_key = true) {
    $query = $this->database->select('jd_route', 't');
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
   * 通过Id查询t_route对象
   */
  public function load_routeById($id) {
    return $this->database->select('jd_route', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }
  //----------jd_netcom防火墙表－－－－－－－－－

  /**
   * 增加t_netcom表数据
   */
  public function add_netcom(array $values) {
    return $this->database->insert('jd_netcom')
      ->fields($values)
      ->execute();
  }

  /**
   * 修改t_netcom表数据
   */
  public function update_netcom(array $values, $id) {
    $this->database->update('jd_netcom')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  /**
   * 修改t_netcom表数据
   */
  public function update_netcomByIp(array $values, $ip) {
    $this->database->update('jd_netcom')
      ->fields($values)
      ->condition('ip', $ip)
      ->execute();
  }

  /**
   * 删除t_netcom表数据
   */
  public function del_netcom($id) {
    $this->database->delete('jd_netcom')
      ->condition('id', $id)
      ->execute();

    $this->delete_alarm($id);
  }

  /**
   * 查询t_netcom表数据
   */
  public function load_netcom(array $conditions = array(), $order = null) {
    $query = $this->database->select('jd_netcom', 't');
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
    if(!empty($order)) {
      $query->orderBy($order, 'ESC');
    }
    return $query->execute()->fetchAll();
  }

  /**
   * 通过Id查询t_netcom对象
   */
  public function load_netcomById($id) {
    return $this->database->select('jd_netcom', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  //==========jd_alarm单元流量警告设置表============
  public function add_alarm($values) {
    return $this->database->insert('jd_alarm')
      ->fields($values)
      ->execute();
  }

  public function load_alarmList() {
    $query = $this->database->select('jd_netcom', 'n');
    $query->leftJoin('jd_alarm', 't', 'n.id=t.id');
    $query->fields('n', array('id','ip'));
    $query->fields('t', array('max_bps', 'min_bps', 'delay_time', 'timeout'));
    return $query->execute()->fetchAll();
  }

  public function load_alarm(array $conditions = array()) {
    $query = $this->database->select('jd_alarm', 't');
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
    return $this->database->select('jd_alarm', 't')
      ->fields('t')
      ->condition('id', $id)
      ->execute()
      ->fetchObject();
  }

  public function update_alarm($values, $id) {
    $this->database->update('jd_alarm')
      ->fields($values)
      ->condition('id', $id)
      ->execute();
  }

  public function delete_alarm($id) {
    $this->database->delete('jd_alarm')
      ->condition('id', $id)
      ->execute();
  }
}
