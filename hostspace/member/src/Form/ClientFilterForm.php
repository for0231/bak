<?php

/**
 * @file
 * Contains \Drupal\member\Form\ClientFilterForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ClientFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'client_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter client messages'),
      '#open' => !empty($_SESSION['admin_client_filter']),
    );
    $form['filters']['client_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client name / Nick')
    );
	  $form['filters']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('account')
    );
    $form['filters']['mail'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('E-mail')
    );
    $form['filters']['role'] = array(
      '#type' => 'select',
      '#title' => $this->t('Agent'),
      '#options' => array('' => 'All') + array_filter(user_role_names(), create_function( '$v', 'return stristr($v,\'Agent\');'))
    );
    $form['filters']['corporate_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Company')
    );
    //加载会员的类型
    $type = \Drupal::entityManager()->getStorage('taxonomy_term')->loadChildren(\Drupal::config('member.settings')->get('client.user_type'));  	
    $type_ops = array('' =>'All');
    foreach ($type as $key=>$value) {
  	  $type_ops[$key] = $value->label();
    }
    $form['filters']['client_type'] = array(
      '#type' => 'select',
      '#title' => '会员类型',
      '#options' => $type_ops,
    );
					
    $fields = array('client_name', 'name','mail','role','corporate_name','client_type');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_client_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_client_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_client_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_client_filter'])) {
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
    $_SESSION['admin_client_filter']['client_name'] = $form_state->getValue('client_name');
	  $_SESSION['admin_client_filter']['name'] = $form_state->getValue('name');
	  $_SESSION['admin_client_filter']['mail'] = $form_state->getValue('mail');
    $_SESSION['admin_client_filter']['role'] = $form_state->getValue('role');
	  $_SESSION['admin_client_filter']['corporate_name'] = $form_state->getValue('corporate_name');
    $_SESSION['admin_client_filter']['client_type'] = $form_state->getValue('client_type');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_client_filter'] = array();
  }
}
