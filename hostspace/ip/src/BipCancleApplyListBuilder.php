<?php
/**
 * @file
 * Contains \Drupal\ip\BipCancleApplyListBuilder.
 */

namespace Drupal\ip;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class BipCancleApplyListBuilder {
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
    $data = \Drupal::service('ip.ipservice')->getAllCancleApply();
    $i = 0;
    foreach($data as $value) {
      $i++;
      $creator = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$value->uid);
      $auditor = \Drupal::service('member.memberservice')->queryDataFromDB('employee',$value->auditor);
      $rows[$value->id] = array(
        'id' => $i,
        'ip' => $value->segment,
        's_net' => $value->begin,
        'e_net' => $value->end,
        'uid' => $creator ? $creator->employee_name : '',
        'remark' => $value->remark,
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
    return $rows?$rows:array();
  }
  private function getOp($id) {
    $op = array();
    if(\Drupal::currentUser()->hasPermission('administer audit apply')) {
      $op['audit'] = array(
        'title' => 'Detail',
        'url' => new Url('ip.bip.cancle.audit', array('apply_id' => $id))
      );
    }
    return $op;
  }
  public function render() {
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array('ID', 'IP段', '起始网络号', '结束网络号',  '申请人', '备注', '申请时间', '审核人', '审核时间', '审核结果', '审核意见', '操作'),
      '#rows' => $this->load()
    );
    $build['pager']['#type'] = 'pager';
    return $build;
  }
}
