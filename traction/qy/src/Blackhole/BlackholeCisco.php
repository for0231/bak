<?php
/**
 * 转思科路由流量类命令
 * @author tgl
 *
 */

namespace Drupal\qy\Blackhole;

class BlackholeCisco extends BlackholeBase {

  public function writeBlackhole($qys, $contrast_fw = true) {
    $blackhole = $this->getBlackhole($qys);
    foreach($blackhole as $key => $item) {
      echo "写黑洞 $key 的开始时间: ".  date("H:i:s") ."\r\n";
      $telnet = FactoryTelnet::getInstance($key, $item['user'], $item['pass'], '#');
      if(empty($telnet)) {
        echo "连接黑洞 $key 失败".  date("H:i:s") ." \r\n";
        continue;
      }
      $telnet->DoCommand('configure', $result, '(config)#');
      $telnet->DoCommand('prefix-set ' . $item['blackhole_command'], $result, '(config-pfx)#');
      $ips = $item['ip'];
      $count = count($ips);
      $n = 1;
      foreach($ips as $ip) {
        if($count == $n) {
          $telnet->DoCommand($ip . '/24', $result, '(config-pfx)#');
        } else {
          $telnet->DoCommand($ip . '/24,', $result, '(config-pfx)#');
        }
        $n++;
      }
      $telnet->DoCommand('exit', $result, '(config)#');
      $telnet->DoCommand('commit', $result, '(config)#', array('what'=> '[cancel]:', 'contain' => array(
        'value' => 'yes/no/cancel',
        'comm' => 'yes'
      )));
      $telnet->DoCommand('exit', $result, '#',  array('what'=> '[cancel]:', 'contain' => array(
        'value' => 'yes/no/cancel',
        'comm' => 'yes'
      )));
    }
  }
  
  /**
   * 得到各个黑洞要写入的IP
   * @param unknown $qys
   * @return multitype:multitype:NULL
   */
  private function getBlackhole($qys) {
    $blackhole = array();
    $route = $this->route;
    $items = explode(',', $route->blackhole);
    foreach ($items as $item) {
      $blackhole[$item] = array(
        'user' => $route->username,
        'pass' => $route->password,
        'count' => $route->max_count,
        'blackhole_command' => $route->blackhole_command,
        'ip' => array()
      );
    }
    foreach($qys as $qy) {
      $ip_arr = explode('.', $qy->ip);
      $ip = "{$ip_arr[0]}.{$ip_arr[1]}.{$ip_arr[2]}.0";
      foreach($blackhole as $key => $value) {
        $ips = $value['ip'];
        if(!in_array($ip, $ips)) {
          $blackhole[$key]['ip'][] = $ip;
        }
      }
    }
    return $blackhole;
  }
}
