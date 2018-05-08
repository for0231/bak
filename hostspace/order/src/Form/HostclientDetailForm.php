<?php

/**
 * @file
 * Contains \Drupal\order\Form\HostclientDetailForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Provide a form controller for question category add.
 */

class HostclientDetailForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $form['client'] = array(
      '#type' => 'details',
      '#title' => $this->t('Client information'),
      '#open' => true
    );
    $form['client']['client_info'] = array(
      '#theme' => 'admin_order_client',
      '#client_obj' => $entity->getObject('client_uid'),
    );
    $form['server'] = array(
      '#type' => 'details',
      '#title' => $this->t('The configuration of server'),
      '#open' => true
    );
    $form['server']['server_info'] = array(
      '#theme' => 'user_hostclient_detail',
      '#hostclient' => $entity
    );
    return $form;
  }






  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return array();
  }

  public function save(array $form, FormStateInterface $form_state) {

  }

}
