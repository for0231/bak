<?php
/**
 * @file
 * Contains \Drupal\order\user\MyTractionList.
 */

namespace Drupal\order\user;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyTractionList {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $qys = array()) {
    if(empty($qys)) {
       return array();
    }
    $datas = $this->getData($qys);
    if(!empty($datas)) {
      $form['qy_table'] = array(
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#empty' => t('No server')
      );
      foreach($datas as $key => $item) {
        $form['qy_table']['#rows'][$key] = $this->buildRow($item);
      }
      return $form;   
    }
    return array();
  }

  private function getData($qys) {
    $datas = array();
    $client_ips = \Drupal::service('hostclient.serverservice')->loadHostclientIp(\Drupal::currentUser()->id());
    foreach($client_ips as $item) {
      if(array_key_exists($item->ip, $qys)) {
        $datas[] = $qys[$item->ip] + array(
          'hostclient_id' => $item->hid,
          'server_id' => $item->server_id,
          'ipm_id' => $item->ipm_id 
        );
      }
    }
    return $datas;
  }

  /**
   * 构建表头
   */
  private function buildHeader() {
    $header['server_code'] = $this->t('Server code');
    $header['ip'] = $this->t('IP');
    $header['net_type'] = $this->t('Circuit');
    $header['bps'] = 'BPS';
    $header['pps'] = 'PPS';
    $header['start'] = $this->t('Start time');
    $header['untie_time'] = $this->t('Expiration time');
    return $header;
  }

  /**
   * 构建行
   */
  private function buildRow($item) {
    $server = entity_load('server', $item['server_id']);
    $row['server_code'] = $server->label();
    $row['ip'] = $item['ip'];
    $row['net_type'] = $item['net_type'];
    $row['bps'] = $item['bps'];
    $row['pps'] = $item['pps'];
    $row['start'] = $item['start'];
    $row['untie_time'] = $item['untie_time'];
    return $row;
  }
}
