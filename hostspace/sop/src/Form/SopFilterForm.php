<?php

/**
 * @file
 * Contains \Drupal\sop\Form\SopFilterForm.
 */

namespace Drupal\sop\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a configuration form for configurable actions.
 */
class SopFilterForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sop_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Sop Filter messages'),
      '#open' => !empty($_SESSION['sop_overview_filter']),
      '#description' => $this->t('这个搜索选项有点模糊，待进一步完善。'),
    );
    $sop_task_status = sop_task_status();
    $form['filters']['task_status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Task Status'),
      '#options' => array(
        '-1' => $this->t('All'),
      ) + sop_task_status(),
      '#default_value' => !empty($_SESSION['sop_overview_filter']['task_status']) ? $_SESSION['sop_overview_filter']['task_status'] : '',
    );
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    );

    if (!empty($_SESSION['sop_overview_filter']['task_status'])) {
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
    $_SESSION['sop_overview_filter']['task_status'] = $form_state->getValue('task_status');
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['sop_overview_filter']['task_status'] = array();
  }

}
