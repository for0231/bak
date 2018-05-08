<?php

/**
 * @file
 * Contains \Drupal\ip\Form\BusinessIpFilterForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class BusinessIpFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ip_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $ip_service = \Drupal::service('ip.ipservice');
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter ip messages'),
      '#open' => !empty($_SESSION['admin_ipb_filter']),
    );
    $form['filters']['ip'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ip')
    );

    $client = \Drupal::service('ip.ipservice')->getIpClient();
    $client_ops = array();
    foreach($client as $key=>$value) {
      $client_name = $value->client_name ? $value->client_name : entity_load('user',$value->puid)->getUsername();
      $client_ops[$value->puid] = $value->corporate_name ? $value->corporate_name : $client_name;
    }

   $form['filters']['puid'] = array(
      '#type' => 'select',
      '#title' => $this->t('User'),
      '#options' => array(''=>'All','-1'=>'非专用段') + $client_ops
    );
    //IP的创建者
    $employee = $ip_service->getIpCreator('business_ip_field_data');
    $employee_ops = array();
    if($employee) {
      foreach($employee as $key=>$value) {
        $employee_ops[$value->uid] = $value->employee_name ? $value->employee_name : entity_load('user',$value->uid)->getUsername();
      }
    }

    $form['filters']['uid'] = array(
      '#type' => 'select',
      '#title' => $this->t('Creator'),
      '#options' => array(''=>'All','1'=>'admin') + $employee_ops
    );
    $form['filters']['ip_segment'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ip segment')
    );
    $form['filters']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description')
    );
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Ip state'),
      '#options' => array('' =>'All') + ipbStatus(),
    );

    //加载服务器类型术语到筛选条件中
    $type_arr = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('business_ip_type',0,1);
    $type_ops = array('0' => 'All');
    foreach ($type_arr as $v) {
      $type_ops[$v->tid] = $v->name;
    }
    $form['filters']['type'] = array(
       '#type' => 'select',
       '#title' => $this->t('Ip type'),
       '#options' => $type_ops
    );
    //加载服务器类型术语到筛选条件中
    $classifys = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('business_ip_segment_type',0,1);
    $classify = array('0' => 'All');
    foreach ($classifys as $v) {
      $classify[$v->tid] = $v->name;
    }
    $form['filters']['classify'] = array(
      '#type' => 'select',
      '#title' => $this->t('classify'),
      '#options' => $classify
    );
    // 加载所有机房数据
    $entity_rooms = entity_load_multiple('room');
    $room_options = array('' => '所有' );
    foreach ($entity_rooms as $row) {
      $room_options[$row->id()] = $row->label();
      $groups = $ip_service->loadIpGroup(array('rid' => $row->id()));
      foreach($groups as $group) {
        $room_options[$row->id() . '_' . $group->gid] = SafeMarkup::format('&nbsp;&nbsp;--' . $group->name, array());
      }
    }
    $form['filters']['room'] = array(
      '#type' => 'select',
      '#title' => $this->t('所属机房'),
      '#options' => $room_options,
    );
    $fields = array('ip', 'puid','uid','ip_segment','description','status','type', 'classify','room');

    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_ipb_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_ipb_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_ipb_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_ipb_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_ipb_filter']['ip'] = $form_state->getValue('ip');
    $_SESSION['admin_ipb_filter']['puid'] = $form_state->getValue('puid');
    $_SESSION['admin_ipb_filter']['uid'] = $form_state->getValue('uid');
    $_SESSION['admin_ipb_filter']['ip_segment'] = $form_state->getValue('ip_segment');
    $_SESSION['admin_ipb_filter']['description'] = $form_state->getValue('description');
    $_SESSION['admin_ipb_filter']['status'] = $form_state->getValue('status');
    $_SESSION['admin_ipb_filter']['type'] = $form_state->getValue('type');
    $_SESSION['admin_ipb_filter']['classify'] = $form_state->getValue('classify');
    $_SESSION['admin_ipb_filter']['room'] = $form_state->getValue('room');

  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_ipb_filter'] = array();
  }
}
