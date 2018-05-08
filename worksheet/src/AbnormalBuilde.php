<?php

/**
 * @file
 * Contains file
 * \Drupal\worksheet\AbnormalBuilde.
 */

namespace Drupal\worksheet;

use Drupal\Core\Url;
/**
 *
 */
class AbnormalBuilde {
  protected $formBuilder;
  public function __construct(){
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  protected function buildHeader() {
    $header['subdivision']  = '所属子部门';
    $header['name']  = '姓名';
    $header['count_num']  = '异常数量';
    $header['put']  = '服务器上架';
    $header['soldout']  = '服务器下架';
    $header['server_reset']  = '服务器重装';
    $header['other']  = '其他产品';
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
    $data = $statistic->getAbnormal($begin,$end,$subdivision);
    if(!empty($data)){
      $yichang=0;$shangjia=0;$soldout=0;$server_reset=0;$other=0;
      $return = array();
      foreach($data as $key=>$value){
        if(!empty($return[$value->uid]['yichang'])&& array_key_exists($value->uid,$return) ){
          $return[$value->uid]['yichang'] += $value->num;
        }else{
          $return[$value->uid]['yichang'] = $value->num;
          $return[$value->uid]['shangjia'] = 0;
          $return[$value->uid]['soldout'] = 0;
          $return[$value->uid]['server_reset'] = 0;
        }
        if(array_key_exists($value->uid, $return) && ($value->tid == '100' || $value->tid == '110' || $value->tid == '111')){
          $return[$value->uid]['shangjia'] += $value->num;
        }
        elseif($value->tid == '100' || $value->tid == '110' || $value->tid == '111'){
          $return[$value->uid]['shangjia'] = $value->num;
        }
        if(array_key_exists($value->uid, $return) && $value->tid == '140'){
          $return[$value->uid]['soldout'] += $value->num;
        }
        elseif($value->tid == '140'){
          $return[$value->uid]['soldout'] = $value->num;
        }
        if(array_key_exists($value->uid, $return)&& $value->tid == '120'){
          $return[$value->uid]['server_reset'] += $value->num;
        }
        elseif($value->tid == '120'){
          $return[$value->uid]['server_reset'] = $value->num;
        }
      }
      if(!empty($return)){
        foreach($return as $key=>$item){
          $yichang += $item['yichang'];
          $shangjia += $item['shangjia'];
          $soldout += $item['soldout'];
          $server_reset += $item['server_reset'];
          $subdivision = $statistic->get_subdivision($key);
          $realname = $statistic->get_realname($key);
          $tmp = array(
            'subdivision'=> !empty($subdivision)?$subdivision[0]->subdivision_value:'',
            'name'=>!empty($realname)?$realname[0]->real_name_value:'',
            'count_num'=>$item['yichang'],
            'put'=> empty($item['shangjia'])?0:$item['shangjia'],
            'soldout'=>empty($item['soldout'])?0:$item['soldout'],
            'server_reset'=>empty($item['server_reset'])?0:$item['server_reset'],
            'other'=>$item['yichang']-$item['shangjia']-$item['soldout']-$item['server_reset']  
          );
          $rows[] = $tmp;
        }
      }
      array_push($rows,array('','合计',$yichang,$shangjia,$soldout,$server_reset,$yichang-$shangjia-$soldout-$server_reset));
    }else{
      $rows= array();
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
