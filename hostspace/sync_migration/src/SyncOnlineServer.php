<?php

namespace Drupal\sync_migration;

class SyncOnlineServer {

  protected $base_url;

  public function __construct($domain_name) {
    $this->base_url = $domain_name;
  }

  /**
   * @ip_server
   *   管理ip对应该的id
   * @bip_id_list
   *  业务IP对应的id
   * @business_ids
   *   产品业务对应的id
   */
  public function syncOnlineServerData($ip_server, $bip_id_list, $business_ids) {
    try {
      $uri = $this->base_url . '/syn/online_server.php';
      $client = \Drupal::httpClient(); 
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
    $json_str = (string)$response->getBody();
    $hostclients = json_decode($json_str, true);

    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $default_business = array();
    foreach($hostclients as $host) {
      $hostclient = $this->saveHostclient($host, $ip_server, $bip_id_list);
      if(!empty($hostclient)) {
        //得到产品的默认业务
        $product_id = $hostclient->getObjectId('product_id');
        if(!array_key_exists($product_id, $default_business)) {
          $default_business[$product_id] = $this->getDefaultBusinessList($product_id);
        }
        $hostclient_business = $default_business[$product_id];
        //得到产品的ip业务
        $ipb_ids = $hostclient->get('ipb_id')->getValue();
        $this->getIPBusiness($hostclient_business, $business_ids, $ipb_ids);
        //保存业务
        $hostclient_service->addHostclientBusiness($hostclient->id(), $hostclient_business);
      }
    }
    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_online_server', 1);
    $config->save();
  }

  private function saveHostclient($host, $ip_server, $bip_id_list) {
    $mip = $host['mip'];
    $products = entity_load_multiple_by_properties('product', array('name' => $host['pid']));
    if(empty($products)) {
      drupal_set_message($mip . '的产品不存在');
      return null;
    }
    $product = reset($products);
    if(!isset($ip_server[$mip])) {
      drupal_set_message($mip . '服务器不存在');
      return null;
    }
    if(!isset($ip_server[$mip]['cabinet_server_id'])) {
      drupal_set_message($mip . '服务器未上柜');
      return null; 
    }    
    $ipbs = preg_split('/(\r\n|\n|\r)/',  $host['ip']);
    $ipb_ids = array();
    foreach($ipbs as $ipb) {
      if(!empty($ipb)) {
        if(!isset($bip_id_list[$ipb])) {
          drupal_set_message($mip . '中的业务ip'. $ipb .'不存在');
          return null;
        }
        $ipb_ids[] = $bip_id_list[$ipb];
      }
    }
    if(empty($ipb_ids)) {
      drupal_set_message($mip . '的业务ip为空');
      return null;
    }
    if(empty($host['client_uid'])) {
      drupal_set_message($mip . '无客户信息传入');
      return null;
    }
    $users = entity_load_multiple_by_properties('user', array('name' => $host['client_uid']));
    if(empty($users)) {
      drupal_set_message($mip . '的客户不存在');
      return null;
    }
    $user = reset($users);
    $client_uid = $user->id();
    $server_id = $ip_server[$mip]['sid'];
    $cabinet_server_id = $ip_server[$mip]['cabinet_server_id'];
    $ipm_id = $ip_server[$mip]['ipm_id'];
    $hostclient = entity_create('hostclient', array(
      'product_id' => $product->id(), 
      'server_id' => $server_id,
      'cabinet_server_id' => $cabinet_server_id,
      'ipm_id' => $ipm_id,
      'ipb_id' => $ipb_ids,
      'client_uid' => $client_uid,
      'equipment_date' => $host['equipdate'],
      'service_expired_date' => $host['dateexpire'],
      'status' => 3
    ));
    $hostclient->save();
    return $hostclient;
 }

  /**
   * 获取默认业务
   */
  private function getDefaultBusinessList($productId) {
    $business_list = array();
    $default_business = \Drupal::service('product.default.business')->getListByProduct($productId);
    foreach($default_business as $item) {
      $business = new \stdClass();
      $business->business_id = $item->businessId;
      $business->business_content = $item->business_content;
      $business_list[$business->business_id] = $business; 
    }
    return $business_list;
  }

  /**
   * 获取IP业务
   */
  private function getIPBusiness(&$hostclient_business, $business_ids, $ipb_ids) {
    if(count($ipb_ids)) {
      $business_ip_id = $business_ids['ip']['id'];
      if(array_key_exists($business_ip_id, $hostclient_business)) {
        $old_ips = $hostclient_business[$business_ip_id]->business_content;
        if(count($ipb_ids) > $old_ips) {
          $hostclient_business[$business_ip_id]->business_content = count($ipb_ids);
        }
      } else {
        $business = new \stdClass();
        $business->business_id = $business_ip_id;
        $business->business_content = count($ipb_ids);
        $hostclient_business[$business_ip_id] = $business;
      }
    }
  }
}
