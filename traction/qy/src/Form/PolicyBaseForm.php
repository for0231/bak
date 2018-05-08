<?php

/**
 * @file
 * Contains \Drupal\qy\Form\PolicyBaseForm.
 */

namespace Drupal\qy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 增加策略表单类
 */
class PolicyBaseForm extends FormBase {

  /**
   * 数据服务变量
   */
  protected $db_service;
  
  /**
   * 修改的策略对象
   */
  protected $policy_edit = array();
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_policy_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $policy_id = 0) {
    $policy_routes = array();
    if(!empty($this->policy_edit)) {
      $items = $this->db_service->load_policy_nopage(array('ip' => $this->policy_edit->ip, 'xx' => $this->policy_edit->xx));
      foreach($items as $item) {
        $policy_routes[$item->routeid] = $item;
      }
    }    
    $form['ip_group'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('form-item')
      ),
      '#weight' => 1,
    );
    $form['ip_group']['title'] = array(
      '#type' => 'label',
      '#title' => 'IP'
    );
    $form['ip_group']['ip'] = array(
      '#type' => 'textfield',
      '#size' => 24,
      '#required' => true,
      '#field_suffix' => '/',
      '#prefix' => '<div class = "container-inline">'
    );
    $form['ip_group']['mask_number'] = array(
      '#type' => 'number',
      '#required' => true,
      '#min' => 0,
      '#max' => 32,
      '#size' => 1,
      '#suffix' => '</div>'
    );
    $form['policy'] = array(
      '#type' => 'vertical_tabs',
      '#weight' => 2
    );
    $routes = $this->db_service->load_route(array(), false);
    $route_ids = array();
    foreach($routes as $route) {
      $route_ids[] = $route->id;
      $groupid = 'route_' . $route->id;
      $form[$groupid] = array(
        '#type' => 'details',
        '#group' => 'policy',
        '#title' => $route->routename
      );
      $form[$groupid]['ms_' . $route->id] = array(
        '#type' => 'select',
        '#title' => '牵引模式',
        '#default_value' => 1,
        '#options' => qy_traction_ms(),
        '#field_suffix' => '只认正常流量牵引条件，不认超大流量牵引条件'
      );
      $form[$groupid]['bps_' . $route->id] = array(
        '#type' => 'number',
        '#title' => '流量阀值',
        '#min' => 1,
        '#field_suffix' => '(BPS 单位:Mbps)'
      );
      $form[$groupid]['pps_' . $route->id] = array(
        '#type' => 'number',
        '#title' => '包阀值',
        '#step' => 'any',
        '#field_suffix' => '(PPS 单位:万pps)'
      );
      $form[$groupid]['time_' . $route->id] = array(
        '#type' => 'number',
        '#title' => '牵引时间',
        '#default_value' => 0, 
        '#field_suffix' => '(分)[0 代表永久]'
      );
      $form[$groupid]['timebyflow_'. $route->id] = array(
        '#type' => 'checkbox',
        '#title' => '启动通过流量加权时间',
        '#default_value' => 1,
      );
      $form[$groupid]['doublebase_'. $route->id] = array(
        '#type' => 'number',
        '#title' => '基础倍数',
        '#default_value' => 1,
        '#max' => 100,
        '#min' => 1,
        '#field_suffix' => '开启通过流量来增加时间时使用的基础倍数'
      );
      $form[$groupid]['traction_tip_' . $route->id] = array(
        '#type' => 'checkbox',
        '#title' => '启动牵引提示',
      );
      $form[$groupid]['note_' . $route->id] = array(
        '#type' => 'textfield',
        '#title' => '备注',
      );
      $form[$groupid]['policyid_' . $route->id] = array(
        '#type' => 'value',
        '#value' => 0
      );
      $policy_route = null;
      if(isset($policy_routes[$route->id])) {
        $policy_route = $policy_routes[$route->id];
      }
      $this->formRoute($form, $form_state, $route->id, $policy_route);
    }
    $form['route_ids'] = array(
      '#type' => 'value',
      '#value' => $route_ids
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
      '#weight' => 30,
    );
    return $form;
  }

  /**
   * 更改form表
   */
  protected function formRoute(array &$form, FormStateInterface $form_state, $route_id, $policy_route) {
    if(!empty($policy_route)) {
      $groupid = 'route_' . $route_id;
      $form['ip_group']['ip']['#default_value'] = $policy_route->ip;
      $form['ip_group']['mask_number']['#default_value'] = $policy_route->mask_number;
      $form[$groupid]['ms_' . $route_id]['#default_value'] = $policy_route->ms;
      $form[$groupid]['note_'. $route_id]['#default_value'] = $policy_route->note;
      $form[$groupid]['bps_'. $route_id]['#default_value'] = $policy_route->bps;
      $form[$groupid]['pps_'. $route_id]['#default_value'] = $policy_route->pps;
      $form[$groupid]['time_'. $route_id]['#default_value'] = $policy_route->time;
      $form[$groupid]['timebyflow_'. $route_id]['#default_value'] = $policy_route->timebyflow;
      $form[$groupid]['doublebase_'. $route_id]['#default_value'] = $policy_route->doublebase;
      $form[$groupid]['traction_tip_'. $route_id]['#default_value'] = $policy_route->traction_tip;
      $form[$groupid]['policyid_'. $route_id]['#value'] = $policy_route->id;
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = $form_state->getValue('ip');
    $mask_number = $form_state->getValue('mask_number');
    if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
      $form_state->setErrorByName('ip', 'Ip格式错误');
      return;
    }
    $ips = explode(".", $ip);
    $n = 32 - $mask_number;
    $num = pow(2, $n);
    $max = $ips[3] + $num - 1;
    if($max > 255) {
      $form_state->setErrorByName('mask_number', 'IP段错误');
    }
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
    $user = \Drupal::currentUser();
    $route_ids = $form_state->getValue('route_ids');
    foreach($route_ids as $route_id) {
      $bps = $form_state->getValue('bps_' . $route_id);
      if(empty($bps) || $bps < 1) {
        continue;
      }
      $value = array(
        'ip' => $form_state->getValue('ip'),
        'mask_number' => $form_state->getValue('mask_number'),
        'routeid' => $route_id,
        'ms' => $form_state->getValue('ms_' . $route_id),
        'note' => $form_state->getValue('note_' . $route_id),
        'bps' => $form_state->getValue('bps_' . $route_id),
        'pps' => $form_state->getValue('pps_' . $route_id),
        'time' => $form_state->getValue('time_' . $route_id),
        'opter' => $user->getUsername(),
        'timebyflow' => $form_state->getValue('timebyflow_' . $route_id),
        'doublebase' => $form_state->getValue('doublebase_' . $route_id),
        'traction_tip' => $form_state->getValue('traction_tip_' . $route_id)
      );
      $policyid = $form_state->getValue('policyid_' . $route_id);
      $alter_value = $this->alterValue($form, $form_state, $value, $route_id);
      if($policyid) {
        $this->db_service->update_policy($alter_value, $policyid);
      } else {
        if($alter_value['xx'] == 3) {
          $alter_value['starts'] = REQUEST_TIME;
        }
        $this->db_service->add_policy($alter_value);
      }
    }
    $this->saveSuccess($form, $form_state);
  }
  
  /**
   * 更改form表单的值
   */
  protected function alterValue(array $form, FormStateInterface $form_state, $value, $route_id) {
    return $value;
  }

  /**
   * 所有线路保存成功的时候执行
   */
  protected function saveSuccess(array $form, FormStateInterface $form_state) {
    drupal_set_message('保存成功');
  }
}
