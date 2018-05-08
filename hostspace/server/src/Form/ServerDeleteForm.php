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
class ServerDeleteForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete server ? server: %server。', array(
      '%server' => $this->entity->label()
    ));
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    // Point to the entity of which this part.
    return new Url('server.overview');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This server will be delete. Please confirm this server has not any been used storage.');
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
    if($this->entity->get('status_equipment')->value == 'on') {
      $form_state->setErrorByName('op', $this->t('The server has been used, can not be deleted.')); 
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->delete();
    HostLogFactory::OperationLog('server')->log($entity, 'delete');
    drupal_set_message($this->t('Server deleted successfully'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }  
}
