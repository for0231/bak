<?php

/**
 * @file
 * Contains \Drupal\contract\Form\ContractFilterForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ContractFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'contract_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter Contract'),
      '#open' => !empty($_SESSION['admin_contract_filter']),
    );
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => '合同执行状态',
      '#options' => array('' => '状态筛选') + contractStatus()
    );
		$form['filters']['name'] = array(
      '#type' => 'textfield',
      '#title' => '合同名称'
    );
    $fields = array('status', 'name');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_contract_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_contract_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_contract_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_contract_filter'])) {
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
    $_SESSION['admin_contract_filter']['status'] = $form_state->getValue('status');
	  $_SESSION['admin_contract_filter']['name'] = $form_state->getValue('name');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_contract_filter'] = array();
  }
}
