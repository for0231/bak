<?php

/**
 * @file
 * Contains \Drupal\part\server\ServerDeleteForm.
 */

namespace Drupal\server\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the part delete confirmation form.
 */
class ServerTypeDeleteForm extends ContentEntityConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete server catalog? catalog: %catalog.', array(
      '%catalog' => $this->entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.server_type');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This catalog will be delete. Please confirm this catalog has not any been used storage.');
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if($this->entity->get('server_number')->value != 0) {
      $form_state->setErrorByName('op', $this->t('The server already exists, cannot be deleted.')); 
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('server')->log($entity, 'delete');
    drupal_set_message($this->t('Server catalog deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}
