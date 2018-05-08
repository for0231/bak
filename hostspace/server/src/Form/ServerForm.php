<?php

/**
 * @file
 * Contains \Drupal\part\Form\ServerForm.
 */

namespace Drupal\server\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provide a form controller for server edit.
 */
class ServerForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['type']['#disabled'] = true;
    $form['cpu']['#disabled'] = true;
    $form['memory']['#disabled'] = true;
    $form['harddisk']['#disabled'] = true;
    $form['mainboard']['#disabled'] = true;
    $form['chassis']['#disabled'] = true;
    return $form;  
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $action = 'update';
    if($entity->isnew()) {
      $action = 'insert';
    }
    $entity->save();
    HostLogFactory::OperationLog('server')->log($entity, $action);
    drupal_set_message($this->t('Server saved successfully'));
    $form_state->setRedirectUrl(new Url('server.overview'));
  }
}
