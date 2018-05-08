<?php

/**
 * @file
 * Contains \Drupal\order\Form\BuildOrderForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

/**
 * Provides the article delete confirmation form.
 */
class StopServerForm extends ContentEntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $entity = $this->entity;
    if($entity->getSimpleValue('status') != 3) {
      return $this->redirect('admin.hostclient.list');
    }
    $form['#title'] = $this->getQuestion();
    $form['#attributes']['class'][] = 'confirmation';
    $form['description'] = array('#markup' => $this->getDescription());
    $form[$this->getFormName()] = array('#type' => 'hidden', '#value' => 1);
    $form['operation_mode'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Please select the storage time'),
      '#options' => order_stop_operation(),
      '#default_value' => '0'
    );
    if (!isset($form['#theme'])) {
      $form['#theme'] = 'confirm_form';
    }
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to down server %server ?', array(
      '%server' => $this->entity->getObject('ipm_id')->label(),
    ));
  }

	/**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('admin.hostclient.list');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity->set('status', 4);

    $operation = $form_state->getValue('operation_mode');
    $time = REQUEST_TIME;
    $stop_info['apply_uid'] = $this->currentUser()->id();
    $stop_info['apply_date'] = $time;
    $stop_info['operation'] = $form_state->getValue('operation_mode');
    $stop_info['client_uid'] = $entity->getObjectId('client_uid');
    if($operation) {
      $stop_info['storage_date'] = strtotime('+'. $operation .' hours', $time);
    } else {
      $stop_info['storage_date'] = $time;
    }
    $stop_info['status'] = 0;
    $stop_info['hostclient_id'] = $entity->id();
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $stop_id = $hostclient_service->addStopInfo($stop_info);
    $entity->save();
    //------写日志--------
    $stop_info_log = $hostclient_service->loadStopInfo($stop_id);
    $entity->other_data = array('data_id' => $stop_id, 'data_name' => 'hostclient_stop_info', 'data' => (array)$stop_info_log);
    $entity->other_status = 'server_stop';
    HostLogFactory::OperationLog('order')->log($entity, 'server_stop');

    drupal_set_message($this->t('The server has been disabled'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }
}


