<?php

/**
 * @file
 * Contains \Drupal\fw_config\Form\IpShieldForm.
 */

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 手动增加牵引表单类
 */
class IpShieldForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_ip_shield_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if(\Drupal::currentUser()->hasPermission('administer fw ip library')) {
      $form['op'] = array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('shield-op'),
        )
      );
      $form['op']['save'] = array(
        '#type' => 'submit',
        '#value' => '更新IP库',
        '#id' => 'update-ip-library'
      );
    }
    $form['filter'] = array(
      '#type' => 'fieldset',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $form['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP地址'
    );
    $form['filter']['query'] = array(
      '#type' => 'button',
      '#value' => '查询',
    );
    $form['filter']['save'] = array(
      '#type' => 'submit',
      '#value' => '提交',
      '#id' => 'ip-shield-save'
    );
    $form['collect1'] = array(
      '#type' => 'fieldset'
    );
    $form['collect1']['param'] = array(
      '#type' => 'checkboxes',
      '#options' => array(
        'ignore' => '忽视所有流量',
        'forbid' => '屏蔽所有流量',
        'forbid_overflow' => '流量超出屏蔽',
        'reject_foreign_access' => '拒绝国外访问'
      )
    );
    $form['collect2'] = array(
      '#type' => 'fieldset'
    );
    $form['collect2']['param_set'] = array(
      '#type' => 'number',
      '#title' => '防护参数集',
      '#default_value' => 0
    );
    $form['collect2']['filter_set'] = array(
      '#type' => 'number',
      '#title' => '过滤规则集',
      '#default_value' => 0
    );
    $form['collect2']['portpro_set_tcp'] = array(
      '#type' => 'number',
      '#title' => 'TCP端口集',
      '#default_value' => 0
    );
    $form['collect2']['portpro_set_udp'] = array(
      '#type' => 'number',
      '#title' => 'UDP端口集',
      '#default_value' => 0
    );
    
    $form['collect3'] = array(
      '#type' => 'fieldset'
    );
    $form['collect3']['param_plugin'] = array(
      '#type' => 'checkboxes',
      '#options' => array(
        'tcp_0' => 'tcp WEB Service Protection v8.89',
        'tcp_1' => 'tcp Game Service Protection v4',
        'tcp_2' => 'tcp Misc Service Protection v3.2',
        'tcp_3' => 'tcp DNS Service Protection v2.3',
        'udp_1' => 'udp DNS Service Protection v2.3',
        'udp_4' => 'UDP App Protection v1.0',
      )
    );
    $form['#attached'] = array(
      'library' => array('fw_config/drupal.ip-shield', 'fw_config/drupal.fw-common')
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }
}
