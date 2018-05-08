<?php
/**
 * @file
 * Contains \Drupal\qy\LogsListBase.
 */

namespace Drupal\qy;

use Drupal\Core\Url;

abstract class LogsListBase {
  /**
   * 列表分类
   */
  protected $list_type;

  protected $db_service;

  protected $conditions = array();

  /**
   * 设置查询条件
   */
  public function setFilter(array $filter) {
    $this->conditions = $filter;
  }

  protected function load() {
    if($this->list_type == 'logqy') {
      return $this->db_service->load_logs($this->conditions + array('log' => 1));
    } else if($this->list_type == 'logsipstop') {
      return $this->db_service->load_logs($this->conditions + array('log' => 2));
    }
    return array();
  }

  protected function buildHeader() {
    $header['ip']  = 'IP';
    $header['routename'] = '线路';
    $header['bps']  = 'BPS流量';
    $header['pps']  = 'PPS流量';
    $header['start']  = '开始时间';
    $header['end']  = '解封时间';
    $header['type']  = '牵引类型';
    $header['note']  = '备 注';
    $header['opter']  = '工号';
    return $header;
  }

  protected function buildRow($item) {
    $row['ip'] = $item->ip;
    $row['routename'] = $item->routename;
    $row['bps'] = $item->bps . 'Mbps';
    $row['pps'] = $item->pps . '万pps';
    $row['start'] = format_date($item->start, 'custom', 'Y-m-d H:i:s');
    $row['end'] = '';
    if(!empty($item->end)) {
      $row['end'] = format_date($item->end, 'custom', 'Y-m-d H:i:s');
    }
    $row['type'] = $item->type;
    $row['note'] = $item->note;
    $row['opter'] = $item->opter;
    return $row;
  }

  public function render() {
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $item) {
      if($row = $this->buildRow($item)) {
        $build['list']['#rows'][$item->id] = $row;
      }
    }
    $build['list_pager'] = array('#type' => 'pager', '#route_name' => '<current>');
    return drupal_render($build);
  }
}
