<?php
/*
 * @file
 * \Drupal\order\ServerDistribution
 */

namespace Drupal\order;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hostlog\HostLogFactory;

class ServerDistribution {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function createInstance() {
    return new Static(
      \Drupal::database()
    );
  }

  /**
   * 分配订单服务器
   */
  public function orderDistributionServer(EntityInterface $order) {
    $transaction = $this->database->startTransaction();
    try {
      $config = \Drupal::config('common.global');
      $is_room = $config->get('is_district_room_id');
      $product_service = \Drupal::service('order.product');
      $products = $product_service->getProductByOrderId($order->id());
      foreach($products as $product) {
        $action = $product->action;
        if($action == 1 ) { //租用产品
          $product_business = $product_service->getOrderBusiness($product->opid);
          $combine_business = order_order_product_business_combine($product_business);
          if($is_room) {
            $this->productHire($order, $product, $combine_business, $product->rid);
          } else {
            $this->productHire($order, $product, $combine_business);
          }
        } else if ($action == 2) {
          $this->productRenew($product, $products);
        } else if ($action == 3) {
          $product_business = $product_service->getOrderBusiness($product->opid);
          if($is_room) {
            $this->productUpgrade($product, $product_business, $product->rid);
          } else {
            $this->productUpgrade($product, $product_business);
          }
        }
      }

      //修改定单状态
      $hostclient_service = \Drupal::service('hostclient.serverservice');
      $all_complete = $hostclient_service->checkHandleStatus($order->id());
      if($all_complete) {
        $order->set('accept', 1);
        $order->set('status', 5);
      } else {
        $order->set('accept', 1);
        $order->set('status', 4);
      }
      $order->save();
    } catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 产品租用分配服务器
   */

  private function productHire($order, $product, $product_business, $rid = 0) {
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $order_service = \Drupal::service('order.orderservice');

    $trial_hostclient = $order_service->loadTrialHostclient($order->id(), $product->opid);
    $product_num = $product->product_num;
    for($i=0; $i < $product_num; $i++) {
      $hostclient_id = 0;
      if($i == 0 && $trial_hostclient) { //存在试用先分配试用的服务器
        $trial_ipm = $trial_hostclient->getObjectId('ipm_id');
        if(!$trial_ipm) {
          $product_obj = entity_load('product', $product->product_id);
          $ipm = $this->getIpm($product_obj, $product_business, $rid);
          $trial_hostclient->set('ipm_id', $ipm->ipm_id);
          $trial_hostclient->set('server_id', $ipm->server_id);
          $trial_hostclient->set('cabinet_server_id', $ipm->sid);
          $trial_hostclient->set('rid', $product->rid);
          $trial_hostclient->brfore_save_ipm = 0;
        }

        $trial_ipb = $trial_hostclient->get('ipb_id')->getValue();
        $rm = array(); //不要试用IP，因为试用是人工分配,不确定是那个分类IP
        foreach($trial_ipb as $item) {
          if(!empty($item)) {
            $rm[] = $item['target_id'];
          }
        }
        $ipb_id = $this->getIpb($product_business, $order->getObjectId('uid'), $trial_hostclient->getObject('ipm_id')->get('group_id')->value);
        if(!empty($ipb_id)) {
          $trial_hostclient->set('ipb_id', $ipb_id);
        }
        $trial_hostclient->save_ipb_change = array('add' => $ipb_id, 'rm' => $rm);
        $trial_hostclient->set('trial', 0);
        $trial_hostclient->set('equipment_date', 0);
        $trial_hostclient->set('service_expired_date', 0);
        $trial_hostclient->set('status', 0);
        $trial_hostclient->save();
        $hostclient_id = $trial_hostclient->id();
        //增加处理信息
        $handle_info['handle_order_id'] = $order->id();
        $handle_info['handle_order_product_id'] = $product->opid;
        $handle_info['handle_action'] = 1;
        $handle_info['client_description'] = $product->description;
        $handle_info['hostclient_id'] = $hostclient_id;
        // @todo 按机房区分待处理事务
        $hid = $hostclient_service->addHandleInfo($handle_info);
        //写日志
        $handle_info_log = $hostclient_service->loadHandleInfo($hid);
        $trial_hostclient->other_data = array('data_id' => $hid, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
        $trial_hostclient->other_status = 'distribution_server';
        HostLogFactory::OperationLog('order')->log($trial_hostclient, 'insert');
        // @todo 按机房区分工单事务。
        \Drupal::service('sop.soptaskservice')->sop_task_iband_for_hostclient($trial_hostclient, 'Trial_UP_hostclient', $hid, 'UP');
      } else {
        $hostclient = $this->createHostclient($product, $product_business, $order->getObjectId('uid'), $rid);
        $hostclient->set('client_uid', $order->getObjectId('uid'));
        $hostclient->set('trial', 0);
        $hostclient->set('init_pwd', '123456');
        $hostclient->set('rid', $product->rid);
        $hostclient->save();
        $hostclient_id = $hostclient->id();
        //增加处理信息
        $handle_info['handle_order_id'] = $order->id();
        $handle_info['handle_order_product_id'] = $product->opid;
        $handle_info['handle_action'] = 1;
        $handle_info['client_description'] = $product->description;
        $handle_info['hostclient_id'] = $hostclient_id;
        // @todo 按机房区分待处理事务
        $hid = $hostclient_service->addHandleInfo($handle_info);
        //写日志
        $handle_info_log = $hostclient_service->loadHandleInfo($hid);
        $hostclient->other_data = array('data_id' => $hid, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
        $hostclient->other_status = 'distribution_server';
        HostLogFactory::OperationLog('order')->log($hostclient, 'insert');
        // 服务器上架流程工单
        // @todo 按机房区分工单事务。
        \Drupal::service('sop.soptaskservice')->sop_task_iband_for_hostclient($hostclient, 'Normal_UP_hostclient', $hid);

      }

      //复制业务数据
      foreach($product_business as $item) {
        $business = $item['business'];
        $this->database->insert('hostclient_business')
          ->fields(array(
            'hostclient_id' => $hostclient_id,
            'business_id' => $business->id(),
            'business_content' => $item['business_value']
          ))
          ->execute();
      }
    }
  }

  /**
   * 产品续费
   */
  private function productRenew($product, $products) {
    $hostclient = entity_load('hostclient', $product->product_id);
    $start = $hostclient->getSimpleValue('service_expired_date');
    $hostclient->set('service_expired_date',  strtotime('+' . $product->product_limit .'month', $start));
    $exits = false; //判断此产品是否还有升续的
    foreach($products as $item) {
      if($product->product_id == $item->product_id && $item->action == 3) {
        $exits = true;
        break;
      }
    }
    if(!$exits) {
      $hostclient->set('unpaid_order', 0);
    }
    $hostclient->save();

    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $handle_info['handle_order_id'] = $product->order_id;
    $handle_info['handle_order_product_id'] = $product->opid;
    $handle_info['handle_action'] = 2;
    $handle_info['client_description'] = $product->description;
    $handle_info['hostclient_id'] = $hostclient->id();
    $handle_info['busi_status'] = 1;
    $time = REQUEST_TIME;
    $handle_info['busi_accept_data'] = $time;
    $handle_info['busi_complete_data'] = $time;
    $handle_info['tech_status'] = 1;
    $handle_info['tech_accept_data'] = $time;
    $handle_info['tech_complete_data'] = $time;
    $hid = $hostclient_service->addHandleInfo($handle_info);

    //写日志
    $handle_info_log = $hostclient_service->loadHandleInfo($hid);
    $hostclient->other_data = array('data_id' => $hid, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
    $hostclient->other_status = 'renew_server';
    HostLogFactory::OperationLog('order')->log($hostclient, 'renew_server');
    // SOP 服务器续费流程工单
    // 续费取消工单流程
    //\Drupal::service('sop.soptaskservice')->sop_task_iband_for_hostclient($hostclient, 'Iband_hostclient', $hid);
  }

  /**
   * 产品升级
   */
  private function productUpgrade($product, $product_business, $rid = 0) {
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $handle_info['handle_order_id'] = $product->order_id;
    $handle_info['handle_order_product_id'] = $product->opid;
    $handle_info['handle_action'] = 3;
    $handle_info['client_description'] = $product->description;
    $handle_info['hostclient_id'] = $product->product_id;
    $hid = $hostclient_service->addHandleInfo($handle_info);
    //增加业务到hostclient表中
    $hostclient_service->addHostclientBusiness($product->product_id, $product_business);
    //分配ip
    $hostclient = entity_load('hostclient', $product->product_id);
    $client_id = $hostclient->getObjectId('client_uid');
    $combine_business = order_order_product_business_combine($product_business);
    $ipb_id = $this->getIpb($combine_business, $client_id, $hostclient->getObject('ipm_id')->get('group_id')->value);
    if(!empty($ipb_id)) {
      $ipb_ids = $ipb_id;
      $original_ipbs = $hostclient->get('ipb_id')->getValue();
      foreach($original_ipbs as $ipb) {
        $ipb_ids[] = $ipb['target_id'];
      }
      $hostclient->set('ipb_id', $ipb_ids);
      $hostclient->save_ipb_change = array('add' => $ipb_id, 'rm' => array());
      $hostclient->save();
    }

    //----写日志------
    $handle_info_log = $hostclient_service->loadHandleInfo($hid);
    $hostclient->other_data = array('data_id' => $hid, 'data_name' => 'hostclient_handle_info', 'data' => (array)$handle_info_log);
    $hostclient->other_status = 'upgrade_server';
    HostLogFactory::OperationLog('order')->log($hostclient, 'upgrade_server');

    // 写工单
    // 升级
    \Drupal::service('sop.soptaskservice')->sop_task_iband_for_hostclient($hostclient, 'Normal_Upgrade_hostclient', $hid);
  }

  /**
   * 创建一台服务器
   *   $product_business: 合并后的业务
   */
  public function createHostclient($order_product, $product_business, $client_id, $rid) {
    $product = entity_load('product', $order_product->product_id);
    $config = \Drupal::config('common.global');
    $config_audo_distribute = $config->get('auto_distribute');
    $ipm = $ipb_id = '';
    if ($config_audo_distribute) {
      $ipm = $this->getIpm($product, $product_business, $rid);
    }
    $value = array();
    if(!empty($ipm)) {
      $value['ipm_id'] = $ipm->ipm_id;
      $value['server_id'] = $ipm->server_id;
      $value['cabinet_server_id'] = $ipm->sid;
      $ipb_id = $this->getIpb($product_business, $client_id, $ipm->group_id);
    }
    if(!empty($ipb_id)) {
      $value['ipb_id'] = $ipb_id;
    }
    $value['product_id'] = $order_product->product_id;
    $value['status'] = 0;
    $hostclient = entity_create('hostclient', $value);
    if(!empty($ipb_id)) {
      $hostclient->save_ipb_change = array('add' => $ipb_id, 'rm' => array());
    }
    if($ipm) {
      $hostclient->brfore_save_ipm = 0;
    }
    return $hostclient;
  }

  /**
   * 获取一台机房中的服务器
   * @param $typeId
   *   服务器类型
   */
  private function getIpm($product, $business_list, $rid) {
    $product_type = $product->getObjectId('server_type');
    $part_business = array();
    foreach($business_list as $key => $item) {
      $business_obj = $item['business'];
      if($business_obj->getSimpleValue('resource_lib') == 'part_lib') {
        $part_business[$key] = $item;
      }
    }
    //@todo 并发可能重复获取同一台服务器；
    if($rid) {
      if(!empty($part_business)) {
        $query = $this->database->query('SELECT r.*,m.group_id FROM idc_cabinet_server_field_data r INNER JOIN idc_cabinet_field_data rc on r.cabinet_id = rc.cid INNER JOIN management_ip_field_data m ON r.ipm_id = m.id INNER JOIN server_field_data s on r.server_id = s.sid WHERE m.status =1 and m.server_type = 1 and s.part_change = 1 and s.type = :type and rc.rid= :rid', array(':type' => $product_type, ':rid' => $rid));
        $results = $query->fetchAll();
        foreach($results as $result) {
          $server = entity_load('server', $result->server_id);
          if($this->checkServerPart($server, $part_business)) {
            return $result;
          }
        }
      }
      $query = $this->database->query('SELECT r.*,m.group_id FROM idc_cabinet_server_field_data r INNER JOIN idc_cabinet_field_data rc on r.cabinet_id = rc.cid INNER JOIN management_ip_field_data m ON r.ipm_id = m.id INNER JOIN server_field_data s on r.server_id = s.sid WHERE m.status =1 and m.server_type = 1 and s.part_change = 0 and s.type = :type and rc.rid = :rid', array(':type' => $product_type, ':rid' => $rid));
      $results = $query->fetchAll();
      if($results) {
        $rand_id = array_rand($results, 1);
        return $results[$rand_id];
      }
    } else {
      if(!empty($part_business)) {
        $query = $this->database->query('SELECT r.*,m.group_id FROM idc_cabinet_server_field_data r INNER JOIN management_ip_field_data m ON r.ipm_id = m.id INNER JOIN server_field_data s on r.server_id = s.sid WHERE m.status =1 and m.server_type = 1 and s.part_change = 1 and s.type = :type', array(':type' => $product_type));
        $results = $query->fetchAll();
        foreach($results as $result) {
          $server = entity_load('server', $result->server_id);
          if($this->checkServerPart($server, $part_business)) {
            return $result;
          }
        }
      }
      $query = $this->database->query('SELECT r.*,m.group_id FROM idc_cabinet_server_field_data r INNER JOIN management_ip_field_data m ON r.ipm_id = m.id INNER JOIN server_field_data s on r.server_id = s.sid WHERE m.status =1 and m.server_type = 1 and s.part_change = 0 and s.type = :type', array(':type' => $product_type));
      $results = $query->fetchAll();
      if($results) {
        $rand_id = array_rand($results, 1);
        return $results[$rand_id];
      }
    }
    return null;
  }

  /**
   * 配件业务
   */
  private function checkServerPart($server, $part_business) {
    //得到租用的内存和硬盘
    $buy_memory = array();
    $buy_harddisk = array();
    //增加附加
    foreach($part_business as $key => $item) {
      $business_obj = $item['business'];
      $entity_type = $business_obj->getSimpleValue('entity_type');
      $values = $item['business_value'];
      $value_arr = explode(',', $values);
      foreach($value_arr as $item) {
        $conent = entity_load('product_business_entity_content', $item);
        if($entity_type == 'part_memory') {
          $buy_memory[] = $conent->getSimpleValue('target_id');
        } else if ($entity_type == 'part_harddisc') {
          $buy_harddisk[] = $conent->getSimpleValue('target_id');
        }
      }
    }
    //得到服务器的内存和硬盘
    $exist_memory = array();
    $memory_list = $server->get('memory')->getValue();
    foreach($memory_list as $memory) {
      if($memory['value'] == 0) {
        $exist_memory[] = $memory['target_id'];
      }
    }
    $exist_harddisk = array();
    $harddisk_list = $server->get('harddisk')->getValue();
    foreach($harddisk_list as $harddisk) {
      if($harddisk['value'] == 0) {
        $exist_harddisk[] = $harddisk['target_id'];
      }
    }
    //比较，如果购买比原有的多和等，则可以。
    $memory_ok = true;
    foreach($exist_memory as $memory_item) {
      $memory_ok = false;
      foreach($buy_memory as $key => $b_memory) {
        if($b_memory == $memory_item) {
          $memory_ok = true;
          unset($buy_memory[$key]);
          break;
        }
      }
      if(!$memory_ok) {
        break;
      }
    }
    $harddisk_ok = true;
    foreach($exist_harddisk as $harddisk_item) {
      $harddisk_ok = false;
      foreach($buy_harddisk as $key => $b_harddisk) {
        if($b_harddisk == $harddisk_item) {
          $harddisk_ok = true;
          unset($buy_harddisk[$key]);
          break;
        }
      }
      if(!$harddisk_ok) {
        break;
      }
    }
    if($memory_ok && $harddisk_ok) {
      return true;
    }
    return false;
  }

  /**
   * 获取业务IP
   * @todo 并发可能会得复，多个业务设置成同一个IP分类
   */
  private function getIpb($product_business, $client_id, $gid) {
    $ipb = array();
    $ibp_business = array();
    foreach($product_business as $key => $item) {
      $business_obj = $item['business'];
      if($business_obj->getSimpleValue('resource_lib') == 'ipb_lib') {
        $ibp_business[$key] = $item;
      }
    }
    foreach($ibp_business as $key =>$item) {
      $ipb_source = array();
      $number = 1;
      $business_obj = $item['business'];
      $op = $business_obj->getSimpleValue('operate');
      $value_inner = $item['business_value'];
      $value_arr = explode(',', $value_inner);
      foreach($value_arr as $value) {
        if($op == 'edit_number') {
          $number = $value;
          //获取IP分类
          $contents = entity_load_multiple_by_properties('product_business_entity_content', array('businessId' => $key));
          $types = array();
          foreach($contents as $content) {
            $types[] = $content->getSimpleValue('target_id');
          }
          if(empty($types)) {
            continue;
          }
          $str_type = implode(',', $types);
          $special_query = $this->database->query('select b.id from business_ip_field_data b where b.status = 1 and b.group_id = '. $gid .' and puid = :uid and b.type in ('. $str_type .')', array(':uid' => $client_id));
          $special_source = $special_query->fetchAll(); //专用IP
          foreach($special_source as $special) {
            if($number > 0) {
              $ipb[] = $special->id;
              $number--;
            } else {
              break;
            }
          }
          if($number == 0) {
            continue;
          }
          $query = $this->database->query('select b.id from business_ip_field_data b where b.status = 1 and b.group_id = '. $gid .' and puid is null and b.type in ('. $str_type .')');
          $ipb_source = $query->fetchAll();
        } else if ($op == 'select_content') {
          $content = entity_load('product_business_entity_content', $value);
          $special_query = $this->database->query('select b.id from business_ip_field_data b where b.status = 1 and b.group_id = '. $gid .' and puid=:uid and b.type = :type', array(
            ':uid' => $client_id,
            ':type' => $content->getSimpleValue('target_id')
          ));
          $special_source = $special_query->fetchAll(); //专用IP
          if(count($special_source) < 1) {
            $query = $this->database->query('select b.id from business_ip_field_data b where b.status = 1 and b.group_id='. $gid .' and puid is null and b.type = :type', array(':type' => $content->getSimpleValue('target_id')));
            $ipb_source = $query->fetchAll();
          }
        } else if ($op == 'select_and_number') {
          $values = explode(':', $value);
          $number = $values[1];
          $content = entity_load('product_business_entity_content', $values[0]);
          $special_query = $this->database->query('select b.id from business_ip_field_data b where b.status = 1 and b.group_id='. $gid .' and puid=:uid and b.type = :type', array(
            ':uid' => $client_id,
            ':type' => $content->getSimpleValue('target_id')
          ));
          $special_source = $special_query->fetchAll(); //专用IP
          foreach($special_source as $special) {
            if($number > 0) {
              $ipb[] = $special->id;
              $number--;
            } else {
              break;
            }
          }
          if($number == 0) {
            continue;
          }
          $query = $this->database->query('select b.id from business_ip_field_data b where b.status = 1 and b.group_id='. $gid .' and puid is null and b.type = :type', array(':type' => $content->getSimpleValue('target_id')));
          $ipb_source = $query->fetchAll();
        }
        if($number > count($ipb_source)) {
          $ipb = array();
          break;
        }
        $keys = array_rand($ipb_source, $number);
        if($number == 1) {
          $ipb[] = $ipb_source[$keys]->id;
        } else if($number > 1) {
          foreach($keys as $key) {
            $ipb[] = $ipb_source[$key]->id;
          }
        }
      }
      if(empty($ipb)) {
        return array();
      }
    }
    return $ipb;
  }

  /**
   * 获取服务器库存
   */
  public function getServerStock($product_type, $rid = 1) {
    if(is_array($rid)) {
      $rids = implode(',', $rid);
      $query = $this->database->query('SELECT count(*) FROM idc_cabinet_server_field_data r INNER JOIN idc_cabinet_field_data rc on r.cabinet_id = rc.cid INNER JOIN management_ip_field_data m ON r.ipm_id = m.id INNER JOIN server_field_data s on r.server_id = s.sid WHERE m.status =1 and m.server_type = 1 and s.type = :type and rc.rid in ('. $rids .')', array(':type' => $product_type));
    } else {
      $query = $this->database->query('SELECT count(*) FROM idc_cabinet_server_field_data r INNER JOIN idc_cabinet_field_data rc on r.cabinet_id = rc.cid INNER JOIN management_ip_field_data m ON r.ipm_id = m.id INNER JOIN server_field_data s on r.server_id = s.sid WHERE m.status =1 and m.server_type = 1 and s.type = :type and rc.rid = :rid', array(':type' => $product_type, ':rid' => $rid));
    }
    return $query->fetchField();
  }
  /**
   * 搜索指定机房下业务IP段
   * @description 工单详情列表下使用
   * @param $rid 机房序号
   */
  public function getMatchIpbSegment($rid) {
    if (empty($rid)) {
      return array();
    }
    $query = $this->database->select('business_ip_field_data','b')
     ->fields('b', array('ip_segment'))
     ->condition('rid', $rid)
     ->distinct(TRUE);
    $results = $query->execute()->fetchAll();
    $options = array();
    foreach($results as $item) {
      $options[$item->ip_segment] = $item->ip_segment;
    }
    return $options;
  }
  /**
   * 手动分配搜索业务IP
   */
  public function getMatchIpb($label = null, $client_uid = null, $ip_type = null, $group_id = null, $segment = array()) {
    $sql = "select b.id,b.ip,b.type from business_ip_field_data b where b.status = 1";
    $filters = array();
    if(!empty($label)) {
      $sql .= " and b.ip like '%". $label ."%'";
    }
    if (!empty($segment)) {
      $sql .= " and (";
      $i = 1; // 数组的顺序号
      foreach ($segment as $row) {
        if ($i == 1) {
          $sql .= " b.ip_segment like '%" . $row . "%'";
        } else {
          $sql .= " or b.ip_segment like '%" . $row . "%'";
        }
        $i++;
      }
      $sql .= " )";
    }
    if(!empty($client_uid)) {
      // @todo 这里解决下面两个地址下的请求
      // 1) admin/sop/sop_task_server/add
      // 2) admin/hostclient/2/business/dept
      $user = user_load_by_name($client_uid);
      if ($user instanceof \Drupal\user\Entity\User)
        $uid = $user->id();
      else
        $uid = $client_uid;
      $sql .= " and (puid is null or puid = $uid)";
    }
    else {
      $sql .= " and puid is null ";
    }
    if(!empty($ip_type)) {
      $sql .= " and classify = $ip_type";
    }
    if (!empty($group_id)) {
      $sql .= " and group_id = $group_id";
    }
    $sql .= ' order by puid desc, type asc';
    $query = $this->database->query($sql, $filters);
    $results = $query->fetchAll();
    $options = array();
    foreach($results as $item) {
      $entity_type = taxonomy_term_load($item->type);
      $options[$item->id] = $item->ip . '-' . $entity_type->label();
    }
    return $options;
  }
  /**
   * 自动获取所有在线服务器管理IP列表
   */
  public function getMatchClientStaticServer($search) {
    $sql = "select hid,ip,ipm_id from hostclients_field_data as u left join management_ip_field_data as m on u.ipm_id = m.id  where u.status=3";
    if (!empty($search)) {
      $sql .= " and m.ip like '%". $search ."%'";
    }
    $sql .= " order by hid desc";
    $query = $this->database->query($sql);
    $results = $query->fetchAll();
    $options = array();
    foreach ($results as $item) {
      $options[] = array(
        'value' => $item->ip . '(' . $item->ipm_id . ')',
        'label' => $item->ip
      );//$item->ip;
    }
    return $options;
  }
  /**
   * 自动获取管理IP
   */
  public function getMatchAllServer($search) {
    $sql = "select ipm_id, ip from idc_cabinet_server_field_data as i left join management_ip_field_data as m on i.ipm_id=m.id";
    if (!empty($search)) {
      $sql .= " where ";
    }
    if (!empty($search[0])) {
      $sql .= " m.ip like '%". $search[0] ."%'";
    }
    $sql .= " order by m.status desc limit 0,10";


    $query = $this->database->query($sql);
    $results = $query->fetchAll();
    $options = array();
    foreach ($results as $item) {
      $options[] = array(
        'value' => $item->ip . '(' . $item->ipm_id . ')',
        'label' => $item->ip
      );//$item->ip;
    }
    return $options;
  }
  /**
   * 自动获取客户的管理IP
   */
  public function getMatchClientServer($search) {
    $sql = "select hid,ip,ipm_id from hostclients_field_data as u left join management_ip_field_data as m on u.ipm_id = m.id  where u.status=3";
    if (!empty($search[0])) {
      $sql .= " and m.ip like '%". $search[0] ."%'";
    }
    $user = user_load_by_name($search[1]);
    if (!empty($search[1]) && ($user instanceof \Drupal\user\Entity\User)) {
      $sql .= " and u.client_uid=". $user->id();
    }
    $sql .= " order by hid desc";
    $query = $this->database->query($sql);
    $results = $query->fetchAll();
    $options = array();
    foreach ($results as $item) {
      $options[] = array(
        'value' => $item->ip . '(' . $item->ipm_id . ')',
        'label' => $item->ip
      );//$item->ip;
    }
    return $options;
  }
  /**
   * 手动分配管理IP的搜索语句方法
   * @param
   *  server_type:服务器类型
   *  current_ipm: 当前管理IP id.修改是要能选择自已
   *
   */
  public function getMatchServer($server_type, $room_id, $current_ipm, $label) {
    $options = array();
    if($room_id) {
      $query =  $this->database->query('select c.sid,m.ip from management_ip_field_data m inner join idc_cabinet_server_field_data c on m.id = c.ipm_id inner join server_field_data s on c.server_id = s.sid where m.status = 1 and m.server_type =1 and s.rid = :rid and s.type =:server_type and m.ip like :label or m.id =:current_ipm limit 0,10', array(
         ':rid' => $room_id,
         ':server_type' => $server_type,
         ':current_ipm' => $current_ipm,
         ':label' => '%'. $label .'%'
      ));
    } else {
      $query =  $this->database->query('select c.sid,m.ip from management_ip_field_data m inner join idc_cabinet_server_field_data c on m.id = c.ipm_id inner join server_field_data s on c.server_id = s.sid where m.status = 1 and m.server_type =1 and s.type =:server_type and m.ip like :label or m.id =:current_ipm limit 0,10', array(
         ':server_type' => $server_type,
         ':current_ipm' => $current_ipm,
         ':label' => '%'. $label .'%'
      ));
    }
    $results = $query->fetchAll();
    foreach($results as $item) {
      $options[] = array(
        'value' => $item->ip . '('. $item->sid .')',
        'label' => $item->ip
      );
    }
    return $options;
  }

  /**
   * 匹配数据验证
   */
  public static function matchValueValidate(&$element, FormStateInterface $form_state) {
   $value = NULL;
   if (!empty($element['#value'])) {
     preg_match('/.+\((\d+)\)/', $element['#value'], $matches);
     $value = $matches[1];
     $form_state->setValueForElement($element, $value);
   }
  }

}

