<?php

/**
 * @file
 * Contains \Drupal\member\Form\CreditFilterForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class CreditFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'credit_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter credit messages'),
      '#open' => !empty($_SESSION['admin_credit_filter']),
    );
  
    $form['filters']['client'] = array(
      '#type' => 'textfield',
      '#title' => t('Client'),
    );
    $form['filters']['company'] = array(
      '#type' => 'textfield',
      '#title' => t('Company'),
    );


    //加载会员的类型
    $form['filters']['client_type'] = array(
      '#type' => 'select',
      '#title' => t('Client type'),
      '#options' => clientType(),
    );
					
    $fields = array('client', 'client_type', 'company');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_credit_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_credit_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_credit_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_credit_filter'])) {
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
    $_SESSION['admin_credit_filter']['client'] = $form_state->getValue('client');
	  $_SESSION['admin_credit_filter']['client_type'] = $form_state->getValue('client_type');
	  $_SESSION['admin_credit_filter']['company'] = $form_state->getValue('company');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_credit_filter'] = array();
  }
}
