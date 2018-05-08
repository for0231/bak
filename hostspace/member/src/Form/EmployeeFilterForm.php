<?php

/**
 * @file
 * Contains \Drupal\member\Form\EmployeeFilterForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class EmployeeFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'employee_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter employee messages'),
      '#open' => !empty($_SESSION['admin_employee_filter']),
    );
    $form['filters']['employee_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Real name')
    );
	  $form['filters']['name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('User name')
    );
		//加载部门到筛选条件里
		$dept = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('dept_employee',0,1);
    $dept_ops = array(''=> 'All');
	  foreach ($dept as $v) {
	 	  $dept_ops[$v->tid] = $v->name;
	  }
    $form['filters']['department'] = array(
      '#type' => 'select',
      '#title' => $this->t('Department'),
			'#options' => $dept_ops,
    );

    $fields = array('employee_name', 'name','department');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_employee_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_employee_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_employee_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_employee_filter'])) {
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
    $_SESSION['admin_employee_filter']['employee_name'] = $form_state->getValue('employee_name');
	  $_SESSION['admin_employee_filter']['name'] = $form_state->getValue('name');
	  $_SESSION['admin_employee_filter']['department'] = $form_state->getValue('department');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_employee_filter'] = array();
  }
}
