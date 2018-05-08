<?php

/**
 * @file
 * Contains \Drupal\part\Form\PartFilterForm.
 */

namespace Drupal\part\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class PartFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'part_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter Part messages'),
      '#open' => !empty($_SESSION['admin_part_filter']),
    );
    $form['filters']['search'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Search')
    );
    $form['filters']['type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Part Type'),
      '#options' => array(
         '' => $this->t('None'),
         'part_cpu' => 'CPU',
         'part_mainboard' => $this->t('Mainboard'),
         'part_memory' => $this->t('Memory'),
         'part_harddisc' => $this->t('Hard disk'),
         'part_chassis' => $this->t('Chassis'),
         'part_raid' => $this->t('Raid'),
         'part_network' => $this->t('Network card'),
         'part_optical' => $this->t('Optical module'),
         'part_switch' => $this->t('Switch'),
      )
    );
    $fields = array('search', 'type');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_part_filter'][$field])) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_part_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_part_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter')
    );
    if (!empty($_SESSION['admin_part_filter'])) {
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
    $_SESSION['admin_part_filter']['search'] = $form_state->getValue('search');
    $_SESSION['admin_part_filter']['type'] = $form_state->getValue('type');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_part_filter'] = array();
  }
}
