<?php

/**
 * @file
 * Contains \Drupal\hostlog\Form\OperationLogFilterForm.
 */

namespace Drupal\hostlog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class OperationLogFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'operation_log_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter logs'),
      '#open' => !empty($_SESSION['operation_log_filter']),
    );
    $form['filters']['search'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#description' => $this->t('暂时只提供描述搜索'),
    );
    
    if(!empty($_SESSION['operation_log_filter']['search'])) {
      $form['filters']['search']['#default_value'] = $_SESSION['operation_log_filter']['search'];
    }    
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['operation_log_filter'])) {
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
    $_SESSION['operation_log_filter']['search'] = $form_state->getValue('search');
  }


  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['operation_log_filter'] = array();
  }
}
