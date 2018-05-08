<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\WorkSheetOptionsBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 * 工单选项数据
 */
class WorkSheetOptionsBuilde {
  //选项数据服务类
  protected $option_service;
  
  public function __construct() {
    $this->option_service = \Drupal::service('worksheet.option');
  }
  
  protected function buildHeader() {
    $header['type']  = '类型';
    $header['options'] = '选项';
    $header['op'] = '操作';
    return $header;
  }

  protected function buildRow() {
    $rows = array();
    
    return $rows;
  }
  
  /**
   * 列表
   */
  public function build() {
    
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    return $build;
  }
}
