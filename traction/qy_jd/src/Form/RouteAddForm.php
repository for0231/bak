<?php

/**
 * @file
 * Contains \Drupal\qy_jd\Form\RouteAddForm.
 */

namespace Drupal\qy_jd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * 线路表单
 */
class RouteAddForm extends FormBase {

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
    return 'jd_route_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $form['routename'] = array(
      '#type' => 'textfield',
      '#title' => '线路名称',
      '#required' => true,
      '#maxlength' => 50
    );
    $form['blackhole'] = array(
      '#type' => 'textfield',
      '#title' => '黑洞地址',
      '#required' => true,
      '#maxlength' => 250,
      '#description' => '多个用逗号分隔。'
    );
    $form['username'] = array(
      '#type' => 'textfield',
      '#title' => '用户名',
      '#required' => true,
      '#maxlength' => 50,
      '#description' => '登录黑洞的用户名'
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => '密码',
      '#required' => true,
      '#maxlength' => 50,
      '#description' => '登录黑洞的密码'
    );
    $form['mode_command'] = array(
      '#type' => 'radios',
      '#title' => '命令模板',
      '#default_value' => 1,
      '#options' => array('1' => 'vyatta', '2' => 'cisco'), //'3' => 'huawei'
      '#attributes' => array(
        'class' => array('container-inline')
      )
    );
    $form['blackhole_command'] = array(
      '#type' => 'textfield',
      '#title' => '黑洞命令',
      '#required' => true,
      '#maxlength' => 50,
      '#default_value' => 'blackhole',
      '#description' => '命令结果：set protocols static route %ip/32 <span>blackhole</span>'
    );
    $form['total_bps'] = array(
      '#type' => 'number',
      '#title' => '总体bps',
      '#required' => true,
      '#field_suffix' => 'm/bps'
    );
    $form['one_bps'] = array(
      '#type' => 'number',
      '#title' => '单墙BPS',
      '#required' => true,
      '#field_suffix' => 'm/bps'
    );
    $form['time'] = array(
      '#type' => 'number',
      '#title' => '牵引时间',
      '#required' => true,
      '#default_value' => 30,
      '#field_suffix' => '分钟'
    );
    $form['max_count'] = array(
      '#type' => 'number',
      '#title' => '最大牵引数',
      '#required' => true,
      '#default_value' => 20,
      '#field_suffix' => '条'
    );
    $form['max_index'] = array(
      '#type' => 'number',
      '#title' => '最大索引值',
      '#required' => true,
      '#default_value' => 100,
      '#description' => '从防火墙中读取数据的最大条数'
    );
    $form['is_global'] = array(
      '#type' => 'checkbox',
      '#title' => '启动全局牵引',
      '#description' => '如果开启那么此线路的流量如果超出范围将牵引全部线路。'
    );
    $form['status'] = array(
      '#type' => 'select',
      '#title' => '状态',
      '#options' => qy_route_status(),
      '#default_value' => 1,
      '#required' => true,
    );
    $form['email_content'] = array(
      '#type' => 'textarea',
      '#title' => '邮件内容',
      '#description' => '1、如果为空此张路牵引的IP将不会发送邮件。<br/> 2、可使用的标签有：{username}-用户名称,{routename}-线路名称,{bps}-流量,{ip},{begintime}-牵引时间, {endtime}-预计解封时间'
    );
    $form['route_id'] = array(
      '#type' => 'value',
      '#value' => 0
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存',
    );
    if($id) {
      $route = $this->db_service->load_routeById($id);
      if(!empty($route)) {
        $form['routename']['#default_value'] = $route->routename;
        $form['blackhole']['#default_value'] = $route->blackhole;
        $form['username']['#default_value'] = $route->username;
        $form['password']['#default_value'] = $route->password;
        $form['mode_command']['#default_value'] = $route->mode_command;
        $form['blackhole_command']['#default_value'] = $route->blackhole_command;
        if($route->mode_command == 2) {
          $form['blackhole_command']['#description'] = '命令结果：prefix-set <span>'. $route->blackhole_command .'</span>';
        } else {
          $form['blackhole_command']['#description'] = '命令结果：set protocols static route %ip/32 <span>'. $route->blackhole_command .'</span>';
        }
        $form['total_bps']['#default_value'] = $route->total_bps;
        $form['one_bps']['#default_value'] = $route->one_bps;
        $form['time']['#default_value'] = $route->time;
        $form['max_count']['#default_value'] = $route->max_count;
        $form['max_index']['#default_value'] = $route->max_index;
        $form['is_global']['#default_value'] = $route->is_global;
        $form['status']['#default_value'] = $route->status;
        $form['email_content']['#default_value'] = $route->email_content;
        $form['route_id']['#value'] = $id;
      }
    }
    $form['#attached']['library'] = array('qy_jd/drupal.route-view');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $arr = explode(',', $form_state->getValue('blackhole'));
    foreach($arr as $ip) {
      if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
         $form_state->setErrorByName('blackhole','黑洞地址格式错误');
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $route_id = $form_state->getValue('route_id');
    $values = array(
      'routename' => $form_state->getValue('routename'),
      'blackhole' => $form_state->getValue('blackhole'),
      'username' => $form_state->getValue('username'),
      'password' => $form_state->getValue('password'),
      'mode_command' => $form_state->getValue('mode_command'),
      'blackhole_command' => trim($form_state->getValue('blackhole_command')),
      'total_bps' => $form_state->getValue('total_bps'),
      'one_bps' => $form_state->getValue('one_bps'),
      'time' => $form_state->getValue('time'),
      'max_count' => $form_state->getValue('max_count'),
      'max_index' => $form_state->getValue('max_index'),
      'is_global' => $form_state->getValue('is_global'),
      'status' => $form_state->getValue('status'),
      'email_content' => $form_state->getValue('email_content')
    );
    if($route_id) {
      $this->db_service->update_route($values, $route_id);
    } else {
      $this->db_service->add_route($values);
    }
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.jd.route'));
  }
}

