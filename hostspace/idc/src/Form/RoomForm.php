<?php

/**
 * @file
 * Contains \Drupal\idc\Form\RoomForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class RoomForm extends ContentEntityForm {

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'update';
    if($entity->isnew()) {
      $action = 'insert';
    }
    $entity->save();
    HostLogFactory::OperationLog('idc')->log($entity, $action);
    drupal_set_message($this->t('Room saved successfully'));
    $form_state->setRedirectUrl(new Url('admin.idc.room'));
  }
}
