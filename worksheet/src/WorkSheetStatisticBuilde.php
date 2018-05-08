<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetListBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class WorkSheetStatisticBuilde {
  
  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;
  
  public function __construct() {
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  
  protected function buildHeader() {
    $header['name']  = '姓名';
    $header['buildWorkin'] = '新建工单';
    $header['count'] = '完成工单';
    $header['join'] = '转交他人';
    $header['exception'] = '质量异常';
    $header['exceAccept'] = '异常接受';
    $header['checkException'] = '审核异常';
    $header['timeException'] = '超时异常';
    return $header;
  }

  protected function buildRow() {
    $statistic = \Drupal::service('worksheet.statistic');
    $data = $statistic->getCount();
    $rows = array();
    foreach($data as $item){
      $tmp = array(
        'uid' => entity_load('user',$item->uid)->label(),
        'buildWorkin' => $item->buildWorkin,
        'finish' => $item->finish,
        'joins' => $item->joins,
        'exception' => $item->exception,
        'exceAccept' => $item->exceAccept,
        'checkException' => $item->checkException,
        'timeException' => $item->timeException
      );
      $rows[] = $tmp;
    }
    return $rows;
  }
  
  /**
   * 列表
   */
  public function build() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\worksheet\Form\WorkSheetStatisticFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    return $build;
  }
}
