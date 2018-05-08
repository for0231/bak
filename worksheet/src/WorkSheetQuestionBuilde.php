<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetQuestionBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class WorkSheetQuestionBuilde{
  
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
    $header['problem_types']  = '问题父级分类名称';
    $header['problem_types_child'] = '问题子级分类名称';
    $header['count'] = '问题个数';
    return $header;
  }

  protected function buildRow() {
    $begin =empty($_SESSION['worksheet_statistic']['begin'])?0:trim(strtotime($_SESSION['worksheet_statistic']['begin']));
    $end = empty($_SESSION['worksheet_statistic']['end'])?0:trim(strtotime($_SESSION['worksheet_statistic']['end']));
    $statistic = \Drupal::service('worksheet.statistic');
    $data = $statistic->getCountQuestion($begin,$end);
    $rows = array();
    foreach($data as $item){
      $tmp = array(
        'problem_types' => empty($item->problem_types)?'无': $statistic->get_problem_name($item->problem_types),
        'problem_types_child' =>empty($item->problem_types_child)?'无':$statistic->get_problem_name($item->problem_types_child),
        'count' => $item->count,
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
