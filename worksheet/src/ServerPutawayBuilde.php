<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\ServerPutawayBuilde
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class ServerPutawayBuilde {
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
    $header['win03']  = 'win03';
    $header['win08']  = 'win08';
    $header['win2012']  = 'win2012';
    $header['centos']  = 'CentOS';
    $header['ubuntu']  = 'UBUNTU';
    $header['system']  = '新系统';
    $header['uninstall']  = '不需要安装';
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
    $type='100,110,111';
    $data = $statistic->getServerPutaway($entity_type,$begin,$end,$type,$subdivision);
    $rows = array();
    if(!empty($data)){
      $all_day =0;$num=0;$ye_num=0;$work=0;
      $spend_time=0;$win03=0;$win08=0;$win2012=0;
      $centos=0;$ubuntu=0;$system=0;$uninstall=0;
      foreach($data as $item){
        $all_day += $item->all_day;
        $num += $item->all_day;
        $ye_num += $item->night;
        $work += $item->all_day;
        $spend_time += $item->usetimess;
        $win03 += $item->win03;
        $win08 += $item->win08;
        $win2012 += $item->win2012;
        $centos += $item->centos;
        $ubuntu += $item->ubuntu;
        $system += $item->new_system;
        $uninstall += $item->un_install;
        $subdivision = $statistic->get_subdivision($item->last_uid);
        $realname = $statistic->get_realname($item->last_uid);
        $tmp = array(
          'subdivision'=>!empty($subdivision)?$subdivision[0]->subdivision_value:'',
          'name'=>!empty($realname)?$realname[0]->real_name_value:'',
          'num'=>$item->all_day,
          'ye_num'=>empty($item->night)?0:$item->night,
          'work'=>$item->all_day,
          'time_new'=>empty($item->usetimess)?null:round(($item->usetimess/$item->all_day)/60,1),
          'spend_time_new'=>round($item->usetimess/60,1),
          'win03'=>$item->win03,
          'win08'=>$item->win08,
          'win2012'=>$item->win2012,
          'centos'=>$item->centos,
          'ubuntu'=>$item->ubuntu,
          'system'=>$item->new_system,
          'uninstall'=>$item->un_install
        );
        $rows[] = $tmp;
      }
      $system_num= $win03+$win08+$win2012+$centos+$ubuntu+$system+$uninstall;
      array_push($rows,array('','合计',$num,$ye_num,$work,round(($spend_time/60)/$num,1),round($spend_time/60,1),$win03,$win08,$win2012,$centos,$ubuntu,$system,$uninstall));
      array_push($rows,array('工单占比','白天',round(($all_day-$ye_num)/$all_day*100, 2)."%",'夜晚',round($ye_num/$all_day*100, 1)."%",'','系统占比例',round($win03/$system_num*100, 2)."%",round($win08/$system_num*100, 1)."%",round($win2012/$system_num*100, 2)."%",round($centos/$system_num*100, 1)."%",round($ubuntu/$system_num*100, 2)."%",round($system/$system_num*100, 1)."%",round($uninstall/$system_num*100, 2)."%"));
    }else{
      $rows=null;
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
