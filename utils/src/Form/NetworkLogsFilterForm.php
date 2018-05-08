<?php

/**
 * @file
 * Contains \Drupal\utils\Form\NetworkLogsFilterForm.
 */

namespace Drupal\utils\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class NetworkLogsFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'network_logs_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filter'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['network_logs_config_log'])
    );
    $form['filter']['uid'] = array(
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#title' => '用户'
    );
    $form['filter']['date_label'] = array(
      '#type'=> 'label',
      '#title' => '操作时间'
    );
    $form['filter']['date'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    $form['filter']['date']['begin_time'] = array(
      '#type' => 'date',
    );
    $form['filter']['date']['end_time'] = array(
      '#type' => 'date',
    );
    $form['filter']['keyword'] = array(
      '#type' => 'textfield',
      '#title' => '关键字'
    );
    $form['filter']['actions'] = array('#type' => 'actions');
    $form['filter']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    if(!empty($_SESSION['network_logs_config_log'])) {
      if($uid = $_SESSION['network_logs_config_log']['uid']) {
        $form['filter']['uid']['#default_value'] = entity_load('user', $uid);
      }
      $form['filter']['date']['begin_time']['#default_value'] = $_SESSION['network_logs_config_log']['begin'];
      $form['filter']['date']['end_time']['#default_value'] = $_SESSION['network_logs_config_log']['end'];
      $form['filter']['keyword']['#default_value'] = $_SESSION['network_logs_config_log']['keyword'];
      $form['filter']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => '清空',
        '#submit' => array('::resetForm'),
      );
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['network_logs_config_log']['uid'] = $form_state->getValue('uid');
    $_SESSION['network_logs_config_log']['begin'] = $form_state->getValue('begin_time');
    $_SESSION['network_logs_config_log']['end'] = $form_state->getValue('end_time');
    $_SESSION['network_logs_config_log']['keyword'] = $form_state->getValue('keyword');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['network_logs_config_log'] = array();
  }
}
