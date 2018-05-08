<?php
/**
 * @file
 * ip牵引为用户发送邮件
 */

namespace Drupal\qy_jd;

use Drupal\qy\QyMailSendBase;

class QyMailSend extends QyMailSendBase{
  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }
  /**
   * 设置数据服务类
   */
  private function __construct() {
    $this->db_service = \Drupal::service('qy_jd.db_service');
  }
  /**
   * 获取要发送邮件的牵引Ip
   */
  protected function getIps($number) {
    $ips = array();
    $route_ids = array();
    $routes = $this->db_service->load_route(array('status' =>1), true);
    foreach($routes as $route) {
      $email_content = trim($route->email_content);
      if(!empty($email_content)) {
        $route_ids[] = $route->id;
      }
    }
    if(empty($route_ids)) {
      return array();
    }
    $qys = $this->db_service->load_qy(array('emial_send' => 0, 'net_type' => array('value' => $route_ids, 'op' => 'IN')), 'ASC');
    $n = 0;
    foreach($qys as $qy) {
      if($n >= $number) {
        break;
      }
      $route = $routes[$qy->net_type];
      $ips[] = array(
        'id' => $qy->id,
        'ip' => $qy->ip,
        'bps' => $qy->bps,
        'begintime' => format_date($qy->start, 'custom', 'Y-m-d H:i:s'),
        'endtime' => $qy->time == 0 ? '需手动解封' : format_date($qy->start + $qy->time * 60, 'custom', 'Y-m-d H:i:s'),
        'routename' => $route->routename,
        'mialcontent' => $route->email_content
      );
      $n++;
    }
    return $ips;
  }
  /**
   * 发送邮件成功
   */
  protected function sendSuccess($id) {
    $this->db_service->update_qy(array('emial_send' => 1), $id);
  }
}
