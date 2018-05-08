<?php
/**
 * @file
 * 操作sop实体表.
 */

namespace Drupal\sop;

use Drupal\Core\Database\Connection;
use Drupal\hostlog\HostLogFactory;
/**
 *
 */
class SopTaskService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   *
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Sop entity table save.
   */
  public function save($entity, $update = TRUE) {
    if ($update) {
      $sop_module_type = $entity->getEntityTypeId();
      $sop_module_type_id = $entity->id();
      $arr_entity = entity_load_multiple_by_properties('sop', array(
        'sid' => $sop_module_type_id,
        'module' => $sop_module_type,
      ));
      $sop_entity = current($arr_entity);
      $sop_entity->set('sop_type', $entity->get('sop_type')->value);
      $sop_entity->set('sop_op_type', $entity->get('sop_op_type')->value);
      $sop_entity->set('sop_status', $entity->get('sop_status')->value);
      $sop_entity->set('sop_complete', $entity->get('sop_complete')->value);
      $sop_entity->set('solving_uid', $entity->get('solving_uid')->target_id);
      $sop_entity->set('presolving_uid', $entity->get('presolving_uid')->target_id);

      $sop_entity->save();
    }
    else {
      $sop_entity = entity_create('sop', array(
        'module' => $entity->getEntityTypeId(),
        'sid' => $entity->id(),
        'sop_op_type' => $entity->get('sop_op_type')->value,
        'sop_status' => $entity->get('sop_status')->value,
        'sop_type' => $entity->get('sop_type')->value,
        'hid' => $entity->get('hid')->target_id,
        'handle_id' => $entity->get('handle_id')->value,
        'created' => REQUEST_TIME,
      ));
      $sop_entity->save();
      $sop_entity->other_status = 'sop_common_task';
      HostLogFactory::OperationLog('sop')->log($sop_entity, 'create');
    }
  }
  /**
   * IP带宽类型工单.
   *
   * @todo 未处理其他不同于IP带宽的字段待处理
   */
  public function sop_task_iband_for_hostclient($hostclient, $type, $hid = NULL, $status = NULL) {
    $extra = $this->sop_task_iband_4_hostcient_data($hostclient, $type, $hid);
    if (empty($status)) {
      $iband = entity_create('sop_task_iband', $extra);
      $iband->save();
    }
    else {
      $origin_iband_entity_array = entity_load_multiple_by_properties('sop_task_iband', array(
        'sop_op_type' => 19,
        'hid' => $hostclient->id(),
      ));
      if (!empty($origin_iband_entity_array)) {
        $iband_entity = reset($origin_iband_entity_array);
        $iband_entity->set('sop_op_type', 16);
        $iband_entity->set('sop_complete', 0);
        $iband_entity->set('sop_type', 'i1');
        $iband_entity->set('handle_id', $hid);
        $iband_entity->set('sop_status', 0);
        $iband_entity->save();
      }
    }
  }

  /**
   * IP带宽类型工单的服务器数据来源.
   */
  private function sop_task_iband_4_hostcient_data($hostclient, $type, $hid = NULL) {
    $sop_extra = array(
      'hid' => $hostclient->id(),
    // handler_info ID.
      'handle_id' => $hid,
      // 'mips' => $hostclient->get('ipm_id')->target_id,.
    );
    $sop_type = $this->sop_task_server_4_op_types($type);

    return $sop_extra + $sop_type;
  }
  /**
   * 生成服务器上下架工单.
   *
   * @param $hostclient 在线服务器对象
   * @param $type 操作类型
   * @param $hid 事务处理的ID*
   */
  public function sop_task_server_for_hostclient($hostclient, $type, $hid = NULL) {
    $extra = $this->sop_task_server_4_hostclient_data($hostclient, $type, $hid);
    $server = entity_create('sop_task_server', $extra);
    $server->save();
  }

  /**
   * 构建SOP服务器工单流程数组.
   *
   * @param $hostclient 在线服务器对象
   * @param $type 操作类型
   * @param $hid 事务处理的ID
   */
  private function sop_task_server_4_hostclient_data($hostclient, $type, $hid = NULL) {
    $sop_extra = array(
      'hid' => $hostclient->id(),
    // handler_info ID.
      'handle_id' => $hid,
      // 'mips' => $hostclient->get('ipm_id')->target_id,.
    );
    $sop_type = $this->sop_task_server_4_op_types($type);

    return array_merge($sop_extra, $sop_type);
  }
  /**
   * 构建服务器上架工单的操作类型.
   *
   * @param $type 常量
   */
  private function sop_task_server_4_op_types($type) {
    $sop_type = array();
    switch ($type) {
      // 服务器IP变更.
      case 'Normal_Upgrade_hostclient_IP':
        $sop_type = array(
          'sop_op_type' => 15,
          'sop_type' => 'i2',
        );
        break;

      // 服务器上架.
      case 'Normal_UP_hostclient':
        $sop_type = array(
          'sop_op_type' => 16,
          'sop_type' => 'i2',
        );
        break;

      // 服务器下架.
      case 'Normal_Down_hostclient':
        $sop_type = array(
          'sop_op_type' => 17,
          'sop_type' => 'i2',
        );
        break;

      // 服务器升级.
      case 'Normal_Upgrade_hostclient':
        $sop_type = array(
          'sop_op_type' => 18,
          'sop_type' => 'i2',
        );
        break;

      // 服务器试用.
      case 'Normal_Trial_hostclient':
        $sop_type = array(
          'sop_op_type' => 19,
          'sop_type' => 'i1',
        );
        break;
    }
    return $sop_type;
  }
  /**
   * 故障申报自动保存故障工单.
   * @code
   * $extra = array(
   *    'qid' => $duplicate->id(),
   *    'level' => 0,
   *    'hid' => $hostclient->id(),
   *    'handle_id' => 0,
   *    'description' => $form_state->getValue('content')[0],
   *    'os' => $hostclient->get('server_system')->target_id,
   *    'sop_type' => 'p1', //默认使用P1
   * );
   * @endcode
   */
  public function question2failure($extra) {
    $failure = entity_create('sop_task_failure', $extra);
    $failure_entity = $failure->save();

    // $this->sopEntityPostSave($failure_entity);
  }


  /**
   * SOP 接受工单.
   *
   * 故障工单-接受工单时处理对应的故障申报实体
   */
  public function questionforfailure($question) {
    $entity = $question;
    $entity->server_uid = \Drupal::currentUser()->id();
    $entity->accept_stamp  = REQUEST_TIME;
    $entity->status = 1;
    // 得到当前问题类型处理完成所需要的时间.
    $times = $entity->get('parent_question_class')->entity->get('limited_stamp')->value;
    // 设置预计完成的时间.
    $entity->pre_finish_stamp  = strtotime('+' . $times . ' minutes', REQUEST_TIME);
    $entity->save();
    // ======================= 写入接受故障的日志 =============.
    HostLogFactory::OperationLog('question')->log($entity, 'control');
    // ======================= 日志写入结束 ====================.
  }

}
