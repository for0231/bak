<?php

namespace Drupal\phpexcel_alter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 *
 */
class PhpexcelAlterController extends ControllerBase {

  /**
   * @description 导出依赖于paypro的采购流程数据。
   */
  public function exportPayproWorkflowData() {
    module_load_include('inc', 'phpexcel');
    $wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://download/');

    $filename = 'paypro--download-' . uniqid() . '.xlsx';
    $filepath = $wrapper->realpath() . '/' . $filename;

    $export_headers = $this->getPayproStatisFilterPartsHeaders();
    $export_datas = $this->getPayproStatisFilterPartsFields();

    $result = phpexcel_export($export_headers, $export_datas, $filepath);

    if ($result === PHPEXCEL_SUCCESS) {
      $file = file_save_data(
        file_get_contents($filepath),
        "public://download/$filename",
        FILE_EXISTS_REPLACE
      );

      $file->status = 0;

      \Drupal::service('file.usage')->add($file, 'phpexcel_alter', 'node', 1);

      $headers = $this->getExportPageHeaders($filename);

      return new BinaryFileResponse($file->uri->getString(), 200, $headers);

    }
    else {
      error_log(print_r('exportPayproWorkflowData error', 1));
    }
    return new BinaryFileResponse([], 200, $headers);
  }

  /**
   *
   */
  private function getExportPageHeaders($filename) {
    return [
      'Content-type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
    ];
  }

  /**
   * @description 获取配件导出表单标题.
   */
  private function getPayproStatisFilterPartsHeaders() {
    return [
      'ID',
      '采购物品名称',
      '采购物品类型',
      '需求数量',
      '币种',
      '单价',
      '需求单号',
      '需求单申请日期',
      '需求日期',
      '需求用途',
      '需求备注',
      '采购单号',
      '采购单名称',
      '币种',
      '采购单金额',
      '采购单申请人',
      '采购单申请日期',
      '采购单备注',
      '预计交付日期',
      '付款单号',
      '付款单名称',
      '币种',
      '付款单金额',
      '付款单申请人',
      '付款单申请日期',
      '付款单备注',
      '支付单号',
      '支付单名称',
      '币种',
      '支付单金额',
      '支付单申请人',
      '支付单申请日期',
      '支付单备注',
      // '物流公司',.
      '物流单',
      '创建人',
      '创建时间',
    ];
  }

  /**
   * @descripiton 获取配件导出表单字段数据.
   */
  private function getPayproStatisFilterPartsFields() {
    $storage = \Drupal::entityManager()->getStorage('part');
    $storage_query = $storage->getQuery();
    $storage_query->condition('save_status', 1);
    $storage_query->condition('id', 0, '<>');
    if (!empty($_SESSION['paypro_statis_filter'])) {
      if (!empty($_SESSION['paypro_statis_filter']['begin'])) {
        $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['begin']), '>');
      }
      if (!empty($_SESSION['paypro_statis_filter']['end'])) {
        $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['end']), '<');
      }
    }
    $ids = $storage_query->execute();
    $entities = $storage->loadMultiple($ids);

    $result = [];

    // 1个配件可能被多个付款单引用.
    // 1个配件可能被多个支付单引用.
    foreach ($entities as $entity) {
      $entity_requirement = entity_load('requirement', $entity->get('rno')->value);
      $entity_purchase = entity_load('purchase', $entity->get('cno')->value);
      $entity_paypre = entity_load('paypre', $entity->get('fno')->value);
      $entity_paypro = entity_load('paypro', $entity->get('pno')->value);
      $fids = \Drupal::service('paypre.paypreservice')->getPaypresByPartId($entity->id());
      error_log(print_r($fids, 1));
      $result[] = [
        $entity->id(),
        $entity->label(),
        $entity->get('parttype')->value,
        $entity->get('num')->value,
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('ftype')->target_id,
        $entity->get('unitprice')->value,
        $entity->get('rno')->value == 0 ? "-" : $entity_requirement->label(),
        $entity->get('rno')->value == 0 ? "-" : date('Y-m-d', $entity_requirement->get('created')->value),
        date('Y-m-d', $entity->get('requiredate')->value),
        $entity->get('rno')->value == 0 ? "-" : strip_tags($entity_requirement->get('purpose')->value),
        $entity->get('rno')->value == 0 ? "-" : strip_tags($entity_requirement->get('description')->value),
        $entity->get('cno')->value == 0 ? "-" : $entity_purchase->label(),
        $entity->get('cno')->value == 0 ? "-" : $entity_purchase->get('title')->value,
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('ftype')->target_id,
        $entity->get('cno')->value == 0 ? "-" : \Drupal::service('purchase.purchaseservice')->getPurchaseAmountPrice($entity_purchase),
        $entity->get('cno')->value == 0 ? "-" : $entity_purchase->get('uid')->entity->get('realname')->value,
        $entity->get('cno')->value == 0 ? "-" : date('Y-m-d', $entity_purchase->get('created')->value),
        $entity->get('cno')->value == 0 ? "-" : strip_tags($entity_purchase->get('description')->value),
        $entity->get('plandate')->value == 0 ? "-" : date('Y-m-d', $entity->get('plandate')->value),
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->label(),
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('title')->value,
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('ftype')->target_id,
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('amount')->value,
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('uid')->entity->get('realname')->value,
        $entity->get('fno')->value == 0 ? "-" : date('Y-m-d', $entity_paypre->get('created')->value),
        $entity->get('fno')->value == 0 ? "-" : strip_tags($entity_paypre->get('description')->value),
        $entity->get('pno')->value == 0 ? "-" : $entity_paypro->label(),
        $entity->get('pno')->value == 0 ? "-" : $entity_paypro->get('title')->value,
        $entity->get('fno')->value == 0 ? "-" : $entity_paypre->get('ftype')->target_id,
        $entity->get('pno')->value == 0 ? "-" : $entity_paypro->get('amount')->value,
        $entity->get('pno')->value == 0 ? "-" : $entity_paypro->get('uid')->entity->get('realname')->value,
        $entity->get('pno')->value == 0 ? "-" : date('Y-m-d', $entity_paypro->get('created')->value),
        $entity->get('pno')->value == 0 ? "-" : strip_tags($entity_paypro->get('description')->value),
        empty($entity->get('ship_supply_no')->value) ? "-" : $entity->get('ship_supply_no')->value,
        $entity->get('uid')->entity->get('realname')->value,
        date('Y-m-d', $entity->get('created')->value),
      ];
    }

    return $result;
  }

}
