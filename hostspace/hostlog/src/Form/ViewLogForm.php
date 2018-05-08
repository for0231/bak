<?php
/**
 * @file 
 * Contains Drupal\hostlog\Form\ViewLogForm.
 */

namespace Drupal\hostlog\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

class ViewLogForm {
  /**
   * @description 构建操作详情的表单
   *
   * @return 构建好的表单数据
   */
  private function getData($lid) {
    $log = \Drupal::service('operation.log')->getLogByLid($lid);
    $row[1] = array(
      'action' => '操作内容',
      'action_val' => $log->data_name,
    );
    $row[2] = array(
      'date' => '操作时间',
      'date_val' => format_date($log->timestamp, 'custom', 'Y-m-d H:i:s'),
    );
    // 操作者
    //$operator = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$log->uid); //不能依赖于member模块,应孩是member模块依赖log
    $row[3] = array(
      'action' => '操作用户',
      'action_val' => entity_load('user', $log->uid)->label(),
        //$operator ? ($operator->employee_name ? $operator->employee_name : entity_load('user', $log->uid)->getUsername()) : 'SYSTEM',
    );
    $row[4] = array(
      'action' => '操作信息',
      'action_val' => $log->message,
    );
    /** 按操作的动作展示操作的具体内容*/
    $action = $log->action;
    if($action == 'update') {   //修改操作
      $entity_id = $log->data_id;
      $entity_type = $log->data_name;
      // 当前修改记录中的对象
      $current = unserialize($log->data);
      $condition = array(
        'data_id' => array('field' => 'data_id', 'data' => $entity_id, 'op' => '='),
        'data_name' => array('field' => 'data_name', 'data' => $entity_type, 'op' => '='),
        'lid' => array('field' => 'lid', 'data' => $log->lid, 'op' => '<')
      );
      // 得到上一条修改记录
      $log_old = null;
      if($log->entity_name == $log->data_name) {
        $condition['data_id']['field' ] = 'entity_id';
        $condition['data_name']['field'] = 'entity_name';
      }
      $log_old = $this->getOldData($condition);
      if(!empty($log_old)) {
        // 得到上一条修改记录中的对象
        $before = unserialize($log_old->data);
        // 得到差异数据
        $diff = $this->checkDifferentData($current, $before);         
        $row[5] = array(
          'content' => '操作内容',
          'conten_val' => SafeMarkup::format($diff, array()),
        );
      }
    }	    
    return $row;
  }

  /**
   * @description 比对两次修改记录中的区别
   *
   *  @param $current  当前的修改记录
   *  @param $before   上一条修改记录
   *  @return  差异数据
   *
   */
  public function checkDifferentData($current, $before) {
    $callback = $current->view_callback;
    $result = call_user_func_array($current->view_callback, array($current, $before));
    return implode('<br>', $result);
  }

  /**
   * @description 得到上一条操作记录
   */
  public function getOldData($condition) {
    return \Drupal::service('operation.log')->getLogDataByCondition($condition);
  }

  /**
   * @description 渲染表单
   */
  public function  render($lid) {
    $build['view'] = array(
      '#type' => 'table',
      '#rows' => $this->getData($lid)
    );
    return $build;
  }
  
}


