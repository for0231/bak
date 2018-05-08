<?php
/**
 * @file
 * ip牵引为用户发送邮件基础类
 */

namespace Drupal\qy;

use Drupal\Component\Render\PlainTextOutput;

abstract class QyMailSendBase {
  /**
   * 数据服务对象
   */
  protected $db_service;

  /**
   * 发送邮件
   */
  public function send($number) {
    $items = $this->getIps($number);
    if(empty($items)) {
      return 0;
    }
    $log = '=======begin('. date("H:i:s") .')======' . "\r\n";
    $file_path = \Drupal::service('settings')->get('qy_file_path') . '/Email';
    if(!is_dir($file_path)) {
      mkdir($file_path, 0777, true);
    }
    $filename = $file_path . '/'. date("Ymd") . 'mail.log';
    $ips = array();
    $info = array();
    $mail_service = \Drupal::service('qy.emial_service');
    foreach($items as $item) {
      $result = $this->getLocalhostEmail($mail_service, $item['ip']);
      if($result) {
        $key = $result->email . '#' . $result->username;
        if(array_key_exists($key, $info)) {
          if(!in_array($item['ip'], $info[$key])) {
            $info[$key][] = $item['ip'];
          }
        } else {
          $info[$key][] = $item['ip'];
        }
      } else {
        $ips[] = $item['ip'];
      }
    }
    if(!empty($ips)) {
      $remote_info = $this->getEmail($ips);
      if(!is_array($remote_info)) {
        $log .= 'error: ' . $remote_info . "\r\n";
        file_put_contents($filename, $log, FILE_APPEND);
        return 0;
      }
      $info = array_merge_recursive($info, $remote_info);
    }
    foreach($items as $item) {
      $this->db_service->update_qy(array('emial_send' => 9), $item['id']);
    }
    if(isset($info['#empty'])){
      $emptys = $info['#empty'];
      foreach($emptys as $emp_ip) {
        $qys = $this->getQy($items, $emp_ip);
        foreach($qys as $qy) {
          $this->db_service->update_qy(array('emial_send' => 2), $qy['id']);
        }
      }
      unset($info['#empty']);
    }
    $langcode = \Drupal::languageManager()->getDefaultLanguage();
    foreach($info as $key => $info_ips) {
      $user_info = explode('#', $key);
      if($user_info[0] == 'z@z.com') {
        continue;
      }
      foreach($info_ips as $ip) {
        $qys = $this->getQy($items, $ip);
        foreach($qys as $qy) {
          $title = '系统通知-IP被牵引';
          $content = strtr($qy['mialcontent'], array(
            '{username}' => $user_info[1],
            '{ip}' => $ip,
            '{bps}' => $qy['bps'],
            '{begintime}' => $qy['begintime'],
            '{endtime}' => $qy['endtime'],
            '{routename}' => $qy['routename']
          ));
          $message = \Drupal::service('plugin.manager.mail')->mail('qy', 'traction', $user_info[0] , $langcode, array(
            'subject' => $title,
            'body' => array($content),
          ));
          $log .= 'ip:' . $ip . "\r\n";
          $log .= 'to:' . $user_info[0] . "\r\n";
          $log .= 'content:' . $content . "\r\n";
          if($message['result']) {
            $this->sendSuccess($qy['id']);
            $log .= "result: success(". date("H:i:s") .") \r\n\r\n";
          } else {
            $this->db_service->update_qy(array('emial_send' => 0), $qy['id']);
            $log .= "result: fail(". date("H:i:s") .") \r\n\r\n";
          }
          file_put_contents($filename, $log, FILE_APPEND);
          $log = '';
          sleep(5);
        }
      }
    }
    return count($items);
  }

  /**
   * 获取牵引信息
   */
  private function getQy($items, $ip) {
    $qys = array();
    foreach($items as $item) {
      if($item['ip'] == $ip) {
        $qys[] = $item;
      }
    }
    return $qys;
  }

  /**
   * 获取Email
   */
  protected function getEmail($ips) {
    $url = \Drupal::service('settings')->get('Interface_user_mail_url');
    if(empty($url)) {
      return array();
    }
    $str_ip = implode(',', $ips);
    $pram =  serialize($str_ip);
    $path = strtr($url, array('{ip}' => $pram));
    $u = parse_url($path);
    $host = $u['host'];
    $port = isset($u['port']) ? $u['port'] : 80;
    $fp = @fsockopen($host, $port, $errno, $errstr, 5);
    if (!$fp) {
      return $errstr;
    }
    if(isset($u['query'])) {
      $send = "GET ". $u['path'] ."?". $u['query'] ." HTTP/1.1\r\n";
    } else {
      $send = "GET ". $u['path'] ." HTTP/1.1\r\n";
    }
    $send .= "Host: $host\r\n";
    $send .= "Cache-Control: no-cache\r\n\r\n";
    fwrite($fp, $send);
    stream_set_timeout($fp, 4);
    $str_body = '';
    $is_body = false;
    while ($f = fgets($fp)) {
      if ($f == "\r\n") {
        $is_body = true;
        continue;
      }
      if($is_body) {
        $str_body.= $f;
      }
    }
    fclose($fp);
    return unserialize($str_body);
  }

  /**
   * 获取牵引系统本
   */
  private function getLocalhostEmail($mail_service, $ip) {
    $ip_arr = explode('.', $ip);
    $result = $mail_service->load_email_nopage(array('ip' => array('value' => "{$ip_arr[0]}.{$ip_arr[1]}.{$ip_arr[2]}.", 'op' =>'like')), 'mask_number');
    foreach($result as $item) {
      $ips = explode('.', $item->ip);
      $mask_number = $item->mask_number;
      $n = 32 - $mask_number;
      $num = pow(2, $n);
      $max = $ips[3] + $num - 1;
      if($ip_arr[3] >= $ips[3] && $ip_arr[3] <= $max) {
        return $item;
      }
    }
    return array();
  }

  /**
   * 获取要发送的邮件的IP;
   */
  abstract protected function getIps($number);
  abstract protected function sendSuccess($id);
}
