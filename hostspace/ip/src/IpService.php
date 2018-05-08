<?php
/**
 * @file
 * 操作IP实体表
 */
namespace Drupal\ip;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IpService {

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
   * 查询专用用户
   */
  public function getIpClient() {
    //SELECT puid FROM `business_ip_field_data` where puid<>0 group by puid 
    $query = $this->database->select('business_ip_field_data','bip');
    $query->innerJoin('user_client_data', 'c', 'bip.puid=c.uid');
    $query->fields('bip',array('puid'));
    $query->fields('c',array('client_name', 'corporate_name'));
		return $query->execute()->fetchAll();
  }

  /**
   * 查询IP创建者
   */
  public function getIpCreator($ip_table) {
    $query = $this->database->select($ip_table,'ip');
    $query->innerJoin('user_employee_data', 'e', 'ip.uid=e.uid');
    $query->fields('ip',array('uid'));
    $query->fields('e',array('employee_name'));
    return $query->execute()->fetchAll();
  }

  /**
   * 写入申请记录
   */
  public function saveApplyRecord($fields) {
    $rs = $this->database->insert('bip_apply')
      ->fields($fields)
      ->execute();
    return $rs;
  }

  /**
   * 得到所有的申请数据
   */
  public function getAllApply($condition = array()) {
    $query = $this->database->select('bip_apply','t')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->fields('t');
    if(!empty($condition)) {

    }
    $query->limit(PER_PAGE_COUNT)
      ->orderBy('id', 'DESC');
     return $query->execute()->fetchAll();
  }

  /**
   * 得到指定的一条申请记录
   */
  public function getApplyById($id) {
    $query = $this->database->select('bip_apply','t');
    $query->fields('t')
      ->condition('id', $id);
    return $query->execute()->fetchObject();
  }

  /**
   * 业务IP入库申请审核
   */
  public function auditApply($fields, $id) {
    $rs = $this->database->update('bip_apply')
      ->fields($fields)
      ->condition('id', $id)
      ->execute();
    return $rs;
  }

  /**
   * 保存业务IP下架申请
   */
  public function saveCancleApply($fields) {
    $rs = $this->database->insert('bip_apply_cancle')
      ->fields($fields)
      ->execute();
    return $rs;
  }

  /**
   * 得到所有的业务IP下架申请数据
   */
  public function getAllCancleApply($condition = array()) {
    $query = $this->database->select('bip_apply_cancle','t')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender'); 
    $query->fields('t');
    if(!empty($condition)) {

    }
    $query->limit(PER_PAGE_COUNT)
      ->orderBy('audit_statue');
     return $query->execute()->fetchAll();    
  }
  /**
   * 得到指定的一条下架申请
   */
  public function getCancleApplyById($id) {
    $query = $this->database->select('bip_apply_cancle','t');
    $query->fields('t')
      ->condition('id', $id);
    return $query->execute()->fetchObject();
  }
  /**
   * 业务IP下架申请审核
   */
  public function auditCancleApply($fields, $id) {
    $rs = $this->database->update('bip_apply_cancle')
      ->fields($fields)
      ->condition('id', $id)
      ->execute();
    return $rs;
  }

  /**
   * 根据IP得到此IP的编号
   */
  public function getIpidByIp($ip) {
    $query = $this->database->select('business_ip_field_data','t');
    $query->fields('t',array('id'))
      ->condition('ip', $ip);
    return $query->execute()->fetchField();
  }

  /**
   * 查询ip_group表数据
   */
  public function loadIpGroup($conditions = array()) {
    $query = $this->database->select('ip_group', 't')
      ->fields('t');
    foreach($conditions as $key => $condition) {
      if(is_array($condition)) {
        if($condition['op'] == 'like') {
          $query->condition($key,  $condition['value'] . '%', 'Like');
        } else {
           $query->condition($key,  $condition['value'], $condition['op']);
        }
      } else {
        $query->condition($key, $condition);
      }
    }
    return $query->execute()
      ->fetchAll();
  }

  public function addIpGroup(array $values) {
    return $this->database->insert('ip_group')
      ->fields($values)
      ->execute();
  }

  public function updateIpGroup(array $values, $id) {
     return $this->database->update('ip_group')
       ->fields($values)
       ->condition('gid', $id)
       ->execute();
  }

  public function deleteIpGroup($gid) {
    $this->database->delete('ip_group')
       ->condition('gid', $gid)
       ->execute();
  }
}

