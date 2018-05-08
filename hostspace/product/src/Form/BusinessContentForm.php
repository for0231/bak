<?php

/**
 * @file
 * Contains \Drupal\product\Form\BusinessForm.
 */

namespace Drupal\product\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;
use Drupal\hostlog\HostLogFactory;

class BusinessContentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    if($entity->isNew()) {
      $request = \Drupal::request()->attributes->all();
      $businessId = $request['product_business'];
      $entity->set('businessId', $businessId);
    }
    return $form; 
  }

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
    HostLogFactory::OperationLog('product')->log($entity, $action);
    drupal_set_message($this->t('Business content saved successfully'));
    $form_state->setRedirectUrl(new Url('admin.product.business'));
  }

}
