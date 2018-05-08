<?php

/**
 * @file
 * Contains \Drupal\order\Form\TrialApplyForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\hostlog\HostLogFactory;

class TrialApplyForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    if(!$entity->access('trial')) {
      throw new EnforcedResponseException($this->redirect('admin.order.list'));
    }
    $form['order_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Order code'),
      '#default_value' => $entity->getSimpleValue('code'),
      '#disabled' => true
    );

    $client = \Drupal::service('member.memberservice')->queryDataFromDB('client',$entity->getObjectId('uid'));
    $form['client'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Client'),
      '#default_value' => $client ? $client->corporate_name : $entity->getObject('uid')->getUsername(),
      '#disabled' => true
    );

    $options = array();
    $products = \Drupal::service('order.product')->getProductByOrderId($entity->id());
    foreach($products as $product) {
      if($product->action == 1) {
        $options[$product->opid] = $product->product_name;
      }
    }
    $form['product'] = array(
      '#type' => 'select',
      '#title' => $this->t('Product'),
      '#options' => $options,
      '#required' => true
    );
     
    $form['ask_description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('The trial description'),
      '#maxlength' => 1000
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
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Apply');
    return $actions;
  }
 
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $order_product_id = $form_state->getValue('product');
    $order_product = \Drupal::service('order.product')->getProductById($order_product_id);

    $ask_description = $form_state->getValue('ask_description');
    $field_arr = array(
      'order_id' => $entity->id(),
      'order_code' => $entity->getSimpleValue('code'),
      'client_id' => $entity->getObjectId('uid'),
      'product_id' => $order_product->product_id, 
      'order_product_id' => $order_product_id,
      'ask_uid' => $this->currentUser()->id(),
      'ask_date' => REQUEST_TIME,
      'ask_description' => $ask_description,
      'status' => 1
    );
    $order_service = \Drupal::service('order.orderservice');
    $trial_id = $order_service->saveTrialRecord($field_arr);
    $entity->set('status', 2);
    $entity->save();

    //----------写日志---------
    $trial = $order_service->getTrialById($trial_id);
    $entity->other_data = array('data_id' => $trial_id, 'data_name' => 'order_server_trial', 'data' => (array)$trial);
    $entity->other_status = 'trial_apply';
    HostLogFactory::OperationLog('order')->log($entity, 'trial_apply');

    drupal_set_message($this->t('Application is successful!Please wait for the audit results.')); //试用申请成功
    $form_state->setRedirectUrl(new Url('admin.order.list'));
  }
}
