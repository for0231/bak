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

class BusinessForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;

    $form['operate'] = array(
      '#type' => 'select',
      '#title' => t('Control'),
      '#options' => getBusinessControl(),
      '#required' => 'true',
      '#description' => t('The business front display control'),
      '#default_value' => $entity->getSimpleValue('operate'),
      '#weight' => 10
    );

    $form['resource_lib'] = array(
      '#type' => 'select',
      '#title' => t('Resource library'),
      '#options' => getBusinessLib(),
      '#default_value' => $entity->getSimpleValue('resource_lib'),
      '#description' => t('This business needs the associated data source'),
      '#weight' => 11,
      '#ajax' => array(
        'callback' => array(get_class($this), 'operateItem'),
        'wrapper' => 'resource_item_wrapper',
        'method' => 'html'
      ),
    );

    $form['content'] = array(
      '#type' => 'container',
      '#weight' => '15',
      '#id' => 'resource_item_wrapper'
    );

    $submit_lib = $form_state->getValue('resource_lib');
    $resource_lib = empty($submit_lib) ? $entity->getSimpleValue('resource_lib') : $form_state->getValue('resource_lib');
    if($resource_lib == 'part_lib') {
      $form['content']['entity_type'] = array(
        '#type' => 'select',
        '#title' => 'Part type',
        '#options' => part_entity_type_list(),
        '#required' => 'true',
        '#default_value' => $entity->getSimpleValue('entity_type'),
        '#description' => 'Business content related part type'
      );
    } else {
      $form['content']['entity_type'] = array();
    }
    $form['combine_mode'] = array(
      '#type' => 'select',
      '#title' => t('Merger way'),
      '#required' => 'true',
      '#options' => array(
        'add' => t('Add'),
        'replace' => t('Replace')
      ),
      '#weight' => '20',
      '#default_value' =>  $entity->getSimpleValue('combine_mode'),
      '#description' => t('The default settings and business combination mode'),
    );
    if(!$entity->isNew()) {
      $lib = $entity->getSimpleValue('resource_lib');
      if($lib != 'none') {
        $disabled = false;
        if($entity->getSimpleValue('locked')) {
          $disabled = true;
        } else {
          $entity_type = $lib == 'create' ? 'product_business_content' : 'product_business_entity_content';
          $child_content = entity_load_multiple_by_properties($entity_type, array('businessId' => $entity->id()));
          if(count($child_content)) {
            $disabled = true;
          }
        }
        $form['operate']['#disabled'] = $disabled;
        $form['resource_lib']['#disabled'] = $disabled;
        $form['combine_mode']['#disabled'] = $disabled;
        if($lib == 'part_lib') {
          $form['content']['entity_type']['#disabled'] = $disabled;
        }
      } else {
        $disabled = false;
        if($entity->getSimpleValue('locked')) {
          $disabled = true;
        }
        $form['resource_lib']['#disabled'] = $disabled;
        $form['combine_mode']['#disabled'] = $disabled;
        $form['operate']['#disabled'] = $disabled;
      }
    }
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $submit_lib = $form_state->getValue('resource_lib');
    if($submit_lib == 'ipb_lib') {
      $entity->set('entity_type', 'ipb');
    }
    $action = 'update';
    if($entity->isnew()) {
      $action = 'insert';
    }
    $entity->save();
    HostLogFactory::OperationLog('product')->log($entity, $action);
    drupal_set_message($this->t('Business saved successfully'));
    $form_state->setRedirectUrl(new Url('admin.product.business'));
  }

  /**
   * 业务操作回调函数
   */
  public static function operateItem(array $form, FormStateInterface $form_state) {
    return $form['content']['entity_type'];
  }
}
