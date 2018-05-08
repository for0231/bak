<?php

/**
 * @file
 * Contains \Drupal\part\Form\PartDeleteForm.
 */

namespace Drupal\part\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\String;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class PartDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete this  part ? Brand: %brand, Title: %title', array(
      '%brand' => $this->entity->get('brand')->value,
      '%title' => $this->entity->get('standard')->value
    ));
  }
  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this part.
    return new Url('part.admin');
  }
  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This part will be delete. Please confirm this part has not any been used storage.');
  }
  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Delete the part(as cpu, memory, mainboard,...) entity first.
    $entity = $this->entity;
    $part_id = $entity->get('ppid')->value;
    $part_type = $entity->get('type')->value;
    $part = entity_load($part_type, $part_id);
    $part->delete();
    // Delete the part and its replies.
    $entity->delete();

    HostLogFactory::OperationLog('part')->log($part, 'delete');
    drupal_set_message($this->t('The part  have been deleted.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
