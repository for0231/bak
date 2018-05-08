<?php

/**
 * @file
 * Contains \Drupal\worksheet\Form\WorkSheetFrameOperateForm.
 */

namespace Drupal\worksheet\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetFrameOperateForm extends WorkSheetOperateBaseForm {
  //状态对应的字段
  protected function statusField() {
    $status_field = array(
      1 => array(
        'worksheet_business' => array('client','contacts','tid','manage_ip','business_ip','product_name', 'ip_class', 'system', 'broadband', 'requirement')
      ),
      5 => array(
        'worksheet_business' => array('client','contacts','tid','manage_ip','business_ip','product_name', 'ip_class', 'system', 'broadband', 'requirement')
      ), 
      15 => array(
        'worksheet_operation' => array('product_name','ip_class','system','broadband','requirement', 'handle_info','problem_difficulty', 'add_card', 'add_arp')
      )
    );
    $entity = $this->entity;
    $status = $entity->get('status')->value;
    $uid = $entity->get('uid')->target_id;
    if($status == 15 && $uid == \Drupal::currentUser()->id()) { //建单人和处理人都是自己则可以编辑
      $status_field[15]['worksheet_operation'][] = 'client';
      $status_field[15]['worksheet_operation'][] = 'tid';
      $status_field[15]['worksheet_operation'][] = 'manage_ip';
      $status_field[15]['worksheet_operation'][] = 'business_ip';
      $status_field[15]['worksheet_operation'][] = 'contacts';
    }
    return $status_field;
  }

  /**
   * 上下架状态对应的按钮
   */
  protected function statusBution() {
    $status_buttions = parent::statusBution();
    $status_buttions[15]['worksheet_operation'][] = 'deliver_info';
    $status_buttions[16]['worksheet_operation'][] = 'deliver_info';
    $status_buttions[30]['worksheet_business'][] = 'deliver_info';
    return $status_buttions;
  }

  /**
   * 设置交付信息
   */
  protected function deliverInfo() {
    $entity = $this->entity;
    $info = '';
    $client = $entity->get('client')->value;
    $manage_ip = $entity->get('manage_ip')->value;
    $info = $client . '：'. $manage_ip . "\r\n";
    $info .= '配置详情：' . $entity->get('product_name')->value . "\r\n";
    $info .= '带宽详情：' . $entity->get('broadband')->value . "\r\n";
    $info .= "外网IP：\r\n" . $entity->get('business_ip')->value . "\r\n";
    $tid = $entity->get('tid')->value;
    if($tid == 120) { //重装
      $handle_info = $entity->get('handle_info')->value;
      $items = explode("\r\n", $handle_info);
      $filter = array('子网掩码', '网    关', 'system', '系统账号', '系统密码');
      foreach($filter as $value) {
        if($value == 'system') {
          $system_name = '';
          if(!empty($entity->get('system')->value)) {
            $service = \Drupal::service('worksheet.option');
            $option = $service->getOptionByid($entity->get('system')->value);
            $system_name = $option->optin_name;
          }
          $info .= '系统类型：' . $system_name . "\r\n";
          continue;
        }
        foreach($items as $item) {
          if(strpos($item, $value) !== false) {
            $info .= $item . "\r\n";
            break;
          }
        }
      }
      $info .= "\r\n收到请确认，有问题请及时反馈。\r\n请注意：若24小时内未回复，将默认您已收到。";
    } else if($tid == 140) { //下架
      $info .= "\r\n已绑定MAC地址。服务器已下架";
    } else { //上架
      $handle_info = $entity->get('handle_info')->value;
      $items = explode("\r\n", $handle_info);
      $filter = array('子网掩码', '网    关', 'system', '系统账号', '系统密码');
      foreach($filter as $value) {
        if($value == 'system') {
          $system_name = '';
          if(!empty($entity->get('system')->value)) {
            $service = \Drupal::service('worksheet.option');
            $option = $service->getOptionByid($entity->get('system')->value);
            $system_name = $option->optin_name;
          }
          $info .= '系统类型：' . $system_name . "\r\n";
          continue;
        }
        foreach($items as $item) {
          if(strpos($item, $value) !== false) {
            $info .= $item . "\r\n";
            break;
          }
        }
      }
      $add_card = $entity->get('add_card')->value;
      $add_arp = $entity->get('add_arp')->value;
      if($add_card && $add_arp) {
        $info .= "\r\n已添加到管理卡。已绑定MAC地址。\r\n收到请确认，有问题请及时反馈。\r\n请注意：若24小时内未回复，将默认您已收到。";
      } else if ($add_card) {
        $info .= "\r\n已添加到管理卡。\r\n收到请确认，有问题请及时反馈。\r\n请注意：若24小时内未回复，将默认您已收到。";
      } else if ($add_arp) {
        $info .= "\r\n已绑定MAC地址。\r\n收到请确认，有问题请及时反馈。\r\n请注意：若24小时内未回复，将默认您已收到。";
      } else {
        $info .= "\r\n收到请确认，有问题请及时反馈。\r\n请注意：若24小时内未回复，将默认您已收到。";
      }
    }
    return $info;
  }
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if(empty($form['business_ip']['#disabled'])) {
      $form['business_ip']['widget']['0']['value']['#states'] = array(
        'disabled' => array(
          ':input[name="tid"]' => array('value' => '120')
        )
      );
    }
    if(empty($form['ip_class']['#disabled'])) {
      $form['ip_class']['widget']['#states'] = array(
        'disabled' => array(
          ':input[name="tid"]' => array('value' => '120')
        )
      );
    }
    if(empty($form['product_name']['#disabled'])) {
      $form['product_name']['widget']['0']['value']['#states'] = array(
        'disabled' => array(
          ':input[name="tid"]' => array('value' => '120')
        )
      );
    }
    if(empty($form['broadband']['#disabled'])) {
      $form['broadband']['widget']['0']['value']['#states'] = array(
        'disabled' => array(
          ':input[name="tid"]' => array('value' => '120')
        )
      );
    }
    if(empty($form['system']['#disabled'])) {
      $form['system']['widget']['#states'] = array(
        'disabled' => array(
          ':input[name="tid"]' => array('value' => '140')
        )
      );
    }
    unset($form['problem_difficulty']['widget']['#options']['_none']);
    if($pd = $this->entity->get('problem_difficulty')->value) {
      $form['problem_difficulty']['widget']['#default_value'] = $pd;
    } else {
      $form['problem_difficulty']['widget']['#default_value'] = 20;
    }
    $form['#theme'] = 'frame_operate_form';
    $form['#attached'] = array(
      'library' => array('worksheet/drupal.manage-ips')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $tid = $form_state->getValue('tid')[0]['value'];
    if($tid == 100 || $tid == 110) {
      $business_ip = $form_state->getValue('business_ip')[0]['value'];
      if(empty($business_ip)) {
        $form_state->setErrorByName('business_ip', '业务Ip不能为空');
      }
      $ip_class = $form_state->getValue('ip_class');
      if(empty($ip_class)) {
        $form_state->setErrorByName('ip_class', 'IP类型不能为空');
      }
    }
  }

  protected function operateForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $tid = $entity->get('tid')->value;
    if($tid == 120) {
      unset($form['problem_difficulty']);
      unset($form['add_card']);
      unset($form['add_arp']);
    }
    if($tid == 140) {
      $form['add_card']['widget']['value']['#title'] = '删除管理卡';
      $form['add_arp']['widget']['value']['#title'] = '绑定ARP';
      unset($form['problem_difficulty']);
    }
    return $form;
  }
}
