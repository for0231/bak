<?php

namespace Drupal\sync_migration;

class SyncIdcData {

  public function syncIdcData($ip_server) {
    $currentSheet = module_load_phpexcel();
    if(!$currentSheet) {
      drupal_set_message('机房信息文件不存在！', 'warning');
      return;
    }
    $cells = array(
      1 => array('A', 'B'),
      2 => array('D', 'E'),
      3 => array('G', 'H'),
      4 => array('J', 'K'),
      5 => array('M', 'N'),
      6 => array('P', 'Q'),
      7 => array('S', 'T'),
      8 => array('V', 'W'),
      9 => array('Y', 'Z'),
      10 => array('AB', 'AC')
    );
    $rows = array(1, 50, 98, 146);
    $room = entity_create('room', array(
      'name' => '洛杉矶机房'
    ));
    $room->save();
    foreach($rows as $row) {
      foreach($cells as $cell) {
        $this->cabinetData($ip_server, $currentSheet, $row, $cell, $room->id());
      }
    }

    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $json_str = json_encode($ip_server);
    $config->set('sync_server_ip_list', $json_str);
    $config->set('sync_idc', 1);
    $config->save();
  }

  private function cabinetData(&$ip_server, $currentSheet, $row, $cell, $rid) {
    $error_ip = array();
    $cabinet_code = trim($currentSheet->getCell($cell[0] . $row)->getValue());
    $cabinet = entity_create('room_cabinet', array(
      'rid' => $rid,
      'code' => $cabinet_code,
      'seat' => 45
    ));
    $cabinet->save();
    $cabinet_id = $cabinet->id();
    //保存交换机
    $switch = $this->saveSwitch($currentSheet, $row, $cell, $cabinet_id);
    if(empty($switch)) {
      return;
    }
    $size = 1;
    for($i = 2; $i < 47; $i++) {
      $ip_xy = $cell[1] . ($row + $i);
      $ip = trim($currentSheet->getCell($ip_xy)->getValue());
      if(empty($ip) || $ip == '无') {
        $size = 1;
        continue;
      }
      if($ip == 'Cable Manager') {
        $size = 1;
        continue;
      }
      if(strpos($ip, 'P') || strpos($ip, 'M')) {
        $size = 1;
        continue;
      }
      if(strpos($ip, '占位')) {
        $size++;
        continue;
      }
      if(!isset($ip_server[$ip])) {
        $error_ip[] = $ip . '服务器不存在';
        $size = 1;
        continue;
      }
      $server_cabinet = $ip_server[$ip]['cabinet'];
      if($server_cabinet != $cabinet_code) {
        $error_ip[] = $ip . '机柜号不一至';
        $size = 1;
        continue;
      }
      $ipms = entity_load_multiple_by_properties('ipm', array('ip' => $ip));
      if(empty($ipms)) {
        $error_ip[] = $ip . '管理IP不存在';
        $size = 1;
        continue;
      }
      $ipm = reset($ipms);
      $uxy = $cell[0] . ($row + $i);
      $seat = trim($currentSheet->getCell($uxy)->getValue());
      $cabinet_server = entity_create('cabinet_server', array(
        'cabinet_id' => $cabinet_id,
        'ipm_id' => $ipm->id(),
        'server_id' => $ip_server[$ip]['sid'],
        'switch_p' => array('target_id' => $switch['p_id'], 'value' => $seat),
        'switch_m' => array('target_id' => $switch['m_id'], 'value' => $seat),
        'start_seat' => $seat,
        'seat_size' => $size
      ));
      $cabinet_server->save();
      $ip_server[$ip]['cabinet_server_id'] = $cabinet_server->id();
      $ip_server[$ip]['ipm_id'] = $ipm->id();

      $ipm->set('port', $seat);
      $ipm->save();
      $size = 1;
    }
    if(!empty($error_ip)) {
      drupal_set_message(implode(';', $error_ip));
    }
  }

  /**
   * 获取交换机，排除了714机柜
   */
  private function saveSwitch($currentSheet, $row, $cell, $cabinet_id) {
    $switch = array();
    for($i = 2; $i < 47; $i++) {
      $xy = $cell[1] . ($row + $i);
      $cell_value = trim($currentSheet->getCell($xy)->getValue());
      if(empty($cell_value) || $cell_value == '无') {
        continue;
      }
      if($cell_value == 'Cable Manager') {
        continue;
      }
      //保存交换机
      $ips_id = 0;
      if(strpos($cell_value, 'P')) {
        $sw_ip = substr($cell_value, 3);
        $ips_objs = entity_load_multiple_by_properties('ips', array('ip' => $sw_ip));
        if(empty($ips_objs)) {
          $ips = entity_create('ips', array('ip' => $sw_ip, 'port' => 45));
          $ips->save();
          $switch['p_id'] = $ips->id();
        } else {
          $ips = reset($ips_objs);
          $switch['p_id'] = $ips->id();
        }
        $ips_id = $switch['p_id'];
      }
      if(strpos($cell_value, 'M')) {
        $sw_ip = substr($cell_value, 3);
        $ips_objs = entity_load_multiple_by_properties('ips', array('ip' => $sw_ip));
        if(empty($ips_objs)) {
          $ips = entity_create('ips', array('ip' => $sw_ip, 'port' => 45));
          $ips->save();
          $switch['m_id'] = $ips->id();
        } else {
          $ips = reset($ips_objs);
          $switch['m_id'] = $ips->id();
        }
        $ips_id = $switch['m_id'];
      }
      if($ips_id) {
        $uxy = $cell[0] . ($row + $i);
        $seat = trim($currentSheet->getCell($uxy)->getValue());
        entity_create('cabinet_switch', array(
          'cabinet_id' => $cabinet_id,
          'ips_id' => $ips_id,
          'start_seat' => $seat,
          'seat_size' => 1
        ))->save();
      }
      if(!empty($switch['p_id']) && !empty($switch['m_id'])) {
        break;
      }
    }
    return $switch;
  }
}
