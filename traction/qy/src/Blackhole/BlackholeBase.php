<?php
/**
 * 写黑洞操作基础类
 * @author tgl
 *
 */

namespace Drupal\qy\Blackhole;

abstract class BlackholeBase {
  /**
   * 线路
   */
  protected $route;
  
  public function __construct($route) {
    $this->route = $route;
  }
  
  /**
   * 写黑洞
   */
  abstract public function writeBlackhole($qys, $contrast_fw = true);
}
