<?php
/**
 * 实便化telnet
 * @author tgl
 *
 */

namespace Drupal\qy\Blackhole;

class FactoryTelnet {

  /**
   * 用实存放每个黑洞的实例
   * @var unknown
   */
  private static $arr = array();
  
  /**
   * 连接黑洞
   *  blackhole_ip：黑洞IP
   *  user: 用户名
   *  pass：密码
   *  ends：array(
   *    0 => 判断可以输入用户名的字符串
   *    1 => 判断可以输入密码的字符串
   *    2 => 判断登录成功的字符串
   *  );
   */
  public static function getInstance($blackhole_ip, $user, $pass, $ends = array()) {
    if(!array_key_exists($blackhole_ip, self::$arr)) {
      $telnet = new PHPTelnet();
      $result = $telnet->Connect($blackhole_ip, $user, $pass, $ends);
      if($result == 0) {
        self::$arr[$blackhole_ip] = $telnet;
        return $telnet;
      }
      return null;
    } else {
      $telnet = self::$arr[$blackhole_ip];
      if($telnet->isConnect()) {
        return $telnet;
      } else {
        unset(self::$arr[$blackhole_ip]);
        $telnet = new PHPTelnet();
        $result = $telnet->Connect($blackhole_ip, $user, $pass, $ends);
        if($result == 0) {
          self::$arr[$blackhole_ip] = $telnet;
          return $telnet;
        }
        return null;
      }
    }
  }
}
