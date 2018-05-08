<?php
/**
 * 实例化写黑洞类
 * @author tgl
 *
 */

namespace Drupal\qy\Blackhole;

class FactoryBlackhole {
  /**
   * 实例化
   */
  public static function getInstance($route) {
    if($route->mode_command == 2) {
      return new BlackholeCisco($route);
    } else {
      return new BlackholeVyatta($route);
    }
  }
}
