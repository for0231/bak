<?php

/**
 * @file
 * Contains \Drupal\contract\Form\FundsIncomeShortcut.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FundsIncomeShortcut extends FormBase {

/**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'funds_shortcut';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type=null, $property=null) {
    // 判断当前请求是收款还是付款
    $type_id = ($property=='income') ? 1 : 2;     
    $form['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => 'NO Data to show !',
      '#rows' => $this->createRows($type_id)
    );
    $form['list_pager']['#type'] = 'pager';
    return $form;
  }

  /**
   * 资金计划列表表头
   */
  private function buildHeader() {
    $property = \Drupal::request()->get('property');    
    $header['contract'] = array(
       'data' => '所属合同',
       'field' => 'contract',
       'specifier' => 'contratc'
    );
    $header['amount'] = array(
       'data' => ($property=='income') ? '收款金额' : '付款金额',
       'field' => 'amount',
       'specifier' => 'amount'
    );
    $header['method'] = array(
       'data' => ($property=='income') ? '收款方式' : '付款方式',
       'field' => 'method',
       'specifier' => 'method'
    );
    $header['plan_time'] = array(
       'data' => ($property=='income') ? '计划收款时间' : '计划付款时间',
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
       'data' => ($property=='income') ? '实际收款时间' : '实际付款时间',
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
  private function createRows($property) {
    $row = array();
    $type = \Drupal::request()->get('type');    
    $condition = array();
    $begin = 0;
    $end = 0;
    if($type == 'week') {  // 本周收/付款
      $monday = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
      $sunday = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")));
      $begin = strtotime($monday);
      $end = strtotime($sunday);    
    } elseif($type== 'month') { // 本月收/付款
      $first_day = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
      $last_day = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")));
      $begin = strtotime($first_day);
      $end = strtotime($last_day);
    } elseif($type== 'next_month') { // 下月收收/付款
      $first_day = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m")+1,1,date("Y")));
      $last_day = date("Y-m-d H:i:s",mktime(23,59,59,date("m")+1,date("t"),date("Y")));
      $begin = strtotime($first_day);
      $end = strtotime($last_day);
    } elseif($type== 'year') {  // 本年收/付款
      $pay = 0;
      //求得年份
      $year = @date("Y",time());
      //一年有多少天
      $days = ($year % 4 == 0 && $year % 100 != 0 || $year % 400 == 0) ? 366 : 365;
      //今年第一天的时间戳
      $first = strtotime("$year-01-01");
      //今年最后一天的时间戳
      $last = strtotime("+ $days days", $first);
	
      $begin = $first;
      $end = $last;
    }
    $condition['plan_start']= array('field' => 'plan_time', 'op' => '>=', 'value' => $begin);
    $condition['plan_end']= array('field' => 'plan_time', 'op' => '<=', 'value' => $end);

    $data = \Drupal::service('contract.contractservice')->getAllPlanByType($property, $this->buildHeader(), $condition);
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }


}
