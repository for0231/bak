<?php

/**
 * @file
 * Contains \Drupal\server\Form\ServerFilterForm
 */

namespace Drupal\server\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a configuration form for configurable actions.
 */
class ServerFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'server_filter_form';
  }  


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Server Filter messages'),
      '#open' => !empty($_SESSION['server_overview_filter']),
    ); 

    $form['filters']['keywords'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Keywords'),
      '#size' => 20,
      '#default_value' => !empty($_SESSION['server_overview_filter']['keywords']) ? $_SESSION['server_overview_filter']['keywords'] : '',
    );

    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => '上柜状态',
      '#options' => array(
        '' => $this->t('All'),
        'on' => $this->t('已上柜'),
        'off' => $this->t('待上柜'),
      ),
      '#default_value' => !empty($_SESSION['server_overview_filter']['status']) ? $_SESSION['server_overview_filter']['status'] : '',
    );
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit', 
      '#value' => $this->t('Filter'),
    );

    if (!empty($_SESSION['server_overview_filter'])) {
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
    $_SESSION['server_overview_filter']['keywords'] = $form_state->getValue('keywords');
    $_SESSION['server_overview_filter']['status'] = $form_state->getValue('status');
  }

  /**
   * {@inheritdoc}
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['server_overview_filter'] = array();
  }
}
