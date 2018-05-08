<?php

/**
 * @file
 * Contains file
 * \Drupal\resourcepool\WorkSheetListBuilde.
 */

namespace Drupal\resourcepool;

use Drupal\Core\Url;
/**
 *
 */
class WorkSheetVlanBuilde {
  protected $formBuilder;
  public function __construct(){
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  protected function buildHeader() {
    $header['ye_vlan']  = '已使用的业务vlan';
    $header['num'] = '数量';
    return $header;
  }

  protected function buildRow() {
    $room = empty($_SESSION['worksheet_statistic']['room'])?0:$_SESSION['worksheet_statistic']['room'];
    $statistic = \Drupal::service('resourcepool.dbservice');
    $tmp = $statistic->getVlanNum($room);
    $rows = array();
    foreach($tmp as $item){
      if ($item->num==0){
        $ye_vlan ='以下是第二网卡vlan的统计数量';
        $num='以下是第二网卡vlan的统计数量';
      }else{
        $num =$item->num;
        $ye_vlan=$item->ye_vlan;
      }
      $tmp = array(
        'ye_vlan' => $ye_vlan,
        'num'=> $num,
      );
      $rows[] = $tmp;
    }
    
    return $rows;
  }
  
  /**
   * 列表
   */
  public function build() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\WorkSheetRackVlanFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    return $build;
  }
}
