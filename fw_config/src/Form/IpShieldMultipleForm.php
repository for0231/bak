<?php

/**
 * @file
 * Contains \Drupal\fw_config\Form\IpShieldMultipleForm.
 */

namespace Drupal\fw_config\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * 批量IP防护
 */
class IpShieldMultipleForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_ip_shield_multiple_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['op'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('shield-op'),
      )
    );
    $form['op']['save'] = array(
      '#type' => 'submit',
      '#value' => '提交',
      '#id' => 'multiple-shield-save'
    );
    $form['left'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('shield-left'),
      )
    );
    $form['left']['ip'] = array(
      '#type' => 'textarea',
      '#placeholder' => '一行输入一个IP'
    );
    $form['config'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('shield-rigit'),
      )
    );
    $form['config']['collect1'] = array(
      '#type' => 'fieldset'
    );
    $form['config']['collect1']['param'] = array(
      '#type' => 'checkboxes',
      '#options' => array(
        'ignore' => '忽视所有流量',
        'forbid' => '屏蔽所有流量',
        'forbid_overflow' => '流量超出屏蔽',
        'reject_foreign_access' => '拒绝国外访问'
      )
    );
    $form['config']['collect2'] = array(
      '#type' => 'fieldset'
    );
    $form['config']['collect2']['param_set'] = array(
      '#type' => 'number',
      '#title' => '防护参数集',
      '#default_value' => 0
    );
    $form['config']['collect2']['filter_set'] = array(
      '#type' => 'number',
      '#title' => '过滤规则集',
      '#default_value' => 0
    );
    $form['config']['collect2']['portpro_set_tcp'] = array(
      '#type' => 'number',
      '#title' => 'TCP端口集',
      '#default_value' => 0
    );
    $form['config']['collect2']['portpro_set_udp'] = array(
      '#type' => 'number',
      '#title' => 'UDP端口集',
      '#default_value' => 0
    );

    $form['config']['collect3'] = array(
      '#type' => 'fieldset'
    );
    $form['config']['collect3']['param_plugin'] = array(
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
