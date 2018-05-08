<?php
namespace Drupal\question\HostLog;

use Drupal\hostlog\OperationLogBase;

/**
 * 操作日志
 */
class OperationLog extends OperationLogBase {
  /**
   * 构建日志消息
   * @param
   *  - $entity 当前操作实体
   *  - $action 当前操作（如insert, update, delete等）
   */
  protected function message($entity, $action) {
    $message = '';
    // 实体类型
    $type = $entity->getEntityTypeId();
    switch($type) {
      case 'question':
        if($action == 'control') {  // 接手一个全新的故障
          if($entity->get('status')->value == 1){
            $message = strtr('%user接手故障。故障编号： %num', array(
              '%user' => $this->getServiceUser($entity->get('server_uid')->entity->id()),
              '%num' => $entity->id(),
            ));
          }
        } elseif($action == 'update') {  // 转交故障/对故障作出回应
          if($entity->other_data) {
            $type = $entity->other_data['data_name'];
            $data = $entity->other_data['data'];
            if($type == 'server_question_convert') {  // 转交故障
              if(isset($data['flag'])) {
                if($data['flag'] == 'receive') {  // 接手别人转交的故障
                  $message = strtr('%user接手%from_user于%time转出的故障。故障编号： %num', array(
                    '%user' => $this->getServiceUser($entity->get('server_uid')->entity->id()),
                    '%from_user' => $this->getServiceUser($data['from_uid']),
                    '%time' => format_date($data['from_stamp'], 'custom', 'Y-m-d H:i'),
                    '%num' => $entity->id(),
                  ));
                }
              }
            }
          }
        } else if ($action == 'insert') {
          $message = strtr('%user提交了一个故障，故障编号：%num', array(
            '%user' => \Drupal::currentUser()->getUsername(),
            '%num' => $entity->id(),
          ));
        } else if($action == 'response') {
          $type = $entity->other_data['data_name'];
          $data = $entity->other_data['data'];
          if($type == 'server_question_detail') {  // 对故障作出回应
            $message =  strtr('%user对故障进行回应：【%content】。故障编号： %num', array(
              '%user' => $this->getServiceUser($entity->get('server_uid')->entity->id()),
              '%content' => strip_tags( $data['content']),
              '%num' => $entity->id(),
            ));
          }
        } else if ($action == 'question_convert') { // 转交故障
          $type = $entity->other_data['data_name'];
          $data = $entity->other_data['data'];
          if(isset($data['flag']) && $data['flag'] == 'transfer_out') {
            $message = strtr('%user将故障转给%to_user。故障编号： %num', array(
              '%user' => $this->getServiceUser($entity->get('server_uid')->entity->id()),
              '%to_user' => $this->getServiceUser($data['to_uid']),
              '%num' => $entity->id(),
            ));
          }
        }
        break;
    }
    return $message;
  }
  /**
   * 查找故障的负责人
   */
  private function getServiceUser($uid) {
    $emp = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$uid);
    $service_user = $emp ? $emp->employee_name : entity_load('user', $uid)->getUsername();
    return $service_user;
  }

  /**
   * 字段差异比较
   */
  protected function diff($name, $current, $before, $type) {
    return null;
  }

  /**
   * 获取label
   */
  protected function getLabel($name) {
    return parent::getLabel($name);
  }
}
