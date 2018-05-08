<?php
/**
 * @file
 *  故障申报相关的数据库操作
 */
namespace Drupal\question;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class QuestionService {

 /**
  * The database service.
  *
  * @var \Drupal\Core\Database\Connection
  */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * 存储对问题处理的回复信息
   *
   * @param $field_arr array()
   *   字段数组 .
   *   字段有：
   *     question_id 故障编号  create 回复时间  uid 作出回复的员工 content  回复的内容
   *
   */
  public function saveReplyMessage(array $field_arr){
    $this->database->insert('server_question_detail')
      ->fields($field_arr)
      ->execute();
  }

  /**
   * 根据问题编号查询所有的回复信息
   *
   * @param $question_id int
   *   问题编号 .
   */
  public function getAllReplyMessageByQuestionId($question_id){
   $query = $this->database->select('server_question_detail','reply')
     ->fields('reply')
     ->condition('question_id', $question_id);
      return $query->execute()->fetchAll();
  }

  /**
   * 存储对问题的转接记录
   *
   * @param $field_arr array()
   *   字段数组 .
   *   字段有：
   *     from_uid 转出用户  to_uid 转入用户 from_stamp 转出时间
   *     to_stamp 转入时间 question_id 问题编号 description 转接描述
   *
   */
  public function saveTransferRecord(array $field_arr) {
    $this->database->insert('server_question_convert')
       ->fields($field_arr)
      ->execute();
  }

  /**
   * 根据部门获取该部门下的员工
   *
   * @param $department
   *   部门编号
   */
  public function getEmployeeByDepartment($department) {
    $query = $this->database->select('user_employee_data','employee')
      ->fields('employee')
      ->condition('department', $department);
    return $query->execute()->fetchAll();
  }

  /**
   * 根据问题编号得到该问题的转接记录
   *
   * @param $question_id
   *   问题的编号
   */
  public function getQuestionTransferRecordByQuestionId($question_id, $header=array()) {
    $query = $this->database->select('server_question_convert','record')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('record')
      ->condition('question_id', $question_id)
      ->limit(PER_PAGE_COUNT)
     // ->orderByHeader($header)
      ->orderBy('from_stamp', 'DESC');
    return $query->execute()->fetchAll();
  }

  /**
   * 对故障问题作出回复
   *
   * @param $field_arr
   *   回复内容组成的数组
   */
  public function saveQuestionReply($field_arr) {
    return $this->database->insert('server_question_detail')
      ->fields($field_arr)
      ->execute();
  }

  /**
   * 设置问题转接记录的接收时间
   *
   * @param $field_arr
   *   回复内容组成的数组
   *
   * @param $filed_arr
   *   要修改的字段
   */
  public function setTransferRecoreAccecpTime($record_id,$filed_arr) {
    $this->database->update('server_question_convert')
      ->fields($filed_arr)
      ->condition('id ', $record_id)
      ->execute();
  }

  /**
   * 根据用户ID查询所有的问题
   *
   * @param $employee_id
   *   负责专员的编号
   *
   * @return 符合条件的结果集
   */
  public function getQuestionByEmployee($employee_id,$condition,$header=array()) {
    $query = $this->database->select('server_question_field_data','q')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('q')
      ->condition('server_uid', $employee_id);
    if($condition) {
      foreach($condition as $k=>$v){
        if($k == 'content__value') {
          $query->condition('content__value', '%'.$v.'%','like');
        }else {
          $query->condition($k, $v);
        }
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header)
      ->orderBy('created', 'DESC');
    return $query->execute()->fetchAll();

  }

  /**
   * 查询各个分类下 转出 转入的故障数量
   *
   * @param $uid  用户
   * @param $flag  标志 out 转出 in 转入
   * @param $category 故障编号
   *
   * @return $count 符合条件的转接数量
   *
   */
  public function getTransferRecordCountByCondition($uid, $flag, $category,$year,$month) {
     $statemtent = '';
    if($flag == 'out') {
       $statemtent = $this->database->query('SELECT COUNT(*) FROM server_question_convert AS c
         INNER JOIN server_question_field_data AS q
           ON c.question_id=q.id
             WHERE c.from_uid=:uid
               AND q.parent_question_class=:category
               AND extract(year from FROM_UNIXTIME(q.created))=:year
               AND extract(month from FROM_UNIXTIME(q.created))=:month
             GROUP BY c.question_id',
               array(':uid'=>$uid, ':category'=>$category, ':year'=>$year ,':month'=>$month));
     } elseif($flag == 'in') {
       $statemtent = $this->database->query('SELECT COUNT(*) FROM server_question_convert AS c
         INNER JOIN server_question_field_data AS q
           ON c.question_id=q.id
             WHERE c.to_uid=:uid
               AND q.parent_question_class=:category
               AND extract(year from FROM_UNIXTIME(q.created))=:year
               AND extract(month from FROM_UNIXTIME(q.created))=:month
             GROUP BY c.question_id',
               array(':uid'=>$uid, ':category'=>$category, ':year'=>$year ,':month'=>$month));
     }
    return $statemtent ? $statemtent->fetchField() : 0 ;

  }

  /**
   * 查询各个分类下 超时/按时完成的故障数量
   *
   * @param $uid  用户
   * @param $flag  标志 out_time 超时  on_time按时
   * @param $category 故障编号
   *
   * @return $count 符合条件的故障数量
   *
   */
  public function getTimeOutQuestionCount($uid, $flag, $category,$year,$month){
    $statemtent = '';
    if($flag == 'out_time') {
      $statemtent = $this->database->query('SELECT count(*) FROM {server_question_field_data}
        WHERE finish_stamp > pre_finish_stamp
          AND parent_question_class=:parent
          AND server_uid=:suid
          AND extract(year from FROM_UNIXTIME(created))=:year
          AND extract(month from FROM_UNIXTIME(created))=:month',
          array(':parent'=>$category, ':suid'=>$uid, ':year'=>$year ,':month'=>$month));

    } elseif($flag == 'on_time') {
      $statemtent = $this->database->query('SELECT count(*) FROM {server_question_field_data}
        WHERE (finish_stamp < pre_finish_stamp OR finish_stamp = pre_finish_stamp)
         AND parent_question_class=:parent
         AND server_uid=:suid
         AND extract(year from FROM_UNIXTIME(created))=:year
         AND extract(month from FROM_UNIXTIME(created))=:month',
       array(':parent'=>$category, ':suid'=>$uid, ':year'=>$year ,':month'=>$month));
    }

    return $statemtent ? $statemtent->fetchField():0 ;

  }

  /**
   * 查询客户申报的所有故障
   *
   * @param $uid int
   *   客户编号
   *
   * @param $condition array
   *   查询条件数组
   *
   * @param $header array
   *  表头
   *
   * @return array()
   *   符合条件的结果集
   *
   */
  public function getQuestionByClient($uid,$condition=array(),$header) {
    $query = $this->database->select('server_question_field_data','q')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('q')
      ->condition('uid', $uid);
    if($condition) {
      foreach($condition as $k=>$v){
        $query->condition($v['field'], $v['value'],$v['op']);
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header)
      ->orderBy('created', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * 查询用户所有的租用服务器IP
   *
   * @param $user_id  integer
   *   用户编号
   *
   * @param $condition  string
   *   筛选条件
   *
   * @param $hid integer
   *   服务器数据逐渐hid
   *
   * @return $server_ip  array
   *   符合条件的结果集
   */
  public function getAllHireIpByUser($user_id, $condition, $hid=null) {

    $query = $this->database->select('hostclient__ipb_id','t');
    $query->innerJoin('business_ip_field_data', 'b', 't.ipb_id_target_id = b.id');
    $query->innerJoin('hostclients_field_data', 'h', 't.entity_id = h.hid');
    $query->fields('t', array('ipb_id_target_id'));
    $query->fields('b', array('ip'));
    // IP模糊查询
    if($condition) {
      $query->condition('b.ip', '%'.$condition.'%', 'LIKE');
    }
    // 查询指定服务器上的IP数据
    if($hid) {
      $query->condition('h.hid', $hid);
    }
    $query->condition('h.status', 3)  // 已经上架正常使用的服务器IP.参见common.module 中 function hostClientStatus()
      ->condition('h.client_uid',$user_id);

    return $query->execute()->fetchAll();
  }

  /**
   * 根据业务IP拿到对应的管理ip编号
   *
   * @param $business_ip string
   *   业务IP
   *
   * @return
   *   查询到的管理IP的编号 ipm_id
   */
  public function getIPmIdByBip($business_ip) {
    $query = $this->database->select('hostclient__ipb_id','t');
    $query->innerJoin('business_ip_field_data', 'b', 't.ipb_id_target_id = b.id');
    $query->innerJoin('hostclients_field_data', 'h', 't.entity_id = h.hid');
    $query->fields('h', array('ipm_id'));
    $query->condition('b.ip', $business_ip);
    return $query->execute()->fetchField();
  }

  /**
   * @description 根据id得到转交指定的一条记录
   *
   * @param  $id int 转交记录的编号
   *
   */
  public function getTranslateRecordById($id) {
    $query = $this->database->select('server_question_convert','c');
    $query->fields('c')
      ->condition('c.id',$id);
    return$query->execute()->fetchObject();
  }
}
