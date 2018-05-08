<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\PolicyAddForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\qy\Form\PolicyBaseForm;

/**
 * 增加策略表单类
 */
class PolicyAddForm extends PolicyBaseForm {
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
      if(empty($policy) || $policy->xx != 0) {
        return $this->redirect('admin.jd.policy');
      }
      $this->policy_edit = $policy;
    }
    return parent::buildForm($form, $form_state, $policy_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterValue(array $form, FormStateInterface $form_state, $values, $route_id) {
    $values['xx'] = 0;
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $ip = $form_state->getValue('ip');
    $items = $this->db_service->load_policy_nopage(array('ip' => $ip, 'xx' => 0));
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
    $form_state->setRedirectUrl(new Url('admin.jd.policy'));
  }
}