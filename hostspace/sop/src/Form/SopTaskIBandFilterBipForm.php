<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopTaskIBandFilterBipForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a configuration form for configurable actions.
 */
class SopTaskIBandFilterBipForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'soptask_iband_filter_bip_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['eg'] = array(
      '#markup' => '测试',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
  }

}
