<?php
/**
 * @file
 *  作息时间Server
 */
 
namespace Drupal\worksheet;

class WorkSheetDateService {

  public function getMonthInfo($years, $month){
    if( !is_int($years) || !is_int($month)){
      return false;
    }
    $entity = entity_load_multiple_by_properties('work_sheet_date', array(
      'years' => $years,
      'month' => $month
    ));
    if(empty(count($entity))){
      $obj = $this->dateInit($years,$month);
      $res = $obj->get('dates_info')->value;
      $info = unserialize($res);
    }else{
      $res = reset($entity)->get('dates_info')->value;
      $info = unserialize($res);
    }
    return $info;
  }

  public function getDataObject($years, $month){
    if( !is_int($years) || !is_int($month)){
      return false;
    }
    $entity = entity_load_multiple_by_properties('work_sheet_date', array(
      'years' => $years,
      'month' => $month
    ));
    if(empty(count($entity))){
      $object = $this->dateInit($years,$month);
    }else{
      $object = reset($entity);
    }
    return $object;
  }

  public function dateInit($years,$month){
    //$days = cal_days_in_month(CAL_GREGORIAN,$month,$years);
    $time = strtotime($years.'-'.$month.'-01');
    $days = date("t",$time);
    $info = array();
    for($i = 1;$i<=$days;$i++){
      $stime = $years.'-'.$month.'-'.$i;
      $d = strtotime($stime);
      $w = date("w",$d);
      $wk = $w == 0 ?  true : $w == 1 ? 0 : 1;
      $info[$i] = array(
        'date' => $d,
        'week' => $w,
        'work' => $wk
      );
    }
    $res = entity_create('work_sheet_date', array(
      'years' => $years,
      'month' => $month,
      'dates_info' => serialize($info)
    ));
    $res->save();
    return $res;
  }
 
  /**
   * $settings 格式:
   * array(
   *   array(
   *    'years' => 2016,
   *     'month' => 10,
   *     'day' => 1,
   *     'work' => 0
   *   )
   *  )
   *
   */
  public function setWorkDate($settings=array()){
    $group = $this->getGroup($settings);
    foreach($group as $year=>$month){
      foreach($month as $k=>$v){
        if(isset($flag) && $flag == 1){
          return false;
        }
        if(empty($info)){
          $dataObject = $this->getDataObject(intval($year), intval($k));
        }
        $info = unserialize($dataObject->get('dates_info')->value);
        foreach($v as $k1=>$v1){
          $info[$k1]['work'] = $v1;
        }
        $res = $dataObject->set('dates_info',serialize($info))->save();
        $flag = $res ? 0 : 1;
      }
    }
    return true;
  }

  public function getGroup($settings){
    $group = array();
    foreach($settings as $k=>$v){
      if(!isset($group[$v['years']][$v['month']])){
        $group[$v['years']][$v['month']] = array( $v['day'] => $v['work']);
      }else{
        $group[$v['years']][$v['month']] += [ $v['day'] => $v['work'] ];
      }
    }
    return $group;
  }

}













