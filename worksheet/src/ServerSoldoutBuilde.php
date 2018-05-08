<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\ServerSoldoutBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class ServerSoldoutBuilde {
  protected $formBuilder;
  public function __construct(){
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  protected function buildHeader() {
    $header['subdivision']  = '所属子部门';
    $header['name']  = '姓名';
    $header['num']  = '上架数量';
    $header['ye_num']  = '晚上上架数量';
    $header['work']  = '工作量';
    $header['time']  = '上架时间';
    $header['spend_time']  = '总耗时';
    return $header;
  }

  protected function buildRow() {
    $rows = array();
    $begin =empty($_SESSION['worksheet_statistic']['begin'])?0:trim(strtotime($_SESSION['worksheet_statistic']['begin']));
    $end = empty($_SESSION['worksheet_statistic']['end'])?0:trim(strtotime($_SESSION['worksheet_statistic']['end'])+ 24*3600);
    $subdivision = empty($_SESSION['worksheet_statistic']['subdivision']) ? 'all': $_SESSION['worksheet_statistic']['subdivision'];
    if($subdivision=='all'){
      $subdivision="'生产组','技术组'";
    }elseif($subdivision=='生产组'){
      $subdivision="'生产组'";
    }elseif($subdivision=='技术组'){
      $subdivision="'技术组'";
    }
    $statistic = \Drupal::service('worksheet.statistic');
    $entity_type = 'work_sheet_frame';
    $type='140';
    $data = $statistic->getServerPutaway2($entity_type,$begin,$end,$type,$subdivision);
    $rows = array();
    if(!empty($data)){
      $all_day =0;$num=0;$ye_num=0;$work=0;$time=0;$spend_time=0;
      foreach($data as $item){
        $all_day += $item->all_day;
        $num += $item->all_day;
        $ye_num += $item->night;
        $work += $item->all_day*0.3;
        $time += empty($item->usetimess)?0:$item->usetimess/$item->all_day;
        $spend_time += $item->usetimess;
        
        $subdivision = $statistic->get_subdivision($item->last_uid);
        $realname = $statistic->get_realname($item->last_uid);
        $tmp = array(
          'subdivision'=>!empty($subdivision)?$subdivision[0]->subdivision_value:'',
          'name'=>!empty($realname)?$realname[0]->real_name_value:'',
          'num'=>$item->all_day,
          'ye_num'=>empty($item->night)?0:$item->night,
          'work'=>$item->all_day*0.3,
          'time'=>empty($item->usetimess)?null:round(($item->usetimess/$item->all_day)/60,1),
          'spend_time'=>round($item->usetimess/60,1)
        );
        $rows[] = $tmp;
      }
      array_push($rows,array('','合计',$num,$ye_num,$work,round(($spend_time/60)/$num,1),round($spend_time/60,1)));
      array_push($rows,array('工单占比','白天',round(($all_day-$ye_num)/$all_day*100, 2)."%",'夜晚',round($ye_num/$all_day*100, 2)."%"));
    }
    return $rows;
  }
  
  /**
   * 列表
   */
  public function build() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\worksheet\Form\WorkSheetOperationsFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    return $build;
  }
}
