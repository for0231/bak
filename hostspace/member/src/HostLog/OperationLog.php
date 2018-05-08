<?php
namespace Drupal\member\HostLog;

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
    $op = '用户';
    if($action == 'insert') {
      $op = '添加';
    } else if ($action == 'update') {
      $op = '编辑';
    } else if ($action == 'delete') {
      $op = '删除';
    }

    $type = $entity->getEntityTypeId();
    if(isset($entity->other_data)) {
      $other_data = $entity->other_data;
      if(isset($other_data['data_name'])) {
        $type = $other_data['data_name'];
      }
    }
    switch($type) {
      case 'employee':
        if($action == 'insert') {
          $dept_name = '';
          if($entity->other_data['data']['department']) {
            $dept = entity_load('taxonomy_term', $entity->other_data['data']['department']);
            $dept_name = $dept->label();
          }
          $message = strtr('%action员工，姓名：%name， 部门：%dept, 职位：%position', array(
            '%action' => $op,
            '%name' => $entity->other_data['data']['employee_name'],
            '%dept' => $dept_name ? $dept_name : '',
            '%position' => $entity->other_data['data']['position'] ? entity_load('taxonomy_term', $entity->other_data['data']['position'])->label() : ''
          ));
        } else {
          $message = strtr('%action员工，姓名：%name 的信息。', array(
            '%action' => $op,
            '%name' => $entity->other_data['data']['employee_name'] ? $entity->other_data['data']['employee_name'] : $entity->getUsername(),
          ));
        }
        break;
      case 'client':
        if($action == 'insert') {
          $message = strtr('注册了一个会员，用户名：%name', array(
            '%action' => $op,
            '%name' => $entity->getUsername()
          ));
        } else {
          $message = strtr('%action了一个会员的信息，用户名：%name', array(
            '%action' => $op,
            '%name' => $entity->getUsername()
          ));
        }
        break;
      case 'user_funds_data': 
        if($action == 'insert') {
          $message = strtr('为用户【%user】设置了信用额度￥%price', array(
            '%user' => $entity->getUsername(),
            '%price' => $other_data['data']['amount']
          ));
        } else if ($action == 'up_fund') {
          $message = strtr('提升了用户【%user】的信用额度。提升金额为：￥%price', array(
            '%user' => $entity->getUsername(),
            '%price' => $other_data['data']['amount']
          ));
        } else if ($action == 'low_fund') {
          $message = strtr('降低了用户【%user】的信用额度。降低金额为：￥%price', array(
            '%user' => $entity->getUsername(),
            '%price' => $other_data['data']['amount']
          ));
        }
        break;
    }

    return $message;
  }

  /**
   * 字段差异比较
   */
  protected function diff($name, $current, $before, $type) {
    $current_arr = $current->other_data['data'];
    $before_arr = $before->other_data['data'];
    if($name == 'department' || $name == 'position') {
      $current_val = $current_arr[$name] ? entity_load('taxonomy_term', $current_arr[$name])->label() : '';
      $before_val =  $before_arr[$name] ? entity_load('taxonomy_term', $before_arr[$name])->label() : '';
      $msg = $this->getLabel($name). '： 【'. $before_val . '】 变更为 【'. $current_val .'】';
      return $msg;
    }
    if($name == 'client_type') {
      $current_value =  clientType()[$current_arr[$name]];
      $before_value =  clientType()[$before_arr[$name]];
      return '客户类型：【'. $before_value .'】变更为【'. $current_value .'】';
    }
    if($name == 'commissioner') {
      $before = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$before_arr['commissioner'])->employee_name;
      $current = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$current_arr['commissioner'])->employee_name;
      return '客服专员：【'. $before .'】 变更为 【' . $current .'】';
    }
    return null;
  }

  /**
   * 获取label
   */
  protected function getLabel($name) {
    if($name == 'corporate_name') {
       return '公司/个人名称';
    }
    if($name == 'department') {
       return '部门';
    }
    if($name == 'position') {
       return '职位';
    }

    return parent::getLabel($name);
  }
}
