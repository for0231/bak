<?php
/**
 * @file
 * Contains \Drupal\sync_migration\SynIpData.
 */

namespace Drupal\sync_migration;

use Drupal\hostlog\HostLogFactory;

class SynIpData{

  protected $base_url;

  public function __construct($domain_name) {
    $this->base_url = $domain_name;
  }
  /**
   * @param $ip_type
   *   0 业务IP
   *   1 管理IP
   *   2 交换机IP
   */
  public function  originAccess($ip_type, $page) {
    try {
      $uri = $this->base_url . '/syn/ip_data_output_to_drupal.php?type=' . $ip_type . '&page=' . $page;
      $client = \Drupal::httpClient(); 
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
      return $response;
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
  }

  /**
   * 交换机IP
   */
  public function synIPSData() {
   for($i=1; $i < 50; $i++) {
      $response = $this->originAccess(2, $i);
      $json_str = (string)$response->getBody();
      if($json_str == 'null') {
        break;
      }
      $ips_s = json_decode($json_str, true);
      foreach($ips_s as $ips) {
        $ips_entity = entity_create('ips', array(
          'ip' => $ips['ip'],
          'port' => $ips['port'],
          'uid' => \Drupal::currentUser()->id(),
          'status_equipment' => 'off',
        ));
        $ips_entity->save();
        
        // 写入添加交换机IP的操作日志  
        HostLogFactory::OperationLog('ip')->log($ips_entity, 'isnert');
      }
    }
    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_sip', 1);
    $config->save();
  }

  /**
   * 管理IP
   */
  public function synIPMData() {
    for($i=1; $i < 50; $i++) {
      $response = $this->originAccess(1, $i);
      $json_str = (string)$response->getBody();
      if($json_str == 'null') {
        break;
      }
      $ipms = json_decode($json_str, true);
      foreach($ipms as $ipm) {
        switch($ipm['status']) {
          case  '可用IP':
            $status = 1;
            break;
          case '已用IP':
            $status = 5;
            break;
          case '禁用IP':
            $status = 4;
            break;
          case '保留IP':
            $status = 2;
            break;
          case '故障IP':
            $status = 3;
            break;
          default:
            $status = 0;
        }
        $ipm_entity = entity_create('ipm', array(
          'ip' => $ipm['ip'],
          'uid' => \Drupal::currentUser()->id(),
          'status' => $status,
          'server_type' => $ipm['server_type'],
          'created' => $ipm['created'],
          'status_equipment' => $ipm['status_equipment'],
          'description' => $ipm['description']
        ));
        $ipm_entity->save();
        
        //写入添加管理IP的操作日志  
        HostLogFactory::OperationLog('ip')->log($ipm_entity, 'insert');
      }
    }
    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_mip', 1);
    $config->save();
  }

  /**
   * 业务IP
   */
  public function synIPBData() {
    $bip_id_list = array();
    for($i=1; $i < 50; $i++) {
      $response = $this->originAccess(0, $i);
      $json_str = (string)$response->getBody();
      if($json_str == 'null') {
        break;
      }
      $ipbs = json_decode($json_str, true);
      $member_service = \Drupal::service('member.memberservice');
      $ip_type = array();
      foreach($ipbs as $ipb) {
        if($ipb['puid'] && $ipb['puid'] != '公用IP段') {
          $puid = $member_service->getUidByName($ipb['puid']);
        }
        switch($ipb['status']) {
          case  '可用IP':
            $status = 1;
            break;
          case '已用IP':
            $status = 5;
            break;
          case '禁用IP':
            $status = 4;
            break;
          case '保留IP':
            $status = 2;
            break;
          case '故障IP':
            $status = 3;
          case '专用IP':
            $status = 1;
            break;
          default:
            $status = 0;
        }

        switch($ipb['ip_segment']) {
          case  '23.234.32':
            $defense = '20G防御';
            break;
          case '23.234.34':
            $defense = '40G防御';
            break;
          case '23.234.36':
            $defense = '60G防御';
            break;
          case '23.234.38':
            $defense = '80G防御';
            break;
          default:
            $defense = '无防御';
        }
        if(!array_key_exists($defense, $ip_type)) {
          $terms = taxonomy_term_load_multiple_by_name($defense, 'business_ip_type');
          if(empty($terms)) {
            drupal_set_message($ipb['ip'] . '分类不存在');
            continue; 
          }
          $term = reset($terms);
          $ip_type[$defense] = $term->id();
        }
        $defense_drupal = $ip_type[$defense];
        $entity = entity_create('ipb', array(
          'ip' => $ipb['ip'],
          'puid' => isset($puid) ? $puid : NULL,
          'uid' => \Drupal::currentUser()->id(),
          'status' => $status,
          'type' => $defense_drupal,
          'ip_segment' => $ipb['ip_segment'].'.0/24',
          'description' => $ipb['description'],
          'created' => isset($ipb['created']) ? $ipb['created'] : REQUEST_TIME,
          'status_equipment' => $ipb['status_equipment'],
          'rid' => 1,
        ));
        $entity->save();
        $bip_id_list[$ipb['ip']] = $entity->id();

        //写入添加业务IP的操作日志 =============
        //HostLogFactory::OperationLog('ip')->log($entity, 'insert');
      }
      sleep(1);
    }

    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $json_str = json_encode($bip_id_list);
    $config->set('sync_bip_id_list', $json_str);
    $config->set('sync_bip', 1);
    $config->save();
  }
}


