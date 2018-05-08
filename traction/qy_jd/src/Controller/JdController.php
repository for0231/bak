<?php
/**
 * @file
 * Contains \Drupal\qy_jd\Controller\JdController.
 */

namespace Drupal\qy_jd\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\qy_jd\TractionListBuilder;
use Drupal\qy_jd\PolicyListBuilder;
use Drupal\qy_jd\LogsListBuilder;
use Drupal\qy_jd\VisitEquipment;
use Drupal\qy_jd\RouteListBuilder;

class JdController extends ControllerBase {
  /**
   * 策略列表
   */
  public function policyList() {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 15,
      '#id' => 'filter_ip'
    );
    $db_service = \Drupal::service('qy_jd.db_service');
    $routes = $db_service->load_route(array(), false);
    $options = array('' => '-所有-');
    foreach($routes as $route) {
      $options[$route->id] = $route->routename;
    }
    $build['filter']['route'] = array(
      '#type' => 'select',
      '#title' => '所属线路',
      '#options' => $options,
      '#id' => 'filter_routeid'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search'
    );
    $build['contnet'] =  array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'false',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'policy'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * 策略列表
   */
  public function policyTmpList() {
    $build['contnet'] =  array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'policytmp'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;  
  }

  /**
   * 开启或关闭
   */
  public function policyStatus($policy_id, $status) {
    $db_service = \Drupal::service('qy_jd.db_service');
    $db_service->update_policy(array(
      'zt' => $status == 'close' ? '1' : 0,
    ),$policy_id);
    return $this->redirect('admin.jd.policy_tmp');  
  }

  /**
   * 删除策略
   */
  public function policyDelete(Request $request, $policy_id) {
    $url = $request->query->get('destination');
    $db_service = \Drupal::service('qy_jd.db_service');
    $db_service->del_policy($policy_id);
    drupal_set_message('删除成功'); 
    return new RedirectResponse($url);
  }

  /**
   *删除整段策略
   */
  public function segmentDelete(Request $request, $policy_id) {
    $url = $request->query->get('destination');
    $db_service = \Drupal::service('qy_jd.db_service');
    $policy = $db_service->load_policyById($policy_id);
    if(empty($policy)) {
      drupal_set_message('删除数据错误');
    } else {
      $number = $db_service->del_policySegment($policy->ip, $policy->xx);
      drupal_set_message('成功删除' . $number . '行数据');
    }
    return new RedirectResponse($url);
  }
  /**
   * 牵引列表
   */
  public function tractionList() {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 15,
      '#id' => 'filter_ip'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search'
    );
    $build['filter']['refresh'] = array(
      '#type' => 'button',
      '#value' => '停止刷新',
      '#id' => 'refresh',
      '#name' => 'refresh'
    );
    $build['filter']['alarm_remove'] = array(
      '#type' => 'link',
      '#title' => '解除全部Alarm',
      '#attributes' => array('class' => array('button')),
      '#url' => new Url('admin.jd.traction.remove.alarm', array(
         'destination' => $this->url('<current>')
      )),
    );

    $build['contnet'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'traction'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * 滤后牵引列表
   */
  public function tractionFilterList() {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 15,
      '#id' => 'filter_ip'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search'
    );
    $build['filter']['refresh'] = array(
      '#type' => 'button',
      '#value' => '停止刷新',
      '#id' => 'refresh',
      '#name' => 'refresh'
    );
    $build['filter']['alarm_remove'] = array(
      '#type' => 'link',
      '#title' => '解除全部Alarm',
      '#attributes' => array('class' => array('button')),
      '#url' => new Url('admin.jd.traction.remove.alarm', array(
         'destination' => $this->url('<current>')
      )),
    );
   
    $build['content'] = array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'tractionfilter'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * IP封停列表
   */
  public function ipStopList() {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 15,
      '#id' => 'filter_ip'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search'
    );

    $build['content'] = array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'false',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'ipstop'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * 解封
   */
  public function tractionRemove(Request $request, $traction_id) {
    $url = $request->query->get('destination');
    $db_service = \Drupal::service('qy_jd.db_service');
    $qy = $db_service->load_qyById($traction_id);
    if(!empty($qy)) {
      $db_service->del_qy($qy);
      drupal_set_message('解除牵引成功');
    }
    return new RedirectResponse($url);
  }

  /**
   * 解除全部Alarm
   */
  public function removeAlarm(Request $request) {
    $url = $request->query->get('destination');
    $db_service = \Drupal::service('qy_jd.db_service');
    $list = $db_service->load_qy_alarm();
    if(empty($list)) {
       drupal_set_message('不存在alarm，解除失败。'); 
    } else {
      foreach($list as $item) {
        $db_service->del_qy($item);
      }
      drupal_set_message('解除全部Alarm成功'); 
    }
    return new RedirectResponse($url);
  }

  /**
   * 牵引日志
   */
  public function logsTraction() {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 15,
      '#id' => 'filter_ip'
    );
    $db_service = \Drupal::service('qy_jd.db_service');
    $routes = $db_service->load_route(array(), false);
    $options = array('' => '-所有-');
    foreach($routes as $route) {
      $options[$route->routename] = $route->routename;
    }
    $build['filter']['routename'] = array(
      '#type' => 'select',
      '#title' => '所属线路',
      '#options' => $options,
      '#id' => 'filter_routename'
    );
    $build['filter']['type'] = array(
      '#type' => 'select',
      '#title' => '类型',
      '#options' => array('' => '-所有-') + qy_traction_type(),
      '#id' => 'filter_type'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search'
    );

    $build['content'] = array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'false',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'logqy'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * ip封停日志
   */
  public function logsIpStop() {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 15,
      '#id' => 'filter_ip'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search'
    );

    $build['content'] = array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'false',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'logsipstop'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * 独立IP检查
   */
  public function ipChecked(Request $request) {
    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter', 'filter-ipchecked'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textarea',
      '#title' => 'IP',
      '#cols' => 40,
      '#rows' => 4,
      '#value' => '',
      '#placeholder' => '一行只能输入一个IP',
      '#id' => 'filter_ip'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '开始搜索',
      '#id' => 'search',
      '#name' => 'search',
      '#attributes' => array('action-type' => 'checkip')
    );

    $build['content'] = array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'false',
        'ajax-path' => \Drupal::url('admin.jd.ip_check.ajax')
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * ajax请求
   */
  public function ipCheckAjax(Request $request) {
    $condition = $request->query->get('filter_ip');
    $ips = array();
    if(!empty($condition)) {
      $filter = str_replace('-', '.', $condition);
      $ips = explode("\n", $filter);
      foreach($ips as $key => $item) {
        if(strcmp(long2ip(sprintf("%u",ip2long($item))), $item)) {
          unset($ips[$key]);
        }
      }
    }
    $db_service= \Drupal::service('qy_jd.db_service');
    $netcoms = $db_service->load_netcom();
    $hosts = array();
    foreach($netcoms as $netcom) {
      $hosts[$netcom->id] = array(
        'ip' => $netcom->ip,
        'user' => $netcom->username,
        'pass' => $netcom->password
      );
    }
    if(!empty($ips)) {
      $visit = new VisitEquipment();
      foreach ($hosts as $key => $host) {
        if(isset($_SESSION['host_cookies'][$key])) {
          $host['cookie'] = $_SESSION['host_cookies'][$key];
        } else {
          $cookies = $visit->login(array($key => $host));
          if(empty($cookies)) {
            continue;
          }
          $host['cookie'] = $cookies[$key];
          $_SESSION['host_cookies'][$key] = $cookies[$key];
        }
        foreach($ips as $ip) {
          $rs = $visit->checked_ip($host, $ip);
          if($rs) {
            $hosts[$key]['check_value'][$ip] = $rs;
          }
        }
      }
    }
    $content = array(
      '#theme' => 'jd_ip_checked',
      '#hosts' => $hosts
    );
    $build = drupal_render($content);
    return new Response($build);
  }

  /**
   * 后台首页
   */
  public function adminIndex() {
    $db_service= \Drupal::service('qy_jd.db_service');
    $values = array();
    $uid = \Drupal::currentUser()->id();
    $user = entity_load('user', $uid);
    if($user->hasRole('qy_client')) {
      $values = $db_service->statistics_gift($user->id());
    } else {
      $values = $db_service->statistics_gift();
    }

    $gjft_1 = 0;
    $gjft_2 = 0;
    foreach($values as $value) {
      if($value->gjft == 1) {
        $gjft_1 = $value->total;
      } else if ($value->gjft == 2) {
        $gjft_2 = $value->total;
      }
    }

    return array(
      '#type' => 'table',
      '#rows' => array(
        array(array('data' => '牵引数量:', 'style' => 'text-align: right;width:50%;' ), array('data' => $gjft_1)),
        array(array('data' => 'IP封停数量:','style' => 'text-align: right;'), array('data' => $gjft_2))
      ),
    );
  }

  /**
   * 线路列表
   */
  public function qyRouteList() {
    $list = new RouteListBuilder();
    return $list->render();
  }

  /**
   * 删除 线路
   */
  public function routeDelete(Request $request, $id) {
    $db_service = \Drupal::service('qy_jd.db_service');
    $db_service->del_route($id);
    drupal_set_message('删除成功');
    return $this->redirect('admin.jd.route');
  }

  /**
   * 删除防为墙
   */
  public function firewallDelete(Request $request, $id) {
    $db_service = \Drupal::service('qy_jd.db_service');
    $db_service->del_netcom($id);
    drupal_set_message('删除成功');
    return $this->redirect('admin.jd.route');
  }

  /**
   * 关闭防火墙监听及写黑洞
   */
  public function colseListen($route_id) {
    $path = \Drupal::service('settings')->get('qy_file_path') . '/jd/routevalue';
    if(!is_dir($path)) {
      mkdir($path, 0777, true);
    }
    $firewall_file = $path . '/restart_listen_'. $route_id .'.txt';
    file_put_contents($firewall_file, '0');
    $blackhole_file = $path . '/restart_blackhole_'. $route_id .'.txt';
    file_put_contents($blackhole_file, '0');
    sleep(1);
    drupal_set_message('关闭成功');
    return $this->redirect('admin.jd.route');
  }

  /**
   * 流量监控
   */
  public function FlowMonitor() {
    $path = drupal_get_path('module', 'qy_jd');
    $build['content'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'audio-path' => $path,
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'jd', 'list_type' => 'monitor'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content', 'qy_jd/drupal.audioload')
      )
    );
    return $build;
  }

  /**
   * 流量详细
   */
  public function FlowInfo($wall_id) {
    $data = array();
    $db_service = \Drupal::service('qy_jd.db_service');
    $netcoms = $db_service->load_netcom(array('id' => $wall_id));
    $hosts = array();
    foreach($netcoms as $netcom) {
      $hosts[$netcom->id] = array(
        'ip' => $netcom->ip,
        'user' => $netcom->username,
        'pass' => $netcom->password
      );
    }

    if(isset($hosts[$wall_id])) {
      $visit = new VisitEquipment();
      $host = $hosts[$wall_id];
      if(isset($_SESSION['host_cookies'][$wall_id])) {
        $host['cookie'] = $_SESSION['host_cookies'][$wall_id];
      } else {
        $cookies = $visit->login(array($wall_id => $host));
        if(!empty($cookies)) {
          $host['cookie'] = $cookies[$wall_id];
          $_SESSION['host_cookies'][$wall_id] = $cookies[$wall_id];
        }
      }
      if(isset($host['cookie'])) {
        $full = array();
        $xml = $visit->getWallInfo($host, array('param_view' => 5));
        $visit->getXmlParam($xml, $full, $data);
      }
    }

    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('防火墙', 'IP', '进入流量','过滤后流量', '进入包数', '过滤后包数', 'syn rate', 'ack rate', 'udp rate', 'icmp rate', 'frag rate', 'nonip rate', 'new tcp rate', 'new udp rate', 'tcp conn in', 'tcp conn out', 'udp conn', 'icmp conn'),
      '#rows' => array(),
      '#empty' => '无数据.'
    );
    foreach($data as $item) {
      $build['table']['#rows'][] = array(
        $host['ip'],
        $item['ip'],
        $item['in_bps'],
        $item['out_bps'],
        $item['in_pps'],
        $item['out_pps'],
        $item['syn_rate'],
        $item['ack_rate'],
        $item['udp_rate'],
        $item['icmp_rate'],
        $item['frag_rate'],
        $item['nonip_rate'],
        $item['new_tcp_rate'],
        $item['new_udp_rate'],
        $item['tcp_conn_in'],
        $item['tcp_conn_out'],
        $item['udp_conn'],
        $item['icmp_conn'],
      );
    }
    return $build;
  }

  /**
   * 流量报警值列表
   */
  public function AlarmList() {
    $db_service = \Drupal::service('qy_jd.db_service');
    $data = $db_service->load_alarmList();
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('防火墙', '最大报警值(Mbps)', '最小报警值(Mbps)', '报警延迟时间(秒)', '操作'),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $item) {
      $build['table']['#rows'][$item->id] = array(
        $item->ip,
        $item->max_bps,
        $item->min_bps,
        $item->delay_time,
        array(
          'data' => array(
            '#type' => 'operations', 
            '#links' => array(
              'update' => array(
                'title' =>'设置',
                'url' => new Url('admin.jd.alarm.edit', array('id' => $item->id))
              )
            )
          )
        )
      );
    }
    return $build;
  }

  /**
   * 导出日志
   */
  public function logsExport() {
    $db_service = \Drupal::service('qy_jd.db_service');
    $datas = $db_service->load_noPageLogs(array('log' => 1));
    qy_logs_export($datas);
    exit;
  }
}
  
