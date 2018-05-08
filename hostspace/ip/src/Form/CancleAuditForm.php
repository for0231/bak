<?php
/**
 * @file  取消IP段申请 审核
 * Contains \Drupal\ip\Form\CancleAuditForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Url;

class  CancleAuditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'bip_cancle_audit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $apply_id=null) {

    $data = \Drupal::service('ip.ipservice')->getCancleApplyById($apply_id);
    $form['apply_id'] = array('#type' => 'value', '#value' => $apply_id);   
    $creator = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$data->uid);
    $auditor = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$data->auditor);

    $form['apply'] = array(
      '#type' => 'details',
      '#title' => '申请详情',
      '#disabled' => TRUE,
      '#open' => TRUE
    );
    $form['apply']['uid'] = array(
      '#type' => 'textfield',
      '#title' => '申请人：',
      '#value' => ($creator ? $creator->employee_name : ''),
      '#size' => 30
    );
    $form['apply']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP段：',
      '#value' =>  $data->segment,
      '#size' => 30
    );   
    $form['apply']['begin'] = array(
      '#type' => 'textfield',
      '#title' => '起始网络号：',
      '#value' => $data->begin,
      '#size' => 30
    );
    $form['apply']['end'] = array(
      '#type' => 'textfield',
      '#title' => '结束网络号：',
      '#value' => $data->end,
      '#size' => 30
    );   
    $form['apply']['remark'] = array(
      '#type' => 'textarea',
      '#title' => '备注：',
      '#value' =>  $data->remark,
    );
    $disable = FALSE;
    if($data->auditor) {
     $disable = TRUE;
    } 
    $form['audit'] =array(
      '#type' => 'fieldset',
      '#title' => '审核信息',
      '#disabled' => $disable,
    );
    $form['audit']['audit_content'] = array(
      '#type' => 'textarea',
      '#default_value' => $data->audit_content,
      '#title' => '审核意见：',
    );
    $form['audit']['agree'] = array(
      '#type' => 'submit',
      '#value' => '同意',       
    );
    $form['audit']['disagree'] = array(
      '#type' => 'submit',
      '#value' => '拒绝',
      '#submit' => array('::disagree'),
    );

    return $form;
  }

  
  /**
   * 拒绝IP下架的申请
   */
  public function disagree(array &$form, FormStateInterface $form_state){
    $advice = $form_state->getValue('audit_content');
    $apply_id = $form_state->getValue('apply_id');

    $ip = $form_state->getValue('ip');
    $begin = $form_state->getValue('begin');
    $end = $form_state->getValue('end');
    $ips = $ip. '.' .$begin. '-'. $end;   
   
    $fields = array(
      'auditor' => \Drupal::currentUser()->id(),
      'audit_date' => REQUEST_TIME,
      'audit_statue' => 0,
      'audit_content' => $advice
    );
    \Drupal::service('ip.ipservice')->auditCancleApply($fields, $apply_id);
    drupal_set_message('你已拒绝业务IP段：'.$ips.'的下架申请。');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $advice = $form_state->getValue('audit_content');
    $apply_id = $form_state->getValue('apply_id');

    $fields = array(
      'auditor' => \Drupal::currentUser()->id(),
      'audit_date' => REQUEST_TIME,
      'audit_statue' => 1,
      'audit_content' => $advice
    );
    \Drupal::service('ip.ipservice')->auditCancleApply($fields, $apply_id);
   
 
    //   --------------  ip数据删除 ---------------
   
    $data = \Drupal::service('ip.ipservice')->getCancleApplyById($apply_id);
    $ip_header = $data->segment;
    $begin = $data->begin;
    $end = $data->end;
    $ip_s = $ip_header . '.' . $begin . '-' . $end;
    
   for($i=$begin;$i<=$end;$i++){
      $ipb=$ip_header.".".$i;
      $ipid = \Drupal::service('ip.ipservice')->getIpidByIp($ipb);
      $ipb_entity = entity_load('ipb', $ipid);
      if(!$ipb_entity) break;
      $ipb_entity->delete();
    }
   drupal_set_message('业务IP：'.$ip_s.'成功下架！'); 
   $form_state->setRedirectUrl(new Url('ip.ipb.admin')); 
  }
}

