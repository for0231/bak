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
class WorkSheetAllotBuilde {
  
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
    $header['allo_num'] = '分配系数';
    return $header;
  }

  protected function buildRow() {
    $statistic = \Drupal::service('worksheet.statistic');
    $begin = empty($_SESSION['worksheet_statistic']['begin'])?0:strtotime($_SESSION['worksheet_statistic']['begin']);
    $end = empty($_SESSION['worksheet_statistic']['end'])?0:strtotime($_SESSION['worksheet_statistic']['end']);
    $data = $statistic->getCountAllo($begin,$end);
    $rows = array();
    if(!empty($data)){
      foreach($data as $item){
        $tmp[$item->allot_uid][] = $item->xishu;
      }
      foreach($tmp as $key=>$item){
        $tmp[$key] = array_sum($item);
      }
      foreach($tmp as $key=>$value){
        $tmp = array(
          'name' => entity_load('user',$key)->label(),
          'allo_num'=>round($value,2),
        );
        $rows[] = $tmp;
      }
    }
    return $rows;
  }
  
  /**
   * 列表
   */
  public function build() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\worksheet\Form\WorkSheetCommentFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    return $build;
  }
}
