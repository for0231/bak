<?php
/**
 * @file
 *  工单数据查询服务类
 */
 
namespace Drupal\worksheet;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class WorkSheetDbService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connectionf
   * @var Drupal\Core\Entity\Query\QueryFactory
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * 得到指定角色的用户ID
   */
  public function getRoleUser($role) {
    $datas = $this->database->select('user__roles', 'r')
      ->fields('r', array('entity_id'))
      ->condition('roles_target_id', $role)
      ->execute()
      ->fetchAll();
      
    $uids = array();
    foreach($datas as $data) {
      $uids[$data->entity_id] = $data->entity_id;
    } 
    return $uids;
  }

  /**
   * 获取已完成工单列表数据
   */
  public function loadHistory(array $contions) {
    $query = $this->makeCondition($contions);
    $query->condition('t.status', 45);
    $query->limit(20);
    return $query->execute()->fetchCol();
  }

  /**
   * 获取异常工单列表数据
   */
  public function loadAbnormal(array $contions) {
    $query = $this->makeCondition($contions);
    $query->condition('t.abnormal_exist',1);
    $query->condition('t.status', 45);
    $query->limit(20);
    return $query->execute()->fetchCol();
  }
  /**
   * 工单查询
   */
  public function loadFilter(array $contions) {
    $query = $this->makeCondition($contions);
    $query->orderby('t.status');
    $query->limit(20);
    return $query->execute()->fetchCol();
  }
  /**
   * 完成工单查询条件
   */
  private function makeCondition(array $contions) {
    $query = $this->database->select('work_sheet_base','t')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('t', array('id'));
    if(!empty($contions)) {
      $type = isset($contions['type']) ? $contions['type'] : 'all';
      if(isset($contions['keyword'])) {
        $keyword = $contions['keyword'];
        if($type=='all') {
          $query->leftJoin('work_sheet_frame', 't1', 't.uuid = t1.uuid');
          $query->leftJoin('work_sheet_ip', 't2', 't.uuid = t2.uuid');
          $query->leftJoin('work_sheet_switch', 't3', 't.uuid = t3.uuid');
          $query->leftJoin('work_sheet_room', 't4', 't.uuid = t4.uuid');
          $query->leftJoin('work_sheet_fault', 't5', 't.uuid = t5.uuid');
          $query->leftJoin('work_sheet_cycle', 't6', 't.uuid = t6.uuid');
          $query->leftJoin('work_sheet_logistics', 't7', 't.uuid = t7.uuid');
          $query->condition($query->orConditionGroup()
            ->condition('t.ip', '%' . $keyword . '%', 'LIKE')
            ->condition('t.client', $keyword . '%', 'LIKE')
            ->condition('t.code', $keyword . '%', 'LIKE')
            ->condition('t1.business_ip', '%' . $keyword . '%', 'LIKE')
            ->condition('t2.handle_info', '%'. $keyword .'%', 'LIKE')
            ->condition('t3.handle_info', '%'. $keyword .'%', 'LIKE')
            ->condition('t4.handle_info', '%'. $keyword .'%', 'LIKE')
            ->condition('t5.phenomenon', '%'. $keyword .'%', 'LIKE')
            ->condition('t5.handle_info', '%'. $keyword .'%', 'LIKE')
            ->condition('t5.reason', '%'. $keyword .'%', 'LIKE')
            ->condition('t6.reason', '%'. $keyword .'%', 'LIKE')
            ->condition('t7.order_code', $keyword .'%', 'LIKE')
            ->condition('t7.logistics_company',  $keyword .'%', 'LIKE')
          );
        } else {
          $query->innerJoin($type, 't1', 't.uuid = t1.uuid');
          $query->condition('t.entity_name', $type);
          if($type== 'work_sheet_fault') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.phenomenon', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.handle_info', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.reason', '%'. $keyword .'%', 'LIKE')
            );
          } else if ($type=='work_sheet_frame') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.business_ip', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.requirement', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.handle_info', '%'. $keyword .'%', 'LIKE')
            );
          } else if ($type=='work_sheet_ip') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.requirement', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.handle_info', '%'. $keyword .'%', 'LIKE')
            );
          } else if ($type=='work_sheet_room') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.requirement', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.handle_info', '%'. $keyword .'%', 'LIKE')
              ->condition('t1.next_step', '%'. $keyword .'%', 'LIKE')
            );
          } else if ($type == 'work_sheet_switch') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.handle_info', '%'. $keyword .'%', 'LIKE')
            );
          } else if ($type == 'work_sheet_cycle') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.reason', '%'. $keyword .'%', 'LIKE')
            );
          } else if ($type == 'work_sheet_logistics') {
            $query->condition($query->orConditionGroup()
              ->condition('t.ip', $keyword . '%', 'LIKE')
              ->condition('t.client', $keyword . '%', 'LIKE')
              ->condition('t.code', $keyword . '%', 'LIKE')
              ->condition('t1.order_code', $keyword .'%', 'LIKE')
              ->condition('t1.logistics_company',  $keyword .'%', 'LIKE')
            );
          }
        }
      } else {
        if($type != 'all'){
          $query->condition('t.entity_name', $type);
        }
      }
      if(isset($contions['creater']) && $contions['creater'] != 'all') {
        $query->condition('t.uid', $contions['creater']);
      }
      if(isset($contions['hander']) && $contions['hander'] != 'all') {
        $query->condition($query->orConditionGroup()
          //完成状态工单，类型为I类,按照交接人
          ->condition($query->andConditionGroup()
            ->condition('t.last_uid', $contions['hander'])
            ->condition('t.status', 45)
            ->condition('t.tid',array(200,210,220,230,240,250,260,300,410),'NOT IN')
          )
          //完成状态工单，类型为P+E类,按照处理人
          ->condition($query->andConditionGroup()
            ->condition('t.handle_uid', $contions['hander'])
            ->condition('t.status', 45)
            ->condition('t.tid',array(200,210,220,230,240,250,260,300,410),'IN')
          )
          ->condition($query->andConditionGroup()
            ->condition('t.handle_uid', $contions['hander'])
            ->condition('t.status', 45, '<')
          )
        );
      }
      if(isset($contions['begin'])) {
        $query->condition('t.created', $contions['begin'], '>=');
      }
      if(isset($contions['end'])) {
        $query->condition('t.created', $contions['end'], '<=');
      }
      if(isset($contions['comment'])){
        $comment = $contions['comment'];
        if($comment==2){
          $comment=0;
        }
        if($type=='work_sheet_fault') {
          if(!isset($contions['keyword'])) {
            $query->innerJoin('work_sheet_fault', 't1', 't.uuid = t1.uuid');
          }
          $query->condition('t1.isno_comment',$comment,'=');
        }elseif($type =='work_sheet_major_fault'){
          if(!isset($contions['keyword'])) {
            $query->innerJoin('work_sheet_major_fault', 't1', 't.uuid = t1.uuid');
          }
          $query->condition('t1.isno_comment',$comment,'=');
        }
      }
    }
    return $query;
  }

  /**
   * 导出数据
   */
  public function historyExportData(array $contions) {
    $type = $contions['type'];
    if(!array_key_exists($type, getEntityType())) {
      return array();
    }
    $query = $this->database->select($type, 't');
    $query->fields('t');
    if(!empty($contions['keyword'])) {
      $keyword = $contions['keyword'];
      if($type== 'work_sheet_fault') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.phenomenon', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
          ->condition('t.reason', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type=='work_sheet_frame') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.business_ip', '%'. $keyword .'%', 'LIKE')
          ->condition('t.requirement', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type=='work_sheet_ip') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.requirement', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type=='work_sheet_room') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.requirement', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
          ->condition('t.next_step', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type == 'work_sheet_switch') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type == 'work_sheet_cycle') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.reason', '%'. $keyword .'%', 'LIKE')
        );
      } else if ($type == 'work_sheet_logistics') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.order_code', $keyword .'%', 'LIKE')
          ->condition('t.logistics_company',  $keyword .'%', 'LIKE')
        );
      }else if ($type == 'work_sheet_major_fault') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
        );
      }
    }
    if(isset($contions['creater']) && $contions['creater'] != 'all') {
      $query->condition('t.uid', $contions['creater']);
    }
    if(isset($contions['hander']) && $contions['hander'] != 'all') {
      $query->condition('t.last_uid', $contions['hander']);
    }
    if(isset($contions['begin'])) {
      $query->condition('t.created', $contions['begin'], '>=');
    }
    if(isset($contions['end'])) {
      $query->condition('t.created', $contions['end'], '<=');
    }
    if(isset($contions['comment'])){
      $comment = $contions['comment'];
      if($comment==2){
        $comment=0;
      }
      if($type=='work_sheet_fault') {
        if(!isset($contions['keyword'])) {
          $query->innerJoin('work_sheet_fault', 't1', 't.uuid = t1.uuid');
        }
        $query->condition('t.isno_comment',$comment,'=');
      }elseif($type =='work_sheet_major_fault'){
        if(!isset($contions['keyword'])) {
          $query->innerJoin('work_sheet_major_fault', 't1', 't.uuid = t1.uuid');
        }
        $query->condition('t.isno_comment',$comment,'=');
      }
    }
    $query->condition('t.status', 45);
    return $query->execute()->fetchAll();
  }
 
  /**
   * 导出数据
   */
  public function abnormalExportData(array $contions) {
    $type = $contions['type'];
    if(!array_key_exists($type, getEntityType())) {
      return array();
    }
    $query = $this->database->select($type, 't');
    $query->fields('t');
    if(!empty($contions['keyword'])) {
      $keyword = $contions['keyword'];
      if($type== 'work_sheet_fault') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.phenomenon', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
          ->condition('t.reason', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type=='work_sheet_frame') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.business_ip', '%'. $keyword .'%', 'LIKE')
          ->condition('t.requirement', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type=='work_sheet_ip') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.requirement', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type=='work_sheet_room') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.requirement', '%'. $keyword .'%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
          ->condition('t.next_step', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type == 'work_sheet_switch') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.handle_info', '%'. $keyword .'%', 'LIKE')
        );
      }
      else if ($type == 'work_sheet_cycle') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.reason', '%'. $keyword .'%', 'LIKE')
        );
      } else if ($type == 'work_sheet_logistics') {
        $query->condition($query->orConditionGroup()
          ->condition('t.ip', $keyword . '%', 'LIKE')
          ->condition('t.client', $keyword . '%', 'LIKE')
          ->condition('t.code', $keyword . '%', 'LIKE')
          ->condition('t.order_code', $keyword .'%', 'LIKE')
          ->condition('t.logistics_company',  $keyword .'%', 'LIKE')
        );
      }
    }
    if(isset($contions['creater']) && $contions['creater'] != 'all') {
      $query->condition('t.uid', $contions['creater']);
    }
    if(isset($contions['hander']) && $contions['hander'] != 'all') {
      $query->condition('t.last_uid', $contions['hander']);
    }
    if(isset($contions['begin'])) {
      $query->condition('t.created', $contions['begin'], '>=');
    }
    if(isset($contions['end'])) {
      $query->condition('t.created', $contions['end'], '<=');
    }
    $query->condition('t.abnormal_exist',1);
    $query->condition('t.status', 45);
    return $query->execute()->fetchAll();
  }
  
  /**
   * 获取handel信息
   */
  public function loadHandle(array $conditions) {
    $query = $this->database->select('work_sheet_handle', 't')
      ->fields('t', array('id', 'uid', 'time', 'operation_id', 'operation', 'is_abnormal', 'person_liable', 'reason', 'wid', 'entity_name'));
    foreach($conditions as $key => $value) {
      $query->condition('t.' . $key, $value);
    }
    return $query->execute()->fetchAll();
  }
  /**
   * 查询并计算工作量
   */
  public function loadWorkload() {
    //默认值班人员
    $i = array();
    $p = array();
    $config = \Drupal::config('worksheet.settings');
    $person_duty = $config->get('person_on_duty');
    foreach($person_duty as $uid) {
      $i[$uid] = 0;
      $p[$uid] = 0;
    }
    $storage = \Drupal::entityManager()->getStorage('user');
    //得到分单人,分单人工作量默认为2
    $config = \Drupal::config('worksheet.settings');
    $assigner = $config->get('task_assigner');
    if($assigner > 0) {
      if(array_key_exists($assigner, $i)) {
        $i[$assigner] += 2;
      } else {
        $i[$assigner] = 2;
      }
      if(array_key_exists($assigner, $p)) {
        $p[$assigner] += 2;
      } else {
        $p[$assigner] = 2;
      }
    }
    //计算接受的工单工作量
    $datas = $this->database->select('work_sheet_base','t')
      ->fields('t', array('id', 'tid', 'handle_uid'))
      ->condition('t.status', 15)
      ->execute()
      ->fetchAll();
    $types = \Drupal::service('worksheet.type')->getTypeDate();
    foreach($datas as $data) {
      $tid = $data->tid;
      if(!isset($types[$tid])) {
        continue;
      }
      $type = $types[$tid];
      $workload = $type->workload;
      $uid = $data->handle_uid;
      if($tid >= 200 && $tid < 300) { //判断是不是P类工单
        if(array_key_exists($uid, $p)) {
          $p[$uid] = $p[$uid] + $workload;
        } else {
          $p[$uid] = $workload;
        }
      } else {
        if(array_key_exists($uid, $i)) {
          $i[$uid] = $i[$uid] + $workload;
        } else {
          $i[$uid] = $workload;
        }
      }
    }
    foreach($i as $uid=>$workload) {
      $user = $storage->load($uid);
      if($workload_i = $user->get('workload_i')->value) {
        $i[$uid] += $workload_i;
      }
    }
    foreach($p as $uid=>$workload) {
      $user = $storage->load($uid);
      if($workload_p = $user->get('workload_p')->value) {
        $p[$uid] += $workload_p;
      }
    }
    asort($i);
    asort($p);
    return array(
      'i' => $i,
      'p' => $p
    );
  }
  /**
   * 机房事务工作量
   */
  public function roomWorkload() {
    $sql = 'select handle_date, sum(job_hours) as sum_job_hours from work_sheet_room  where `status` in (1,10,15,16,20,25) GROUP BY handle_date';
    $res = $this->database
       ->query($sql)
       ->fetchAll();
    $items = array();
    foreach($res as $re) {
      $key = $re->handle_date;
      $items[$key] = $re->sum_job_hours;
    }
    return $items;
  }
  /**
   * 修改机房事务工作处理事件
   */
  public function updateRoomHandleDate() {
    $state = \Drupal::state();
    $last_exec_time = $state->get('exec_room_handle_date', 0);
    $year = date('Y', time());
    $month = date('m', time());
    $day = date('d', time());
    $exec_time = mktime(9,0,0, $month, $day, $year);
    if((time() - $last_exec_time) >= 86400 && time() >= $exec_time) {
      $info = \Drupal::service("worksheet.date")->getMonthInfo((int)$year, (int)$month);
      $key = (int)$day;
      $day_info = $info[$key];
      if($day_info['work'] == 1) {
        $entitys = entity_load_multiple_by_properties('work_sheet_room', array('handle_date' => 1));
        foreach($entitys as $entity) {
          $entity->set('handle_date', 0);
          $entity->save();
        }
      }
      $state->set('exec_room_handle_date', $exec_time);
    }
  }
  /**
   * 通过编辑获取ID
   */
  public function getBaseByCode($code) {
    $bases = $this->database->select('work_sheet_base','t')
      ->fields('t', array('wid', 'entity_name'))
      ->condition('t.code', $code)
      ->orderby('t.id')
      ->execute()
      ->fetchAll();
    if(!empty($bases)) {
      return $bases[0];
    }
    return array();   
  }
  /**
   * 根据wid查询工单的评论信息
   */
  public function getCommentByid($code,$entity_name){
    if($entity_name !='work_sheet_major_fault' && $entity_name !='work_sheet_fault'){
      $data = null;
    }else{
      $data = $this->database->select($entity_name,'c')
        ->fields('c',array('if_question','if_right','if_deal','if_quality','comment_note','performance','isno_comment','comment_uid'))
        ->condition('c.wid',$code)
        ->execute()
        ->fetchAll();
    }
    return $data;
  }
  /**
   * 保存工单的评论
   */
  public function saveWorkSheetComment($fields,$code,$entity_name) {
    if($entity_name !='work_sheet_major_fault' && $entity_name !='work_sheet_fault'){
      $rs = null;
    }else{
      $rs = $this->database->update($entity_name)
        ->fields($fields)
        ->condition('wid',$code)
        ->execute();
    }
    return $rs;
  }
  /**
   * 根据code得到指定工单的评论
  */
  public function getcomm($code,$entity_name){
    if($entity_name !='work_sheet_major_fault' && $entity_name !='work_sheet_fault'){
      $data = null;
    }else{
      $data = $this->database->select($entity_name,'c')
        ->fields('c',array('isno_comment'))
        ->condition('c.code',$code)
        ->execute()
        ->fetchAll();
    }
    return $data;
  }
  public function add_allot($fields){
    return $this->database->insert('work_sheet_allot')
    ->fields($fields)
    ->execute();
  }
  public function update_allot(){
    $sql ="SELECT allot_id from work_sheet_allot ORDER BY allot_id DESC LIMIT 1";
    $data = $this->database->query($sql)->fetchAll();
    if($data){
      $rs = $this->database->update('work_sheet_allot')
        ->fields( array('allot_endtime'=>strtotime(date("Y-m-d H:i:s",intval(time()))) ))
        ->condition('allot_id',$data[0]->allot_id)
        ->execute();
      if($rs){
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  public function get_handle_uid($entity_name){
    $data = $this->database->select($entity_name,'c')
      ->fields('c',array('handle_uid','wid'))
      ->condition('isno_comment','','<>')
      ->execute()
      ->fetchAll();
    return $data;
  }
  public function getCommentuid($wid,$entity_name){
    $sql ="SELECT  distinct uid from work_sheet_handle where entity_name= '{$entity_name}' and wid ={$wid}";
    $data = $this->database
       ->query($sql)
       ->fetchAll();
    return $data;
  }
  //记录工单未完成和已完成的个数曲线图
  public function add_status($fields){
    return $this->database->insert('work_sheet_status')
    ->fields($fields)
    ->execute();
  }
  public function update_status($fields,$group_wid){
    $rs = $this->database->update('work_sheet_status')
      ->fields($fields)
      ->condition('group_wid',$group_wid)
      ->execute();
  }
  public function select_sop_status($begin,$end){
    $data = $this->database->select('work_sheet_status','c')
      ->fields('c',array('group_wid','btime','etime','bstatus','estatus'))
      ->condition('btime',$begin, '>=')
      ->condition('btime',$end, '<=')
      ->execute()
      ->fetchAll();
    return $data;
  }
}
