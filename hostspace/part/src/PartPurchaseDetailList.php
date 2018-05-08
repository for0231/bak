<?php
/**
 * @file 
 * Contains \Drupal\part\PartPurchaseDetailList.
 */

namespace Drupal\part;

use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PartPurchaseDetailList {
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
    $part_service = \Drupal::service('part.partservice');
    $data = $part_service->getUrchaseDetail($condition, $header);
    return $data;
  }


  /*
   * 创建筛选条件
   */
  private function createFilter() {
    $conditions = array();
    if(!empty($_SESSION['purchase_detail_filter'])) {
      if(!empty($_SESSION['purchase_detail_filter']['keyword'])) {
         $keyword = $_SESSION['purchase_detail_filter']['keyword'];
         $conditions['keyword'] = $keyword; 
      }
      if(!empty($_SESSION['purchase_detail_filter']['start'])) {
        $start = strtotime($_SESSION['purchase_detail_filter']['start']);
        $conditions['start'] = array('field' => 'd.created' , 'op' => '>=', 'value' =>$start);
      }
      if(!empty($_SESSION['purchase_detail_filter']['expire'])) {
        $expire = strtotime($_SESSION['purchase_detail_filter']['expire']);
        $expire_end = strtotime('+1 day', $expire);
        $conditions['expire'] = array('field' => 'd.created' , 'op' => '<','value' => $expire_end);
      }
    }
    return $conditions;
    
  }

  /*
   * 创建表头
   */
  private function createHeader() {
    $header['type'] = array(
      'data' => t('Part type'),
      'field' => 'type',
      'specifier' => 'type'
    );
    $header['brand'] = t('Brand');
    $header['standard'] = t('Standard');
    $header['model'] = t('Model');
    $header['interface'] = t('Interface');
    $header['capacity'] = t('Capacity');
    $header['number'] = t('Number');
    $header['created'] = array(
      'data' => t('Created'),
      'field' => 'created',
      'specifier' => 'created'
    );
    return $header;
  }

  /**
   * 创建行
   */
  private function createRow($purchase) {
    $type = $purchase->type;
    $row['type'] = part_entity_type_name($type);
    $row['brand'] = $purchase->brand;
    $row['standard'] = $purchase->standard;
    $row['model'] = $purchase->model;
    $ppid = $purchase->ppid;
    if($type == 'part_harddisc') {
      $entity = entity_load('part_harddisc', $ppid);
      $term_interface = $entity->get('interface_type')->entity;
      $row['interface'] = $term_interface ? $term_interface->label() : '';
      $term_capacity = $entity->get('hard_disc_capacity')->entity;
      $row['capacity'] = $term_capacity ? $term_capacity->label() : '';
    } else if ($type == 'part_memory') {
      $entity = entity_load('part_memory', $ppid);
      $term_interface = $entity->get('memory_type')->entity;
      $row['interface'] = $term_interface ? $term_interface->label() : '';
      $term_capacity = $entity->get('capacity')->entity;
      $row['capacity'] = $term_capacity ? $term_capacity->label() : '';
    } else {
       $row['interface'] = '';
       $row['capacity'] = '';
    }
    $row['number'] = $purchase->stock;
    $row['create'] = date('Y-m-d H:i:s', $purchase->created);
    return $row;
  }

  /**
   * 采购列表
   */
  public function render(){
    $build['filter'] = $this->formBuilder->getForm('Drupal\part\Form\PurchaseDetailFilterForm');
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->createHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $item) {
      $build['list']['#rows'][$item->did] = $this->createRow($item);
    }
    $build['purchase_detail_pager'] = array('#type' => 'pager');
    return $build;
  }
}
