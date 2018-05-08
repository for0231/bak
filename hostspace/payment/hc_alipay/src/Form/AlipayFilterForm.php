<?php

/**
 * @file
 * Contains \Drupal\hc_alipay\Form\AlipayFilterForm.
 */

namespace Drupal\hc_alipay\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the alipay payment records filter form.
 */
class AlipayFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'alipay_payment_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter'),
      '#open' => '',
    );
    $form['filters']['order'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order ID'),
      '#default_value' => 'a',
    );

    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions'] = array(
      'submit' => array(
        '#type' => 'submit',
        '#value' => $this->t('Filter'),
      ),
      'reset' => array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      ),
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
    $_SESSION['payment_alipay_filter'] = array();
  }
}
