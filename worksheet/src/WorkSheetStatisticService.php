<?php
/**
 * @file
 *  统计服务类
 */
 
namespace Drupal\worksheet;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class WorkSheetStatisticService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  
  protected static $statisticTable = "work_sheet_statistic";

  protected $condtions = array();
 
  public function __construct(Connection $database) {
    $this->database = $database;
  }
  /**
   * 增加统计数据
   */
  public function add(array $value) {
    return $this->database->insert('work_sheet_statistic')
      ->fields($value)
      ->execute();
  }

  /**
   * 获得处理过工单的uid
   */
  public function getUids(){
    $sql = "select  uid from work_sheet_statistic group by uid";
    $res = $this->database
      ->query($sql)
      ->fetchAll();
    $uids = array();
    foreach($res as $k=>$v){
        $uids[] = intval($v->uid);
    }
    return $uids;
  }

  public function timeLimit($begin,$end){
    if($begin) {
      $this->condtions[] = "created > $begin";
    }
    if($end) {
      $end = $end + 86400;
      $this->condtions[] = "created < $end";
    }
  }

  public function setUser($user){
    $this->condtions[] = "user_dept = '{$user}'";
  }

  public function getCount(){
    $where = '';
    if(!empty($this->condtions)) {
      $where = "where " . implode(" and ", $this->condtions);
    }
    $sql = "select 
      uid,
      sum(case `event` when 1 then 1 else 0 end) as 'buildWorkin',
      sum(case `event` when 2 then 1 else 0 end) as 'joins',
      sum(case `event` when 3 then 1 else 0 end) as 'finish',
      sum(case `event` when 4 then 1 else 0 end) as 'checkException',
      sum(case `event` when 5 then 1 else 0 end) as 'exceAccept',
      sum(case `event` when 6 then 1 else 0 end) as 'exception',
      sum(case `event` when 7 then 1 else 0 end) as 'timeException'
    from work_sheet_statistic $where group by uid;";
    $res = $this->database
      ->query($sql)
      ->fetchAll();
    return $res;
  }

  /**
   * 删除统计表数据
   */
  public function delete($wid, $entity_name) {
    $this->database->delete('work_sheet_statistic')
      ->condition('wid', $wid)
      ->condition('entity_name', $entity_name)
      ->execute();
  }
  /**
   * 删除工单状态表数据
   */
  public function delete_status($wid, $entity_name) {
    $this->database->delete('work_sheet_status')
      ->condition('wid', $wid)
      ->condition('entity_name', $entity_name)
      ->execute();
  }
  /**
   * 删除异常
   */
  public function deleteAbnormal($wid, $entity_name, $created, $event) {
    $this->database->delete('work_sheet_statistic')
      ->condition('wid', $wid)
      ->condition('entity_name', $entity_name)
      ->condition('created', $created)
      ->condition('event', $event)
      ->execute();
  }
  public function getCountComment($begin,$end){
    $this->timeLimit($begin,$end);
    $where = '';
    if(!empty($this->condtions)) {
      $where = "where " . implode(" and ", $this->condtions);
    }
    $sql ="SELECT t.comment_uid,count(*) as count,SUM(t.performance) as performance,
      sum(case t.if_question when 0 then 1 else 0 end) as if_question_count,
      sum(case t.if_right when 0 then 1 else 0 end) as if_right_count,
      sum(case t.if_deal when 0 then 1 else 0 end) as if_deal_count,
      SUM(case t.if_quality WHEN 0 THEN 1 else 0 end)as if_quality_count FROM
      (SELECT if_quality,if_deal,if_question,if_right,comment_uid,created,performance from work_sheet_fault where isno_comment=1 
      UNION ALL
      SELECT if_quality,if_deal,if_question,if_right,comment_uid,created,performance from work_sheet_major_fault where isno_comment=1
      )t $where GROUP BY t.comment_uid";
    $result = $this->database->query($sql)->fetchAll();
    return $result;
  }
  public function getCountQuestion($begin,$end){
    $this->timeLimit($begin,$end);
    $where = '';
    if(!empty($this->condtions)) {
      $where = "where " . implode(" and ", $this->condtions);
    }
    $sql = "SELECT problem_types,problem_types_child,count(*) as count 
    from work_sheet_fault $where GROUP BY problem_types,problem_types_child" ;
    $result = $this->database->query($sql)->fetchAll();
    return $result;
  }
  public function get_problem_name($num){
    $query = $this->database->select('work_sheet_options','t');
    $query->fields('t',array('optin_name'));
    $query->condition('t.option_type','problem_type');
    $query->condition('t.id',$num);
    $result = $query->execute()->fetchAll();
    if(!empty($result)){
      return $result[0]->optin_name;
    }else{
      return null;
    }
  }
  public function getCountAllo($begin,$end){
    $config = \Drupal::configFactory()->getEditable('worksheet.settings');
    $allocate_time = $config->get('allocate_time');
    $number =0;
    foreach($allocate_time as $value){
      //计算一天的工作量
      $strarray = explode('-',$value[0]);
      $first =  strtotime($strarray[0]);
      $sencod = strtotime($strarray[1]);
      $number += abs(($sencod-$first)/60*$value[1]);
      
    }
    $this->timeLimit($begin,$end);
    $where = '';
    if(!empty($this->condtions)) {
      $where = 'and '.implode(" and ", $this->condtions);
    }
    $sql ="SELECT * from work_sheet_allot where allot_endtime>0 $where";
    $userlist = $this->database->query($sql)->fetchAll();
    $num=0;
    foreach ($userlist as $item){
      $time = $item->allot_endtime - $item->created;
      $begin_time_str = date('H:i:s', $item->created);
      $begin_time_str = strtotime($begin_time_str);
      $endtime_str = date('H:i:s', $item->allot_endtime);
      $endtime_str = strtotime($endtime_str);
      if($time>86400){
        //一天或者以上
        //begin_time_str分配开始 endtime_str分配结束 first时间段开始 sencod时间段结束
        //计算天数
        $day = intval($time/86400);
        $begin_time_str = $begin_time_str + $day*86400;
        $begin_time_str = date('H:i:s', $begin_time_str);
        $begin_time_str = strtotime($begin_time_str);
        $timearray=array(0=>$begin_time_str,1=>$endtime_str);
        $num = $this->time_quantum($timearray);
        $num +=$number;
        $item->xishu = $num;
      }else{
        //未满一天  可能的情况06-01 23:00:00---06-01 09:00:00
        $chatime = $endtime_str-$begin_time_str;
        if($chatime<0){
          $year = date("Y");
          $month = date("m");
          $day = date("d");
          $time24 = mktime(23,59,59,$month,$day,$year);
          $timearray1=array(0=>$begin_time_str,1=>$time24);
          $num2= $this->time_quantum($timearray1);
          $start = mktime(0,0,0,$month,$day,$year);
          $timearray2=array(0=>$start,1=>$endtime_str);
          $num3= $this->time_quantum($timearray2);
          $num =$num2+$num3;
          //06-01 23:00:00---06-01 09:00:00
        }else{
          //06-01 08:00:00---06-01 19:00:00
          $timearray=array(0=>$begin_time_str,1=>$endtime_str);
          $num = $this->time_quantum($timearray);
        }
        $item->xishu = $num;
      }
    }
    return $userlist;
  }

  public function time_quantum($timearray){
    $config = \Drupal::configFactory()->getEditable('worksheet.settings');
    $allocate_time = $config->get('allocate_time');
    $num =0;
    foreach($allocate_time as $value){
      $strarray = explode('-',$value[0]);
      $first =  strtotime($strarray[0]);
      $sencod = strtotime($strarray[1]);
      $return = $this->isMixTime($first,$sencod,$timearray[0],$timearray[1]);
      if($return){
        $nextbegintime = $timearray[1]-$sencod;
        if($nextbegintime>0){
          //超出了当前时间段
          //计算未超出部分
          $num2 = abs(($sencod-$timearray[0])/60*$value[1]);
          $nexttime = $sencod-$timearray[0]+$timearray[0];
          $num += $num2;
          $timearray[0] = $nexttime;
          $sencod = $timearray[1];
        }
        else{
          //未超出当前时间段
          $returntime = array();
          $num3 = ($timearray[1]-$timearray[0])/60 * $value[1];
          $num+=$num3;
        }
      }
    }
    return abs($num);
  }
  public function isMixTime($begintime1,$endtime1,$begintime2,$endtime2) {
    $status = $begintime2 - $begintime1; 
    if($status>0){
      $status2 = $begintime2 - $endtime1; 
      if($status2>0){ 
        return false; 
      }else{ 
        return true; 
      }  
    }
    else{
      $status2 = $begintime1 - $endtime2; 
      if($status2>0){ 
        return false; 
      }else{
      return true; 
      } 
    } 
  }
  //包括系统的统计
  public function getServerPutaway($entity_type,$begin,$end,$type,$subdivision){
    $sql = "SELECT sub2.entity_id as last_uid,stati.all_day,stati.night,stati.usetimess,stati.win03,stati.win08,stati.win2012,stati.centos,stati.ubuntu,stati.new_system,stati.un_install from user__subdivision as sub2 LEFT JOIN(select last_uid, count(*) as all_day, nights.night, 
      sum(all_days.usetime) as usetimess, 
      sum(case system when 1 then 1 else 0 end) as 'win03', 
      sum(case system when 2 then 1 else 0 end) as 'win08', 
      sum(case system when 3 then 1 else 0 end) as 'win2012', 
      sum(case when system=4 or system=5 or system=6 then 1 else 0 end) as 'centos',
      sum(case system when 7 then 1 else 0 end) as 'ubuntu', 
      sum(case system when 13 then 1 else 0 end) as 'new_system', 
      sum(case system when 12 then 1 else 0 end) as 'un_install' from (SELECT f.last_uid,f.uid,f.system,(b.com_time-b.begin_time) as usetime 
      from work_sheet_base as b LEFT JOIN {$entity_type} as f on b.uuid = f.uuid LEFT JOIN user__subdivision as sub on f.last_uid= sub.entity_id where b.`status`=45 
      AND b.entity_name='{$entity_type}' AND f.tid IN ({$type}) AND sub.subdivision_value in ({$subdivision}) AND b.created BETWEEN {$begin} AND {$end}) as all_days LEFT JOIN (SELECT f.uid,COUNT(*) as night from work_sheet_base as b LEFT JOIN {$entity_type} as f on b.uuid = f.uuid LEFT JOIN user__subdivision as sub on f.uid= sub.entity_id where b.`status`=45 AND b.entity_name='{$entity_type}' AND f.tid IN ({$type}) AND sub.subdivision_value in ({$subdivision}) AND b.created BETWEEN {$begin} AND {$end} GROUP BY f.uid) as nights on nights.uid = all_days.last_uid group by last_uid,nights.night) as stati on sub2.entity_id = stati.last_uid where sub2.subdivision_value in ({$subdivision}) ORDER BY sub2.subdivision_value";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }
  //不包括系统的统计
  public function getServerPutaway2($entity_type,$begin,$end,$type,$subdivision){
    $sql = "SELECT sub2.entity_id as last_uid,stati.all_day,stati.night,stati.usetimess from user__subdivision as sub2 LEFT JOIN(select last_uid, count(*) as all_day, nights.night, 
      sum(all_days.usetime) as usetimess from (SELECT f.last_uid,f.uid,(b.com_time-b.begin_time) as usetime from work_sheet_base as b LEFT JOIN {$entity_type} as f on b.uuid = f.uuid LEFT JOIN user__subdivision as sub on f.last_uid= sub.entity_id where b.`status`=45 
      AND b.entity_name='{$entity_type}' AND f.tid IN ({$type}) AND sub.subdivision_value in ({$subdivision}) AND b.created BETWEEN {$begin} AND {$end}) as all_days LEFT JOIN (SELECT f.uid,COUNT(*) as night from work_sheet_base as b LEFT JOIN {$entity_type} as f on b.uuid = f.uuid LEFT JOIN user__subdivision as sub on f.uid= sub.entity_id where b.`status`=45 AND b.entity_name='{$entity_type}' AND f.tid IN ({$type}) AND sub.subdivision_value in ({$subdivision}) AND b.created BETWEEN {$begin} AND {$end} GROUP BY f.uid) as nights on nights.uid = all_days.last_uid group by last_uid,nights.night)as stati on sub2.entity_id = stati.last_uid where sub2.subdivision_value in ({$subdivision}) ORDER BY sub2.subdivision_value";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }
  //获取子部门
  public function get_subdivision($uid){
    $query = $this->database->select('user__subdivision','t');
    $query->fields('t',array('subdivision_value'));
    $query->condition('t.entity_id',$uid);
    $result = $query->execute()->fetchAll();
    return $result;
  }
  //获取真实姓名
  public function get_realname($uid){
    $query = $this->database->select('user__real_name','t');
    $query->fields('t',array('real_name_value'));
    $query->condition('t.entity_id',$uid);
    $result = $query->execute()->fetchAll();
    return $result;
  }
  //获取统计生产技术异常工单
  public function getAbnormal($begin,$end,$subdivision){
    $sql ="SELECT subd.entity_id as uid,sb2.tid,sb2.num from (
      select base.last_uid as uid, base.tid, count(*) as num from work_sheet_base as base inner join work_sheet_handle as han on base.wid = han.wid LEFT JOIN
      user__subdivision as sub on sub.entity_id=base.last_uid where base.entity_name='work_sheet_frame' and han.entity_name='work_sheet_frame' and han.is_abnormal = 1 and han.operation_id IN (22,25,26) and base.created BETWEEN {$begin} AND {$end} and sub.subdivision_value in ({$subdivision}) group by base.last_uid, base.tid
      UNION ALL
      select base.last_uid as uid, base.tid, count(*) as num from work_sheet_base as base inner join work_sheet_handle as han on base.wid = han.wid LEFT JOIN user__subdivision as sub on sub.entity_id=base.last_uid where base.entity_name='work_sheet_switch' and han.entity_name='work_sheet_switch' and han.is_abnormal = 1 and han.operation_id IN (22,25,26) and base.created BETWEEN {$begin} AND {$end} and sub.subdivision_value in ({$subdivision}) group by base.last_uid, base.tid
      UNION ALL
      select base.last_uid as uid, base.tid, count(*) as num from work_sheet_base as base inner join work_sheet_handle as han on base.wid = han.wid LEFT JOIN user__subdivision as sub on sub.entity_id=base.last_uid where base.entity_name='work_sheet_ip' and han.entity_name='work_sheet_ip' and han.is_abnormal = 1 and han.operation_id IN (22,25,26) and base.created BETWEEN {$begin} AND {$end} and sub.subdivision_value in ({$subdivision}) group by base.last_uid, base.tid
      )as sb2 RIGHT JOIN user__subdivision as subd on subd.entity_id= sb2.uid where subd.subdivision_value in ({$subdivision}) ORDER BY subd.subdivision_value";
    $data = $this->database->query($sql)->fetchAll();
    return $data;
  }
}
