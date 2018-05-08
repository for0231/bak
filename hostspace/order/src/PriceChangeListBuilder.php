<?php

/**
 * @file
 * Contains \Drupal\order\PriceChangeListBuilder.
 */

namespace Drupal\order;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PriceChangeListBuilder {
  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
			$container->get('form_builder')
    );
  }

  /**
   * 加载数据
   */
  private function load() {
    $header = $this->createHeader();
    $condition = $this->createFilter();
    $order_service = \Drupal::service('order.orderservice');
    $data = $order_service->getPriceChangeData($condition, $header);
    return $data;
  }

  /**
   * 创建筛选条件
   *
   * @return $condition array
   *    组装好的条件数组
   *
   */
  private function createFilter() {
    $condition = array();
    if(!empty($_SESSION['apply_filter'])) {
      if(!empty($_SESSION['apply_filter']['oid'])) {
        $condition['order_code'] = $_SESSION['apply_filter']['oid'];
      }
      if(!empty($_SESSION['apply_filter']['status'])) {
        $condition['status'] = $_SESSION['apply_filter']['status'];
      }
      if(!empty($_SESSION['apply_filter']['ask_uid'])) {
        $condition['ask_uid'] = $_SESSION['apply_filter']['ask_uid'];
      }
      if(!empty($_SESSION['apply_filter']['aduit_uid'])) {
        $condition['audit_uid'] = $_SESSION['apply_filter']['aduit_uid'];
      }
    }

    return $condition;
  }

  /**
   * 创建表头
   */
  private function createHeader() {
    $header['order_code'] = array(
      'data' => t('Orde Code'),
      'field' => 'order_id',
      'specifier' => 'order_id'
    );
    $header['client'] = t('Client');
    $header['order_price'] = t('Order price');
    $header['change_price'] = t('Apply change price');
    $header['ask_uid'] = t('Applicant');
    $header['created'] = array(
      'data' => t('Application Period'),
      'field' => 'created',
      'specifier' => 'created'
    );
    $header['audit_uid'] = t('Auditor');
    $header['audit_stamp'] = array(
      'data' => t('Audit time'),
      'field' => 'audit_stamp',
      'specifier' => 'audit_stamped'
    );
    $header['status'] = array(
      'data' => t('Status'),
      'field' => 'status',
      'specifier' => 'status'
    );
    $header['operations'] = t('Operations');
    return $header ;
  }


  /**
   * 创建行
   *
   * @param $data array
   *  要显示的数据
   *
   * @param $rows array
   *   构建好的行数据
   */
  private function createRow($price_change) {
    $row['order_code'] = $price_change->order_code;

    $member_service = \Drupal::service('member.memberservice');
    $client_obj = $member_service->queryDataFromDB('client',$price_change->client_id);
    // 为了能够适应非管，非客人员下单后在列表页面出错而作出的下列处理
    if ($client_obj instanceof \Drupal\user\Entity\User) {
      $client = $client_obj->get('name')->value;
    } else {
      $client = $client_obj->corporate_name ? $client_obj->corporate_name : $client_obj->client_name;
    }
    $row['client'] = $client;

    $row['price'] = $price_change->order_price;
    $row['change_price'] = $price_change->change_price;

    $ask = $member_service->queryDataFromDB('employee', $price_change->ask_uid);
    $row['ask_uid'] = $ask ? $ask->employee_name : 'admin';
    $row['create'] = date('Y-m-d H:i:s', $price_change->created);

    if($price_change->audit_uid > 0) {
      $audit = $member_service->queryDataFromDB('employee', $price_change->audit_uid);
      $row['audit_uid'] = $audit ? $audit->employee_name : 'admin';
      $row['audit_stamp'] = date('Y-m-d H:i:s', $price_change->audit_stamp);
    } else {
      $row['audit_uid'] = '';
      $row['audit_stamp'] = '';
    }
    $row['status'] = changePriceStatus()[$price_change->status];

    $row['operations']['data'] = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($price_change->id, $price_change->status)
    );
    return $row;
  }


  /**
   * 构建操作的链接数组
   *
   * @param $editUrl
   *   同意申请所指向的routing_name
   *
   * @param $deleteUal
   *   拒绝申请所指向的routing_name
   *
   * @return 组装好的Operations数组
   */
  private function getOperations($price_change_id, $status) {
    $op = array();
    if($status == 1) {
      $op['audit'] = array(
        'title' => t('Audit'),
        'url' => new Url('admin.change_price.audit', array('price_change_id' => $price_change_id))
      );
    } 
    $op['detail'] = array(
      'title' => t('Detail'),
      'url' => new Url('admin.change_price.detail', array('price_change_id' => $price_change_id))
    );
    return $op;
  } 

  /**
   * 改价列表
   */
  public function render(){
    $build['filter'] = $this->formBuilder->getForm('Drupal\order\Form\PriceChangeFilterForm');
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->createHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $item) {
      $build['list']['#rows'][$item->id] = $this->createRow($item);
    }
    $build['price_change_pager'] = array('#type' => 'pager');
    return $build;
  }
}

