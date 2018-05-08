<?php

namespace Drupal\hostlog;

/**
 * 构建日志类
 */
class HostLogFactory {
  /**
   * 操作日志
   */
  public static function OperationLog($module) {
    $class = '\Drupal\\'. $module .'\HostLog\OperationLog';
    if(class_exists($class) && is_subclass_of($class, '\Drupal\hostlog\OperationLogBase')) {
      return new $class();
    }
    return new OperationLogBase();
  }
}
