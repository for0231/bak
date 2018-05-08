<?php
/**
 * @file
 * Contains \Drupal\qy_wd\Controller\FirewallController.
 */

namespace Drupal\qy_wd\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\qy\FactoryAjaxListBuill;
use Drupal\qy\Blackhole\FactoryBlackhole;
use Drupal\qy_wd\VisitEquipment;

class FirewallController extends ControllerBase {
  /**
   * 开启防火墙监听
   */
  public function listenWall($route_id) {
    header('Content-Type: text/html; charset=utf-8');
    //判断线路
    $db_service= \Drupal::service('qy_wd.db_service');
    $route = $db_service->load_routeById($route_id);
    if(empty($route)) {
      return new Response('线路不存在');
    } else {
      if($route->status == 0) {
        return new Response('未设置为可监听线路');
      }
    }
    //设置为永久执行
    set_time_limit(0);
    ignore_user_abort(true);
    //用来关闭已有的内存循环
    $path = \Drupal::service('settings')->get('qy_file_path');
    $route_value_path = $path . '/wd/routevalue';
    if(!is_dir($route_value_path)) {
      mkdir($route_value_path, 0777, true);
    }
    $route_value_file = $route_value_path . '/restart_listen_'. $route_id .'.txt';
    $log_path = $path . '/wd/log';
    if(!is_dir($log_path)) {
      mkdir($log_path, 0777, false);
    }
    $value = time();
    file_put_contents($route_value_file, $value);
    //获取最小bps
    $config = \Drupal::config('qy_wd.settings');
    $min_bps = $config->get('min_bps');
    //结束请求
    echo str_repeat(' ' ,1000);
    echo '防火墙监听已开启';
    ob_end_flush();
    flush();

    //===开始永久执行===
    $visit = new VisitEquipment();
    ob_start();
    while(true) {
      echo "#####begin(". date("H:i:s") .")##### \r\n";
      //检查防火墙
      $visit->check_host($db_service, $route, $min_bps);
      //判断是否重新发请求了
      $is_return = false;
      $restart = file_get_contents($route_value_file);
      if($value != $restart) {
        $is_return = true;
        echo "关闭了防火墙数据监听\r\n";
      }
      //记录信息写到文件中
      $m = memory_get_usage();
      echo "内存使用: $m \r\n";
      echo "#####end##### \r\n\r\n";
      $str = ob_get_contents();
      if(!empty($str)) {
        $filename = $log_path . '/'. date("Ymd") . 'firewall.log';
        file_put_contents($filename, $str, FILE_APPEND);
      }
      ob_clean();
      //判断是否关闭
      if($is_return) {
        break;
      }
      sleep(1);
    }
    return new Response('');
  }

  /**
   * 写黑洞
   */
  public function listenBlackhole($route_id) {
    header('Content-Type: text/html; charset=utf-8');
    //判断线路
    $db_service= \Drupal::service('qy_wd.db_service');
    $routes = $db_service->load_route(array('status' => 1));
    if(!isset($routes[$route_id])){
      return new Response('未设置为写黑洞线路');
    }
    $route = $routes[$route_id];
    //设置为永久执行
    set_time_limit(0);
    ignore_user_abort(true);
    //用来关闭已有的内存循环
    $path = \Drupal::service('settings')->get('qy_file_path');
    $route_value_path = $path . '/wd/routevalue';
    if(!is_dir($route_value_path)) {
      mkdir($route_value_path, 0777, true);
    }
    $route_value_file = $route_value_path . '/restart_blackhole_'. $route_id .'.txt';
    $log_path = $path . '/wd/log';
    if(!is_dir($log_path)) {
      mkdir($log_path, 0777, false);
    }
    $value = time();
    file_put_contents($route_value_file, $value);
    //结束请求
    echo str_repeat(' ' ,1000);
    echo '写黑洞已开启';
    ob_end_flush();
    flush();
    //=====开始永久执行=======
    $visit = FactoryBlackhole::getInstance($route);
    $a = 0;
    $last_max_id = 0;//最大id;
    $last_count = 0; //总条数
    ob_start();
    while(true) {
      echo "#####begin(". date("H:i:s") .")##### \r\n";
      //写洞
      $items = $db_service->loadRouteqy($route->id);
      $current_count = count($items);
      $current_max_id = 0;
      if(!empty($items)) {
      	$max_qy = reset($items);
      	$current_max_id = $max_qy->id;
      }
      if($a == 0) {
        $visit->writeBlackhole($items, true);
        $last_max_id = $current_max_id;
        $last_count = $current_count;
      } else {
        if($current_max_id > $last_max_id || $current_count != $last_count) {
          if($a >= 300) {
            $visit->writeBlackhole($items, true);
            $last_max_id = $current_max_id;
            $last_count = $current_count;
            $a = 0;
          } else {
            $visit->writeBlackhole($items, false);
            $last_max_id = $current_max_id;
            $last_count = $current_count;
          }
        }
      }
      if($a >= 300) {
        $visit->writeBlackhole($items, true);
        $last_max_id = $current_max_id;
        $last_count = $current_count;
        $a = 0;
      }
      $a++;

      //判断是否重新发请求了
      $is_return = false;
      $restart = file_get_contents($route_value_file);
      if($value != $restart) {
        $is_return = true;
        echo "关闭了写黑洞\r\n";
      }

      //记录信息写到文件中
      $m = memory_get_usage();
      echo "内存使用: $m \r\n";
      echo "#####end##### \r\n\r\n";
      $str = ob_get_contents();
      if(!empty($str)) {
        $filename = $log_path . '/'. date("Ymd") . 'blackhold.log';
        file_put_contents($filename, $str, FILE_APPEND);
      }
      ob_clean();
      //判断是否关闭
      if($is_return) {
        break;
      }
      sleep(1);
    }
    return new Response('');
  }


  public function listenkill() {
    header('Content-Type: text/html; charset=utf-8');
    //设置为永久执行
    set_time_limit(0);
    ignore_user_abort(true);
    //用来关闭已有的内存循环
    $path = \Drupal::service('settings')->get('qy_file_path');
    $route_value_path = $path . '/wd/routevalue';
    if(!is_dir($route_value_path)) {
      mkdir($route_value_path, 0777, true);
    }
    $route_value_file = $route_value_path . '/restart_kill.txt';
    $value = time();
    file_put_contents($route_value_file, $value);
    //结束请求
    echo str_repeat(' ' ,1000);
    echo '解牵引开启成功';
    ob_flush();
    flush();

    $db_service= \Drupal::service('qy_wd.db_service');
    $routes = $db_service->load_route();
    $traction_list = FactoryAjaxListBuill::getList('qy_wd', 'traction');
    $traction_list_path = $route_value_path . '/traction_list.html';
    $traction_filter = FactoryAjaxListBuill::getList('qy_wd', 'tractionfilter');
    $traction_filter_path = $route_value_path . '/traction_filter.html';
    $flow_monitor = FactoryAjaxListBuill::getList('qy_wd', 'monitor');
    $flow_monitor_path = $route_value_path . '/monitor_firewall.html';
    $unit_routes = $db_service->load_routeUnit();
    while(true) {
      //解临时策略
      $now = time();
      $policies = $db_service->load_policy_nopage(array('xx' => 3));
      foreach($policies as $policy) {
        $time = $policy->starts + ($policy->kills * 60);
        if($time < $now ) {
          $db_service->del_policy($policy->id);
        }
      }
      //解牵引和生成静态表格
      $qy_type = array(); //保存各线路IP
      $qy_all_route = array(); //保存金局牵引的IP
      $qy_ips = array(); //要播报的高防IP
      $qy_list = array();
      $qy_filter = array();
      $qys = $db_service->load_qy();
      foreach($qys as $qy) {
        if($qy->time > 0) {
          $time = $qy->start + ($qy->time * 60);
          if($time < $now ) {
            $db_service->del_qy($qy);
            continue;
          }
        }
        //删除超限的的数据
        $route = $routes[$qy->net_type];
        $limit = $route->max_count;
        if(empty($qy_type[$qy->net_type])) {
          $qy_type[$qy->net_type] = array();
        }
        $count = count($qy_type[$qy->net_type]) + count($qy_all_route);
        if($count < $limit) {
          if($qy->state) {
            $qy_all_route[] = $qy->ip;
          } else {
            $qy_type[$qy->net_type][] = $qy->ip;
          }
        } else {
          $db_service->del_qy($qy);
          continue;
        }
        if($qy->start + 30 > $now && $qy->prompt_tip && !in_array($qy->ip, $qy_ips)) {
          $qy_ips[] = $qy->ip;
        }
        if($qy->gjft != 2) {
          $qy_list[] = $qy;
          if($qy->type != 'cleaning') {
            $qy_filter[] = $qy;
          }
        }
      }
      $qy_html = $traction_list->createHtml($qy_list, $routes);
      file_put_contents($traction_list_path, $qy_html);
      $qy_filter_html = $traction_filter->createHtml($qy_filter, $routes);
      file_put_contents($traction_filter_path, $qy_filter_html);
      $alarms = $db_service->load_alarm();
      $flow_monitor_html = $flow_monitor->createHtml($alarms, $unit_routes, $qy_ips);
      file_put_contents($flow_monitor_path, $flow_monitor_html);
      //判断是否重新发请求了
      $restart = file_get_contents($route_value_file);
      if($value != $restart) {
        break;
      }
      sleep(1);
    }
    return new Response('');
  }
}
