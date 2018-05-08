<?php
/**
 * @file
 * Contains \Drupal\worksheet\Controller\SopController.
 */

namespace Drupal\worksheet\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\worksheet\WorkSheetListBuilde;
use Drupal\worksheet\WorkSheetStatisticBuilde;
use Drupal\worksheet\WorkSheetCommentBuilde;
use Drupal\worksheet\WorkSheetHistoryBuilde;
use Drupal\worksheet\WorkSheetAbnormalBuilde;
use Drupal\worksheet\WorkSheetQuestionBuilde;
use Drupal\worksheet\WorkSheetDetailBuilde;
use Drupal\worksheet\WorkSheetAllotBuilde;

use Drupal\worksheet\WorkSheetRackPartListBuilder;

use Drupal\worksheet\ServerPutawayBuilde;
use Drupal\worksheet\ServerSoldoutBuilde;
use Drupal\worksheet\ServerResetBuilde;

use Drupal\worksheet\IporBandBuilde;
use Drupal\worksheet\PortBuilde;
use Drupal\worksheet\ServiceRoomBuilde;

use Drupal\worksheet\AbnormalBuilde;
use Symfony\Component\HttpFoundation\JsonResponse;
class SopController extends ControllerBase {

  protected $cycle_exec = 'work_sheet_cycle_exec';
  protected $formBuilder;
  
  public function __construct() {
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  /**
   * 工单列表页
   */
  public function SopList() {
    $build['mode'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter', 'list-mode','sop-mode'),
      )
    );
    $options = \Drupal::service('worksheet.type')->getAllOptions();
    $build['mode']['type'] = array(
      '#type' => 'select',
      '#title' => '优先级：',
      '#options' => array('' => ' All ') + $options,
      '#attributes' => array(
        'class' => array('type-level')
      )
    );
    $build['mode']['title'] = array(
      '#type' => 'label',
      '#title' => '查询模式：',
      '#title_display' => 'before'
    );
    $modes = array(1 => '全体模式', 2=>'部门模式', 3=>'个人模式', 4=>'精简模式');
    foreach($modes as $key => $mode) {
      $build['mode']['mode' . $key] = array(
        '#type' => 'radio',
        '#title' => $mode,
        '#name'=> 'mode',
        '#attributes' => array(
          'value' => $key
        )
      );
    }
    $build['order'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter', 'list-order'),
      )
    );
    $build['order']['title'] = array(
      '#type' => 'label',
      '#title' => '排序方式：',
      '#title_display' => 'before'
    );
    $orders = array(1=> '状态+时间', 2=> '姓名+时间', 3 => '状态+姓名+时间', 4 => '状态+类型+时间');
    foreach($orders as $key => $order) {
      $build['order']['order' . $key] = array(
        '#type' => 'radio',
        '#title' => $order,
        '#name'=> 'order',
        '#attributes' => array(
          'value' => $key
        )
      );
    }
    $default_mode = 4;
    $default_order = 3;
    $default_type = '';
    if(!empty($_SESSION['sop_list'])) {
      $default_mode = $_SESSION['sop_list']['mode'];
      $default_order = $_SESSION['sop_list']['order'];
      if(isset($_SESSION['sop_list']['type'])) {
        $default_type = $_SESSION['sop_list']['type'];
      }
    }
    $build['mode']['mode' . $default_mode]['#attributes']['checked'] = 'checked';
    $build['order']['order' . $default_order]['#attributes']['checked'] = 'checked';
    $build['mode']['type']['#value'] = $default_type;
    $build['contnet'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-path' => \Drupal::url('admin.worksheet.sop.data')
      ),
      '#attached' => array(
        'library' => array('worksheet/drupal.work-sheet-list', 'worksheet/drupal.worksheet-default')
      )
    );
    return $build;
  }
  /**
   * 工单列表数据
   */
  public function SopListData() {
    $list = new WorkSheetListBuilde();
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 4;
    $order = isset($_GET['order']) ? $_GET['order'] : 3;
    $type = isset($_GET['type']) ? $_GET['type'] : '';
    if(empty($_SESSION['sop_list'])) {
      $_SESSION['sop_list'] = array(
        'mode' => $mode,
        'order' => $order,
        'type' => $type
      );
    } else {
      if($_SESSION['sop_list']['mode'] != $mode) {
        $_SESSION['sop_list']['mode'] = $mode;
      }
      if($_SESSION['sop_list']['order'] != $order) {
        $_SESSION['sop_list']['order'] = $order;
      }
      if($_SESSION['sop_list']['type'] != $type) {
        $_SESSION['sop_list']['type'] = $type;
      }
    }
    $list->setMode($mode);
    $list->setOrder($order);
    if(!empty($type)) {
      $list->setTypes(explode(',', $type));
    }
    $data = $list->build();
    return new Response($data);
  }
  /**
   * 机房事务列表
   */
  public function roomList() {
    $build['mode'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter', 'list-mode'),
      )
    );
    $build['mode']['title'] = array(
      '#type' => 'label',
      '#title' => '查询模式：',
      '#title_display' => 'before'
    );
    $modes = array(1 => '全体模式', 2=>'部门模式', 3=>'个人模式', 4=>'精简模式');
    foreach($modes as $key => $mode) {
      $build['mode']['mode' . $key] = array(
        '#type' => 'radio',
        '#title' => $mode,
        '#name'=> 'mode',
        '#attributes' => array(
          'value' => $key
        )
      );
    }
    $build['order'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter', 'list-order'),
      )
    );
    $build['order']['title'] = array(
      '#type' => 'label',
      '#title' => '排序方式：',
      '#title_display' => 'before'
    );
    $orders = array(1=>'状态+优先级+处理日期+建单时间', 2=>'处理日期+状态+优先级+建单时间');
    foreach($orders as $key => $order) {
      $build['order']['order' . $key] = array(
        '#type' => 'radio',
        '#title' => $order,
        '#name'=> 'order',
        '#attributes' => array(
          'value' => $key
        )
      );
    }
    $default_mode = 4;
    $default_order = 1;
    if(!empty($_SESSION['room_list'])) {
      $default_mode = $_SESSION['room_list']['mode'];
      $default_order = $_SESSION['room_list']['order'];
    }
    $build['mode']['mode' . $default_mode]['#attributes']['checked'] = 'checked';
    $build['order']['order' . $default_order]['#attributes']['checked'] = 'checked';
    $build['contnet'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-path' => \Drupal::url('admin.worksheet.room.data')
      ),
      '#attached' => array(
        'library' => array('worksheet/drupal.work-sheet-list', 'worksheet/drupal.worksheet-default')
      )
    );
    return $build;
  }

  /**
   * 机房数据列表
   */
  public function roomListData() {
    $list = new \Drupal\worksheet\WorkSheetRoomListBuilde();
    $mode = isset($_GET['mode']) ? $_GET['mode'] : 4;
    $order = isset($_GET['order']) ? $_GET['order'] : 1;
    if(empty($_SESSION['room_list'])) {
      $_SESSION['room_list'] = array(
        'mode' => $mode,
        'order' => $order,
      );
    } else {
      if($_SESSION['room_list']['mode'] != $mode) {
        $_SESSION['room_list']['mode'] = $mode;
      }
      if($_SESSION['room_list']['order'] != $order) {
        $_SESSION['room_list']['order'] = $order;
      }
    }
    $list->setMode($mode);
    $list->setOrder($order);
    $data = $list->build();
    return new Response($data);
  }

  /**
   * 物流工单列表
   */
  public function logisticsList() {
    $build['contnet'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-path' => \Drupal::url('admin.worksheet.logistics.data')
      ),
      '#attached' => array(
        'library' => array('worksheet/drupal.work-sheet-list', 'worksheet/drupal.worksheet-default')
      )
    );
    return $build;
  }

  public function logisticsListData() {
    $list = new \Drupal\worksheet\WorkSheetLogisticsListBuilde();
    $data = $list->build();
    return new Response($data);
  }

  /**
   * 导出备注数据
   */
  public function exportRemember() {
    $list = new \Drupal\worksheet\WorkSheetRoomListBuilde();
    $mode = isset($_SESSION['room_list']['mode']) ? $_SESSION['room_list']['mode'] : 4;
    $order = isset($_SESSION['room_list']['order']) ? $_SESSION['room_list']['order'] : 1;
    $list->setMode($mode);
    $list->setOrder($order);
    $list->export();
    exit;
  }
  /**
   * 工单统计页
   */
  public function SopStatistic() {
    $beginTime = 0;
    $endTime = 0;
    if(!empty($_SESSION['worksheet_statistic']['begin'])) {
      $beginTime = strtotime($_SESSION['worksheet_statistic']['begin']);
    }
    if(!empty($_SESSION['worksheet_statistic']['end'])) {
      $endTime = strtotime($_SESSION['worksheet_statistic']['end']);
    }
    $service = \Drupal::service('worksheet.statistic');
    $service->timeLimit($beginTime, $endTime);
    if(!empty($_SESSION['worksheet_statistic']['user']) && $_SESSION['worksheet_statistic']['user'] != 'all'){
      $service->setUser($_SESSION['worksheet_statistic']['user']);
    }
    $statistic = new WorkSheetStatisticBuilde(); 
    return $statistic->build();
  }

  /**
   *  历史工单
   */
  public function history(){
    $history = new WorkSheetHistoryBuilde();
    return $history->build();
  }

  /**
   * 历史数据导出
   */
  public function historyExport() {
    set_time_limit(0);
    $history = new WorkSheetHistoryBuilde();
    $history->export();
    exit;
  }

  /**
   * 异常工单列表
   */
  public function abnormal() {
    $history = new WorkSheetAbnormalBuilde();
    return $history->build();
  }
  
  /**
   * 异常工单数据导出
   */
  public function abnormalExport() {
    $history = new WorkSheetAbnormalBuilde();
    $history->export();
    exit;
  }
  
  /**
   * 工单详细
   */
  public function detail($entity_type, $wid) {
    $detail = new WorkSheetDetailBuilde($entity_type, $wid);
    return $detail->build();
  }
  
  /**
   * 删除工单
   */
  public function sopDelete($entity_type, $wid) {
    $entity = entity_load($entity_type, $wid);
    $roles = \Drupal::currentUser()->getRoles();
    if(in_array('worksheet_manage',$roles)) {
      $entity->delete();
    } else {
      $current_uid = \Drupal::currentUser()->id(); 
      $create_uid = $entity->get('uid')->target_id;
      $status = $entity->get('status')->value;
      $last_uid = $entity->get('last_uid')->target_id;
      if($current_uid == $create_uid && ($status==1 || $status==15) && empty($last_uid)) {
        $entity->delete();
      } else {
        drupal_set_message('无权删除');
      }
    }
    if(isset($_GET['source']) && $_GET['source'] == '/admin/worksheet/room/list') {
      return $this->redirect('admin.worksheet.room.list');
    }
    return $this->redirect('admin.worksheet.sop');
  }
  
  public function voiceRemind($entity_type, $wid) {
    $entity = entity_load($entity_type, $wid);
    $status = $entity->get('status')->value;
    if($status == 1) {
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个新工单', $uids);
    } else if ($status == 10) {
      $uids = \Drupal::service('worksheet.dbservice')->getRoleUser('worksheet_operation');
      $voice = \Drupal::service("voice.voice_server");
      $voice->openVioce('当前系统有一个业务修正工单', $uids);
    }
    drupal_set_message('已成功发起提醒。');
    if(isset($_GET['source']) && $_GET['source'] == '/admin/worksheet/room/list') {
      return $this->redirect('admin.worksheet.room.list');
    }
    return $this->redirect('admin.worksheet.sop');
  }

  /**
   * 取消异常
   */
  public function cancelAbnormal(EntityInterface $work_sheet_handle) {
    $operation_id = $work_sheet_handle->get('operation_id')->value;
    switch($operation_id) {
      case 20:
      //异常审核
      $work_sheet_handle->set('is_abnormal', 0);
      $work_sheet_handle->set('person_liable', 0);
      $work_sheet_handle->set('reason', '');
      $work_sheet_handle->save();
      $wid = $work_sheet_handle->get('wid')->value;
      $entity_name = $work_sheet_handle->get('entity_name')->value;
      $created = $work_sheet_handle->get('time')->value;
      \Drupal::service('worksheet.statistic')->deleteAbnormal($wid, $entity_name, $created, 4);
      break;
      case 21:
      //异常接受
      $work_sheet_handle->set('is_abnormal', 0);
      $work_sheet_handle->set('person_liable', 0);
      $work_sheet_handle->set('reason', '');
      $work_sheet_handle->set('operation_id', 2);
      $work_sheet_handle->set('operation', operationOptions()[2]);
      $work_sheet_handle->save();
      $wid = $work_sheet_handle->get('wid')->value;
      $entity_name = $work_sheet_handle->get('entity_name')->value;
      $created = $work_sheet_handle->get('time')->value;
      \Drupal::service('worksheet.statistic')->deleteAbnormal($wid, $entity_name, $created, 5);
      break;
      case 22:
      //质量异常
      $work_sheet_handle->set('is_abnormal', 0);
      $work_sheet_handle->set('person_liable', 0);
      $work_sheet_handle->set('reason', '');
      $work_sheet_handle->save();
      $wid = $work_sheet_handle->get('wid')->value;
      $entity_name = $work_sheet_handle->get('entity_name')->value;
      $created = $work_sheet_handle->get('time')->value;
      \Drupal::service('worksheet.statistic')->deleteAbnormal($wid, $entity_name, $created, 6);
      break;
      case 25:
      //运维交付(超时)
      $work_sheet_handle->set('is_abnormal', 0);
      $work_sheet_handle->set('operation_id', 6);
      $work_sheet_handle->set('operation', operationOptions()[6]);
      $work_sheet_handle->save();
      $wid = $work_sheet_handle->get('wid')->value;
      $entity_name = $work_sheet_handle->get('entity_name')->value;
      $created = $work_sheet_handle->get('time')->value;
      \Drupal::service('worksheet.statistic')->deleteAbnormal($wid, $entity_name, $created, 7);
      break;
      case 26:
      //交业务(超时)
      $work_sheet_handle->set('is_abnormal', 0);
      $work_sheet_handle->set('operation_id', 5);
      $work_sheet_handle->set('operation', operationOptions()[5]);
      $work_sheet_handle->save();
      $wid = $work_sheet_handle->get('wid')->value;
      $entity_name = $work_sheet_handle->get('entity_name')->value;
      $created = $work_sheet_handle->get('time')->value;
      \Drupal::service('worksheet.statistic')->deleteAbnormal($wid, $entity_name, $created, 7);
      break;
      case 27:
      //接受工单(超时)
      $work_sheet_handle->set('is_abnormal', 0);
      $work_sheet_handle->set('operation_id', 2);
      $work_sheet_handle->set('operation', operationOptions()[2]);
      $work_sheet_handle->save();
      break;
      default:
      return new Response('无异常');
      break;
    }
    $wid = $work_sheet_handle->get('wid')->value;
    $entity_name = $work_sheet_handle->get('entity_name')->value;
    $entities = entity_load_multiple_by_properties('work_sheet_handle', array('wid' => $wid, 'entity_name' => $entity_name, 'is_abnormal' => 1));
    if(empty($entities)) {
      $entity = entity_load($entity_name, $wid);
      $entity->set('abnormal_exist', 0);
      $entity->save();
    }
    return $this->redirect('admin.worksheet.sop.detail', array('entity_type' => $entity_name, 'wid' => $wid));
  }

  /**
   * 保存机房时间数据
   */
  public function saveRoomTime(Request $request) {
    $year = $request->query->get('year');
    $month = $request->query->get('month');
    $day = $request->query->get('day');
    $work = $request->query->get('work');
    $settings = array(
      array(
        'years' => $year,
        'month' => $month,
        'day' => $day,
        'work' => $work
      )
    );
    $flag = \Drupal::service("worksheet.date")->setWorkDate($settings);
    if($flag) {
      return new Response('OK');
    }
    return new Response('NO');
  }
  /**
   * 工单中选项列表
   */
  public function optionList() {
    $option = new \Drupal\worksheet\WorkSheetOptionsBuilde();
    return $option->build();
  }

  /**
   * 工单查询
   */
  public function sopFilter() {
    $filter = new \Drupal\worksheet\WorkSheetFilterBuilde();
    return $filter->build();
  }

  /**
   * 定时执行任务
   */
  public function cycleExec(){
    if(@$_POST['access'] != 'xunyun'){
      echo 'access faild';
      return new Response();
    }
    $state = \Drupal::state();
    $data = $state->get($this->cycle_exec);
    foreach($data as $k=>$item){
      if(!$item['switch']){
        continue;
      }
      $year = date('Y',time());
      $month = date('m',time());
      $day = date('d',time());
      $strtime = $year.'-'.$month.'-'.$day.' '.$item['hours'].':'.$item['min'].':'.'0';
      $time = strtotime($strtime);
      if((($time - time()) >= 0 ) && (($time - time()) < 60) ){
        $entity = entity_create('work_sheet_cycle', array(
          'code' => date('YmdHis').rand(100000,999999),
          'ip' => $item['name'],
          'phenomenon' => $item['content'],
          'unique_cycle_key' => $item['key'],
          'tid' => $item['type'],
          'client' => $item['client'],
          'exception' => 1
        ));
        $entity->handle_record_info = array(
          'uid' => 1,
          'time' => REQUEST_TIME,
          'operation_id' => 1,
          'is_abnormal' => 0
        );
        $entity->statistic_record = array(
          array('uid' => 1,'event' => 1 )
        );
        $entity->save();
      }
    }
    return new Response(' ');
  }

  public function cycleDelete($work_sheet_cycle_key){
    $state = \Drupal::state(); 
    $data = $state->get($this->cycle_exec);
    unset($data[$work_sheet_cycle_key]);
    $state->set($this->cycle_exec,$data);
    return $this->redirect('admin.worksheet.cycle.add');
  }

  //值班人员设置
  public function dutyPerson($uid, $op) {
    if($op == 'delete') {
      $config = \Drupal::configFactory()->getEditable('worksheet.settings');
      $person_on_duty = $config->get('person_on_duty');
      foreach($person_on_duty as $key => $duty_uid) {
        if($duty_uid == $uid) {
          unset($person_on_duty[$key]);
          $config->set('person_on_duty', $person_on_duty);
          $config->save();
          break;
        }
      }
    }
    else if ($op == 'add') {
      $config = \Drupal::configFactory()->getEditable('worksheet.settings');
      $person_on_duty = $config->get('person_on_duty');
      $person_on_duty[] = $uid;
      $return = array_unique($person_on_duty);
      $config->set('person_on_duty', $return);
      $config->save();
    }
    if(isset($_GET['back'])) {
      return $this->redirect('admin.worksheet.task.assigner');
    }
    return new Response('ok');
  }
  //工单分配时间段系数
  public function alloDelete($time){
    $config = \Drupal::configFactory()->getEditable('worksheet.settings');
    $allocate_time = $config->get('allocate_time');
    foreach($allocate_time as $key=>$value) {
      if($time == $value[0]) {
        unset($allocate_time[$key]);
        $config->set('allocate_time', $allocate_time);
        $config->save();
      }
    }
    if(isset($_GET['back'])) {
      return $this->redirect('admin.worksheet.task.assigner');
    }
    return new Response('ok');
  }
  /**
   * 关联工单
   */
  public function relationSop($code) {
    $base = \Drupal::service('worksheet.dbservice')->getBaseByCode($code);
    if(empty($base)) {
      return new Response('工单编号没有找到对应的工单');
    }
    $types = array(
      'work_sheet_frame' => 'frame',
      'work_sheet_ip' => 'ip',
      'work_sheet_switch' => 'switch',
      'work_sheet_room' => 'room',
      'work_sheet_fault' => 'fault',
      'work_sheet_cycle' => 'cycle',
      'work_sheet_logistics' => 'logistics'
    );
    $entity_name = $base->entity_name;
    if(isset($types[$entity_name])) {
      $type = $types[$entity_name];
      $route = 'admin.worksheet.sop.'. $type .'.operation';
      return $this->redirect($route, array($entity_name => $base->wid));
    }
    return new Response('工单编号没有找到对应的工单');
  }
  public function CommentStatistic(){
    $commentStatistic = new WorkSheetCommentBuilde();
    return $commentStatistic->build();
  }
  public function QuestionStatistic(){
    $questionStatistic = new WorkSheetQuestionBuilde();
    return $questionStatistic->build();
  }
  public function AllotStatistic(){
    $allotStatistic = new WorkSheetAllotBuilde();
    return $allotStatistic->build();
  }

  public function filter() {
    $condition = array();
    if(isset($_SESSION['rack_part'])) {
      if(!empty($_SESSION['rack_part']['manage_ip'])) {
        $condition['manage_ip'] = $_SESSION['rack_part']['manage_ip'];
      }
      if(!empty($_SESSION['rack_part']['rack'])) {
        $condition['rack'] = $_SESSION['rack_part']['rack'];
      }
      if(!empty($_SESSION['rack_part']['ye_vlan'])) {
        $condition['ye_vlan'] = $_SESSION['rack_part']['ye_vlan'];
      }
      if(!empty($_SESSION['rack_part']['networkcard_vlan'])) {
        $condition['networkcard_vlan'] = $_SESSION['rack_part']['networkcard_vlan'];
      }
    }
    return $condition;
  }
  public function getXunyunUrl(){
    $build['xunyun'] = array(
     '#theme' => 'xunyun_url',
     '#hostclient' => null,
     '#attached' => array(
       'library' => array('worksheet/drupal.xunyun-url'),
     ),
    );
    return $build;
  }
  //服务器上架统计
  public function ServerPutawayStatistic(){
    $putawayStatistic = new ServerPutawayBuilde();
    return $putawayStatistic->build();
  }
  //服务器重装统计
  public function ServerResetStatistic(){
    $resetStatistic = new ServerResetBuilde();
    return $resetStatistic->build();
  }
  //服务器下架统计
  public function ServerSoldoutStatistic(){
    $soldoutStatistic = new ServerSoldoutBuilde();
    return $soldoutStatistic->build();
  }
  //Ip or 带宽统计
  public function IporBandwidthStatistic(){
    $iporBandStatistic = new IporBandBuilde();
    return $iporBandStatistic->build();
  }
  //UP&DOWN端口统计
  public function PortStatistic(){
    $portStatistic = new PortBuilde();
    return $portStatistic->build();
  }
  //机房事务统计
  public function ServiceRoomStatistic(){
    $serviceRoomStatistic = new ServiceRoomBuilde();
    return $serviceRoomStatistic->build();
  }
  //AbnormalStatistic
  public function AbnormalStatistic(){
    $abnormalStatistic = new AbnormalBuilde();
    return $abnormalStatistic->build();
  }
  public function NumberCurveStatistic(){
    $build['filters'] = array(
      '#type' => 'container',
      '#title' => '查询条件',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $build['filters']['begin'] = array(
      '#type' => 'textfield',
      '#title' => '开始时间',
      '#size' => 20,
      '#id' => 'edit-btime',
    );
    $build['filters']['end'] = array(
      '#type' => 'textfield',
      '#title' => '结束时间',
      '#size' => 20,
      '#id' => 'edit-etime',
    );
    $build['filters']['interval'] = array(
      '#type' => 'select',
      '#title' => '时间间隔',
      '#options' => array(
        '5'=>'5m',
        '30'=>'30m',
        '60'=>'1h',
        '120'=>'2h',
        '300'=>'5h',
        '600'=>'10h',
        '1440'=>'24h'
      ),
      '#id' => 'interval',
    );
    $build['filters']['submit'] = array(
      '#type' => 'submit',
      '#value' => '查询',
      '#id' => 'search'
    );
    $build['contnet'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'chartcontainer',
        'style' => 'height: 550px; min-width: 500px'
      ),
      '#attached' => array(
        'library' => array('worksheet/drupal.work-sheet-date','worksheet/drupal.worksheet-status')
      )
    );
    return $build;
  }
  public function NumberCurveData(Request $request){
    set_time_limit(0);
    $begin = $request->query->get('begin');
    $end = $request->query->get('end');
    $interval = $request->query->get('interval');
    $begin = strtotime($begin);
    $end = strtotime($end);
    if(!$begin || !$end){
      return new JsonResponse('time');
    }
    //通过选择的时间段和时间间隔返回 构建横坐标和纵坐标的数据
    $service = \Drupal::service('worksheet.dbservice');
    $datalist = $service->select_sop_status($begin,$end);
    //查询未完成数据
    //5|30|60|120|300|600|1440
    //时间段的分钟数
    $second_interval=$interval*60;
    $time_quantum = ($end-$begin)/60;
    if($time_quantum < $interval){
      return new JsonResponse('false');
    }else{
      for($i=$begin;$i<=$end;$i+=$second_interval){
        $list[]=$i;
      }
      $end_num =  end($list);
      if($end_num != $end){
        array_push($list,$end);
      }
    }
    $arraylist= array();
    foreach($datalist as $item){
      $arraylist[] = array(
        'group_wid'=>$item->group_wid,
        'btime'=>$item->btime,
        'etime'=>$item->etime,
        'bstatus'=>$item->bstatus,
        'estatus'=>$item->estatus
      );
    }
    $index_list = array();
    $sop_index = array();
    $sop_finish_index=array();
    foreach($arraylist as $k1=>$value1){
      foreach($list as $k2=>$value2){
        $btime = $value1['btime'];
        $etime = $value1['etime'];
        $interval_start = $list[0];
        if($btime >= $interval_start && $btime<= $list[$k2+1] && $etime>$list[$k2+1]){
          $sop_index[]= $value2;
        }
        if($etime>=$list[$k2] && $etime<=$list[$k2+1]){
          $sop_finish_index[] = $value2;
        }
      }
    }
    $list22 = array_count_values($sop_index);
    $list2 = array_diff ($list,$sop_index);
    $list2 = array_flip ($list2);
    foreach($list2 as $key=>$value){
      $list3[$key] = 0;
    }
    $third = $list22+$list3;
    ksort($third);
    $return_list1 = array();
    foreach($third as $key=>$value){
      $return_list1[] = array($key,$value);
    }
    $finish_list22 = array_count_values($sop_finish_index);
    $finish_list2 = array_diff ($list,$sop_finish_index);
    $finish_list2 = array_flip ($finish_list2);
    foreach($finish_list2 as $key=>$value){
      $finish_list3[$key] = 0;
    }
    $finish_third = $finish_list22+$finish_list3;
    ksort($finish_third);
    $return_list2 = array();
    foreach($finish_third as $key=>$value){
      $return_list2[] = array($key,$value);
    }
    $return_list = array();
    $return_list[0]= $return_list1;
    $return_list[1]= $return_list2;
    return new JsonResponse($return_list);
  }
}
