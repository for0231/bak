<?php

/**
 * @file
 * Contains \Drupal\contract\Form\ContractShortcut.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContractShortcut extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'contract_shortcut';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type=null) {
    $form['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#empty' => 'NO Data to show !',
      '#rows' => $this->createRows($type)
    );
    $form['list_pager']['#type'] = 'pager';

    return $form;
  }
    /**
   * 资金计划列表表头
   */
  private function buildHeader() {
    $header['code'] = array(
       'data' => '合同编号',
       'field' => 'code',
       'specifier' => 'code'
    );
    $header['name'] = array(
       'data' => '合同名称',
       'field' => 'name',
       'specifier' => 'name'
    );
    $header['amount'] = array(
       'data' => '合同金额',
       'field' => 'amount',
       'specifier' => 'amount'
    );
    $header['client'] = array(
       'data' => '客户名称',
       'field' => 'client',
       'specifier' => 'client'
    );
    $header['project'] = array(
       'data' => '所属项目',
       'field' => 'project',
       'specifier' => 'project'
    );
    
    $header['type'] = array(
       'data' => '合同类别',
       'field' => 'type',
       'specifier' => 'type'
    );
   	$header['creator'] = array(
       'data' => '建立人',
       'field' => 'uid',
       'specifier' => 'uid'
     );
		$header['created'] = array(
       'data' => '建立时间',
       'field' => 'created',
       'specifier' => 'created'
    );
		$header['status'] = array(
       'data' => '合同状态',
       'field' => 'status',
       'specifier' => 'status'
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
    $begin = 0;
    $end = 0;
    if($type == 'week') {  // 本周项目
      $monday = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y")));
      $sunday = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y")));
      $begin = strtotime($monday);
      $end = strtotime($sunday);    
    } elseif($type== 'month') { // 本月项目
      $first_day = date("Y-m-d H:i:s",mktime(0, 0 , 0,date("m"),1,date("Y")));
      $last_day = date("Y-m-d H:i:s",mktime(23,59,59,date("m"),date("t"),date("Y")));
      $begin = strtotime($first_day);
      $end = strtotime($last_day);
    } elseif($type== 'year') {  // 本年项目
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
    
    $container = \Drupal::getContainer();
    // 加载对应的数据
    $storage = $container->get('entity.manager')->getStorage('host_contract');
    $queryFactory = $container->get('entity.query');
    $entity_query = $queryFactory->get('host_contract');
    $entity_query->condition('created', $begin, '>=');
    $entity_query->condition('created', $end, '<=');
   
    $entity_query->pager(20);
    $ids = $entity_query->execute();
    $data = $storage->loadMultiple($ids);    
    foreach($data as $entity) {
      $status = $entity->getproperty('status') ? $entity->getproperty('status') : 1;
      $row[$entity->id()]['code']['data'] = array(
        '#type' => 'link',
        '#title' => $entity->label(),
        '#url' => new Url('entity.host_contract.edit_form', array('host_contract'=>$entity->id())),
      );
      $row[$entity->id()]['name']['data'] = array(
        '#type' => 'link',
        '#title' => $entity->getproperty('name'),
        '#url' => new Url('entity.host_contract.edit_form', array('host_contract'=>$entity->id())),
      ); 
      $row[$entity->id()] += array(
        'name' => $entity->getproperty('name') ,
        'amount' => $entity->getproperty('amount') ,
        'client' => $entity->getPropertyObject('client')->label(),
        'type' => $entity->getPropertyObject('type')->label(),
        'creator' => getEmployeeName($entity->getPropertyObject('uid')->id()),
        'created' => format_date($entity->getproperty('created'), 'custom', 'Y-m-d'),   
        'status' => contractStatus()[$status]
      );
      $row[$entity->id()]['op']['data'] = array(
        '#type' => 'operations',     
        '#links' => $this->getOp($entity)
      );
    }
    
    return $row;
  }

  /**
   * 获取操作
   */
  private function getOp($entity) {
    $operations = array();
    $status = $entity->getProperty('status');
    $current_user = \Drupal::currentUser();
    if($current_user->hasPermission('administer contract execute') && $status == 1) {
      $operations['execute'] = array(
        'title' => '执行合同',
        'weight' => -5,
        'url' => $entity->urlInfo('execute-form'),
      );
    }
    if($current_user->hasPermission('administer contract execute') && in_array($status, array(1, 2))) {
      $operations['stop'] = array(
        'title' => '终止合同',
        'weight' => -4,
        'url' => $entity->urlInfo('stop-form'),
      );
    }
    if($current_user->hasPermission('administer contract execute') && in_array($status, array(1, 2))) {
      $operations['complete'] = array(
        'title' => '结束合同',
        'weight' => -4,
        'url' => $entity->urlInfo('complete-form'),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }


}
