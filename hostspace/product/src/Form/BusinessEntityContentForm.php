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
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\hostlog\HostLogFactory;

class BusinessEntityContentForm extends ContentEntityForm {

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
    $business = $entity->getObject('businessId');
    $lib = $business->getSimpleValue('resource_lib');
    if($lib == 'part_lib') {
      $business_entity_type = $business->getSimpleValue('entity_type');
      $entity->set('entity_type', $business_entity_type);
      $options = array();
      $part_list = entity_load_multiple($business_entity_type);
      foreach($part_list as $key=>$part) {
        $options[$key] = $part->label();
      }
      $form['target_id'] = array(
        '#type' => 'select',
        '#title' => t('Select part'),
        '#options' => $options,
        '#required' => true,
        '#default_value' => $entity->getSimpleValue('target_id')
      );
    } else if($lib == 'ipb_lib') {
      $options = array();
      $entity->set('entity_type', 'taxonomy_term');
      $taxonomy_list = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('business_ip_type');
      foreach($taxonomy_list as $item) {
        $options[$item->tid] = $item->name;
      }
      $form['target_id'] = array(
        '#type' => 'select',
        '#title' => t('Select ip type'),
        '#options' => $options,
        '#required' => true,
        '#default_value' => $entity->getSimpleValue('target_id')
      );
    } else {
       throw new EnforcedResponseException($this->redirect('admin.product.business'));
    }
    return $form; 
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;
    $target_id = $form_state->getValue('target_id');
    if(!empty($target_id)) {
      $filter['target_id'] = $target_id;
      $business = $entity->getObject('businessId');
      $lib = $business->getSimpleValue('resource_lib');
      if($lib == 'ipb_lib') {
        $filter['entity_type'] = 'taxonomy_term';
      } else {
        $filter['businessId'] = $business->id();
      }
      $entities = entity_load_multiple_by_properties('product_business_entity_content', $filter);
      if(!empty($entities)) {
        if($entity->isNew()) {
          $form_state->setErrorByName('target_id',$this->t('The data been exists.'));
        } else {
          if($entity->id() != reset($entities)->id()) {
            $form_state->setErrorByName('target_id',$this->t('The data been exists.'));
          }
        }
      }
    }
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
