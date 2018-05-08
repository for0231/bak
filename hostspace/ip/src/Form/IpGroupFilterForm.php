<?php
/**
 * @file  IP段入库申请
 * Contains \Drupal\ip\Form\IpGroupFilterForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class IpGroupFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'ip_group_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => 'Ip分组查询',
      '#open' => !empty($_SESSION['admin_ip_group_filter']),
    );
    $form['filters']['name'] = array(
      '#type' => 'textfield',
      '#title' => '分组名'
    );
    $entity_rooms = entity_load_multiple('room');
    $room_options = array('' => '- 请选择 -' );
    foreach ($entity_rooms as $row) {
      $room_options[$row->id()] = $row->label();
    }
    $form['filters']['rid'] = array(
      '#type' => 'select',
      '#title' => '所属机房',
      '#options' => $room_options
    );
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    if (!empty($_SESSION['admin_ip_group_filter'])) {
      $form['filters']['name']['#default_value'] = $_SESSION['admin_ip_group_filter']['name'];
      $form['filters']['rid']['#default_value'] = $_SESSION['admin_ip_group_filter']['rid'];
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
    $_SESSION['admin_ip_group_filter']['name'] = $form_state->getValue('name');
    $_SESSION['admin_ip_group_filter']['rid'] = $form_state->getValue('rid');
  }

    /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_ip_group_filter'] = array();
  }
}
