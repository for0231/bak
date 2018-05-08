<?php

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RuleAddForm extends FormBase{
  
  public function getFormId() {
    return 'rule_add_from';
  }
  
  public function buildForm(array $form, FormStateInterface $form_state) {
    $build['num'] = array(
      '#type' => 'textfield',
      '#title' => '规则序号',
      '#value'=>'-1',
    );
    $build['des'] = array(
      '#type' => 'textfield',
      '#title' => '规则描述',
      '#placeholder' => '规则描述的格式如：155idc 23.234.25.28 17.3.4 add',
    );
    $build['iplocal'] = array(
      '#type' => 'textfield',
      '#title' => '本地地址',
      '#required'=> true,
    );
    $build['ipremote'] = array(
      '#type' => 'textfield',
      '#title' => '远程地址',
      '#required'=> true,
    );
    $build['type'] = array(
      '#type' => 'textfield',
      '#title' => '协议类型',
      '#value' => 'ip',
      '#required'=> true,
    );
    $build['behavior'] = array(
      '#type' => 'select',
      '#title' => '规则行为',
      '#options' => array(
        'pass'=>'放行',
        'forbid'=>'屏蔽'
      )
    );
    $build['submit'] = array(
      '#type' => 'submit',
      '#value' => '提交',
    );
    return $build;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::config('fw_config.settings');
    $conn = new \Drupal\fw_config\FwConnect();
    $write_fw = $config->get('write_fws');
    $write_fws = explode("\r\n", $write_fw);
    foreach($write_fws as $item) {
      $item_arr = explode("-", $item);
      $url = 'http://'. $item_arr[0] .'/cgi-bin/params_filter_edit.cgi';
      $login = $conn->login($item_arr[0], $item_arr[1], $item_arr[2]);
      if(!$login) {
        drupal_set_message($item_arr[0] . '登录失败');
        continue;
      }
      $xml = $conn->postData($url, array(
        'param_recv' => 'ON',
        'param_rule_index' => -1,
        'param_index' => $form_state->getValue('num'),
        'param_desc' => $form_state->getValue('des'),
        'param_laddr' => $form_state->getValue('iplocal'),
        'param_raddr' => $form_state->getValue('ipremote'),
        'param_protocol' => $form_state->getValue('type'),
        'param_action' => $form_state->getValue('behavior'),
        'param_set_index' => 15
      ));
      if (is_object($xml)) {
        drupal_set_message($item_arr[0].'添加成功');
      } else {
        drupal_set_message($item_arr[0].'添加失败');
      }
    }
    $form_state->setRedirectUrl(new Url('admin.fw.rule.list'));
  }
}

?>