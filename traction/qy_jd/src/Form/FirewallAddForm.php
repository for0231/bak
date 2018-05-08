<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\FirewallAddForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\EnforcedResponseException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 增加IP封停表单类
 */
class FirewallAddForm extends FormBase {

 protected $db_service;

  public function __construct($db_service) {
    $this->db_service = $db_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
       \Drupal::service('qy_jd.db_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'jd_firewall_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $route_id = 0) {
    if(empty($route_id)) {
      throw new EnforcedResponseException($this->redirect('admin.jd.route'));
    }
    $route = $this->db_service->load_routeById($route_id);
    if(empty($route)) {
      throw new EnforcedResponseException($this->redirect('admin.jd.route'));
    }
    $form['type'] = array(
      '#type' => 'select',
      '#title' => '类型',
      '#required' => true,
      '#options' => array($route->id => $route->routename),
      '#default_value' => $route->id
    );
    $form['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#required' => true
    );
    $form['port'] = array(
      '#type' => 'number',
      '#title' => '端口',
      '#required' => true,
    );
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => '用户名',
      '#required' => true,
      '#default_value' => 'autonull'
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => '密码',
      '#required' => true,
      '#default_value' => 'f6bqnvduod'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip = trim($form_state->getValue('ip'));
    if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
		  $form_state->setErrorByName('ip',$this->t('Ip格式错误'));
		}
    $netcoms = $this->db_service->load_netcom(array('ip' => array('value' => $ip, 'op' => 'like')));
    if(!empty($netcoms)) {
      $netcom = reset($netcoms);
      $firewall_id = $form_state->getValue('firewall_id');
      if($firewall_id != $netcom->id) {
        $form_state->setErrorByName('ip',$this->t('Ip重复'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip = $form_state->getValue('ip');
    $value = array(
      'type' => $form_state->getValue('type'),
      'ip' => trim($ip) . ':' . trim($form_state->getValue('port')),
      'username' => trim($form_state->getValue('username')),
      'password' => trim($form_state->getValue('password')),
      'time' => REQUEST_TIME
    );
    $this->db_service->add_netcom($value);
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.jd.route'));
  }
}

