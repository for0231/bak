<?php

/**
 * @file
 * Contains \Drupal\part\Form\SwitchForm.
 */

namespace Drupal\part\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for switch edit.
 */
class SwitchForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['stock'] = array(
      '#type' => 'number',
      '#title' => t('Storage Number'),
      '#weight' => 95,
      '#default_value' => 0
    );
    $form['#prefix'] = '<div class="column">';
    $form['#suffix'] = '</div>';
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $action = 'update';
    $entity = $this->entity;
    if($entity->isNew()) {
      $action = 'insert';
    }
    $entity->save();
    HostLogFactory::OperationLog('part')->log($entity, $action);
    drupal_set_message($this->t('Switch saved successfully'));
  }
}
