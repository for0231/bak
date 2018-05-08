<?php
/**
 * vyatta类线路命令
 * @author tgl
 */

namespace Drupal\qy\Blackhole;

class BlackholeVyatta extends BlackholeBase {
  /**
   * 保存最后一次查黑洞的IP
   */
  private $last_ip = array();
  
  public function writeBlackhole($qys, $contrast_fw = true) {
    $blackhole = $this->getBlackhole($qys);
    foreach($blackhole as $key => $item) {
      echo "写黑洞 $key 的开始时间: ".  date("H:i:s") ."\r\n";
      $telnet = FactoryTelnet::getInstance($key, $item['user'], $item['pass'], 'iBGP:~$ ');
      if(empty($telnet)) {
        echo "连接黑洞 $key 失败".  date("H:i:s") ." \r\n";
        continue;
      }
      if($contrast_fw) {
        $telnet->DoCommand('show configuration commands | match blackhole', $result, 'iBGP:~$ ');
        preg_match_all('/(\d+)\.(\d+)\.(\d+)\.(\d+)/', $result, $matches);
        $this->last_ip[$key] = $matches[0];
      }
      $commands = $this->withBlackhole($item['ip'], $this->last_ip[$key], $item['count']);
      $this->last_ip[$key] = $item['ip'];
      $add = $commands['add'];
      $rm = $commands['rm'];
     if(empty($add) && empty($rm)) {
        echo "无写入数据\r\n";
        continue;
      }
      $telnet->DoCommand('configure', $result, 'iBGP# ');
      foreach($rm as $rm_ip) {
        $telnet->DoCommand('del protocols static route '. $rm_ip .'/32', $result, 'iBGP# ');
      }
      foreach($add as $add_ip) {
        $telnet->DoCommand('set protocols static route '. $add_ip .'/32 ' . $item['blackhole_command'], $result, 'iBGP# ');
      }
      $telnet->DoCommand('commit', $result, 'iBGP# ');
      $telnet->DoCommand('exit', $result, 'iBGP:~$ ', array('what'=> 'iBGP# ', 'contain' => array(
        'value' => 'exit discard',
        'comm' => 'exit discard'
      )));
      echo "\r\n写入黑洞 $key 成功(". date("H:i:s") .")\r\n";
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
      foreach($blackhole as $key => $value) {
        $ips = $value['ip'];
        if(!in_array($qy->ip, $ips)) {
          $blackhole[$key]['ip'][] = $qy->ip;
        }
      }
    }
    return $blackhole;
  }

  /**
   * 要牵引的IP与黑洞IP对比
   */
  private function withBlackhole($qy_ips, $blackhole_ips, $count) {
    $add = array();
    $rm = array();
    //找出要添加的和已经存在的.
    $n = 0;
    $exists = array();
    foreach($qy_ips as $qy_ip) {
      if(in_array($qy_ip, $blackhole_ips)) {
        $exists[] = $qy_ip;
      } else {
        if($n < $count) {
          $add[] = $qy_ip;
        }
        $n++;
      }
    }
    //超总数移出数据库存在的. 不能减去rm的是因为$qy_ips里面已经不存在移出的了。
    $total = count($add) + count($exists);
    if($total > $count) {
      $s = count($exists) -1;
      $num = $total - $count;
      for($i=0; $i<$num; $i++) {
        $rm[] = $exists[$s - $i];
      }
    }
    //移出已经解牵引
    foreach($blackhole_ips as $blackhole_ip) {
      if(!in_array($blackhole_ip, $qy_ips)) {
        $rm[] = $blackhole_ip;
      }
    }
    $commands = array(
      'add' => $add,
      'rm' => $rm
    );
    return $commands;
  }
}
