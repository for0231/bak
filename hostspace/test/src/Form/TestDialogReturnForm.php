<?php

/**
 * @file
 * Contains \Drupal\test\Form\TestDialogReturnForm.
 */

namespace Drupal\test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a user login form.
 */
class TestDialogReturnForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_dialog_return_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test1'] = array(
      '#type' => 'textfield',
      '#title' => '用户名' 
    );

    $form['test2'] = array(
      '#type' => 'textfield',
      '#title' => '密码'
    );

    $form['actions'] = array(
      '#type' => 'actions',
      'submit' => array(
         '#type' => 'submit',
         '#value' => '保存',
         '#button_type' => 'primary'
      )
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
