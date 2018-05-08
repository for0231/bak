<?php
/**
 * @file
 * Contains \Drupal\utils\Controller\NetworkConfigController.
 */

namespace Drupal\utils\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\utils\PhpSocketClient;

class NetworkConfigController extends ControllerBase {
  //查看日志
  public function logView() {
    $logs = new \Drupal\utils\NetworkLogsList();
    return $logs->build();
  }
  
  /**
   * 保存配置
   */
  public function saveConfig() {
    $response = new AjaxResponse();
    $settings = \Drupal::service('settings');
    $sock_ip = $settings->get('py_socket_ip');
    if(empty($sock_ip)) {
      $response->addCommand(new AlertCommand('请求地址不存在'));
      return $response;
    }
    $ip = $_GET['ip'];
    $port = $_GET['port'];
    $manageip = $_GET['manageip'];
    $switchname = $_GET['switchname'];
    
    $sock = new PhpSocketClient($sock_ip, 9000);
    $send = array('ip' => $ip, 'port' => $port, 'op' => 'login_save_config');
    $res = $sock->command(json_encode($send), 'port_config');
    
    //记录日志
    $db_service = \Drupal::service('utils.networkconfig');
    $log_id = $db_service->insertLog(array(
      'created' => time(),
      'uid' => \Drupal::currentUser()->id(),
      'type' => 'portconfig',
      'command' => '接口配置：交换机['. $ip .']端口['. $port .']保存配置',
      'response' => $res
    ));
    $response->addCommand(new AlertCommand($res));
    //$response->addCommand(new RedirectCommand(\Drupal::url('admin.utils.port.config', array('manageip' =>$manageip,'port' => $port, 'switchname' => $switchname,'switchip' => $ip))));
    return $response;
  }
}