<?php
namespace Drupal\resourcepool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ZCustomerFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zCustomer_filter_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state,$type='') {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => '查询条件',
      '#open' => !empty($_SESSION['rack_part']),
    );
    $form['filters']['link_id'] = array(
      '#type' => 'textfield',
      '#title' => '链路ID',
      '#default_value' => empty($_SESSION['rack_part']['link_id']) ? '' : $_SESSION['rack_part']['link_id']
    );
    $form['filters']['client_name'] = array(
      '#type' => 'textfield',
      '#title' => '客户',
      '#default_value' => empty($_SESSION['rack_part']['client_name'])? '': $_SESSION['rack_part']['client_name']
    );
    $form['filters']['rent_time'] = array(
      '#type' => 'textfield',
      '#title' => '租用时间',
      '#default_value' => empty($_SESSION['rack_part']['rent_time'])?null: $_SESSION['rack_part']['rent_time'],
    );
    $form['filters']['end_time'] = array(
      '#type' => 'textfield',
      '#title' => '到期时间',
      '#default_value' => empty($_SESSION['rack_part']['end_time'])?null: $_SESSION['rack_part']['end_time'],
    );
    $form['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询'
    );
    $form['filters']['reset'] = array(
      '#type' => 'submit',
      '#value' => '清空',
      '#submit' => array('::resetForm'),
    );
    $form['#attached'] = array(
      'library' => array('resourcepool/drupal.respool-time')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['rack_part']['link_id'] = $form_state->getValue('link_id');
    $_SESSION['rack_part']['client_name'] = $form_state->getValue('client_name');
    $_SESSION['rack_part']['rent_time'] = empty($form_state->getValue('rent_time'))?null:$form_state->getValue('rent_time');
    $_SESSION['rack_part']['end_time'] = empty($form_state->getValue('end_time'))?null:$form_state->getValue('end_time');
  }
  
  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['rack_part'] = array();
  }
}
