<?php

/**
 * @file Drupal\sop\HostLog\OperationLog.
 */

namespace Drupal\sop\HostLog;

use Drupal\hostlog\OperationLogBase;

/**
 * 操作日志.
 */
class OperationLog extends OperationLogBase {
  /**
   * 构建日志消息.
   *
   * @param
   *  - $entity 当前操作实体.
   *  - $action 当前操作（如insert, update, delete等).
   */
  protected function message($entity, $action) {
    $message = '';
    if (isset($entity->other_status)) {
      $other_status = $entity->other_status;
      switch ($other_status) {
        case 'sop_common_task':
          if ($action == 'delete') {
            $message = strtr('工单【%id】已删除', array(
              '%id' => $entity->id(),
            ));
          }
          elseif ($action == 'create') {
            $sop_status = sop_task_status()[$entity->get('sop_status')->value];
            $sop_type = sop_task_op_status()[$entity->get('sop_op_type')->value];
            $message = strtr('工单【%id】已创建, 类型：【%type】， 状态：【%status】', array(
              '%id' => $entity->id(),
              '%type' => $sop_type,
              '%status' => $sop_status,
            ));
          }
          elseif ($action == 'update') {
            $module_sop_type = sop_task_op_status();
            $module_sop_status = sop_task_status();
            $sop_status = $module_sop_status[$entity->get('sop_status')->value];
            $sop_type = $module_sop_type[$entity->get('sop_op_type')->value];
            $hostclient = $entity->get('hid')->entity;
            $mip = !empty($hostclient->getObject('ipm_id')) ? $hostclient->getObject('ipm_id')->label() : '';
            $bips = $this->getHostclientBusinessIP($hostclient);

            $message = strtr('工单【%id】已更新, 类型：【%type】， 状态：【%status】,管理IP：【%mip】, 业务IP：【%bips】', array(
              '%id' => $entity->id(),
              '%type' => $sop_type,
              '%status' => $sop_status,
              '%mip' => $mip,
              '%bips' => $bips,
            ));
          }
          break;
      }
    }

    return $message;
  }

  /**
   * Private function.
   */
  private function getHostclientBusinessIP($hostclient = NULL) {
    if (empty($hostclient)) {
      return NULL;
    }
    $bips_options = array();
    $bips_values = $hostclient->get('ipb_id');
    foreach ($bips_values as $value) {
      $ipb_obj = $value->entity;
      if ($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $bips_options[$ipb_obj->id()] = $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label();
      }
    }
    return implode(',', $bips_options);
  }

}
