<?php

/**
 * @file
 * Contains \Drupal\ip\Form\ManagementIpFilterForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;

class ManagementIpFilterForm extends FormBase {

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
      '#open' => !empty($_SESSION['admin_ip_filter']),
    );
    $form['filters']['ip'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ip')
    );
    //IP的创建者
    $employee = $ip_service->getIpCreator('management_ip_field_data');
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
     //加载IP使用状态到筛选条件里
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Ip state'),
      '#options' => array('' => 'All') + ipmStatus(),
    );
    //加载服务器类型术语到筛选条件中
    $form['filters']['server_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Server type'),
      '#options' => array('' => 'All') + ip_server_type()
    );

    $entity_rooms = entity_load_multiple('room');
    $room_options = array('' => '所有' );
    foreach ($entity_rooms as $row) {
      $room_options[$row->id()] = $row->label();
      $groups = $ip_service->loadIpGroup(array('rid' => $row->id()));
      foreach($groups as $group) {
        $room_options[$row->id() . '_' . $group->gid] = SafeMarkup::format('&nbsp;&nbsp;--' . $group->name, array());
      }
    }
    $form['filters']['rid'] = array(
      '#type' => 'select',
      '#title' => '所属机房',
      '#options' => $room_options,
    );

    $fields = array('ip', 'rid', 'uid','status','server_type');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_ip_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_ip_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_ip_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_ip_filter'])) {
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
    $_SESSION['admin_ip_filter']['ip'] = $form_state->getValue('ip');
    $_SESSION['admin_ip_filter']['uid'] = $form_state->getValue('uid');
    $_SESSION['admin_ip_filter']['rid'] = $form_state->getValue('rid');
    $_SESSION['admin_ip_filter']['status'] = $form_state->getValue('status');
    $_SESSION['admin_ip_filter']['server_type'] = $form_state->getValue('server_type');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_ip_filter'] = array();
  }
}
