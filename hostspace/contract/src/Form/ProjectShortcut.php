<?php

/**
 * @file
 * Contains \Drupal\contract\Form\ProjectShortcut.
 */

namespace Drupal\contract\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ProjectShortcut extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'project_shortcut';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $type=null) {
  //  $form = $this->buildFilterForm($form, $form_state);
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
       'data' => '项目编号',
       'field' => 'code',
       'specifier' => 'code'
    );
    $header['name'] = array(
       'data' => '项目名称',
       'field' => 'name',
       'specifier' => 'name'
    );

    $header['client'] = array(
       'data' => '项目客户',
       'field' => 'client',
       'specifier' => 'client'
    );
    $header['type'] = array(
       'data' => '项目类型',
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
       'data' => '项目状态',
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
    $storage = $container->get('entity.manager')->getStorage('host_project');
    $queryFactory = $container->get('entity.query');
    $entity_query = $queryFactory->get('host_project');
    $entity_query->condition('created', $begin, '>=');
    $entity_query->condition('created', $end, '<=');
   
    $entity_query->pager(20);
    $ids = $entity_query->execute();
    $data = $storage->loadMultiple($ids);
    
    foreach($data as $entity) {
      $status = $entity->getProjectproperty('status') ? $entity->getProjectproperty('status') : 1;
      $row[$entity->id()] = array(
        'code' => $entity->label(),
        'name' => $entity->getProjectproperty('name') ,
        'client' => $entity->get('client')->entity->label(),
        'type' => ip_server_type()[$entity->getProjectproperty('type')],
        'creator' => getEmployeeName($entity->get('uid')->entity->id()),
        'created' => format_date($entity->getProjectproperty('created'), 'curstom', 'Y-m-d'),   
        'status' => projectStatus()[$status]
      );
      $row[$entity->id()]['op']['data'] = array(
        '#type' => 'operations',     
        '#links' => $this->getOp($entity->id())
      );
    }
    
    return $row;
  }

  /**
   * 获取操作
   */
  private function getOp($id) {
    $op = array();
    $op['edit'] = array(
      'title' => '编辑',
      'url' => new Url('entity.host_project.edit_form', array('host_project'=> $id))
    );
    $op['delete'] = array(
      'title' => '删除',
      'url' => new Url('entity.host_project.delete_form', array('host_project'=> $id))
    );

    return $op;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }


}
