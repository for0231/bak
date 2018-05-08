<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskServerDetailForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 工单服务器上下架类详情表单.
 */
class SopTaskServerDetailForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sop_task_server_detail_form';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

  }

}
