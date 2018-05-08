<?php

/**
 * @file
 * Contains \Drupal\question\Form\MyQuestionFilterForm.
 */

namespace Drupal\question\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class MyQuestionFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'my_question_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter question messages'),
      '#open' => !empty($_SESSION['my_question_filter']),
    );
    $form['filters']['uid'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client')
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

    $form['filters']['content'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Content')
    );

   $fields = array('uid','category','status','content');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['my_question_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['my_question_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['my_question_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['my_question_filter'])) {
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
    $_SESSION['my_question_filter']['uid'] = $form_state->getValue('uid');
	  $_SESSION['my_question_filter']['category'] = $form_state->getValue('category');
		$_SESSION['my_question_filter']['status'] = $form_state->getValue('status');
	  $_SESSION['my_question_filter']['content'] = $form_state->getValue('content');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['my_question_filter'] = array();
  }
}
