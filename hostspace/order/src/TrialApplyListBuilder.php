<?php
/**
 * @file
 * Contains \Drupal\order\TrialApplyListBuilder.
 */

namespace Drupal\order;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrialApplyListBuilder {
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
    $data = $order_service->getTrialData($condition, $header);
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
    if(!empty($_SESSION['trial_filter'])) {
      if(!empty($_SESSION['trial_filter']['oid'])) {
        $condition['order_code'] = $_SESSION['trial_filter']['oid'];
      }
      if(!empty($_SESSION['trial_filter']['product'])) {
        $condition['product_id'] = $_SESSION['trial_filter']['product'];
      }
      if(!empty($_SESSION['trial_filter']['status'])) {
        $condition['status'] = $_SESSION['trial_filter']['status'];
      }
      if(!empty($_SESSION['trial_filter']['ask_uid'])) {
        $condition['ask_uid'] = $_SESSION['trial_filter']['ask_uid'];
      }
      if(!empty($_SESSION['trial_filter']['aduit_uid'])) {
        $condition['audit_uid'] = $_SESSION['trial_filter']['aduit_uid'];
      }
    }

    return $condition;
  }

  /**
   * 创建表头
   */
  private function createHeader() {
    $header['order_code'] = array(
      'data' => t('Order code'),
      'field' => 'order_id',
      'specifier' => 'order_id'
    );
    $header['client'] = t('Client');
    $header['product'] = t('Trial Product');
    $header['apply_user'] = t('Applicant');
    $header['apply_date'] = array(
      'data' => t('Application Period'),
      'sort' => 'desc',
      'field' => 'audit_date',
      'specifier' => 'audit_date'
    );
    $header['audit_uid'] = t('Auditor');
    $header['audit_date'] = array(
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
    return $header;
  }

  /**
   * 构建一行数据
   */
  private function buildRow($trial) {
    $row['order_code'] = $trial->order_code;

    $member_service = \Drupal::service('member.memberservice');
    $client_obj = $member_service->queryDataFromDB('client',$trial->client_id);
    // 为了能够适应非管，非客人员下单后在列表页面出错而作出的下列处理
    if ($client_obj instanceof \Drupal\user\Entity\User) {
      $client = $client_obj->get('name')->value;
    } else {
      $client = $client_obj->corporate_name ? $client_obj->corporate_name : $client_obj->client_name;
    }
    $row['client'] = $client;

    $order_product = \Drupal::service('order.product')->getProductById($trial->order_product_id);
    $row['product'] = $order_product->product_name;

    $ask = $member_service->queryDataFromDB('employee', $trial->ask_uid);
    $row['apply_user'] = $ask ? $ask->employee_name : 'admin';
    $row['apply_date'] = date('Y-m-d H:i:s',$trial->ask_date);
    if($trial->audit_uid > 0) {
      $audit = $member_service->queryDataFromDB('employee', $trial->audit_uid);
      $row['audit_uid'] = $audit ? $audit->employee_name : 'admin';
      $row['audit_date'] = date('Y-m-d H:i:s',$trial->audit_date);
    } else {
      $row['audit_uid'] = '';
      $row['audit_date'] = '';
    }
    $row['status'] = trialServerStatus()[$trial->status];
    $row['operations']['data'] = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($trial)
    );
    return $row;
  }

  /**
   * 构建操作
   */
  private function getOperations($trial) {
    $op = array();
    if($trial->status == 1) {
      $op['audit'] = array(
        'title' => t('Audit'),
        'url' => new Url('admin.order.trial.audit', array('trial_id' => $trial->id))
      );
    }
    $op['detail'] = array(
      'title' => t('Detail'),
      'url' => new Url('admin.order.trial.detail', array('trial_id' => $trial->id))
    );
    return $op;
  }

  /**
   * 显示列表
   */
  public function render(){
    $build['filter'] = $this->formBuilder->getForm('Drupal\order\Form\TrialFilterForm');
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->createHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $trial) {
      if($row = $this->buildRow($trial)) {
        $build['list']['#rows'][$trial->id] = $row;
      }
    }
    $build['trial_pager'] = array('#type' => 'pager');
    return $build;
  }
}

