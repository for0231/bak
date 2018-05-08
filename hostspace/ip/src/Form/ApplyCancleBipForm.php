<?php
/**
 * @file  IP段下架申请 
 * Contains \Drupal\ip\Form\ApplyCancleBipForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Url;

class ApplyCancleBipForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'apply_cancle_bip';
  }
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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
    $form['remark'] = array(
      '#type' => 'textarea',
      '#title' => '描述'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Apply'),
    ); 
    return $form;
  }

 /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $ip_paragraph = trim($form_state->getValue('ip_paragraph'));
    $ipd_start = trim($form_state->getValue('ipd_start'));
    $ipd_end = trim($form_state->getValue('ipd_end'));
    if($ipd_start>$ipd_end){
      $k=$ipd_start;
      $ipd_startpb=$ipd_end;
      $ipd_end=$ipd_start;
    }
    
    for($i=$ipd_start;$i<=$ipd_end;$i++){
      $ips=$ip_paragraph.".".$i;
      if(strcmp(long2ip(sprintf("%u",ip2long($ips))),$ips)){
        $form_state->setErrorByName('ip',$ips. '不是一个正确的IP。');
      }
    }
  }
 
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ip_paragraph = trim($form_state->getValue('ip_paragraph'));
    $ipd_start = trim($form_state->getValue('ipd_start'));
    $ipd_end = trim($form_state->getValue('ipd_end'));
    $remark = trim($form_state->getValue('remark'));

    $fields = array(
      'segment' => $ip_paragraph,
      'begin' => $ipd_start,
      'end' => $ipd_end,
      'remark' => $remark,
      'uid' => \Drupal::currentUser()->id(),
      'created' => REQUEST_TIME, 
    );
    $rs = \Drupal::service('ip.ipservice')->saveCancleApply($fields);
    if($rs) {
      drupal_set_message('你的申请已经提交成功。');
    } else {
      drupal_set_message('申请提交失败！', 'warning');
    }
  }
}
