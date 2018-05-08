<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\PolicyMultipleAddForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\qy\Form\PolicyBaseForm;

/**
 * 增加策略表单类
 */
class PolicyMultipleAddForm extends PolicyBaseForm {
  /**
   * 设置db_service
   */
  public function __construct() {
    $this->db_service = \Drupal::service('qy_jd.db_service');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    unset($form['ip_group']);
    return $form;
  }

  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $route_ids = $form_state->getValue('route_ids');
    foreach($route_ids as $route_id) {
      $bps = $form_state->getValue('bps_' . $route_id);
      if(empty($bps) || $bps < 1) {
        continue;
      }
      if(empty($form_state->getValue('pps_' . $route_id))) {
        $form_state->setErrorByName('pps_' . $route_id, '请输入pps');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    //获取提交的值
    $route_ids = $form_state->getValue('route_ids');
    $values = array();
    foreach($route_ids as $route_id) {
      $bps = $form_state->getValue('bps_' . $route_id);
      if(empty($bps) || $bps < 1) {
        continue;
      }
      $values[$route_id] = array(
        'ms' => $form_state->getValue('ms_' . $route_id),
        'note' => $form_state->getValue('note_' . $route_id),
        'bps' => $form_state->getValue('bps_' . $route_id),
        'pps' => $form_state->getValue('pps_' . $route_id),
        'time' => $form_state->getValue('time_' . $route_id),
        'timebyflow' => $form_state->getValue('timebyflow_' . $route_id),
        'doublebase' => $form_state->getValue('doublebase_' . $route_id),
        'traction_tip' => $form_state->getValue('traction_tip_' . $route_id)
      );
    }
    //保存
    $keys = array_keys($values);
    $returns = $this->db_service->load_policy_nopage(array('xx' => 0));
    $items = array();
    foreach($returns as $return) {
      $items[$return->ip][$return->routeid] = $return;
    }
    foreach($values as $route_id => $value) {
      $is_update = false;
      foreach($items as $ip=>$item) {
        if(isset($item[$route_id])) {
          $is_update = true;
        } else {
          $policy = reset($item);
          $add = $value + array(
            'ip' => $policy->ip,
            'mask_number' => $policy->mask_number,
            'routeid' => $route_id,
            'xx' => 0,
            'opter' => \Drupal::currentUser()->getUsername()
          );
          $this->db_service->add_policy($add);
        }
      }
      if($is_update) {
        unset($value['note']);
        $this->db_service->update_policyByRoute($value, $route_id);
      }
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.jd.policy'));
  }
}