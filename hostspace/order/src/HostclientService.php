<?php

namespace Drupal\order;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class HostclientService {

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
   * 查询有租用服务器的客户
   *
   * @return 符合条件的结果集
   *
   */
  public function getServerUser($status = -1) {
    $query = $this->database->select('hostclients_field_data','h');
    $query->innerJoin('user_client_data', 'c', 'h.client_uid = c.uid');
    $query->innerJoin('users_field_data', 'u', 'h.client_uid = u.uid');
    $query->fields('h',array('client_uid'));
    $query->fields('c',array('uid', 'corporate_name', 'client_name' ));
    $query->fields('u',array('name'));
    if($status != -1) {
      $query->condition('h.status', $status); // 服务器在线正常适用的 status=3
    }
    $query->orderBy('h.client_uid');
    return $query->execute()->fetchAll();
  }

  /**
   * 我购买过的产品
   */
  public function getMyHaveProduct() {
    $query = $this->database->select('hostclients_field_data', 'h');
    $query->fields('h', array('product_id'));
    $query->condition('h.client_uid', \Drupal::currentUser()->id());
    $query->groupBy('h.product_id');
    return $query->execute()->fetchCol();
  }


  /**
   * 条件筛选服务器
   *
   * @param $conditions array
   *  服务器条件
   *
   * @param $ip_condition array
   *   IP条件
   *
   * @param $order_condition string
   *   订单条件
   *
   * @return 符合条件的结果集
   *
   */
  public function getServerByCondition($conditions = array(),$ip_condition = array()) {
    $query = $this->database->select('hostclients_field_data','h')
       ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    // 关联表
    foreach($ip_condition as $key => $ip) {
      if($key == 'ipb') {
        $query->innerJoin('hostclient__ipb_id','t', 'h.hid = t.entity_id');
        $query->innerJoin('business_ip_field_data', 'b', 't.ipb_id_target_id = b.id');
      } elseif($key == 'ipm') {
        $query->innerJoin('management_ip_field_data', 'm', 'h.ipm_id = m.id');
      }
    }

    $query->fields('h', array('hid'));
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        $query->condition('h.'. $key, $value['value'], $value['op']);
      } else {
        $query->condition('h.'. $key, $value);
      }
    }
    foreach($ip_condition as $key => $ip) {
      if($key == 'ipb') {
        $query->condition('b.ip', $ip.'%', 'LIKE');
      } else if($key == 'ipm') {
        $query->condition('m.ip', $ip.'%', 'LIKE');
      }
    }
    $query->orderBy('h.hid', 'DESC');
    $query->limit(PER_PAGE_COUNT);
    return $query->execute()->fetchCol();
  }

  /**
   * 获取我的服务器数量
   */
  public function myServerNumber($uid) {
    $query = $this->database->select('hostclients_field_data','h')
      ->fields('h', array('hid'));
    $query->condition('client_uid', $uid);
    $query->condition('trial', 0);
    $query->condition('status', array(2,3), 'IN');
    $hids = $query->execute()->fetchCol();
    return count($hids);
  }

  /**
   * 获取指定用户服务器的IP
   */
  public function loadHostclientIp($userId) {
    $query = $this->database->select('hostclients_field_data', 'h');
    $query->innerJoin('hostclient__ipb_id','t', 'h.hid = t.entity_id');
    $query->innerJoin('business_ip_field_data', 'b', 't.ipb_id_target_id = b.id');
    $query->fields('h', array('hid', 'server_id', 'ipm_id'))
      ->fields('b', array('ip'));
    return $query->execute()->fetchAll();
  }

  /**
   * 获取业务数据
   */
  public function loadHostclientBusiness($hostclient_id) {
    return $this->database->select('hostclient_business', 'hb')
      ->fields('hb')
      ->condition('hostclient_id', $hostclient_id)
      ->execute()
      ->fetchAll();
  }

  /**
   * 增加升级业务到hostclient业务表中
   */
  public function addHostclientBusiness($hostclient_id, $product_business) {
    $old_business_list = $this->loadHostclientBusiness($hostclient_id);
    foreach($product_business as $business) {
      $exist = $this->existBusiness($old_business_list, $business->business_id);
      if(empty($exist)) {
        $this->database->insert('hostclient_business')
          ->fields(array(
            'hostclient_id' => $hostclient_id,
            'business_id' => $business->business_id,
            'business_content' => $business->business_content
          ))
          ->execute();
      } else {
        $value = '';
        $business_obj = entity_load('product_business', $business->business_id);
        $ctl = $business_obj->getSimpleValue('operate');
        $op = $business_obj->getSimpleValue('combine_mode');
        if($op == 'add') {
          if($ctl == 'edit_number') {
            $value = $exist->business_content + $business->business_content;
          } else if ($ctl == 'select_content') {
            $value = $exist->business_content . ',' . $business->business_content;
          } else if ($ctl == 'select_and_number') {
            $old_value = $exist->business_content;
            $new_value = $business->business_content;
            $old_value_arr = explode(',', $old_value);
            $new_value_arr = explode(':', $new_value);
            $list_value = array();
            $is_add = false;
            foreach($old_value_arr as $old_item) {
              $old_item_arr = explode(':', $old_item);
              if($old_item_arr[0] == $new_value_arr[0]) {
                $list_value[] = $old_item_arr[0] . ':' . ($old_item_arr[1] + $new_value_arr[1]);
                $is_add = true;
              } else {
                $list_value[] = $old_item;
              }
            }
            if(!$is_add) {
              $list_value[] = $new_value;
            }
            $value = implode(',', $list_value);
          }
        } else {
          $value = $business->business_content;
        }
        $this->updateHostclientBusinessValue($hostclient_id, $business->business_id, $value);
      }
    }
  }

  /**
   * 修改指定的业务值
   */
  public function updateHostclientBusinessValue($hostclient_id, $business_id, $value) {
    $this->database->update('hostclient_business')
      ->fields(array('business_content' => $value))
      ->condition('hostclient_id', $hostclient_id)
      ->condition('business_id', $business_id)
      ->execute();
  }

  /**
   * 删除服务器业务
   */
  public function delHostclientBusiness($hostclient_id) {
    $this->database->delete('hostclient_business')
      ->condition('hostclient_id', $hostclient_id)
      ->execute();
  }

  /**
   * 删除指定IP对应的业务
   */
  public function delHostclientBusinessByIp($hostclient_id, $ipbs) {
    //得到业务应该的Ip
    $remove_business = array();
    foreach($ipbs as $ipb) {
      $ipb_obj = entity_load('ipb', $ipb);
      $ipb_type = $ipb_obj->get('type')->target_id;
      $entities = entity_load_multiple_by_properties('product_business_entity_content', array(
         'entity_type' => 'taxonomy_term', 
         'target_id' => $ipb_type
      ));
      $content = reset($entities);
      $remove_business[$content->getObjectId('businessId')][$content->id()][] = $ipb;
    }
    $business_list = $this->loadHostclientBusiness($hostclient_id);
    foreach($remove_business as $key => $bus_ips) {
      $Hostclient_business = null;
      foreach($business_list as $hostclient_bus) {
        if($key == $hostclient_bus->business_id) {
          $Hostclient_business = $hostclient_bus;
          break;
        }
      }
      if(empty($Hostclient_business)) {
        continue;
      }
      $business_obj = entity_load('product_business', $key);
      $operate = $business_obj->getSimpleValue('operate');
      if($operate == 'edit_number') {
        $curr_number = $Hostclient_business->business_content;
        $rm_number = 0;
        foreach($bus_ips as $ips) {
          $rm_number += count($ips);
        }
        $number = $curr_number - $rm_number;
        if($number > 0) {
          $this->updateHostclientBusinessValue($hostclient_id, $key, $number); 
        } else {
          $this->database->delete('hostclient_business')
            ->condition('hostclient_id', $hostclient_id)
            ->condition('business_id', $key)
            ->execute();
        }
      } else if ($operate == 'select_content') {
        $curr_value = $Hostclient_business->business_content;
        $curr_value_arr = explode(',', $curr_value);
        $value = array();
        foreach($curr_value_arr as $item) {
          if(!array_key_exists($item, $bus_ips)) {
            $value[] = $item;
          }
        }
        if(empty($value)) {
          $this->database->delete('hostclient_business')
            ->condition('hostclient_id', $hostclient_id)
            ->condition('business_id', $key)
            ->execute();
        } else {
          $this->updateHostclientBusinessValue($hostclient_id, $key, implode(',', $value));
        }
      } else if ($operate == 'select_and_number') {
        $curr_value = $Hostclient_business->business_content;
        $curr_value_arr = explode(',', $curr_value);
        $value = array();
        foreach($curr_value_arr as $item) {
          $item_arr = explode(':', $item);
          if(array_key_exists($item_arr[0], $bus_ips)) {
            $rm_number = count($bus_ips[$item_arr[0]]);
            $number = $item_arr[1] - $rm_number;
            if($number > 0) {
              $value[] = $item_arr[0] . ':' . $number;
            }
          } else {
            $value[] = $item;
          }
        }
        if(empty($value)) {
          $this->database->delete('hostclient_business')
            ->condition('hostclient_id', $hostclient_id)
            ->condition('business_id', $key)
            ->execute();
        } else {
          $this->updateHostclientBusinessValue($hostclient_id, $key, implode(',', $value)); 
        }
      }
    }
  }

  /**
   * 检查业务是否存在
   */
  private function existBusiness($business_list, $business_id) {
    foreach($business_list as $business) {
      if($business_id == $business->business_id) {
        return $business;
      }
    }
    return null;
  }

  /**
   * 增加部门操作
   */
  public function addHandleInfo(array $handle_info) {
    return $this->database->insert('hostclient_handle_info')
      ->fields($handle_info)
      ->execute();
  }

  /**
   * 修改部门操作
   */
  public function updateHandleInfo(array $handle_info, $handle_id) {
    $this->database->update('hostclient_handle_info')
      ->fields($handle_info)
      ->condition('hid', $handle_id)
      ->execute();
  }

  /**
   * 获取处理信息
   */
  public function loadHandleInfo($handle_id) {
    return $this->database->select('hostclient_handle_info', 'h')
      ->fields('h')
      ->condition('hid', $handle_id)
      ->execute()
      ->fetchObject();
  }

 /**
  * 获取未处理信息
  */
  public function loadHandleInfoUntreated($dept) {
    $query = $this->database->select('hostclient_handle_info', 'h')
      ->fields('h');
    if($dept == 'business') {
      $query->condition('busi_status', 0);
    } else {
      $query->condition('tech_status', 0);
      $query->condition('busi_status', 1);
    }
    return $query->execute()
      ->fetchAll();
  }

  /**
   * 得到订单生成的hoostclient_id
   */
  public function getHandleInfoHid($order_id) {
    $hids = $this->database->select('hostclient_handle_info','h')
      ->fields('h', array('hostclient_id'))
      ->condition('handle_order_id', $order_id)
      ->execute()
      ->fetchCol();
     return array_unique($hids);
  }

  /**
   * 获取试用的handle_info
   */
  public function getHandleInfoTrial($hostclient_id) {
    $handle_info = $this->database->select('hostclient_handle_info', 'h')
      ->fields('h')
      ->condition('hostclient_id', $hostclient_id)
      ->condition('handle_action', 4)
      ->execute()
      ->fetchAll();
    return reset($handle_info);
  }

  /**
   * 检查订单是否处理完成
   */
  public function checkHandleStatus($order_id) {
    $query = $this->database->select('hostclient_handle_info', 'h')
       ->fields('h', array('hid'));
    $query->condition($query->orConditionGroup()
      ->condition('busi_status', 0)
      ->condition('tech_status', 0)
    );
    $result = $query->condition('handle_order_id', $order_id)
      ->execute()
      ->fetchAll();
    if(empty($result)) {
      return true;
    }
    return false;
  }

  /**
   * 保存业务配件到服务器中-租用
   */
  public function saveServerPartHire($hostclient, $product_business_list) {
    $part_list = array(); //得到租用服务器的内存和硬盘
    foreach($product_business_list as $product_business) {
      $business_obj = entity_load('product_business', $product_business->business_id);
      $resource_lib = $business_obj->getSimpleValue('resource_lib');
      if($resource_lib == 'part_lib') {
        $entity_type = $business_obj->getSimpleValue('entity_type');
        $values = $product_business->business_content;
        $value_arr = explode(',', $values);
        foreach($value_arr as $item) {
          $conent = entity_load('product_business_entity_content', $item);
          $part_list[$entity_type][] = array('target_id' => $conent->getSimpleValue('target_id'), 'value' => 0);
        }
      }
    }
    if(empty($part_list)) {
      return;
    }
    $server = $hostclient->getObject('server_id');
    $change = false;
    foreach($part_list as $entity_type => $parts) {
      if($entity_type == 'part_memory') {
        $add_part = array();
        $memorys = $server->get('memory')->getValue();
        foreach($parts as $part) {
          $part_id = $part['target_id'];
          $b = false;
          foreach($memorys as $key => $memory) {
            if($memory['target_id'] == $part_id && $memory['value'] == 0) {
              $b = true;
              unset($memorys[$key]);
              break;
            }
          }
          if(!$b) {
            $add_part[] = $part;
          }
        }
        if(!empty($add_part)) {
          $values =  array_merge($server->get('memory')->getValue(), $add_part);
          $server->set('memory', $values);
          $change = true;
        }
      } else if ($entity_type == 'part_harddisc') {
        $add_part = array();
        $harddisks = $server->get('harddisk')->getValue();
        foreach($parts as $part) {
          $part_id = $part['target_id'];
          $b = false;
          foreach($harddisks as $key => $harddisk) {
            if($harddisk['target_id'] == $part_id && $harddisk['value'] == 0) {
              $b = true;
              unset($harddisks[$key]);
              break;
            }
          }
          if(!$b) {
            $add_part[] = $part;
          }
        }
        if(!empty($add_part)) {
          $values =  array_merge($server->get('harddisk')->getValue(), $add_part);
          $server->set('harddisk', $values);
          $change = true;
        }
      }
    }
    if($change) {
      $server->set('part_change', true);
      $server->save();
    }
  }

  /**
   * 保存业务配件到服务器中-升级
   */
  public function saveServerPartUpgrade($hostclient, $product_business_list) {
    $part_list = array(); //得到租用服务器的内存和硬盘
    foreach($product_business_list as $product_business) {
      $business_obj = entity_load('product_business', $product_business->business_id);
      $resource_lib = $business_obj->getSimpleValue('resource_lib');
      if($resource_lib == 'part_lib') {
        $entity_type = $business_obj->getSimpleValue('entity_type');
        $values = $product_business->business_content;
        $value_arr = explode(',', $values);
        foreach($value_arr as $item) {
          $conent = entity_load('product_business_entity_content', $item);
          $part_list[$entity_type][] = array('target_id' => $conent->getSimpleValue('target_id'), 'value' => 0);
        }
      }
    }
    if(empty($part_list)) {
      return;
    }
    $server = $hostclient->getObject('server_id');
    foreach($part_list as $entity_type => $parts) {
      if($entity_type == 'part_memory') {
        $memorys = $server->get('memory')->getValue();
        $values = array_merge($memorys, $parts);
        $server->set('memory', $values);
      } else if ($entity_type == 'part_harddisc') {
        $harddisks = $server->get('harddisk')->getValue();
        $values = array_merge($harddisks, $parts);
        $server->set('harddisk', $values);
      }
    }
    $server->set('part_change', true);
    $server->save();
  }

  /**
   * 加载停用服务器信息
   */
  public function loadStopInfo($stop_id) {
    return $this->database->select('hostclient_stop_info', 's')
      ->fields('s')
      ->condition('sid', $stop_id)
      ->execute()
      ->fetchObject();
  }

  /**
   * 增加停用操作
   */
  public function addStopInfo(array $stop_info) {
    return $this->database->insert('hostclient_stop_info')
      ->fields($stop_info)
      ->execute();
  }

  /**
   * 修改停用操作
   */
  public function updateStopInfo(array $stop_info, $stop_id) {
    $this->database->update('hostclient_stop_info')
      ->fields($stop_info)
      ->condition('sid', $stop_id)
      ->execute();
  }

  /**
   * 获取停用服务器列表
   */
  public function getStopListDate($condition = array()) {
    $query = $this->database->select('hostclient_stop_info', 's')
      ->fields('s');

    foreach($condition as $key => $value) {
      $query->condition($key, $value);
    }
    return $query->execute()
      ->fetchAll();
  }

  /**
   * 获取停用服务器列表-分页
   */
  public function getStopPageList($condition = array()) {
    $query = $this->database->select('hostclient_stop_info', 's')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');

    $query->fields('s');
    foreach($condition as $key => $value) {
      $query->condition($key, $value);
    }

    $query->limit(PER_PAGE_COUNT);
    return $query->execute()
      ->fetchAll();
  }

  /**
   * 停用恢复
   */
  public function stopRecover($stop_id) {
    $transaction = $this->database->startTransaction();
    try {
      $stop_info = $this->loadStopInfo($stop_id);
      $new_stop_info = array(
        'status' => 2, 
        'handle_uid' => \Drupal::currentUser()->id(), 
        'handle_date' => REQUEST_TIME
      );
      $this->updateStopInfo($new_stop_info, $stop_id);

      $hostclient = entity_load('hostclient', $stop_info->hostclient_id);
      $hostclient->set('status', 3);
      $hostclient->save();
      return $hostclient;
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 停用入库
   */
  public function stopStorage($stop_id) {
    $transaction = $this->database->startTransaction();
    try {
      $stop_info = $this->loadStopInfo($stop_id);
      $info = array();
      //修改管理IP状态
      $hostclient = entity_load('hostclient', $stop_info->hostclient_id);
      $ipm = $hostclient->getObject('ipm_id');
      $ipm->set('status', 1);
      $ipm->save();
      $info['server'] = $hostclient->getObject('server_id')->label();
      $info['ipm'] = $ipm->label();
      //修改业务IP状态
      $ipb_ids = $hostclient->get('ipb_id')->getValue();
      foreach($ipb_ids as $ipb_id) {
        if(isset($ipb_id['target_id'])) {
          $ipb_obj = entity_load('ipb', $ipb_id['target_id']);
          $ipb_obj->set('status', 1);
          $ipb_obj->save();
          $info['ipb'][] = $ipb_obj->label();
        }
      }
      //修改停用表信息
      $new_stop_info = array(
        'status' => 1, 
        'handle_uid' => \Drupal::currentUser()->id(), 
        'handle_date' => REQUEST_TIME,
        'info' => json_encode($info)
      );
      $this->updateStopInfo($new_stop_info, $stop_id);
      //删除当前业务
      $this->delHostclientBusiness($stop_info->hostclient_id);
      $hostclient->delete();
      return $hostclient;
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 查询需要续费的服务器数量
   * @apram $con 条件
   * @param $op 操作符
   * @param $tag 是否是查询在多少天之内到期的服务器
   *  - will 几天之内即将到期
   */
  public function getServerCountByCondition($con, $op, $uid,$tag='') {
     $query = $this->database->select('hostclients_field_data','h')
       ->fields('h', array('hid')) ;
     if($tag == 'will') {
       $query->condition('service_expired_date',REQUEST_TIME , '>');
     }
      $query->condition('service_expired_date', $con, $op )
       ->condition('client_uid' , $uid)
       ->condition('status' , 3);
    return $query->execute()->fetchAll();
  }
  /**
   * 获取在线服务器的管理IP信息,有时可能会为空,使用实体的取值方式会出错
   * @param $hid 在线服务器ID
   * @return Object 服务器处理信息对象
   */
  public function getManageIP4SopByHostclientId($hid) {
    return $this->database->select('hostclients_field_data', 'h')
      ->fields('h')
      ->condition('hid', $hid)
      ->execute()
      ->fetchObject();
  }

  /**
   * @todo 减少服务器IP -这个可能涉及多个种类的IP
   * 比如，普通无防御IP，高防IP
   * 目前只涉及hostclient_business表里business_id,business_content
   * @param $hid hostclient的ID
   * @param $ipbs 将减少的IP数组
   */
  public function updateHostclientBusiness($hid, $ipbs) {
    $query = $this->database->select('hostclient_business', 'hb')
      ->fields('hb');
    $query->condition('hostclient_id', $hid)
      ->condition('business_id', 3);
    $old = $query->execute()->fetchAll();
    $old = current($old);
    $transaction = $this->database->startTransaction();
    try {
      // 这里减少用户购买的IP数量
      $query = $this->database->update('hostclient_business', array('business_content' => $old->business_content - count($ipbs)));
      return 1;
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }
}

