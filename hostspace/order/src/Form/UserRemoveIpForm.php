<?php

/**
 * @file
 * Contains \Drupal\order\Form\UserRemoveIpForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class UserRemoveIpForm extends ContentEntityForm {
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
    $form['ipb_tab'] = array(
      '#type' => 'details',
      '#title' => $this->t('该服务器下IP列表'),
      '#open' => true,
    );
    $form['ipb_tab']['wanted'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Change your search IP'),
      '#description' => $this->t('Do you confirm to change this server IP'),
    );
    $form['ipb_tab']['ipb_values'] = array(
      '#type' => 'select',
      '#id' => 'edit-ipb-id',
      '#multiple' => true,
      '#title' => $this->t('Please choose to remove ip'),
      '#options' => $ipb_options,
    );
    $sop_iband_entity_array =  entity_load_multiple_by_properties('sop_task_iband', array(
      'hid' => $entity->id(),
    ));
    $sop_iband = current($sop_iband_entity_array);

    $sips = $sop_iband->get('sips');
    foreach($sips as $value) {
      $ipb_obj = $value->entity;
      if($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $sop_ipb_options[$ipb_obj->id()] = $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label();
      }
    }
    if (!empty($sips) && count($sips) != 0) {
      $form['ipb_tab']['sop_ip'] = array(
        '#type' => 'select',
        '#id' => 'edit-sop-ipb',
        '#title' => $this->t('Deleted IPs'),
        '#multiple' => true,
        '#options' => $sop_ipb_options,
      );
    }
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
    $actions['submit']['#value'] = $this->t('Save');
    return $actions;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $entity = $this->entity;

    /**
     * @description 验证工单里面是否已经包含了停用IP
     */
    $sop_iband_entity_array =  entity_load_multiple_by_properties('sop_task_iband', array(
      'hid' => $entity->id(),
    ));
    $sop_iband = current($sop_iband_entity_array);

    $sips = $sop_iband->get('sips');
    /**
    if (!empty($sips)) {
        $form_state->setErrorByName('ipb_values',$this->t('该服务器已经提交过移除IP，请待后台完成后再提交!'));
    }
    */
    $ipb_values = $form_state->getValue('ipb_values');
    if(!empty($ipb_values)) {
      $surplus_ipbs = array();
      $original_ipbs = $entity->get('ipb_id')->getValue();
      foreach($original_ipbs as $ipb) {
        if(!array_key_exists($ipb['target_id'], $ipb_values)) {
          $surplus_ipbs[] = $ipb['target_id'];
        }
      }
      // 未确认是更换
      if ($form_state->getValue('wanted') == 0) {
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
      }
    } else {
      $form_state->setErrorByName('ipb_values',$this->t('Please select to delete IP'));
    }
  }


  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // 修改SOP并保存
    $entity = $this->entity;
    $ipb_values = $form_state->getValue('ipb_values');
    $op_type = $form_state->getValue('wanted');
    $sop_entity_array =  entity_load_multiple_by_properties('sop', array(
      'hid' => $entity->id(),
      'module' => 'sop_task_iband',
    ));
    $sop_iband_entity_array =  entity_load_multiple_by_properties('sop_task_iband', array(
      'hid' => $entity->id(),
    ));
    $sop_iband = current($sop_iband_entity_array);
    $sop = current($sop_entity_array);
    // 如果这两个实体不一致则错误。
    // sop的sid应该等于sop_iband的ID
    $sid = $sop->get('sid')->value;
    if ($sid != $sop_iband->id()) {
      drupal_set_message('工单指向异常, 保存失败!');
    } else {
      // 更改工单状态
      // 保存工单状态
      if(!empty($ipb_values)) {
        $sop_iband->set('sop_status', 0);
        if ($op_type == 0) {
          $sop_iband->set('sop_op_type', 26);
          $sop->set('sop_op_type', 26);
        } else {
          $sop_iband->set('sop_op_type', 27);
          $sop->set('sop_op_type', 27);
        }
        $sop_iband->set('sop_complete', 0);
        $sop->set('sop_status', 0);
        $sop->set('sop_complete', 0);
        $sop_iband->set('sips', $ipb_values);
        if (\Drupal::moduleHandler()->moduleExists('order') && $form_state->getValue('wanted') == 0) {
          \Drupal::service('hostclient.serverservice')->updateHostclientBusiness($entity->id(), $sips);
        }
        $sop->save();
        $sop_iband->save();
      }

      drupal_set_message('已生成工单! 从工单系统里面进行后续操作!!^.^');
    }
  }
}
