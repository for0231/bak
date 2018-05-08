<?php

/**
 * @file
 * Contains \Drupal\part\Form\RaidForm.
 */

namespace Drupal\part\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for raid edit.
 */
class RaidForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
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
    drupal_set_message($this->t('Raid saved successfully')); 
  }
}
