<?php

namespace Drupal\utils;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

class NetworkLogsList {
  
  protected $db_service;
  
  public function __construct() {
    $this->db_service = \Drupal::service('utils.networkconfig');
  }

  public function build() {
    $conditions = array();
    if(!empty($_SESSION['network_logs_config_log'])) {
      if(!empty($_SESSION['network_logs_config_log']['uid'])) {
        $conditions['uid'] = $_SESSION['network_logs_config_log']['uid'];
      }
      if(!empty($_SESSION['network_logs_config_log']['begin'])) {
        $conditions['begin_time'] = strtotime($_SESSION['network_logs_config_log']['begin']);
      }
      if(!empty($_SESSION['network_logs_config_log']['end'])) {
        $conditions['end_time'] = strtotime($_SESSION['network_logs_config_log']['end']) + 24*3600;
      }
      if(!empty($_SESSION['network_logs_config_log']['keyword'])) {
        $conditions['keyword'] = $_SESSION['network_logs_config_log']['keyword']; 
      }
    }
    $build = \Drupal::formBuilder()->getForm('Drupal\utils\Form\NetworkLogsFilterForm');   
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array('用户', '时间', '执行内容', '执行结果'),
      '#rows' => array(),
      '#empty' => '无数据'
    );
    $logs = $this->db_service->logList($conditions);
    foreach($logs as $log) {
      $command = '';
      if($log->type == 'networkconfig') {
        $datas = unserialize($log->command);
        $command = $datas['ips'];
      } else {
        $command = $log->command;
      }
      $build['list']['#rows'][] = array(
        entity_load('user', $log->uid)->label(),
        date('Y-m-d h:i:s', $log->created),
        $command,
        $log->response
      );
    }
    $build['list_page'] = array('#type' => 'pager');
    return $build;
  }
  
}
