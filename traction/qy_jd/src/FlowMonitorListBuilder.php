<?php
/**
 * @file
 * Contains \Drupal\qy_jd\FlowMonitorListBuilder.
 */

namespace Drupal\qy_jd;

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
    $this->db_service = \Drupal::service('qy_jd.db_service');
  }

  public function setFilter(array $filter) {
    $this->conditions = $filter;
  }

  private function load() {
    return $this->db_service->load_netcom(array(), 'type');
  }

  private function buildHeader() {
    $header['route'] = '线路';
    $header['ip']  = '防火墙IP';
    $header['in_bps']  = '进入流量';
    $header['out_bps']  = '过滤后流量';
    $header['in_pps']  = '进入包数';
    $header['out_pps']  = '过滤后包数';
    $header['top_ip'] = '最大流量IP';
    $header['super_time'] = '超墙时间(秒)';
    $header['syn_rate'] = 'syn rate';
    $header['ack_rate'] = 'ack rate';
    $header['udp_rate'] = 'udp rate';
    $header['icmp_rate'] = 'icmp rate';
    $header['frag_rate'] = 'frag rate';
    $header['nonip_rate'] = 'nonip rate';
    $header['new_tcp_rate'] = 'new tcp rate';
    $header['new_udp_rate'] = 'new udp rate';
    $header['tcp_conn_in'] = 'tcp conn in';
    $header['tcp_conn_out'] = 'tcp conn out';
    $header['udp_conn'] = 'udp conn';
    $header['icmp_conn'] = 'icmp conn';
    $header['op'] = '操作';
    return $header;
  }

  public function render() {
    $alarms = $this->db_service->load_alarm();
    $routes = $this->db_service->load_route(array('status' => array('value' => 0, 'op' => '>')));
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
      if(!isset($routes[$item->type])) {
        continue;
      }
      $id = $item ->id;
      $html_top_ip = str_replace(",", '<br>', $item->top_ip);
      $route_name = $routes[$item->type]->routename;
      //设置列信息
      $row = array();
      $super_time = 0;
      if(isset($alarms[$id])) {
        $alarm = $alarms[$id];
        $bps = $item->in_bps;
        if($bps > $alarm->max_bps || $bps < $alarm->min_bps) {
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
      $row[] = array('#markup' => $route_name);
      $row[] = array('#markup' => $item->ip);
      $row[] = array('#markup' => $item->in_bps);
      $row[] = array('#markup' => $item->out_bps);
      $row[] = array('#markup' => $item->in_pps);
      $row[] = array('#markup' => $item->out_pps);
      $row[] = array('#markup' => $html_top_ip);
      $row[] = array('#markup' => $super_time . 'S');
      $row[] = array('#markup' => $item->syn_rate);
      $row[] = array('#markup' => $item->ack_rate);
      $row[] = array('#markup' => $item->udp_rate);
      $row[] = array('#markup' => $item->icmp_rate);
      $row[] = array('#markup' => $item->frag_rate);
      $row[] = array('#markup' => $item->nonip_rate);
      $row[] = array('#markup' => $item->new_tcp_rate);
      $row[] = array('#markup' => $item->new_udp_rate);
      $row[] = array('#markup' => $item->tcp_conn_in);
      $row[] = array('#markup' => $item->tcp_conn_out);
      $row[] = array('#markup' => $item->udp_conn);
      $row[] = array('#markup' => $item->icmp_conn);
      $row[] = array('#type' => 'operations', '#links' => array(
        'info' => array(
          'title' =>'流量详细',
          'url' => new Url('admin.jd.monitor.info', array('wall_id' => $id), array('attributes' => array('target' => '_blank')))
        )
      ));
      $build['list'][$id] = $row;
    }
    return drupal_render($build);
  }*/

  /**
   *这种写法循环生成hmtl内存不会增加用renderHtml方法内存会不停的增加
   */
  public function createHtml($alarms, $routes, $ips) {
    $data = $this->load();
    $html = '<table>';
    $html .= '<thead><tr><th>线路</th><th>防火墙IP</th><th>进入流量</th><th>过滤后流量</th><th>进入包数</th><th>过滤后包数</th><th>最大流量IP</th><th>超墙时间(秒)</th>';
    $html .= '<th>syn rate</th><th>ack rate</th><th>udp rate</th><th>icmp rate</th><th>frag rate</th><th>nonip rate</th><th>new tcp rate</th><th>new udp rate</th><th>tcp conn in</th><th>tcp conn out</th><th>udp conn</th><th>icmp conn</th><th>操作</th></tr></thead>';
    $html .= '<tbody>';
    if(count($data) == 0) {
      $html .= '<tr><td class="empty message" colspan="11">无数据</td></tr>';
    }
    $now = time();
    foreach($data as $item) {
      if(!isset($routes[$item->type])) {
        continue;
      }
      $id = $item ->id;
      $html_top_ip = str_replace(",", '<br>', $item->top_ip);
      $route_name = $routes[$item->type]->routename;
      //设置列信息
      $attributes = '';
      $super_time = 0;
      if(isset($alarms[$id])) {
        $alarm = $alarms[$id];
        $bps = $item->in_bps;
        if($bps > $alarm->max_bps || $bps < $alarm->min_bps) {
          $attributes .= 'style="color:red"';
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
      $html .= '<td>'. $route_name .'</td>';
      $html .= '<td>'. $item->ip .'</td>';
      $html .= '<td>'. $item->in_bps .'</td>';
      $html .= '<td>'. $item->out_bps .'</td>';
      $html .= '<td>'. $item->in_pps .'</td>';
      $html .= '<td>'. $item->out_pps .'</td>';
      $html .= '<td>'. $html_top_ip .'</td>';
      $html .= '<td>'. $super_time . 'S</td>';
      $html .= '<td>'. $item->syn_rate .'</td>';
      $html .= '<td>'. $item->ack_rate .'</td>';
      $html .= '<td>'. $item->udp_rate .'</td>';
      $html .= '<td>'. $item->icmp_rate .'</td>';
      $html .= '<td>'. $item->frag_rate .'</td>';
      $html .= '<td>'. $item->nonip_rate .'</td>';
      $html .= '<td>'. $item->new_tcp_rate .'</td>';
      $html .= '<td>'. $item->new_udp_rate .'</td>';
      $html .= '<td>'. $item->tcp_conn_in .'</td>';
      $html .= '<td>'. $item->tcp_conn_out .'</td>';
      $html .= '<td>'. $item->udp_conn .'</td>';
      $html .= '<td>'. $item->icmp_conn .'</td>';
      $op = '<ul class="dropbutton">';
      $op .= '<li>'. \Drupal::l('流量详细', new Url('admin.jd.monitor.info', array('wall_id' => $id), array('attributes' => array('target' => '_blank')))) .'</li>';
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
