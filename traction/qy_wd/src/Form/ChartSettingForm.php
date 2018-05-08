<?php

/**
 * @file
 * IP流量图表监控设置
 *
 * Contains \Drupal\qy_wd\Form\ChartSettingForm.
 */
namespace Drupal\qy_wd\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ChartSettingForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'qy_chart_setting_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = 0) {
    $config = \Drupal::config('qy_wd.settings');
    $form['chart_save_ips'] = array(
      '#type' => 'textarea',
      '#title' => '保存图表的IP',
      '#cols' => 20,
      '#rows' => 8,
      '#default_value' => $config->get('chart_save_ips'),
      '#description' => '多个IP用英文逗号隔离如(192.168.1.1,192.168.1.2)'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => '保存'
    );
    return $form;
  }

    /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ips = array();
    $str_ips = $form_state->getValue('chart_save_ips');
    if(!empty($str_ips)) {
      $row_arr = explode("\r\n", $str_ips);
      foreach($row_arr as $row_ips) {
        $ip_arr = explode(",", $row_ips);
        foreach($ip_arr as $ip) {
          if(empty($ip)) {
            continue;
          }
          $ip = trim($ip);
          if(strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
            $form_state->setErrorByName('chart_save_ips', '输入的格式有错误('. $ip .')');
          } else {
            $ips[$ip] = $ip;
          }
        }
      }
    }
    $form_state->chart_ips = implode(',', $ips);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('qy_wd.settings');
    $config->set('chart_save_ips', $form_state->chart_ips);
    $config->save();
    drupal_set_message('保存成功');
    $form_state->setRedirectUrl(new Url('admin.wd.chart'));
  }
}
