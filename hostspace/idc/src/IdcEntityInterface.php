<?php
/**
 * 实体公用接口
 */

namespace Drupal\idc;


interface IdcEntityInterface {
  /**
   * 获取简单的实体字段的值
   */
  public function getSimpleValue($name);

  /**
   * 获取对象字段的实体
   */
  public function getObject($name);

  /**
   * 获取对象字段的实体Id
   */
  public function getObjectId($name);
}
