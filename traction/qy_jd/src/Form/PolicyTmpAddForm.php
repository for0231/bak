<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\PolicyTmpAddForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\qy\Form\PolicyBaseForm;

/**
 * 增加策略表单类
 */
class PolicyTmpAddForm extends PolicyBaseForm {
  /**
   * 设置db_service
   */
  public function __construct() {
    $this->db_service = \Drupal::service('qy_jd.db_service');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $policy_id = 0) {
    if($policy_id > 0) {
      $policy = $this->db_service->load_policyById($policy_id);
      if(empty($policy) || $policy->xx != 3) {
        return $this->redirect('admin.jd.policy_tmp');
      }
      $this->policy_edit = $policy;
    }
    return parent::buildForm($form, $form_state, $policy_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function formRoute(array &$form, FormStateInterface $form_state, $route_id, $policy_route) {
    $form['route_' . $route_id]['kills_' . $route_id] = array(
      '#type' => 'number',
      '#title' => '结束时间',
      '#min' => 1,
      '#max' => 4320,
      '#weight' => -1,
      '#field_suffix' => '分钟,至少1分钟，最高不能超过72小时'
    );
    if(!empty($policy_route)) {
      $form['route_' . $route_id]['kills_' . $route_id]['#default_value'] = $policy_route->kills;
    }
    parent::formRoute($form, $form_state, $route_id, $policy_route);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterValue(array $form, FormStateInterface $form_state, $values, $route_id) {
    $values['xx'] = 3;
    $values['kills'] = $form_state->getValue('kills_' . $route_id);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $ip = $form_state->getValue('ip');
    $items = $this->db_service->load_policy_nopage(array('ip' => $ip, 'xx' => 3));
    if(empty($this->policy_edit)) {
      if(!empty($items)) {
        $form_state->setErrorByName('ip', 'IP已经存在！');
        return;
      }
    } else {
      $b = true;
      if(empty($items)){
        $b = false;
      } else {
        foreach($items as $item) {
          if($item->id == $this->policy_edit->id) {
            $b = false;
            continue;
          }
        }
      }
      if($b) {
        $form_state->setErrorByName('ip', 'IP已经存在！');
      }
    }
  }  

  /**
   * {@inheritdoc}
   */
  protected function saveSuccess(array $form, FormStateInterface $form_state) {
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.jd.policy_tmp'));
  }
}