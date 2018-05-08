<?php

/**
 * @file 网站全局设置
 * Contains Drupal\common\Form\GlobalSettings
 */

namespace Drupal\common\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GlobalSettings extends ConfigFormBase {

  /**
   * Constructs a SiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }
   /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'global_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['common.global'];
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // 配置对象
    $config = $this->config('common.global');

    $form['ip'] = array(
      '#type' => 'details',
      '#open' => false,
      '#title' => 'IP模块库存预警设置'
    );
    $form['ip']['ipm_threshold'] = array(
      '#type' => 'number',
      '#title' => '管理IP预警值',
      '#default_value' => $config->get('ipm_threshold'),
      '#required' => TRUE,
    );
    $form['ip']['ipb_threshold'] = array(
      '#type' => 'number',
      '#title' => '业务IP预警值',
      '#default_value' => $config->get('ipb_threshold'),
      '#required' => TRUE,
    );
    $form['ip']['ips_threshold'] = array(
      '#type' => 'number',
      '#title' => '交换机预警值',
      '#default_value' => $config->get('ips_threshold'),
      '#required' => TRUE,
    );
    $form['global_tips'] = array(
      '#type' => 'details',
      '#open' => false,
      '#title' => '消息提醒模式'
    );
    $warning_array = array(
    );
    // 预警方式待更新为checkbox组.
    $form['global_tips']['warning_mode'] = array(
      '#type' => 'select',
      '#title' => '预警方式',
      '#default_value' => $config->get('warning_mode'),
      '#required' => TRUE,
      '#options' => warnMode(),
    );

    $form['auto_distribute'] = array(
      '#type' => 'details',
      '#open' => false,
      '#title' => '自动IP分配设置',
      '#description' => '开启或关闭网站客户支付完订单后自动分配服务器功能',
    );
    $auto_distribute = array(
      '0' => '关闭',
      '1' => '开启',
    );
    $form['auto_distribute']['is_auto_distribute'] = array(
      '#type' => 'select',
      '#title' => 'IP自动分配',
      '#options' => $auto_distribute,
      '#default_value' => $config->get('auto_distribute'),
      '#required' => TRUE,
    );
    if (\Drupal::moduleHandler()->moduleExists('idc')) {
      $rooms = array();
      $entity_rooms = entity_load_multiple('room');
      foreach ($entity_rooms as $row) {
        $rooms[$row->id()] = $row->label();
      }
      $form['district_room_id'] = array(
        '#type' => 'details',
        '#open' => false,
        '#title' => '限定机房属性',
        '#description' => '全站限定机房属性。如限定业务IP有机房属性，某些IP只能分配到对应机房的服务器使用。特定机房里包含了多少数量的某配置等等。',
      );
      $district_room_id = array(
        0 => '关闭',
        1 => '开启',
      );
      $form['district_room_id']['is_district_room_id'] = array(
        '#type' => 'select',
        '#title' => '开启机房限定',
        '#options' => $district_room_id,
        '#default_value' => $config->get('is_district_room_id'),
        '#required' => true,
      );
      $form['district_room_id']['is_district_room'] = array(
        '#type' => 'select',
        '#title' => '指定默认机房',
        '#options' => $rooms,
        '#default_value' => $config->get('is_district_room'),
        '#required' => true,
      );
      foreach($rooms as $key => $room) {
        $val = $config->get('room_rule_ip' . $key) ;
        if(empty($val)) {
          $val = 1;
        };
        $form['district_room_id']['room_rule_ip' . $key ] = array(
          '#type' => 'number',
          '#title' => $room . 'Ip分配倍数',
          '#default_value' => $val
        );
      }
      $server_trial_time = $config->get('server_trial_time');
      if(empty($server_trial_time)) {
        $server_trial_time  = 24;
      }
      $form['district_room_id']['server_trial_time'] = array(
        '#type' => 'number',
        '#title' => '默认试用时间(小时)',
        '#default_value' => $server_trial_time,
      );
    }
    $form['is_server_panel'] = array(
      '#type' => 'details',
      '#open' => false,
      '#title' => '开启服务器控制面板',
      '#description' => '开启或关闭会员中心服务器的控制面板功能.',
    );
    $form['is_server_panel']['server_panel'] = array(
      '#type' => 'select',
      '#title' => '是否开启面板',
      '#default_value' => $config->get('server_panel'),
      '#required' => TRUE,
      '#options' => array(0 => '关闭', 1 => '开启'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('common.global');
    $config->set('ipm_threshold', $form_state->getValue('ipm_threshold'))
      ->set('ipb_threshold', $form_state->getValue('ipb_threshold'))
      ->set('ips_threshold', $form_state->getValue('ips_threshold'))
      ->set('warning_mode', $form_state->getValue('warning_mode'))
      ->set('auto_distribute', $form_state->getValue('is_auto_distribute'))
      ->set('is_district_room_id', $form_state->getValue('is_district_room_id'))
      ->set('is_district_room', $form_state->getValue('is_district_room'))
      ->set('server_panel', $form_state->getValue('server_panel'))
      ->set('server_trial_time', $form_state->getValue('server_trial_time'));
    if (\Drupal::moduleHandler()->moduleExists('idc')) {
      $entity_rooms = entity_load_multiple('room');
      foreach ($entity_rooms as $row) {
        $key = $row->id();
        $config->set('room_rule_ip' . $key, $form_state->getValue('room_rule_ip' . $key));
      }
    }
    $config->save();
    parent::submitForm($form, $form_state);

  }

}
