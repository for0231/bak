<?php
/**
 * @file
 * Contains \Drupal\qy_wd\Controller\WdController.
 */

namespace Drupal\qy_wd\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\qy_wd\TractionListBuilder;
use Drupal\qy_wd\PolicyListBuilder;
use Drupal\qy_wd\LogsListBuilder;
use Drupal\qy_wd\VisitEquipment;
use Drupal\qy_wd\RouteListBuilder;

class WdController extends ControllerBase {
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
      '#size' => 20,
      '#id' => 'filter_ip'
    );
    $db_service = \Drupal::service('qy_wd.db_service');
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
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'policy'))
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
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'policytmp'))
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
    $db_service = \Drupal::service('qy_wd.db_service');
    $db_service->update_policy(array(
      'zt' => $status == 'close' ? '1' : 0,
    ),$policy_id);
    return $this->redirect('admin.wd.policy_tmp');
  }

  /**
   * 删除策略
   */
  public function policyDelete(Request $request, $policy_id) {
    $url = $request->query->get('destination');
    $db_service = \Drupal::service('qy_wd.db_service');
    $db_service->del_policy($policy_id);
    drupal_set_message('删除成功');
    return new RedirectResponse($url);
  }

  /**
   *删除整段策略
   */
  public function segmentDelete(Request $request, $policy_id) {
    $url = $request->query->get('destination');
    $db_service = \Drupal::service('qy_wd.db_service');
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
      '#size' => 20,
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
      '#url' => new Url('admin.wd.traction.remove.alarm', array(
         'destination' => $this->url('<current>')
      )),
    );

    $build['contnet'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'traction'))
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
      '#size' => 20,
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
      '#url' => new Url('admin.wd.traction.remove.alarm', array(
         'destination' => $this->url('<current>')
      )),
    );

    $build['content'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'tractionfilter'))
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
      '#size' => 20,
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
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'ipstop'))
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
    $db_service = \Drupal::service('qy_wd.db_service');
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
    $db_service = \Drupal::service('qy_wd.db_service');
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
      '#size' => 20,
      '#id' => 'filter_ip'
    );
    $db_service = \Drupal::service('qy_wd.db_service');
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
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'logqy'))
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
      '#size' => 20,
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
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'logsipstop'))
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
    $options = array('一单元', '二单元','三单元', '四单元','五单元', '六单元','七单元', '八单元','九单元', '十单元','十一单元', '十二单元','十三单元', '十四单元','十五单元', '十六单元');
    $build['wallinfo'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'wallinfo'),
      )
    );
    $config = \Drupal::config('qy_wd.settings');
    $listen_unit = $config->get('listen_unit');
    $units = array();
    if(!empty($listen_unit)) {
      $units = explode(',', $listen_unit);
    }
    foreach ($options as $key => $value) {
      $build['wallinfo'][$key] = array(
        '#type' => 'checkbox',
        '#title' => $value,
        '#return_value' => $key + 1,
        '#name' => 'wallinfo'
      );
      if(in_array($key+1, $units)) {
        $build['wallinfo'][$key]['#checked'] = 'checked';
      }
    }

    $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline', 'list-filter'),
      )
    );

    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 20,
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
        'ajax-path' => \Drupal::url('admin.wd.ip_check.ajax')
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
    $visit = new VisitEquipment();
    $ip = $request->query->get('filter_ip');
    $wall_str = '';
    if(empty($ip)) {
      $config = \Drupal::config('qy_wd.settings');
      $listen_unit = $config->get('listen_unit');
      if(!empty($listen_unit)) {
        $units = explode(',', $listen_unit);
        $wall_str = $visit->getFirewallStr($units);
      }
    } else {
      $ip = str_replace('-', '.', $ip);
      $wallinfo = $request->query->get('wallinfo');
      $wall_arr = explode('-', $wallinfo);
      $wall_str = $visit->getFirewallStr($wall_arr);
    }
    $hosts = array();
    if(!empty($wall_str)) {
      if(empty($ip)) {
        $config = \Drupal::config('qy_wd.settings');
        $min_bps = $config->get('min_bps');
        $hs = $visit->iplist($wall_str, $min_bps);
        if(!empty($hs)) {
          unset($hs['full']);
          foreach($hs as $key => $row) {
            $hs[$key]['in_bps'] = round($row['in_bps'] / 128, 2);
            $hs[$key]['out_bps'] = round($row['out_bps'] / 128, 2);
            $volume[$key] = $row['in_bps'];
          }
          array_multisort($volume, SORT_DESC, $hs);
          $hosts = array_slice($hs, 0, 100);
        }
      } else {
        if(!strcmp(long2ip(sprintf("%u",ip2long($ip))), $ip)) {
          $host = $visit->oneIpList($wall_str, $ip);
          if(!empty($host)) {
            $host['in_bps'] = round($host['in_bps'] / 128, 2);
            $host['out_bps'] = round($host['out_bps'] / 128, 2);
            $hosts[] = $host;
          }
        }
      }
    }
    $content = array(
      '#theme' => 'ip_checked',
      '#hosts' => $hosts
    );
    $build = drupal_render($content);
    return new Response($build);
  }

  /**
   * 后台首页
   */
  public function adminIndex() {
    $db_service= \Drupal::service('qy_wd.db_service');
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
    $db_service = \Drupal::service('qy_wd.db_service');
    $db_service->del_route($id);
    //修改监听线路
    $exist_unit = $db_service->load_routeUnit();
    $listen_unit = array_keys($exist_unit);
    sort($listen_unit);
    $listen_str = implode(',', $listen_unit);
    $config = \Drupal::configFactory()->getEditable('qy_wd.settings');
    $config->set('listen_unit', $listen_str);
    $config->save();

    drupal_set_message('删除成功');
    return $this->redirect('admin.wd.route');
  }

  /**
   * 关闭防火墙监听及写黑洞
   */
  public function colseListen($route_id) {
    $path = \Drupal::service('settings')->get('qy_file_path') . '/wd/routevalue';
    if(!is_dir($path)) {
      mkdir($path, 0777, true);
    }
    $firewall_file = $path . '/restart_listen_'. $route_id .'.txt';
    file_put_contents($firewall_file, '0');
    $blackhole_file = $path . '/restart_blackhole_'. $route_id .'.txt';
    file_put_contents($blackhole_file, '0');
    sleep(1);
    drupal_set_message('关闭成功');
    return $this->redirect('admin.wd.route');
  }

  /**
   * 流量监控
   */
  public function FlowMonitor() {
    $path = drupal_get_path('module', 'qy_wd');
    $build['content'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('ajax-content'),
        'ajax-refresh' => 'true',
        'audio-path' => $path,
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'wd', 'list_type' => 'monitor'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content', 'qy_wd/drupal.audioload')
      )
    );
    return $build;
  }

  /**
   * 流量详细
   */
  public function FlowInfo($wall_id) {
    $data = array();
    if($wall_id > 0 && $wall_id < 17) {
      $visit = new VisitEquipment();
      $wall_str = $visit->getFirewallStr(array($wall_id));
      $config = \Drupal::config('qy_wd.settings');
      $min_bps = $config->get('min_bps');
      $data = $visit->iplist($wall_str, $min_bps);
      if(!empty($data)) {
        unset($data['full']);
      }
    }
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('单元', 'IP', '进入包数', '滤后包数', '进入流量(Mbps)', '滤后流量(Mbps)', '进入的tcp数','进入的udp数','进入的icmp数', '防护状态'),
      '#rows' => array(),
      '#empty' => '无数据.'
    );
    if(!empty($data)) {
      foreach($data as $key => $item) {
        $volume[$key] = $item['in_bps'];
      }
      array_multisort($volume, SORT_DESC, $data);
      $index = 1;
      foreach($data as $item) {
        $build['table']['#rows'][] = array(
          $wall_id,
          $item['ip'],
          $item['in_pps'],
          $item['out_pps'],
          round($item['in_bps'] / 128, 2),
          round($item['out_bps'] / 128, 2),
          $item['in_tcp'],
          $item['in_udp'],
          $item['in_icmp'],
          $item['status']
        );
        $index++;
        if($index > 100) {
          break;
        }
      }
    }
    return $build;
  }

  /**
   * 报警值列表
   */
  public function AlarmList() {
    $data = array();
    $config = \Drupal::config('qy_wd.settings');
    $listen_unit = $config->get('listen_unit');
    if(!empty($listen_unit)) {
      $units = explode(',', $listen_unit);
      $db_service = \Drupal::service('qy_wd.db_service');
      $data = $db_service->load_alarm(array('id' => array('value' => $units, 'op' => 'IN')));
    }
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('单元', '最大报警值(Mbps)', '最小报警值(Mbps)', '报警延迟时间(秒)', '操作'),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $item) {
      $build['table']['#rows'][$item->id] = array(
        $item->id,
        $item->max_bps,
        $item->min_bps,
        $item->delay_time,
        array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(
              'update' => array(
                'title' =>'设置',
                'url' => new Url('admin.wd.alarm.edit', array('id' => $item->id))
              )
            )
          )
        )
      );
    }
    return $build;
  }

  /**
   * 流量图表
   */
  public function flowChart() {
   $build['filter'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#title' => 'IP',
      '#size' => 20,
      '#id' => 'edit-ip'
    );
    $build['filter']['stime'] = array(
      '#type' => 'textfield',
      '#title' => '开始时间',
      '#size' => 20,
      '#value' => date('Y-m') . '-01',
      '#id' => 'edit-stime'
    );
    $build['filter']['etime'] = array(
      '#type' => 'textfield',
      '#title' => '结束时间',
      '#size' => 20,
      '#value' => date('Y-m-d'),
      '#id' => 'edit-etime'
    );
    $build['filter']['search'] = array(
      '#type' => 'button',
      '#value' => '查询',
      '#id' => 'search'
    );
    $build['chart'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'chartcontainer',
        'style' => 'height: 550px; min-width: 500px'
      ),
      '#attached' => array(
        'library' => array('qy/drupal.attack-chart', 'core/jquery.ui.datepicker')
      )
    );
    return $build;
  }
  
  /**
   * 获取数据
   */
  public function getData(Request $request) {
    set_time_limit(0);
    //判断格式
    $ip = $request->query->get('ip');
    $ip_arr = explode('--', $ip);
    $re_arr = array_reverse($ip_arr);
    $re_ip = implode('.', $re_arr);
    if(strcmp(long2ip(sprintf("%u",ip2long($re_ip))), $re_ip)) {
      return new JsonResponse('no');
    }
    $stime = $request->query->get('stime');
    $sunixTime = strtotime($stime);
    if(!$sunixTime) {
      return new JsonResponse('time');
    }
    $etime = $request->query->get('etime');
    $eunixTime = strtotime($etime);
    if(!$eunixTime) {
      return new JsonResponse('time');
    }
    if(date('Ym',$sunixTime) != date('Ym', $eunixTime)) {
      return new JsonResponse('timeequal');
    }
    //计算要查询的天数
    $eunixTime = $eunixTime + (24*3600-1);
    $s_day = date('j', $sunixTime);
    $e_day = date('j', $eunixTime);
    $day = ($e_day - $s_day) + 1;
    //计算文件的路径
    $year_month = date('Ym', $sunixTime);
    $key = bindec(decbin(ip2long($re_ip)));
    $segment = 't' . $ip_arr[0] . 'g' . $ip_arr[1] . 'l' . $ip_arr[2];
    $base_path = \Drupal::service('settings')->get('qy_file_path') . '/wd/chart';
    $path = $base_path . '/'. $year_month . '/'. $segment . '/' . $key . '.txt';
    if(file_exists($path)) {
      $interval = floor((15840 / 720) * $day); //计算出间距
      $value = array();
      $index = 1;
      $bps = 0;
      $time = 0;
      $file = fopen($path, "r");
      while(!feof($file)) {
        $row = trim(fgets($file));
        if(empty($row)) {
          continue;
        }
        $row_arr = explode(':', $row);
        $time = $row_arr[0];
        if($time < $sunixTime) {
          continue;
        }
        if($time > $eunixTime) {
          break;
        }
        $bps += $row_arr[1];
        if($index == $interval) {
          $bps = $bps/128;
          $value[] = array($time * 1000, round($bps/$index, 4));
          $bps = 0;
          $index = 0;
        }
        $index++;
      }
      if($bps > 0) {
        $bps = $bps/128;
        $value[] = array($time * 1000, round($bps/$index, 4));
      }
      fclose($file);
      return new JsonResponse($value);
    }
    return new JsonResponse('no');
  }

  /**
   * 收集防火墙牵引数据
   */
  public function collectData($key) {
    if($key == '6f5e793198af4fdf897c74b9ede3c9de') {
      set_time_limit(0);
      $base_path = \Drupal::service('settings')->get('qy_file_path') . '/wd/chart';
      $config = \Drupal::config('qy_wd.settings');
      $units = explode(',', $config->get('listen_unit'));
      $ips = explode(',', $config->get('chart_save_ips'));
      $log_ips = array();
      foreach($ips as $ip) {
        $ip_arr = explode('.', $ip);
        $re_arr = array_reverse($ip_arr);
        $re_ip = implode('.', $re_arr);
        $key = bindec(decbin(ip2long($re_ip)));
        $log_ips[(string)$key] = 't' . $ip_arr[0] . 'g' . $ip_arr[1] . 'l' . $ip_arr[2];
      }
      $visit = new VisitEquipment();
      $data = array();
      for($n=0; $n < 11; $n++) {
        $items = $visit->saveChartDate($base_path, $units, $log_ips);
        foreach($items as $ip => $item) {
          if(array_key_exists($ip, $data)) {
            $data[$ip]['content'] = $data[$ip]['content'] . $item['content'];
          } else {
            $data[$ip] = $item;
          }
        }
        sleep(5);
      }
      foreach($data as $file) {
        file_put_contents($file['file'], $file['content'], FILE_APPEND);
      }
    }
    return new Response('', 204);
  }

  /**
   * 导出日志
   */  
  public function logsExport() {
    set_time_limit(0);
    $db_service = \Drupal::service('qy_wd.db_service');
    $datas = $db_service->load_noPageLogs(array('log' => 1));
    qy_logs_export($datas);
    exit;
  }
}
