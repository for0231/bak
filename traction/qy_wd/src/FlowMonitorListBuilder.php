<?php
/**
 * @file
 * Contains \Drupal\qy_wd\FlowMonitorListBuilder.
 */

namespace Drupal\qy_wd;

use Drupal\Core\Url;

class FlowMonitorListBuilder {
  /**
   * 列表分类
   */
  protected $list_type;

  protected $db_service;
  /**
   * 查询条件
   */
  protected $conditions = array();

  /**
   * {@inheritdoc}
   */
  public static function create($list_type) {
    return new static($list_type);
  }

  private function __construct($list_type) {
    $this->list_type = $list_type;
    $this->db_service = \Drupal::service('qy_wd.db_service');
  }

  public function setFilter(array $filter) {
    $this->conditions = $filter;
  }

  private function load() {
    $config = \Drupal::config('qy_wd.settings');
    $listen_unit = $config->get('listen_unit');
    if(empty($listen_unit)) {
      return array();
    }
    $units = explode(',', $listen_unit);
    $data = $this->db_service->load_unit_flow($units);
    return $data;
  }

  private function buildHeader() {
    $header['id']  = '单元';
    $header['route'] = '所属线路';
    $header['die_time'] = '宕机时间(秒)';
    $header['in_pps']  = '进入包数';
    $header['out_pps']  = '过滤后包数';
    $header['in_bps']  = '进入流量';
    $header['out_bps']  = '过滤后流量';
    $header['top_ip'] = '最大流量IP';
    $header['super_time'] = '超墙时间(秒)';
    $header['op'] = '操作';
    return $header;
  }

  public function render() {
    $routes = $this->db_service->load_routeUnit();
    $alarms = $this->db_service->load_alarm();
    $ips = array();
    $qys = $this->db_service->load_qy(array('prompt_tip' => 1));
    foreach($qys as $qy) {
      if($qy->start + 30 > time() && !in_array($qy->ip, $ips)) {
        $ips[] = $qy->ip;
      }
    }
    return $this->createHtml($alarms, $routes, $ips);
  }

  /*public function renderHtml($alarms, $routes) {
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    $now = time();
    foreach($data as $item) {
      $id = $item ->id;
      if(!isset($routes[$id])) {
        continue;
      }
      $bps = floor($item->in_bps / 128);
      $routename =  $routes[$id];
      $html_top_ip = str_replace(",", '<br>', $item->top_ip);
      $row = array();
      $super_time = 0;
      if(isset($alarms[$id])) {
        $alarm = $alarms[$id];
        if($item->die_time > 0 || $bps > $alarm->max_bps || $bps < $alarm->min_bps) {
          $row['#attributes']['style'] = 'color:red';
          $row['#attributes']['class'] = array('alarm');
          $timeout = $alarm->timeout;
          if($alarm->timeout == 0) {
            $this->db_service->update_alarm(array('timeout' => $now), $id);
            $timeout = $now;
          }
          $row['#attributes']['first-alarm'] = $timeout;
          $row['#attributes']['delay-time'] = $alarm->delay_time;
          $row['#attributes']['current-time'] = $now;
          $super_time = $now - $timeout;
        } else {
          if($alarm->timeout) {
            $super_time = 0;
            $this->db_service->update_alarm(array('timeout' => 0), $id);
          }
        }
      }
      $row[] = array('#markup' => $id);
      $row[] = array('#markup' => $routename);
      $row[] = array('#markup' => $item->die_time);
      $row[] = array('#markup' => $item->in_pps);
      $row[] = array('#markup' => $item->out_pps);
      $row[] = array('#markup' => $bps . 'Mbps');
      $row[] = array('#markup' => floor($item->out_bps / 128) . 'Mbps');
      $row[] = array('#markup' => $html_top_ip);
      $row[] = array('#markup' => $super_time . 'S');
      $row[] = array('#type' => 'operations', '#links' => array(
        'info' => array(
          'title' =>'流量详细',
          'url' => new Url('admin.wd.monitor.info', array('wall_id' => $id), array('attributes' => array('target' => '_blank')))
        )
      ));
      $build['list'][$id] = $row;
    }
    return drupal_render($build);
  }*/

  /**
   * 这种写法循环生成hmtl内存不会增加用renderHtml方法内存会不停的增加
   */
  public function createHtml($alarms, $routes, $ips) {
    $data = $this->load();
    $html = '<table>';
    $html .= '<thead><tr><th>单元</th><th>所属线路</th><th>宕机时间(秒)</th><th>进入包数</th><th>过滤后包数</th><th>进入流量</th><th>过滤后流量</th><th>最大流量IP</th><th>超墙时间(秒)</th><th>操作</th></tr></thead>';
    $html .= '<tbody>';
    if(count($data) == 0) {
      $html .= '<tr><td class="empty message" colspan="11">无数据</td></tr>';
    }
    $now = time();
    foreach($data as $item) {
      $id = $item ->id;
      if(!isset($routes[$id])) {
        continue;
      }
      $bps = floor($item->in_bps / 128);
      $routename =  $routes[$id];
      $html_top_ip = str_replace(",", '<br>', $item->top_ip);
      $attributes = '';
      $super_time = 0;
      if(isset($alarms[$id])) {
        $alarm = $alarms[$id];
        if($item->die_time > 0 || $bps > $alarm->max_bps || $bps < $alarm->min_bps) {
          $attributes .= 'style="color:red"';
          $attributes .='class= "alarm"';
          $timeout = $alarm->timeout;
          if($alarm->timeout == 0) {
            $this->db_service->update_alarm(array('timeout' => $now), $id);
            $timeout = $now;
          }
          if($now - $timeout >= $alarm->delay_time) {
            $attributes .='class= "alarm"';
          }
          $attributes .= 'first-alarm="'.$timeout.'"';
          $attributes .= 'delay-time="'. $alarm->delay_time .'"';
          $attributes .= 'current-time = "'.$now.'"';
          $super_time = $now - $timeout;
        } else {
          if($alarm->timeout) {
            $super_time = 0;
            $this->db_service->update_alarm(array('timeout' => 0), $id);
          }
        }
      }
      $html .= '<tr '. $attributes.'>';
      $html .= '<td>'. $id .'</td>';
      $html .= '<td>'. $routename .'</td>';
      $html .= '<td>'. $item->die_time .'</td>';
      $html .= '<td>'. $item->in_pps .'</td>';
      $html .= '<td>'. $item->out_pps .'</td>';
      $html .= '<td>'. $bps . 'Mbps</td>';
      $html .= '<td>'. floor($item->out_bps / 128) . 'Mbps</td>';
      $html .= '<td>'. $html_top_ip .'</td>';
      $html .= '<td>'. $super_time .'S</td>';
      $op = '<ul class="dropbutton">';
      $op .= '<li>'. \Drupal::l('流量详细', new Url('admin.wd.monitor.info', array('wall_id' => $id), array('attributes' => array('target' => '_blank')))) .'</li>';
      $op .= '</ul>';
      $html .= '<td>'. $op .'</td>';
      $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    if(!empty($ips)) {
      $html .= '<table><tbody><tr class="notnullalarm"><td style="color:#0000ff">高防IP牵引播报：'. implode(',', $ips) .'</td></tr></tbody></table>';
    }
    return $html;
  }
}
