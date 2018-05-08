<?php
/**
 * @file
 * Contains \Drupal\qy\PolicyListBase.
 */

namespace Drupal\qy;

use Drupal\Core\Url;

abstract class PolicyListBase {
  /**
   * 列表分类
   */
  protected $list_type;

  protected $db_service;

  protected $conditions = array();

  public function setFilter(array $filter) {
    $this->conditions = $filter;
  }

  protected function load() {
    $items = array();
    if($this->list_type == 'policy') {
      $ip_str = '';
      if(isset($this->conditions['ip'])) {
        $ip = $this->conditions['ip'];
        $ip_arr = explode('.', $ip['value']);
        if(count($ip_arr) == 4) {
          $this->conditions['ip']['value'] = $ip_arr[0] .'.'. $ip_arr[1]. '.' . $ip_arr[2];
          $ip_str = $ip['value'];
        }
      }
      if(empty($ip_str)) {
        $items = $this->db_service->load_policy($this->conditions + array(
          'xx' => 0,
        ), 'ip');
      } else {
        $ips = explode('.', $ip_str);
        $policys = $this->db_service->load_policy_nopage($this->conditions + array(
          'xx' => 0,
        ));
        foreach($policys as $key => $policy) {
          $policy_ips = explode('.', $policy->ip);
          $max = $policy_ips[3];
          if($policy->mask_number < 32) {
            $n = 32 - $policy->mask_number;
            $num = pow(2, $n);
            $max = $policy_ips[3] + $num - 1;
          }
          if($ips[3] >= $policy_ips[3] && $ips[3] <= $max) {
            $items[$key] = $policy;
          }
        }
      }
    } else if ($this->list_type == 'policytmp') {
      $items = $this->db_service->load_policy($this->conditions + array(
        'xx' => 3
      ), 'ip');
    }
    $return = array();
    foreach($items as $item) {
      $return[$item->ip][$item->id] = $item;
    }
    return $return;
  }

  protected function buildHeader() {
    $header['ip'] = 'IP';
    $header['route']  = '所属线路';
    $header['ms'] = '牵引模式';
    $header['bps'] = '最高BPS流量(M)';
    $header['pps'] = '最高PPS流量(万)';
    $header['time'] = '牵引持续时间(分)';
    if($this->list_type == 'policytmp') {
      $header['starts'] = '开始时间';
      $header['end'] = '结束时间';
      $header['over'] = '剩余结束时间';
    }
    $header['note'] = '备注';
    $header['opter'] = '工号';
    $header['op'] = '操作';
    return $header;
  }

  protected function buildRow($items, $n) {
    $rows = array();
    $first = true;
    foreach($items as $item) {
      if($first) {
        $rows[$item->id]['ip'] = array(
          'rowspan' => $n,
          'data' => array(
            'text' => array(
              '#markup' => $item->ip . '/' . $item->mask_number
            ),
            'op' => array(
              '#type' => 'operations',
              '#links' =>  $this->getWholeOperations($item)
            )
          )
        );
      }
      $route = $this->getRoute($item->routeid);
      $routename = '';
      if(!empty($route)) {
        $routename = $route->routename;
      }
      $rows[$item->id]['route'] = $routename;
      $mstext = qy_traction_ms()[$item->ms];
      $rows[$item->id]['ms'] = $mstext;
      $rows[$item->id]['bps'] = $item->bps;
      $rows[$item->id]['pps'] = $item->pps;
      $rows[$item->id]['time'] = $item->time;
      if($this->list_type == 'policytmp') {
        $rows[$item->id]['starts'] = format_date($item->starts, 'custom', 'Y-m-d H:i:s');
        $rows[$item->id]['end'] = format_date($item->starts + $item->kills * 60, 'custom', 'Y-m-d H:i:s');
        $rows[$item->id]['over'] = qy_time2string($item->starts + $item->kills * 60 - REQUEST_TIME);
      }
      $rows[$item->id]['note'] = $item->note;
      $rows[$item->id]['opter'] = $item->opter;
      $rows[$item->id]['op']['data'] = array('#type' => 'operations', '#links' => $this->getOperations($item));
      $first = false;
    }
    return $rows;
  }
  //单行操作
  protected function getOperations($item) {
    return array();
  }
  //整段操作
  protected function getWholeOperations($item) {
    return array();
  }

  public function render() {
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.'),
    );
    foreach($data as $items) {
      $build['list']['#rows'] += $this->buildRow($items, count($items));
    }
    $build['list_pager'] = array('#type' => 'pager', '#route_name' => '<current>');
    return drupal_render($build);
  }
  
  protected function getRoute($routeid) {
    static $routes = array();
    if(isset($routes[$routeid])) {
      return $routes[$routeid];
    }
    $route = $this->db_service->load_routeById($routeid);
    $routes[$routeid]= $route;
    return $route;    
  }
}


