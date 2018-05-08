<?php

/**
 * @file
 * Contains \Drupal\ip\Form\SwitchFilterForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class SwitchFilterForm extends FormBase {

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
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter ip messages'),
      '#open' => !empty($_SESSION['admin_switch_filter']),
    );
    $form['filters']['ip'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Ip')
    );
   $form['filters']['port'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Port')
    );
    $form['filters']['status_equipment'] = array(
      '#type' => 'select',
      '#title' => $this->t('Whether to use'),
      '#options' => array(''=> t('Select'), 'on'=>t('on'), 'off'=> t('off'))  
    );
    $employee = \Drupal::service('ip.ipservice')->getIpCreator('switch_ip_field_data');
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

    $form['filters']['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description')
    );

   $fields = array('ip', 'port', 'status_equipment', 'uid','description');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_switch_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_switch_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_switch_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_switch_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_switch_filter']['ip'] = $form_state->getValue('ip');
    $_SESSION['admin_switch_filter']['port'] = $form_state->getValue('port');
    $_SESSION['admin_switch_filter']['status_equipment'] = $form_state->getValue('status_equipment');
    $_SESSION['admin_switch_filter']['uid'] = $form_state->getValue('uid');
    $_SESSION['admin_switch_filter']['description'] = $form_state->getValue('description');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_switch_filter'] = array();
  }
}
