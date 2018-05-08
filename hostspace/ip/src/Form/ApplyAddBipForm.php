<?php
/**
 * @file  IP段入库申请
 * Contains \Drupal\ip\Form\ApplyAddBipForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class ApplyAddBipForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'apply_add_bip';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // 配置对象
    $config = $this->config('common.global');
    $form['group_ip'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
   $form['group_ip']['ip_paragraph'] = array(
      '#type' => 'textfield',
      '#required' =>TRUE,
      '#title' => 'ip',
      '#size' => 20
    );
    $form['group_ip']['ipd_start'] = array(
      '#type' => 'number',
      '#required' =>TRUE,
      '#size' => 5
    );
    $form['group_ip']['ipd_end'] = array(
      '#type' => 'number',
      '#required' =>TRUE,
      '#size' => 5
    );

    $form['user'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $form['user']['agent'] = array(
      '#type' => 'select',
      '#title' => '代理',
      '#options' => array('' => '请选择专用用户') + array_filter(user_role_names(), create_function( '$v', 'return stristr($v,\'Agent\');')),
      '#ajax' => array(
        'callback' => array(get_class($this), 'loadClientByAgent'),
        'wrapper' => 'client_wrapper',
        'method' => 'html'
      )
    );
    $form['user']['client_wrapper'] = array(
      '#type' => 'container',
      '#id' => 'client_wrapper'
    );
    $agent = $form_state->getValue('agent');
    if($agent) {
      $client = \Drupal::service('member.memberservice')->getAllAgent($agent);
      $options = array('' => '-选择-');
      foreach($client as $c) {
        $options[$c->uid] = $c->client_name ? $c->client_name : entity_load('user', $c->uid)->getUsername();
      }
      $form['user']['client_wrapper']['client'] = array(
        '#type' => 'select',
        '#title' => '专用用户',
        '#options' => $options
      );
    } else {
      $form['user']['client_wrapper']['client'] = array();
    }
    $form['user']['tip'] = array('#markup' => '若没有专用用户请忽略此选项。');

    // 防御
    $defense_ops = $this->rebuildArray('business_ip_type', '无防御');
    $form['defense'] = array(
      '#type' => 'select',
      '#title' => '防御',
      '#options' => $defense_ops['options'],
      '#default_value' => $defense_ops['default'],
      '#required' => true
    );
    // 类型
    $type_ops = $this->rebuildArray('business_ip_segment_type', '普通段');
    $form['type'] = array(
      '#type' => 'select',
      '#title' => '类型',
      '#options' => $type_ops['options'],
      '#default_value' => $type_ops['default']
    );
    // 加载所有机房数据
    $entity_rooms = entity_load_multiple('room');
    $room_options = array();
    foreach ($entity_rooms as $row) {
      $room_options[$row->id()] = $row->label();
    }
    $form['rid'] = array(
      '#type' => 'select',
      '#title' => '所属机房',
      '#required' => true,
      '#options' => $room_options,
      '#ajax' => array(
        'callback' => array(get_class($this), 'groupItem'),
        'wrapper' => 'group_item_wrapper',
        'method' => 'html'
      )
    );
    $form['content'] = array(
      '#type' => 'container',
      '#id' => 'group_item_wrapper'
    );
    $rid = $form_state->getValue('rid');
    if(!empty($rid)) {
      $group_options = array();
      $groups = \Drupal::service('ip.ipservice')->loadIpGroup(array('rid' => $rid));
      foreach($groups as $group) {
        $group_options[$group->gid] = $group->name;
      }
      $form['content']['group_id'] = array(
        '#type' => 'select',
        '#title' => '所属分组',
        '#required' => true,
        '#options' => $group_options
      );
    } else {
      $form['content']['group_id'] = array();
    }

    $form['remark'] = array(
      '#type' => 'textarea',
      '#title' => '此IP段的备注',
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Apply'),
    );
    return $form;
  }

  public function loadClientByAgent(array $form, FormStateInterface $form_state) {
    return $form['user']['client_wrapper']['client'];
  }

  public function groupItem(array $form, FormStateInterface $form_state) {
    return $form['content']['group_id'];
  }

  private function getAllClientData() {
    $options = array();
    $clients = \Drupal::service('member.memberservice')->getAllAgent();
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip_paragraph = trim($form_state->getValue('ip_paragraph'));
    $ipd_start = trim($form_state->getValue('ipd_start'));
    $ipd_end = trim($form_state->getValue('ipd_end'));
    if($ipd_start > $ipd_end) {
      $form_state->setErrorByName('ip_start','起始IP不能大于结束IP');
    }
    if($ipd_start>$ipd_end){
      $k=$ipd_start;
      $ipd_startpb=$ipd_end;
      $ipd_end=$ipd_start;
    }
    for($i=$ipd_start;$i<=$ipd_end;$i++){
      $ips=$ip_paragraph.".".$i;
      if(strcmp(long2ip(sprintf("%u",ip2long($ips))),$ips)){
        $form_state->setErrorByName('ip', $ips. '不是一个正确的IP。');
      }
      $ipbs = entity_load_multiple_by_properties('ipb', array('ip' => $ips));
      if(!empty($ipbs)) {
        $form_state->setErrorByName('ip', $ips. '已经申请入库了。');
      }
    }
  }
  /**
   * 根据加载出来的术语 重构出下拉框的选项和默认值
   */
  private function rebuildArray($taxonomy, $default_condition=null) {
    $array = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($taxonomy, 0, 1);
    $defense_arr = array();
    $default_val = 0;
    foreach($array as $defense) {
      if($defense->name == $default_condition) {
        $default_val = $defense->tid;
      }
      $defense_arr[$defense->tid] = $defense->name;
    }
    $rebuild['default'] = $default_val;
    $rebuild['options'] = $defense_arr;
    return $rebuild;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip_paragraph = trim($form_state->getValue('ip_paragraph'));
    $ipd_start = trim($form_state->getValue('ipd_start'));
    $ipd_end = trim($form_state->getValue('ipd_end'));
    $defense = $form_state->getValue('defense');
    $type = $form_state->getValue('type');
    $remark = $form_state->getValue('remark');
    $exclude_ip   = $form_state->getValue('exclude_ip');
    $client   = $form_state->getValue('client');
    $rid = $form_state->getValue('rid');
    $group_id = $form_state->getValue('group_id');
    $fields = array(
      'segment' => $ip_paragraph,
      'begin' => $ipd_start,
      'end' => $ipd_end,
      'defense' => $defense,
      'type' => $type,
      'remark' => $remark,
      'uid' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME,
      'rid' => $rid,
      'group_id' => $group_id
    );
    if($client) {
      $fields =  $fields + array('client' => $client);
    }
    $rs = \Drupal::service('ip.ipservice')->saveApplyRecord($fields);
    if($rs) {
      $entity = entity_create('ipb', array('id' => 0));
      $entity->other_data = array('data_name' => 'bip_apply', 'data'=> $fields, 'data_id' => 0);
      HostLogFactory::OperationLog('ip')->log($entity, 'apply');
      drupal_set_message('你的申请已经提交成功。');
    } else {
      drupal_set_message('申请提交失败！', 'warning');
    }
  }
}
