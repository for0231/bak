<?php
/**
 * @file
 * Contains \Drupal\qy\TractionListBase.
 */

namespace Drupal\qy;

use Drupal\Core\Url;

abstract class TractionListBase {
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
    if($this->list_type == 'traction') {
      return $this->db_service->load_qy($this->conditions + array(
        'gjft' => array('op' => 'or', 'or_field' => array(
          array('name'=>'gjft', 'value'=>2, 'op'=>'<>'), 
          array('name'=>'gjft')
        ))
      ));
    } else if($this->list_type == 'tractionfilter') {
      return $this->db_service->load_qy($this->conditions + array(
        'gjft' => array('op' => 'or', 'or_field' => array(
          array('name'=>'gjft', 'value'=>2, 'op'=>'<>'), 
          array('name'=>'gjft')
        )),
        'type' => array('value' => 'cleaning', 'op' => '<>')
      ));
    } else if ($this->list_type == 'ipstop') {
      return $this->db_service->load_qy($this->conditions + array(
        'gjft' => 2,
      ));
    }
    return array();
  }

  protected function buildHeader() {
    $header['net_type'] = '线路';
    $header['ip'] = 'IP';
    $header['bps'] = 'BPS流量';
    $header['pps'] = 'PPS流量';
    $header['start'] = '牵引时间';
    $header['untie_time'] = '预计解封时间';
    if($this->list_type != 'ipstop')
      $header['over_time'] = '剩余解封时间';
    $header['type'] = '牵引类型';
    $header['note'] = '备注';
    $header['opter'] = '工号';
    $header['op'] = '操作';
    return $header;
  }

  protected function buildRow($item, $route) {
    $row['net_type'] = $route->routename;
    $row['ip'] = $item->ip;
    $row['bps'] = $item->bps . 'Mbps';
    $row['pps'] = $item->pps . '万pps';
    $row['start'] = format_date($item->start, 'custom', 'Y-m-d H:i:s');
    $untie_time = $item->time == 0 ? '需手动解封' : format_date($item->start + $item->time * 60, 'custom', 'Y-m-d H:i:s');
    $row['untie_time'] = $untie_time;
    if($this->list_type != 'ipstop') {
      $time = $item->start + $item->time * 60 - time();
      if(empty($this->conditions)) {
        $row['over_time'] = qy_time2string($time);
      } else {
        //无条件情况下增加无法即时看到
        $row['over_time'] = array(
          'begin-second' => $time,
          'data' => qy_time2string($time)
        );
      }
    }
    $row['type'] = $item->type;
    $row['note'] = $item->note;
    $row['opter'] = $item->opter;
    $row['op']['data'] = array('#type' => 'operations', '#links' => $this->getOperations($item));
    return $row;
  }

  abstract protected function getOperations($item);

  public function render() {
    $data = $this->load();
    $routes = $this->db_service->load_route();
    return $this->createHtml($data, $routes);
  }

  /**
   * 为已查询出数据提供html生成
   */
  /*public function renderHtml($data, $routes) {
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );

    foreach($data as $item) {
      if($row = $this->buildRow($item, $routes[$item->net_type])) {
        $build['list']['#rows'][$item->id] = $row;
      }
    }
    return drupal_render($build);
  }*/

  /**
   * 这种写法循环生成hmtl内存不会增加用renderHtml方法内存会不停的增加
   */
  public function createHtml($data, $routes) {
    $html = '<table>';
    $html .= '<thead><tr><th>线路</th><th>IP</th><th>BPS流量</th><th>PPS流量</th><th>牵引时间</th><th>预计解封时间</th><th>剩余解封时间</th><th>牵引类型</th><th>备注</th><th>工号</th><th>操作</th></tr></thead>';
    $html .= '<tbody>';
    if(count($data) == 0) {
      $html .= '<tr><td class="empty message" colspan="11">无数据</td></tr>';
    }
    foreach($data as $item) {
      $route = $routes[$item->net_type];
      if($item->prompt_tip) {
        $html .= '<tr style="color:#0000ff">';
      } else {
        $html .= '<tr>';
      }
      $html .= '<td>'. $route->routename .'</td>';
      $html .= '<td>'. $item->ip .'</td>';
      $html .= '<td>'. $item->bps . 'Mbps</td>';
      $html .= '<td>'. $item->pps . '万pps</td>';
      $html .= '<td>'. format_date($item->start, 'custom', 'Y-m-d H:i:s').'</td>';
      $untie_time = $item->time == 0 ? '需手动解封' : format_date($item->start + $item->time * 60, 'custom', 'Y-m-d H:i:s');
      $html .= '<td>'. $untie_time .'</td>';
      $time = $item->start + $item->time * 60 - time();
      if(empty($this->conditions)) {
        $html .= '<td>'. qy_time2string($time) .'</td>';
      } else {
        $html .= '<td begin-second="'. $time .'">'. qy_time2string($time) .'</td>';
      }
      $html .= '<td>'. $item->type .'</td>';
      $html .= '<td>'. $item->note .'</td>';
      $html .= '<td>'.$item->opter.'</td>';
      $operations = $this->getOperations($item);
      $op = '<ul class="dropbutton">';
      foreach($operations as $operation) {
        $op .= '<li>'. \Drupal::l($operation['title'], $operation['url']) .'</li>';
      }
      $op .= '</ul>';
      $html .= '<td>'.$op.'</td>';
      $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
  }
}


