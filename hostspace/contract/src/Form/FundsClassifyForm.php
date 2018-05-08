<?php

/**
 * @file
 * Contains \Drupal\contract\Form\FundsClassifyForm.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;

class FundsClassifyForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'funds_income';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type=null) {
    $form = $this->buildFilterForm($form, $form_state);
    // 判断当前请求是收款还是付款
    $type_id = ($type=='income') ? 1 : 2;     
    $form['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => 'NO Data to show !',
      '#rows' => $this->createRows($type_id)
    );
    $form['list_pager']['#type'] = 'pager';
    $_SESSION['funds_type'] = $type;
    return $form;
  }
  /**
   * 创建筛选表单
   */
  private function buildFilterForm(array $form, FormStateInterface $form_state) {
    $type = \Drupal::request()->get('type');    
    if(isset($_SESSION['funds_type']) && $_SESSION['funds_type']!=$type) {
      $_SESSION['admin_funds_filter'] = array();
    }

    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter '),
      '#open' => true //!empty($_SESSION['admin_funds_filter']),
    );
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => '资金状态',
      '#options' => array('-1' => '状态筛选') + fundsStatus()
    );		
    $fields = array('status');
    $allempty = true;
    foreach ($fields as $field) {
      if(!empty($_SESSION['admin_funds_filter'][$field]) || (isset($_SESSION['admin_funds_filter']['status']) && $_SESSION['admin_funds_filter']['status'] != -1)) {
        $form['filters'][$field]['#default_value'] = $_SESSION['admin_funds_filter'][$field];
        $allempty = false;
      }
    }
    if($allempty) {
      $_SESSION['admin_funds_filter'] = array();
    }
    $form['filters']['actions'] = array(
      '#type' => 'actions',
      '#attributes' => array('class' => array('container-inline')),
    );
    $form['filters']['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    );
    if (!empty($_SESSION['admin_funds_filter'])) {
      $form['filters']['actions']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }
    return $form;
  }
  /**
   * 资金计划列表表头
   */
  private function buildHeader() {
    $type = \Drupal::request()->get('type');    
    $header['contract'] = array(
       'data' => '所属合同',
       'field' => 'contract',
       'specifier' => 'contratc'
    );
    $header['amount'] = array(
       'data' => ($type=='income') ? '收款金额' : '付款金额',
       'field' => 'amount',
       'specifier' => 'amount'
    );
    $header['method'] = array(
       'data' => ($type=='income') ? '收款方式' : '付款方式',
       'field' => 'method',
       'specifier' => 'method'
    );
    $header['plan_time'] = array(
       'data' => ($type=='income') ? '计划收款时间' : '计划付款时间',
       'field' => 'plan_time',
       'specifier' => 'plan_time'
    );
    $header['is_overtime'] = array(
       'data' => '是否逾期(逾期天数)',
    );
		$header['status'] = array(
       'data' => '状态',
       'field' => 'status',
       'specifier' => 'status'
    );
    $header['success_time'] = array(
       'data' => ($type=='income') ? '实际收款时间' : '实际付款时间',
       'field' => 'success_time',
       'specifier' => 'success_time'
    );

    $header['remark'] = array(
       'data' => '备注',
    );

    $header['op'] = array(
       'data' => '操作',
    );

   return $header;
  }
  /**
   * 构建表格数据
   */
  private function createRows($type) {
    $row = array();
    $condition = array();
    if(!empty($_SESSION['admin_funds_filter'])) {
			if($_SESSION['admin_funds_filter']['status'] != -1){
        $condition['status']= array('field' => 'status', 'op' => '=', 'value' => $_SESSION['admin_funds_filter']['status']);
			}      
    }
    $data = \Drupal::service('contract.contractservice')->getAllPlanByType($type, $this->buildHeader(), $condition);
    foreach($data as $d) {
      $contract = entity_load('host_contract', $d->contract);
      $startdate = $d->plan_time;
      $enddate = REQUEST_TIME;
      $days=round(($enddate-$startdate)/(60*60*60*60));
      $row[$d->id] = array(
        'contract' => $contract->getproperty('name'),
        'amount' => $d->amount,
        'method' => fundsMethod()[$d->type],
        'plan' => format_date($d->plan_time, 'custom', 'Y-m-d'),
        'diff' => $days,
        'status' => fundsStatus()[$d->status],
        'success' => $d->success_time ? format_date($d->success_time, 'custom', 'Y-m-d') : '---',
        'remark' => $d->remark,
      );
      if(!$d->status) {
        $row[$d->id]['operations']['data'] = array(
          '#type' => 'operations',     
          '#links' => $this->getOp($d->id, $contract->id()) 
        );
      } else {
        $row[$d->id] += array('op' => '---');
      }
    }
    return $row;
  }
  /**
   * 获取操作
   */
  private function getOp($id,$host_contract ) {
    $op = array();
    $op['complete'] = array(
      'title' => '完成',
      'url' => new Url('contract.funds.complete', array('host_contract'=> $host_contract, 'funds_plan'=>$id))
    );
    return $op;
  }

  /**
   * 重置筛选条件
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_funds_filter'] = array();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['admin_funds_filter']['status'] = $form_state->getValue('status');
  }


}
