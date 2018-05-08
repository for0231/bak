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
class WorkSheetCommentBuilde {
  
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
    $header['count']  = '评论总数';
    $header['if_question_count'] = '工单无问题个数';
    $header['if_question_percent'] = '工单无问题百分比';
    $header['if_right_count'] = '定位分类正确个数';
    $header['if_right_percent'] = '定位分类正确百分比';
    $header['if_deal_count'] = '正确处理个数';
    $header['if_deal_percent'] = '正确处理百分比';
    $header['if_quality_count'] = '优质工单个数';
    $header['if_quality_percent'] = '优质工单百分比';
    $header['performance'] = '评论得分';
    return $header;
  }

  protected function buildRow() {
    $statistic = \Drupal::service('worksheet.statistic');
    $begin = empty($_SESSION['worksheet_statistic']['begin'])?0:strtotime($_SESSION['worksheet_statistic']['begin']);
    $end = empty($_SESSION['worksheet_statistic']['end'])?0:strtotime($_SESSION['worksheet_statistic']['end']);
    $data = $statistic->getCountComment($begin,$end);
    $rows = array();
    foreach($data as $item){
      $tmp = array(
        'comment_uid' => entity_load('user',$item->comment_uid)->label(),
        'count' => $item->count,
        'if_question_count' => $item->if_question_count,
        'if_question_percent'=>round(($item->if_question_count/$item->count)*100,2).'%',
        'if_right_count' => $item->if_right_count,
        'if_right_percent'=>round(($item->if_right_count/$item->count)*100,2).'%',
        'if_deal_count' => $item->if_deal_count,
        'if_deal_percent'=>round(($item->if_deal_count/$item->count)*100,2).'%',
        'if_quality_count' => $item->if_quality_count,
        'if_quality_percent'=>round(($item->if_quality_count/$item->count)*100,2).'%',
        'performance'=>$item->performance,
      );
      $rows[] = $tmp;
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
