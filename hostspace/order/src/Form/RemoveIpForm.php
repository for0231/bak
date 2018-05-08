<?php

/**
 * @file
 * Contains \Drupal\order\Form\RemoveIpForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class RemoveIpForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $ipb_options = array();
    $ipb_values = $entity->get('ipb_id');
    foreach($ipb_values as $value) {
      $ipb_obj = $value->entity;
      if($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $ipb_options[$ipb_obj->id()] = $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label();
      }
    }
    $form['ipb_values'] = array(
      '#type' => 'select',
      '#id' => 'edit-ipb-id',
      '#multiple' => true,
      '#title' => $this->t('Please choose to remove ip'),
      '#size'=> 10,
      '#options' => $ipb_options,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;

    $ipb_values = $form_state->getValue('ipb_values');
    if(!empty($ipb_values)) {
      $surplus_ipbs = array();
      $original_ipbs = $entity->get('ipb_id')->getValue();
      foreach($original_ipbs as $ipb) {
        if(!array_key_exists($ipb['target_id'], $ipb_values)) {
          $surplus_ipbs[] = $ipb['target_id'];
        }
      }
      if(empty($surplus_ipbs)) {
        $form_state->setErrorByName('ipb_values',$this->t('IP cannot remove all')); 
      } else {
        $form_state->surplus_ipbs = $surplus_ipbs;
        //剩余IP所属业务
        $surplus_business = array();
        foreach($surplus_ipbs as $ipb) {
          $ipb_obj = entity_load('ipb', $ipb);
          $ipb_type = $ipb_obj->get('type')->target_id;
          $entities = entity_load_multiple_by_properties('product_business_entity_content', array(
            'entity_type' => 'taxonomy_term', 
            'target_id' => $ipb_type
          ));
          $content = reset($entities);
          $surplus_business[$content->getObjectId('businessId')][$content->id()][] = $ipb;
        }
        //验证剩余IP所属业务是否包含完默认业务
        $b = true;
        $default_service = \Drupal::service('product.default.business'); 
        $default_business = $default_service->getListByProduct($entity->getObjectId('product_id'));
        foreach($default_business as $business) {
          $business_obj = entity_load('product_business', $business->businessId);
          $lib = $business_obj->getSimpleValue('resource_lib');
          if($lib != 'ipb_lib') {
            continue;
          }
          $operate = $business_obj->getSimpleValue('operate');
          if($operate == 'edit_number') {
            if(!isset($surplus_business[$business->businessId])) {
              $b = false;
              break;
            }
            $def_number = $business->business_content;
            $sur_number = 0;
            $sur_business = $surplus_business[$business->businessId];
            foreach($sur_business as $ips) {
              $sur_number += count($ips);
            }
            if($def_number > $sur_number) {
              $b = false;
              break;
            }
          } else if ($operate == 'select_content') {
            if(!isset($surplus_business[$business->businessId])) {
              $b = false;
              break;
            }
            $def_value = $business->business_content;
            $sur_business = $surplus_business[$business->businessId];
            if(!array_key_exists($def_value, $sur_business)) {
              $b = false;
              break;
            }
          } else if ($operate == 'select_and_number') {
            if(!isset($surplus_business[$business->businessId])) {
              $b = false;
              break;
            }
            $def_value = $business->business_content;
            $def_value_arr = explode(':', $def_value);
            $sur_business = $surplus_business[$business->businessId];
            if(!array_key_exists($def_value_arr[0], $sur_business)) {
              $b = false;
              break;
            }
            $sur_number = count($sur_business[$def_value_arr[0]]);
            if($def_value_arr[1] > $sur_number) {
              $b = false;
              break;
            }
          }
        }
        if(!$b) {
          $form_state->setErrorByName('ipb_values',$this->t('IP cannot delete the default!'));
        }
      }
    } else {
      $form_state->setErrorByName('ipb_values',$this->t('Please select to delete IP')); 
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $ipb_values = $form_state->getValue('ipb_values');
    if(!empty($ipb_values)) {
      $surplus_ipbs = $form_state->surplus_ipbs;
      $entity->set('ipb_id', $surplus_ipbs);
      $entity->save_ipb_change = array('rm' => $ipb_values, 'add' => array());
      $entity->save_business_change = true;
      $entity->save();
      //--------写日志--------
      HostLogFactory::OperationLog('order')->log($entity, 'remove_ip');
    }
    drupal_set_message($this->t('Ip remove successful.'));
    $form_state->setRedirectUrl(new Url('admin.hostclient.list'));
  }
}

