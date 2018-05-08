<?php
/**
 * @file
 * 操作合同模块数据
 */
namespace Drupal\contract;

use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ContractService {

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
   *  添加资金计划
   *
   * @param $fields
   *   字段数据
   */
  public function saveFundsPlan($fields) {
    $rs = $this->database->insert('host_contract_funds_plan')
      ->fields($fields)
      ->execute();
    return $rs;
  }

  /**
   * 查询指定合同的资金计划
   *
   * @param $contract
   *   合同编号
   */
  public function getPlaneByContract($contract, $header) {
    $query = $this->database->select('host_contract_funds_plan','t')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender'); 
    $query->fields('t')
      ->condition('contract', $contract);    
    $query->limit(5)
      ->orderByHeader($header)
      ->orderBy('status');
     return $query->execute()->fetchAll();
  }

  /**
   * 删除资金计划 
   * @param $id
   *  资金计划的编号
   */
  public function deletePlanById($id) {
    $result = $this->database->delete('host_contract_funds_plan')
      ->condition('id', $id)
      ->execute();
    return $result;
  }
  /**
   * 得到合同的收/付款总额
   * @param type  1收款 2 付款
   * @param $contract_id 合同编号
   */
  public function getAmount($type, $contract_id, $status=null) {
    $sql = 'select sum(amount) cash from host_contract_funds_plan where type=:type and contract=:contractId';
    $arr = array(':type' => $type, ':contractId' => $contract_id);
    if($status != null) {
      $sql .= ' and status=:status';
      $arr += array(':status' => $status);
    }
    $statemtent = $this->database->query($sql, $arr);
    $rs = $statemtent->fetchField();
    return  $rs ? $rs : 0;
  }

  /**
   * 查询指定合同的资金计划
   *
   * @param $contract
   *   合同编号
   */
  public function getContractAllPlan($contract) {
    $query = $this->database->select('host_contract_funds_plan','t')
      ->fields('t')
      ->condition('contract', $contract);    
     return $query->execute()->fetchAll();
  }
  /**
   * 修改资金计划的执行状态
   * @param $id
   *   资金计划编号
   *
   * @param $fields array
   *  要修改的字段数组
   */
  public function modifyFunds($id, $fields=array()) {
    return $this->database->update('host_contract_funds_plan')
      ->fields($fields)
      ->condition('id', $id)
      ->execute(); 
  }

  /**
   * 得到合同的收/付款总额
   * @param $id 资金计划编号
   */
  public function getplanById($id) {
    $query = $this->database->select('host_contract_funds_plan','t')
      ->fields('t')
      ->condition('id', $id);
    return $query->execute()->fetchObject();
  }

  /**
   *  添加交货计划
   *
   * @param $fields
   *   字段数据
   */
  public function saveGoodsPlan($fields) {
    $rs = $this->database->insert('host_contract_delivery_plan')
      ->fields($fields)
      ->execute();
    return $rs;
  }

  /**
   * 查询指定合同的交货计划
   *
   * @param $contract
   *   合同编号
   */
  public function getAllGoodsPlanByContract($contract) {
    $query = $this->database->select('host_contract_delivery_plan','t')
      ->fields('t')
      ->condition('contract', $contract);    
     return $query->execute()->fetchAll();
  }

  /**
   * 删除交货计划 
   * @param $id
   *  计划的编号
   */
  public function deleteGoodsPlanById($id) {
    $result = $this->database->delete('host_contract_delivery_plan')
      ->condition('id', $id)
      ->execute();
    return $result;
  }

  /**
   * 修改交货计划的执行状态
   * @param $id
   *   资金计划编号
   *
   * @param $fields array
   *  要修改的字段数组
   */
  public function modifyGoods($id, $fields=array()) {
    return $this->database->update('host_contract_delivery_plan')
      ->fields($fields)
      ->condition('id', $id)
      ->execute(); 
  }

    /**
   * 查询所有合同的资金计划
   * @param $type 资金性质
   *
   */
  public function getAllPlanByType($type, $header, $condition=array()) {
    $query = $this->database->select('host_contract_funds_plan','t')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender'); 
    $query->fields('t')
      ->condition('type', $type);   
    if(!empty($condition)) {
      foreach($condition as $con) {
        $query->condition($con['field'], $con['value'], $con['op']);
      }
    }
    $query->limit(20)
      ->orderByHeader($header)
      ->orderBy('status'); 
     return $query->execute()->fetchAll();
  }
}
