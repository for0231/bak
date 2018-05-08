<?php
/**
 * @file
 * Contains \Drupal\qy\Controller\QyController.
 */

namespace Drupal\qy\Controller;

use Drupal\Core\Controller\ControllerBase;
use \Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\qy\FactoryAjaxListBuill;

class QyController extends ControllerBase {
  /**
   * 处理403和404错误
   */
  public function requeryError() {
    return array(
      '#markup' => '您请求的页面不存在或没有权限，请' . \Drupal::l('点击跳转到首页', new Url('<front>'))
    );
    return $build;
  }

  /**
   * 请处ajax请求数据
   */
  public function ajaxList(Request $request, $module_provider, $list_type) {
    $provider = qy_module_provider();
    $moduleName = $provider[$module_provider];
    $list = FactoryAjaxListBuill::getList($moduleName, $list_type);
    $filter = array();
    $conditions = $request->query->all();
    foreach($conditions as $key => $value) {
      if($key == 'page') {
        continue;
      }
      $arr_key = explode('_', $key);
      if($key == 'filter_ip') {
        $filter['ip'] = array(
          'value' => str_replace('-', '.', $value),
          'op' => 'like'
        );
      } else {
        $filter[$arr_key[1]] = $value;
      }
    }
    if(!empty($filter)) {
      $list->setFilter($filter);
    } else {
      $path = \Drupal::service('settings')->get('qy_file_path') . '/' . $module_provider . '/routevalue';
      $file = '';
      if($list_type == 'traction') {
        $file = $path . '/traction_list.html';
      } else if($list_type == 'tractionfilter') {
        $file = $path . '/traction_filter.html';
      } else if ($list_type == 'monitor') {
        $file = $path . '/monitor_firewall.html';
      }
      if($file != '' && file_exists($file)) {
        $data = file_get_contents($file);
        if(!empty($data)) {
          return new Response($data);
        }
      }
    }
    $data = $list->render();
    return new Response($data);
  }

  /**
   * 牵引邮件列表
   */
  public function mailList() {
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
        'ajax-path' => \Drupal::url('admin.qy.ajax.list', array('module_provider' => 'qy', 'list_type' => 'qyemail'))
      ),
      '#attached' => array(
        'library' => array('qy/drupal.ajax-content')
      )
    );
    return $build;
  }

  /**
   * 删除邮箱
   */
  public function mailDelete($mail_id) {
    $mail_service = \Drupal::service('qy.emial_service');
    $mail_service->del_email($mail_id);
    drupal_set_message('删除成功');
    $url = \Drupal::url('admin.qy.mail.list');
    return new RedirectResponse($url);
  }
  
  /**
   * 发送邮件
   */
  public function sendMail($key) {
    $qy_config = \Drupal::config('qy.settings');
    $send_mail = $qy_config->get('send_mail');
    if($send_mail && $key == '6f5e793198af4fdf897c74b9ede3c9de') {
      set_time_limit(0);
      $count = 10;
      $open_modules = qy_module_open_firewall();
      foreach($open_modules as $module) {
        $email = FactoryAjaxListBuill::getList($module, 'sendMail');
        $number = $email->send($count);
        $count = $count - $number;
      }
    }
    return new Response('', 204);
  }

  /**
   * 清除日志
   */
  public function logsClear($key) {
    if($key == '6f5e793198af4fdf897c74b9ede3c9de') {
      $open_modules = qy_module_open_firewall();
      $time = REQUEST_TIME - 3600*24*30;
      foreach($open_modules as $module) {
        $db_service = \Drupal::service($module . '.db_service');
        $db_service->del_logs(array('start' => array('value'=> $time, 'op' => '<')));
      }
      db_delete('watchdog')
        ->condition('timestamp', $time, '<')
        ->execute();
    }
    return new Response('', 204);
  }
  /**
   * 执行同步数据
   */
  public function syncDataExec() {
    set_time_limit(0);
    if(empty($_GET['callback'])) {
      return new Response('参数错误');
    }
    $callback = $_GET['callback'];
    if(empty($_GET['user']) || empty($_GET['pass'])) {
      $return = $callback . '({"info":"参数错误"})';
      return new Response($return);
    }
    $users = entity_load_multiple_by_properties('user', array('name' => $_GET['user'], 'status' => 1));
    if(empty($users)) {
      $return = $callback . '({"info":"用户名错误"})';
      return new Response($return);
    }
    $user = reset($users);
    if(!\Drupal::service('password')->check($_GET['pass'], $user->getPassword())) {
      $return = $callback . '({"info":"密码错误"})';
      return new Response($return);
    }
    if(!$user->hasPermission('administer qy sync data')) {
      $return = $callback . '({"info":"无权限"})';
      return new Response($return);
    }
    $path = \Drupal::service('settings')->get('qy_file_path');
    if(!is_dir($path . '/sync/')) {
      mkdir($path . '/sync/', 0777, true);
    }
    $sync_file = $path . '/sync/syncData.txt';
    $return_file = $path . '/sync/syncResult.txt';
    if(file_exists($sync_file) || file_exists($return_file)) {
      $return = $callback . '({"info":"临时文本错误，无法执行同步"})';
      return new Response($return);
    }
    //关闭监听
    $walls = qy_module_open_firewall();
    foreach($walls as $wall) {
      if($wall == 'qy_wd') {
        $wall_path = $path . '/wd/routevalue/';
      } else {
        $wall_path = $path . '/jd/routevalue/';
      }
      $fileNames = scandir($wall_path);
      foreach($fileNames as $fileName) {
        if(stripos($fileName, 'restart_') !== false) {
          file_put_contents($wall_path . $fileName, '0');
        }
      }
    }
    sleep(1);
    //执行判断文本
    file_put_contents($sync_file, '1');
    //清空原来的牵引条目
    foreach($walls as $wall) {
      if($wall == 'qy_wd') {
        db_query('truncate t_qy');
      } else {
        db_query('truncate jd_qy');
      }
    }
    //判断执行情况.
    $exec = false;
    $n = 1;
    while(true) {
      if(file_exists($return_file)) {
        $exec = true;
        break;
      }
      if($n > 180) {
        break;
      }
      $n++;
      if(n > 3) {
        usleep(100000);
      } else {
        sleep(1);
      }
    }
    drupal_flush_all_caches();
    if($exec) {
      if (unlink($return_file)) {
        $return = $callback . '({"info":"同步命令执行成功"})';
      } else {
        $return = $callback . '({"info":"同步命令执行成功, 但删除临时文件出错"})';
      }
    } else {
      $return = $callback . '({"info":"同步命令执行失败"})';
    }
    return new Response($return);
  }
}