<?php

/**
 * @file
 * Contains \Drupal\test\Form\TestDialogForm.
 */

namespace Drupal\test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides a user login form.
 */
class TestDialogForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'test_dialog_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['test1'] = array(
      '#type' => 'button',
      '#value' => '静态内容弹出框',
      '#attributes' => array(
        'class' => array('dialog-static')
      )
    );

    $form['test2'] = array(
      '#type' => 'button',
      '#value' => '动态内容弹出框1',
      '#attributes' => array(
        'class' => array('dialog-dynamic')
      )
    );


    $form['test3'] = array(
      '#type' => 'button',
      '#value' => '动态内容弹出框2',
      '#ajax' => array(
        'url' => new Url('test.dialog.return'),
        'dialogType' => 'modal'
      ),
    );
    
    $form['test4'] = array(
      '#type' => 'button',
      '#value' => '反向ajax',
      '#attributes' => array(
        'class' => array('ajax-polling')
      ),
    );
    
    $form['#attached']['library'][]  = 'test/drupal.test_dialog';
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
