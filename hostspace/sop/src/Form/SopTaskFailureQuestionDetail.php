<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskFailureQuestionDetail.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 工单问题详情表单.
 */
class SopTaskFailureQuestionDetail extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sop_task_failure_question_detail_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $question = NULL) {
    $form = array(
      '#markup' => 'ad',
    );
    // $form['#theme'] = array('admin_handle_task_failure_info');.
    return $form;
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
