<?php
/**
 * @file
 * Contains \Drupal\order\TrialApplyListBuilder.
 */

namespace Drupal\order;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class StopServerListBuilder {

  protected $hostclient_service;

  public function __construct() {
    $this->hostclient_service = \Drupal::service('hostclient.serverservice');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance() {
    return new static();
  }

  /**
   * 数据查询
   */
  public function load() {
    return $this->hostclient_service->getStopListDate(array('status' => 0));
  }

  /**
   * 构建头
   */
  public function buildHeader() {
    $header['ipm'] = t('Management ip');
    $header['product_name'] = t('Product name');
    $header['client'] = t('Client');
    $header['server_status'] = t('Server status');
    $header['stop_time'] = t('Stop time');
    $header['can_storage'] = t('Can storage');
    $header['storage_time'] = t('Can storage time');
    $header['operations'] = t('Operations');
    return $header;
  }

  /**
   * 构建行
   */
  public function buildRow($stop_info) {
    $hostclient = entity_load('hostclient', $stop_info->hostclient_id);
    if(empty($hostclient)) {
      return array();
    }
    $row['ipm'] = $hostclient->getObject('ipm_id')->label();
    $row['product_name'] = $hostclient->getObject('product_id')->label();
    $row['client'] = $hostclient->getObject('client_uid')->label();
    $row['server_status'] = hostClientStatus()[$hostclient->getSimplevalue('status')];
    $row['stop_time'] = format_date($stop_info->apply_date, 'custom' ,'Y-m-d H:i:s');
    $row['can_storage'] = REQUEST_TIME > $stop_info->storage_date ? t('Yes') : t('No');
    $row['storage_time'] = format_date($stop_info->storage_date, 'custom' ,'Y-m-d H:i:s');
    $row['operations']['data'] = array('#type' => 'operations', '#links' => $this->getOperations($stop_info));
    return $row;
  }

    /**
   * 获取操作
   */
  private function getOperations($stop_info) {
    $operations['storage'] = array(
      'title' =>t('Storage'),
      'url' => new Url('admin.hostclient.stop.storage', array('stop_id' => $stop_info->sid))
    );

    $operations['recover'] = array(
      'title' => t('Recover'),
      'url' => new Url('admin.hostclient.stop.recover', array('stop_id' => $stop_info->sid))
    );
    return $operations;
  }

  public function render() {
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $stop) {
      if($row = $this->buildRow($stop)) {
        $build['list']['#rows'][$stop->sid] = $row;
      }
    }
    $build['stop_pager'] = array('#type' => 'pager');
    return $build;
  }
}

