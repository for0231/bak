<?php
/**
 * @file
 * 操作member实体表
 */
namespace Drupal\member;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MemberService {

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
   * 去除数组中重复的元素
   */
  private function filterRepeatItemOfArray($array) {
    return array_filter($array, create_function( '$v', 'return !empty($v);'));
  }


  /**
   * 存储员工信息
   *
   * @param $uid  int
   * 员工编号
   *
   * @param $filed_arr array()
   * 字段数组
   */
  public function saveEmployeeInfo($filed_arr) {

    $this->database->insert('user_employee_data')
       ->fields($this->filterRepeatItemOfArray($filed_arr))
      ->execute();
  }

  /**
   * 存储客户信息
   *
   * @param $filed_arr array()
   * 字段数组
   */
  public function saveClientInfo( $filed_arr) {

    $this->database->insert('user_client_data')
       ->fields($this->filterRepeatItemOfArray($filed_arr))
      ->execute();
  }

 /**
  *查询数据库
  *
  * @param $user_type
  *   客户类型  employee 员工 client 客户
  *
  * @param $uid
  *   用户的编号 id
  *
  * @return
  *  用户对象 @see Drupal\user\user
  */
  public function getStandardUserInfo($user_type, $uid) {
    $table_name = 'user_client_data';

    if($user_type == 'employee') {
      $table_name = 'user_employee_data';
    }

    $query = $this->database->select($table_name,'mem');
    $query->innerJoin('users_field_data', 'ufd', 'mem.uid=ufd.uid');
    $query->fields('mem');
    $query->fields('ufd');
    $query->condition('mem.uid', $uid);
    $data = $query->execute()->fetchObject();
    return $data;
  }
  public function queryDataFromDB($user_type, $uid){
    if (empty($uid)) {
      return '';
    }
    $user = $this->getStandardUserInfo($user_type, $uid);
    if (!empty($user))
      return $user;
    else {
      $user = user_load($uid);
      return $user;
    }
  }

  /**
  *查询数据库
  *
  * @param $user_type
  *   客户类型  employee 员工 client 客户
  *
   @param $uid
  *   用户的编号 id
  *
  * @return
  *  用户对象 @see Drupal\user\user
  */
  public function queryUserByName($user_type,$realname){

   $query = '';
    if($user_type == 'employee') {
      $query = $this->database->select('user_employee_data','t');
      $query->rightJoin('users_field_data', 'ufd', 't.uid=ufd.uid');
      $query->fields('t');
      $query->fields('ufd');

      $query->condition($query->orConditionGroup()
        ->condition('t.employee_name', '%' . $realname . '%', 'LIKE')
        ->condition('ufd.name', '%' . $realname . '%', 'LIKE')
      );
    } elseif($user_type == 'client') {
      $query = $this->database->select('user_client_data','t');
      $query->rightJoin('users_field_data', 'ufd', 't.uid=ufd.uid');
      $query->fields('t');
      $query->fields('ufd');
      $query->condition($query->orConditionGroup()
        ->condition('t.client_name', '%' . $realname . '%', 'LIKE')
        ->condition('ufd.name', '%' . $realname . '%', 'LIKE')
      );
    }
    return $query->execute()->fetchObject();
  }


 /**
  * 修改用户信息
  *
  * @param $uid
  *   用户编号
  *
  * @param $field_arr
  *   要修改的信息组成的数组
  *
  * @param $user_type
  *   用户类型 employee：员工  client：会员
  */
  public function updateUserInfo($uid, $field_arr,$user_type) {
    //移除数组中为空值的项
    $field_arr = $this->filterRepeatItemOfArray($field_arr);
    if(!empty($field_arr)) {
      if($user_type == 'employee') {
       return  $this->database->update('user_employee_data')
          ->fields($field_arr)
          ->condition('uid', $uid)
          ->execute();
      } elseif($user_type == 'client') {
        return $this->database->update('user_client_data')
          ->fields($field_arr)
          ->condition('uid', $uid)
          ->execute();
      }
    }
  }

  /**
   * 删除用户
   *
   * @param $user_type
   *   用户类型 employee 员工 client 会员客户
   *
   * @param $uid
   *   用户编号
   *
   */
  function deleteUser($user_type,$uid) {
    if($user_type == 'employee') {
      $this->database->delete('user_employee_data')
        ->condition('uid', $uid)
        ->execute();
    } elseif($user_type == 'client') {
      $this->database->delete('user_client_data')
        ->condition('uid', $uid)
        ->execute();
    }
  }

  /**
   * 查询所有的员工数据
   *
   * @table_name 表名
   *
   * @param $condition array
   *   筛选条件组成的数组
   *
   * @param $header
   *  用于排序的表头
   *
   * @retuan 查询到的符合条件的所有用户集合
   *
   */
  public function getAllMember($table_name,$condition=array(),$header=array()){
    $query = $this->database->select($table_name,'mem')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->innerJoin('users_field_data', 'ufd', 'mem.uid=ufd.uid');
    $query->fields('mem');
    $query->fields('ufd');
    if(!empty($condition)){
      foreach($condition as $v) {
        $query->condition($v['field'], $v['value'], $v['op']);
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header);
    return $query->execute()->fetchAll();
  }

  /**
   * 查询所有的客户数据
   *
   * @condition array
   *   筛选条件组成的数组
   *
   * @param header array
   *   排序的表头
   *
   * @param role string
   *  选择的角色条件的id
   *
   * @retuan 查询到的符合条件的所有用户集合
   *
   */
  public function getAllClient($condition=array() ,$header=array(), $role = null){
    $query = $this->database->select('user_client_data','mem')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');

    $query->innerJoin('users_field_data', 'ufd', 'mem.uid=ufd.uid');

    if($role) {
      $query->innerJoin('user__roles', 'r', 'mem.uid=r.entity_id');
    }
    $query->fields('mem');
    $query->fields('ufd');
    if(!empty($condition)){
      foreach($condition as $v) {
        if($v['field'] == 'mem.client_name') {
          $query->condition(
            $query->orConditionGroup()->condition('mem.client_name', $v['value'], $v['op'])
              ->condition('mem.nick', $v['value'], $v['op'])
          );
        }else{
          $query->condition($v['field'], $v['value'],$v['op']);
        }
      }
    }
    if($role) {
      $query->condition('r.roles_target_id', $role);
    }

    $query->limit(PER_PAGE_COUNT)
      ->orderByHeader($header);

    return $query->execute()->fetchAll();

  }


  /**
   *  -----此方法未使用--------
   *
   * 根据用户id得到该用户的角色组
   *
   * @param $uid int
   *   用户的id
   *
   * @return 指定用户所属的角色组
   */
  public function getMemberRoles($uid){
    $query = $this->database->select('users_roles','role')
      ->fields('role')
      ->condition('uid', $uid);
    return $query->execute()->fetchAll();
  }

  /**
   * 查询所有的员工数据
   *
   * @retuan 查询到的所有用户集合
   */
  public function getAllEmployee(){
    $query = $this->database->select('user_employee_data','mem');
    $query->innerJoin('users_field_data', 'ufd', 'mem.uid=ufd.uid');
    $query->fields('mem');
    $query->fields('ufd');
    return $query->execute()->fetchAll();
  }

  /**
   * 根据部门获取该部门下的员工
   *
   * @param $department
   *   部门编号
   */
  public function getEmployeeByDepartmentID($department) {

    $query = $this->database->select('user_employee_data','employee')
      ->fields('employee')
      ->condition('department', $department);
    return $query->execute()->fetchAll();
  }

  /**
   * 得到客户的信用额度
   *
   * @param $uid int
   *  用户id
   */
  public function getClientCredit($uid) {
    $query = $this->database->select('user_funds_data','funds')
      ->fields('funds')
      ->condition('uid', $uid);
    return $query->execute()->fetchObject();
  }

 /**
  * 用户信用额度调整
  *
  * @param $uid
  *   用户编号
  *
  * @param $funds array
  *  额度
  *
  * @param $op_record array
  *  用户信用额度调整操作记录
  *
  */
  public function adjustClientCredit($uid, $funds, $op_record) {
    $this->database->update('user_funds_data')
      ->fields($funds)
      ->condition('uid', $uid)
      ->execute();

    $result = $this->saveCreditOPRecord($op_record);

    return $result ;

  }
  /**
   * 给用户现金充值
   *
   * @param $funds array
   *   用户现金调整字段
   */
  public function setClientPrivateData($funds) {
    if (empty($funds)) {
      return 0;
    }
    $query = $this->database->select('user_funds_data', 'ufd')
      ->fields('ufd')
      ->condition('uid', $funds['uid'])
      ->execute()
      ->fetchObject();
    if (isset($query->cash)) {
      $funds['cash'] = $query->cash + $funds['cash'];
    }
    $transaction = $this->database->startTransaction();
    try {
      if(!empty($query)) {
        // update 更新时如果是充值，则对user_funds_data的账户余额值进行加法运算
        $this->database->update('user_funds_data')
          ->fields($funds)
          ->condition('uid', $funds['uid'])
          ->execute();
      } else {
        // insert
        $this->database->insert('user_funds_data')
          ->fields($funds)
          ->execute();
      }
      return 1;
    } catch (\Exception $e) {
      $transaction->rollback();

      return 0;
    }
  }
  /**
   * 给用户设置信用额度
   *
   * @param $founds_credit array
   *   用户信用额度调整字段数组
   *
   * @param $op_record array
   *  用户信用额度调整操作记录
   *
   */
  public function setClientCredit($founds_credit, $op_record) {

    // 写入用户额度或现金数据
    $c = $this->setClientPrivateData($founds_credit);
    if (isset($op_record['type'])) {
      $r = $this->saveConsumerOPRecord($op_record);
    }
    else {
    // 写入额度调整记录
      $r = $this->saveCreditOPRecord($op_record);
    }
    if ($c && $r) {
      return 1;
    }
    else {
      return 0;
    }
  }
  /**
   * 保存调整用户的充值/消费的操作记录
   *
   * @param 
   *  - $op_record array
   *  用户信用额度调整操作记录
   *
   */
  public function saveConsumerOPRecord($op_record) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->insert('user_consumer_records')
       ->fields($op_record)
       ->execute();

      return 1;

    }catch (\Exception $e) {
      $transaction->rollback();

      return 0;
    }
  }
  /**
   * 保存调整用户的信用额度的操作记录
   *
   * @param
   *  - $op_record array
   *  用户信用额度调整操作记录
   *
   */
  public function saveCreditOPRecord($op_record) {
    return $this->database->insert('user_funds_op_record')
     ->fields($op_record)
     ->execute();
  }


  /**
   * 得到指定用户的信用额度操作记录
   *
   * @param $uid
   *   会员的编号
   */
  public function getCreditAdjustRecord($uid) {
    $query = $this->database->select('user_funds_op_record','record')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('record')
      ->condition('client_uid', $uid)
      ->limit(PER_PAGE_COUNT)
      ->orderBy('created', 'DESC');
    return $query->execute()->fetchAll();
  }

  /**
   * 得到所有客户的信用额度
   *
   * @param $condition array
   *   筛选条件数组
   *
   */
  public function getAllClientCredit($condition=array()) {

    $query = $this->database->select('user_funds_data','funds')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');

    $query->innerJoin('user_client_data', 'client', 'funds.uid = client.uid');
    $query->fields('funds');
    $query->fields('client', array('client_name', 'corporate_name', 'client_type'));
    // 筛选条件
    if(!empty($condition)) {
      foreach($condition as $value) {
        $query->condition($value['field'], $value['value'], $value['op']);
      }
    }
    $query->limit(PER_PAGE_COUNT)
      ->orderBy('credit', 'DESC');

    return $query->execute()->fetchAll();
  }

  /**
   * 得到所有发布文章的作者
   */
  public function getAllAuthor() {
    $query = $this->database->select('node_field_data','n');
    $query->innerJoin('user_employee_data', 'e', 'n.uid=e.uid');
    $query->fields('n', array('uid'));
    $query->fields('e', array('employee_name'));
    //$query->groupBy('n.uid'); //解决报错 @todo 确认是否需要Group,而不用order?
    return $query->execute()->fetchAll();
  }

  /**
   * 根据用户名得到用户的id
   *   适用于：同步用户数据时
   */
  public function getUidByName($name) {
    $query = $this->database->select('users_field_data','user');
    $query->fields('user', array('uid'));
    $query->condition('name', $name);
    return $query->execute()->fetchField();
  }

  /**
   * 用户消费
   */
  public function setClientConsumerCredit($founds) {
    // 消费用户现金或额度
    $c = $this->setClientConsumerPrivateData($founds['funds']);
    // 写入消费记录
    $r = $this->saveClientConsumerOPRecord($founds['op_record']);

    if ($c && $r) {
      return 1;
    }
    else {
      return 0;
    }
  }

  /**
   * 消费用户现金或额度
   */
  public function setClientConsumerPrivateData($funds) {
    if (empty($funds)) {
      return 0;
    }
    $transaction = $this->database->startTransaction();
    try {
      $this->database->update('user_funds_data')
        ->fields($funds)
        ->condition('uid', $funds['uid'])
        ->execute();
      return 1;
    } catch (\Exception $e) {
      $transaction->rollback();

      return 0;
    }
  }

  /**
   * 写入消费记录
   */
  public function saveClientConsumerOPRecord($record) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->insert('user_consumer_records')
       ->fields($record)
       ->execute();
      return 1;

    }catch (\Exception $e) {
      $transaction->rollback();
      return 0;
    }
  }

  /**
   * 设置余额预警开关
   *
   * @param
   *   - $uid int 用户编号
   *   - $flag string 预警开关  （ON/OFF）
   */

  public function setBalanceAlarm($uid, $alarm) {
    return $this->database->update('user_funds_data')
      ->fields(array('alarm' => $alarm))
      ->condition('uid', $uid)
      ->execute();
  }

  /**
   * 查询指定用户所有的消费记录
   *
   * @param
   *  - $uid int 用户编号
   *
   * @return 所有符合条件的结果集
   */
  public function getConsumerRecordByUid($uid) {
    $query = $this->database->select('user_consumer_records','record')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('record')
      ->condition('client_uid', $uid)
      ->limit(PER_PAGE_COUNT)
      ->orderBy('created ', 'DESC');
    return $query->execute()->fetchAll();
  }
  /**
   * 得到指定用户的收入支出的总计
   *
   * @param
   *  - $uid int 用户编号
   *  - $type int 消费类型 1->充值（收入）  2-> 消费（支出）
   *
   * @return $value  统计的总和
   *
   */
  public function getAmount($uid,$type) {
    $query = $this->database->query(' SELECT SUM(amount) AS total FROM user_consumer_records 
      WHERE client_uid=:uid AND type=:type', array(':uid' => $uid, ':type' => $type));
    $value = $query->fetchField();
    return $value ? $value : 0.00;
  }

  /**
   * 查询所有的充值/消费记录
   *
   * @param
   *  - $type int 消费类型  1充值 2消费
   *
   * @return 所有符合条件的结果集
   */
  public function getAllCashRecord($type, $header) {
    $query = $this->database->select('user_consumer_records','record')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('record')
      ->condition('type', $type)
      ->limit(PER_PAGE_COUNT)
      ->orderByHeader($header)
      ->orderBy('created ', 'DESC');
    return $query->execute()->fetchAll();
  }


  /**
   * 根据代理级别加载所有的客户
   *
   * @param
  *   - $role 代理级别
   *
   * @return array() 所有符合条件的数据集
   *
   */
  public function getAllAgent($role=null){

    $query = $this->database->select('user_client_data','mem');
    $query->innerJoin('users_field_data', 'ufd', 'mem.uid=ufd.uid');

    if($role) {
      $query->innerJoin('user__roles', 'r', 'mem.uid=r.entity_id');
    }

    $query->fields('mem');
    $query->fields('ufd');
    if($role) {
      $query->condition('r.roles_target_id', $role);
    }
    return $query->execute()->fetchAll();

  }

  /**
   * 匹配客户名称
   */
  public function getMatchClients($type_string) {
    $options = array();
    $query = $this->database->query('select u.uid,u.name,c.nick,c.client_name,c.corporate_name from user_client_data as c left join users_field_data as u on u.uid=c.uid where name like :string or nick like :string or client_name like :string or corporate_name like :string  order by uid desc limit 0,10', array(
      ':string' => '%' . $type_string . '%',
    ));
    $results = $query->fetchAll();
    foreach($results as $item) {
      $options[] = array(
        'value' => $item->name,
        'label' => $item->name.'|'.$item->client_name.'|'.$item->nick.'|'.$item->corporate_name,
      );
    }

    return $options;
  }
  
  /**
   * 增加
   */
  public function addProof($values) {
    return $this->database->insert('user_funds_proof')
      ->fields($values)
      ->execute();
  }
  
  public function loadProof($conditions = array()) {
    $query = $this->database->select('user_funds_proof', 't')
      ->fields('t');
    foreach($conditions as $key => $value) {
      if(is_array($value)) {
        if($value['op'] == 'like') {
          $query->condition($key, '%,' . $value['value'] . ',%', 'like');
        } else {
          $query->condition($key, $value['value'], $value['op']);
        }
      } else {
        $query->condition($key, $value);
      }
    }
    return $query->execute()->fetchAll();
  }
}
