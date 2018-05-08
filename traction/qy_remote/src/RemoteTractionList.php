<?php
/**
 * @file
 * Contains \Drupal\qy_remote\RemoteTractionList.
 */

namespace Drupal\qy_remote;

use Drupal\Core\Url;

class RemoteTractionList {
  /**
   * 列表分类
   */
  protected $list_type;

  protected $conditions = array();

  /**
   * {@inheritdoc}
   */
  public static function create($list_type) {
    return new static($list_type);
  }

  private function __construct($list_type) {
    $this->list_type = $list_type;
  }

  public function setFilter(array $filter) {
    $this->conditions = $filter;
  }

  private function load($db_service) {
    if($this->list_type == 'remote') {
      $user = \Drupal::currentUser();
      return $db_service->load_qy($this->conditions + array(
        'type' => 'cleaning',
        'uid' => $user->id()
      ));
    }
    return array();
  }

  private function buildHeader() {
    $header['net_type'] = '线路';
    $header['ip'] = 'IP';
    $header['bps'] = 'BPS流量';
    $header['pps'] = 'PPS流量';
    $header['start'] = '牵引时间';
    $header['untie_time'] = '预计解封时间';
    $header['over_time'] = '剩余解封时间';
    $header['type'] = '牵引类型';
    $header['note'] = '备注';
    $header['opter'] = '工号';
    return $header;
  }

  private function buildRow($item, $route) {
    $row['net_type'] = $route->routename;
    $row['ip'] = $item->ip;
    $row['bps'] = $item->bps . 'Mbps';
    $row['pps'] = $item->pps . '万pps';
    $row['start'] = format_date($item->start, 'custom', 'Y-m-d H:i:s');
    $untie_time = $item->time == 0 ? '需手动解封' : format_date($item->start + $item->time * 60, 'custom', 'Y-m-d H:i:s');
    $row['untie_time'] = $untie_time;
    $row['over_time'] = qy_time2string($item->start + $item->time * 60 - REQUEST_TIME);
    $row['type'] = $item->type;
    $row['note'] = $item->note;
    $row['opter'] = $item->opter;
    return $row;
  }

  public function render() {
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    $db_services = qy_remote_firewall_dbserver();
    foreach($db_services as $db_service) {
      $data = $this->load($db_service);
      $routes = $db_service->load_route();
      foreach($data as $item) {
        if($row = $this->buildRow($item, $routes[$item->net_type])) {
          $build['list']['#rows'][] = $row;
        }
      }
    }
    return drupal_render($build);
  }
}


