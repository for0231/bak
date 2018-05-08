<?php
/**
 * @file  IP段入库申请 审核
 * Contains \Drupal\ip\Form\ApplyAuditForm.
 */

namespace Drupal\ip\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\hostlog\HostLogFactory;

class  ApplyAuditForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'bip_apply_audit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $apply_id=null) {
    $ip_service = \Drupal::service('ip.ipservice');
    $member_service = \Drupal::service('member.memberservice');
    $data = $ip_service->getApplyById($apply_id);
    $form['apply_id'] = array('#type' => 'value', '#value' => $apply_id);
    //IP所属用户
    $client_obj = null;
    $clients_name = '';
    $client = '公共IP段';
    if($client_uid = $data->client) {
      $client_obj = $member_service->queryDataFromDB('client', $client_uid);
      if($client_obj) {
        $clients_name = $client_obj->client_name ? $client_obj->client_name : $puser->label();
        $company = $client_obj->corporate_name ? $client_obj->corporate_name : $clients_name;
      }
    }
    $creator = $member_service->queryDataFromDB('employee', $data->uid);
    $auditor = $member_service->queryDataFromDB('employee', $data->auditor);

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

    $form['begin'] = array(
      '#type' => 'hidden',
      '#value' => $data->begin,
    );
    $form['end'] = array(
      '#type' => 'hidden',
      '#value' => $data->end,
    );
    $form['sgement'] = array(
      '#type' => 'hidden',
      '#value' => $data->segment,
    );
    $form['apply']['room'] = array(
      '#type' => 'textfield',
      '#title' => '所属机房',
      '#value' => !empty($data->rid) ? entity_load('room', $data->rid)->label() : '所有',
      '#size' => 30,
    );
    $group_name = '';
    if(!empty($data->group_id)) {
      $groups = $ip_service->loadIpGroup(array('gid' => $data->group_id));
      if(!empty($groups)) {
        $group_name = reset($groups)->name;
      }
    }
    $form['apply']['group_id'] = array(
      '#type' => 'textfield',
      '#title' => '所属分组',
      '#size' => 30,
      '#default_value' => $group_name
    );
    $form['apply']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP段：',
      '#value' =>  $data->segment.'.'. $data->begin.'-'.$data->end,
      '#size' => 30
    );
    $form['apply']['type'] = array(
      '#type' => 'textfield',
      '#title' => '类型：',
      '#value' => entity_load('taxonomy_term', $data->type)->label(),
      '#size' => 30
    );
    $form['apply']['defense'] = array(
      '#type' => 'textfield',
      '#title' => '防御值：',
      '#value' =>  entity_load('taxonomy_term', $data->defense)->label(),
      '#size' => 30
    );
    $form['apply']['client'] = array(
      '#type' => 'textfield',
      '#title' => '专用用户：',
      '#value' =>  $clients_name,
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
      '#value' => '同意&启用',
      '#description' => '',
    );
    $form['audit']['disagree'] = array(
      '#type' => 'submit',
      '#value' => '拒绝',
      '#submit' => array('::disagree'),
    );
    return $form;
  }


  /**
   * 拒绝IP入库的申请
   */
  public function disagree(array &$form, FormStateInterface $form_state){
    $advice = $form_state->getValue('audit_content');
    $apply_id = $form_state->getValue('apply_id');
    $fields = array(
      'auditor' => \Drupal::currentUser()->id(),
      'audit_date' => REQUEST_TIME,
      'audit_statue' => 0,
      'audit_content' => $advice
    );
    \Drupal::service('ip.ipservice')->auditApply($fields, $apply_id);
    $data = \Drupal::service('ip.ipservice')->getApplyById($apply_id);
    $entity = entity_create('ipb', array('id' => 0));
    $entity->other_data = array('data_name' => 'bip_apply', 'data'=> (array)$data, 'data_id' => 0);
    HostLogFactory::OperationLog('ip')->log($entity, 'audit');
    drupal_set_message('你已拒绝业务IP：'.$form_state->getValue('ip').'的入库申请。');
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
    \Drupal::service('ip.ipservice')->auditApply($fields, $apply_id);
    //   --------------  ip数据写入 ---------------

    $data = \Drupal::service('ip.ipservice')->getApplyById($apply_id);
    // 得到IP段
    $ip_header = $form_state->getValue('sgement');
    $begin =  $form_state->getValue('begin');
    $end =  $form_state->getValue('end');
    for($i=$begin; $i<=$end; $i++) {
      $ip = $ip_header.".".$i;
      $ipbs = entity_load_multiple_by_properties('ipb', array('ip' => $ip));
      if(!empty($ipbs)) {
        continue;
      }
      $entity = entity_create('ipb',array(
        'ip' => $ip,
        'type' => $data->defense,
        'classify' => $data->type,
        'puid' => $data->client,
        'status' => 1,
        'description' => $data->remark,
        'ip_segment' =>  $data->segment.'.0/24',
        'rid' => $data->rid,
        'group_id' => $data->group_id
      ));
      $entity->save();
      HostLogFactory::OperationLog('ip')->log($entity, 'insert');
    }
    drupal_set_message('业务IP：'.$data->segment.'.'.$begin.'-'.$end.'成功入库');
    $form_state->setRedirectUrl(new Url('ip.ipb.admin'));
  }
}

