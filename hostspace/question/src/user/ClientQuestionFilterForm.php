<?php

/**
 * @file
 * Contains \Drupal\question\user\ClientQuestionFilterForm.
 */

namespace Drupal\question\user;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ClientQuestionFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'client_question_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'container'
     /* '#type' => 'details',
      '#title' => $this->t('Filter question messages'),
      '#open' => !empty($_SESSION['client_question_filter']),*/
    );
	 	//加载问题分类到筛选条件里
		$category = entity_load_Multiple('question_class');
		$category_ops = array('' =>'All');
		foreach ($category as $k=>$v) {
			$category_ops[$v->id()] = $v->label();
		}

		$form['filters']['category'] = array(
      '#type' => 'select',
      '#title' => $this->t('Category of question'),
			'#options' => $category_ops,
    );
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Status of question'),
	    '#options' => array('0' => t('Select status')) + questionStatus()
    );

    // 时间筛选条件
    $form['filters']['start'] = array(
    	'#title' => t('Start date'),
    	'#type' => 'textfield',
      '#size' => 12,
    );
    $form['filters']['expire'] = array(
    	'#title' => t('End Date'),
    	'#type' => 'textfield',
      '#size' => 12,
    );
    //---给表单绑定时间控件的js文件
    $form['#attached']['library'] = array('core/jquery.ui.datepicker', 'common/hostspace.select_datetime');

    $form['filters']['content'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Content'),
      '#size' => 20
    );


   $fields = array('date_time', 'category','status','content', 'start', 'expire');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['client_question_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['client_question_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['client_question_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['client_question_filter'])) {
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
    $_SESSION['client_question_filter']['date_time'] = $form_state->getValue('date_time');
	  $_SESSION['client_question_filter']['category'] = $form_state->getValue('category');
		$_SESSION['client_question_filter']['status'] = $form_state->getValue('status');
	  $_SESSION['client_question_filter']['content'] = $form_state->getValue('content');
    $_SESSION['client_question_filter']['start'] = $form_state->getValue('start');
		$_SESSION['client_question_filter']['expire'] = $form_state->getValue('expire');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['client_question_filter'] = array();
  }
}
