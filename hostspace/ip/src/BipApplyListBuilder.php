<?php
/**
 * @file IP段入库申请列表
 * Contains \Drupal\ip\BipApplyListBuilder.
 */

namespace Drupal\ip;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BipApplyListBuilder {
  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
      $container->get('form_builder')
    );
  }

  private function load(){
    $rows = array();
    $ip_service = \Drupal::service('ip.ipservice');
    $member_service = \Drupal::service('member.memberservice');
    $data = $ip_service->getAllApply();
    foreach($data as $value) {
      //IP所属用户
      $client_obj = null;
      $clients_name = '';
      $client = '公共IP段';
      if($client_uid = $value->client) {
        $client_obj = $member_service->queryDataFromDB('client', $client_uid);
        if($client_obj) {
          $clients_name = $client_obj->client_name ? $client_obj->client_name : $puser->label();
        }
      }
      $creator = $member_service->queryDataFromDB('employee', $value->uid);
      $auditor = $member_service->queryDataFromDB('employee', $value->auditor);
      $group_name = '';
      if(!empty($value->group_id)) {
        $groups = $ip_service->loadIpGroup(array('gid' => $value->group_id));
        if(!empty($groups)) {
         $group_name = reset($groups)->name;
        }
      }

      $rows[$value->id] = array(
        'ip' => $value->segment.'.'. $value->begin.'-'.$value->end,
        'room' => $value->rid < 1 ? '公用' : entity_load('room', $value->rid)->label(),
        'group_id' => $group_name,
        'type' => entity_load('taxonomy_term', $value->type)->label(),
        'defense' => entity_load('taxonomy_term', $value->defense)->label(),
        'remark' => $value->remark,
        'client' => $clients_name,
        'uid' => $creator ? $creator->employee_name : '',
        'created' => format_date($value->created, 'custom', 'Y-m-d H:i:s'),
        'auditor' => $auditor ? $auditor->employee_name : '---',
        'audit_date' => $value->audit_date ?  format_date($value->audit_date, 'custom', 'Y-m-d H:i:s') : '---',
        'audit_status' => !$auditor ? '--' : ($value->audit_statue ? '审核通过' : '审核未通过'),
        'advice' => $value->audit_content
      );
      //给每一行绑定操作按钮
      $rows[$value->id]['operations']['data'] = array(
        '#type' => 'operations',
        '#links' => $this->getOp($value->id)
      );
    }
    return $rows;
  }
  /**
   * 得到你列表的操作选项
   */
  private function getOp($id) {
    $op = array();
    if(\Drupal::currentUser()->hasPermission('administer audit apply')) {
      $op['audit'] = array(
        'title' => 'Detail',
        'url' => new Url('ip.bip.apply.audit', array('apply_id' => $id))
      );
    }
    return $op;
  }
  /*
   * 表单渲染
   */
  public function render() {
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array('IP段', '机房', '所属分组', '类型', '防御值', '备注', '专用用户', '申请人', '申请时间', '审核人', '审核时间', '审核结果', '审核意见', '操作'),
      '#rows' => $this->load(),
      '#empty' => t('No data')
    );
    $build['pager']['#type'] = 'pager';
    return $build;
  }
}
