<?php
/**
 * @file
 * Contains \Drupal\fw_config\Controller\ShieldController.
 */

namespace Drupal\fw_config\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ShieldController extends ControllerBase {
  /**
   *查询
   */
  public function shieldQuery() {
    $config = \Drupal::config('fw_config.settings');
    $ip = str_replace('-', '.', $_GET['hostaddr']);
    $ip_library = $config->get('ip_library');
    $arr = explode('.', $ip);
    $subnet_ip = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
    if(!in_array($subnet_ip, $ip_library)) {
      $res['status'] = 'false';
      $res['msg'] = '无效IP';
      return new JsonResponse($res);
    }
    $conn = new \Drupal\fw_config\FwConnect();
    $read_fw = $config->get('read_fws');
    $read_fws = explode("-", $read_fw);
    $login = $conn->login($read_fws[0], $read_fws[1], $read_fws[2]);
    if(!$login) {
      $res['status'] = 'false';
      $res['msg'] = '登录失败';
      return new JsonResponse($res);
    }
    $url = 'http://'. $read_fws[0] .'/cgi-bin/status_hostset.cgi?hostaddr=' . $ip;
    $xml = $conn->getRead($url);
    if(is_object($xml)) {
      $res['status'] = 'true';
    } else {
      $res['status'] = 'false';
      $res['msg'] = $xml;
      return new JsonResponse($res);
    }
    $res['ip'] = (string)$xml->address;
    $res['ignore'] = (string)$xml->ignore;
    $res['forbid'] = (string)$xml->forbid;
    $res['forbid_overflow'] = (string)$xml->forbid_overflow;
    $res['reject_foreign'] = (string)$xml->reject_foreign;
    $res['param_set'] = (int)$xml->param_set;
    $res['filter_set'] = (int)$xml->filter_set;
    $res['portpro_set_tcp'] = (int)$xml->portpro_set_tcp;
    $res['portpro_set_udp'] = (int)$xml->portpro_set_udp;
    $res['param_plugin'] = array();
    $plugins = $xml->plugin;
    foreach($plugins as $plugin) {
      $protocol = (string)$plugin->protocol;
      $id = (string)$plugin->id;
      $res['param_plugin'][$protocol . '_' . $id] = (string)$plugin->enabled;
    }
    return new JsonResponse($res);
  }
  /**
   * 保存IP防护
   */
  public function shieldSave() {
    if(empty($_POST)) {
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    if(empty($_POST['param_setting_addr'])) {
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    $config = \Drupal::config('fw_config.settings');
    $ip = $_POST['param_setting_addr'];
    $ip_library = $config->get('ip_library');
    $arr = explode('.', $ip);
    $subnet_ip = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
    if(!in_array($subnet_ip, $ip_library)) {
      $res['msg'] = '无效IP';
      return new JsonResponse($res);
    }
    $conn = new \Drupal\fw_config\FwConnect();
    $write_fw = $config->get('write_fws');
    $write_fws = explode("\r\n", $write_fw);
    $msg = array();
    foreach($write_fws as $item) {
      $item_arr = explode("-", $item);
      $url = 'http://'. $item_arr[0] .'/cgi-bin/status_hostset.cgi';
      $login = $conn->login($item_arr[0], $item_arr[1], $item_arr[2]);
      if(!$login) {
        $msg[] = $item_arr[0] . '登录失败';
        continue;
      }
      $fw_post = array('param_exist' => 'ON') + $_POST;
      if(!$item_arr[3] && isset($fw_post['param_plugin_udp_4'])) {
        unset($fw_post['param_plugin_udp_4']);
      }
      $xml = $conn->postData($url, $fw_post);
      if (is_object($xml)) {
        $msg[] = $item_arr[0] . ':' . (string)$xml->info;;
      } else {
        $msg[] = $item_arr[0] . ':' . $xml;
      }
    }
    $res['msg'] = implode("\r\n", $msg);
    //记录日志
    $logger = \Drupal::logger('fw_config');
    $infos = $this->shieldInfo();
    $log = array();
    foreach($_POST as $key => $val) {
      $log[] = $infos[$key] . '：' . $val;
    }
    $logger->info(implode('<br/>', $log));
    return new JsonResponse($res);
  }
  /**
   * 防护提交数据
   */
  private function shieldInfo() {
    return array(
      'param_setting_addr' => 'IP',
      'param_ignore' => '忽视所有流量',
      'param_forbid' => '屏蔽所有流量',
      'param_forbid_overflow' => '流量超出屏蔽',
      'param_reject_foreign_access' => '拒绝国外访问',
      'param_param_set' => '防护参数集',
      'param_filter_set' => '过滤规则集',
      'param_portpro_set_tcp' => 'TCP端口集',
      'param_portpro_set_udp' => 'UDP端口集',
      'param_plugin_tcp_0' => 'tcp WEB Service Protection v8.89',
      'param_plugin_tcp_1' => 'tcp Game Service Protection v4',
      'param_plugin_tcp_2' => 'tcp Misc Service Protection v3.2',
      'param_plugin_tcp_3' => 'tcp DNS Service Protection v2.3',
      'param_plugin_udp_1' => 'udp DNS Service Protection v2.3',
      'param_plugin_udp_4' => 'UDP App Protection v1.0'
    );
  }
  /**
   *ip防护查询
   */
  public function shieldFilter() {
    $build['filter'] = array(
      '#type' => 'fieldset',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textarea',
      '#cols' => 60,
      '#rows' => 5,
      '#value' => '',
      '#id' => 'shield-ips',
      '#placeholder' => '一行输入一个IP'
    );
    $build['filter']['query'] = array(
      '#type' => 'button',
      '#value' => '查询',
      '#id' => 'ip-shield-filter'
    );
    $build['table'] = array(
      '#type' => 'table',
      '#id' => 'filter-return',
      '#header' => array(
        'IP',
        '忽略所有流量',
        '屏蔽所有流量',
        '流量超出屏蔽',
        '拒绝国外访问',
        '防护参数集',
        '过滤规则集',
        'TCP端口集',
        'UDP端口集',
        'tcp WEB',
        'tcp Game',
        'tcp Misc',
        'tcp DNS',
        'udp DNS',
        'UDP App'
      ),
      '#empty' => '无数据'
    );
    $build['#attached'] = array(
      'library' => array('fw_config/drupal.ip-shield', 'fw_config/drupal.fw-common')
    );
    return $build;
  }

  /**
   * 屏弊列表
   */
  public function fblinkList() {
    $build['filter'] = array(
      '#type' => 'fieldset',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $build['filter']['iplocal'] = array(
      '#type' => 'textfield',
      '#id' => 'ip-iplocal',
      '#title' => '本地地址',
      '#required'=> true,
      '#size'=>'35'
    );
    $build['filter']['ipremote'] = array(
      '#type' => 'textfield',
      '#id' => 'ip-ipremote',
      '#title' => '远程地址',
      '#size'=>'35'
    );
    $build['filter']['query'] = array(
      '#type' => 'button',
      '#value' => '查询',
      '#id' => 'ip-fblink-query'
    );
    $build['filter']['reset'] = array(
      '#type' => 'button',
      '#value' => '重置',
      '#id' => 'ip-fblink-reset'
    );
    $build['content'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'query-content',
      )
    );
    $build['#attached'] = array(
      'library' => array('fw_config/drupal.fblink-list')
    );
    return $build;
  }


  /**
   * 屏弊查询
   */
  public function fblinkQuery() {
    if(empty($_POST)) {
      $res['status'] = 'false';
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    if(empty($_POST['param_iplocal'])) {
      $res['status'] = 'false';
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    $iplocal = $_POST['param_iplocal'];
    $ipremote = $_POST['param_ipremote'];
    $config = \Drupal::config('fw_config.settings');
    $ip_library = $config->get('ip_library');
    $arr = explode('.', $iplocal);
    $subnet_ip = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
    if(!in_array($subnet_ip, $ip_library)) {
      $res['status'] = 'false';
      $res['msg'] = '无效IP';
      return new JsonResponse($res);
    }
    $readmore_fws = $config->get('readmore_fws');
    $readmore_fws = explode("\r\n", $readmore_fws);
    $rows = array();
    //提示消息
    $infos = array();
    $conn = new \Drupal\fw_config\FwConnect();
    foreach ($readmore_fws as $readmore_fw){
      $read_fws = explode("-", $readmore_fw);
      $url = 'http://'. $read_fws[0] .'/cgi-bin/status_fblink.cgi';
      $login = $conn->login($read_fws[0], $read_fws[1], $read_fws[2]);
      if(!$login) {
        $infos[]= $read_fws[0].':登录失败';
        continue;
      }
      $xml = $conn->postData($url, array('param_submit_type' => 'select', 'param_this_sort' => 1,'param_filter'=>$iplocal));
      if (is_object($xml)) {
        $fblink = $xml->fblink;
        //不查找远程地址
        if(empty($ipremote)){
          foreach($fblink as $item) {
            $rows[] = array(
              $readmore_fw,
              (string)$item->local_address,
              (string)$item->remote_address,
              (string)$item->release_time,
              (string)$item->forbid_reason
            );
          }
        }else{
          foreach($fblink as $item) {
            if($item->remote_address == $ipremote){
              $rows[] = array(
                $readmore_fw,
                (string)$item->local_address,
                (string)$item->remote_address,
                (string)$item->release_time,
                (string)$item->forbid_reason
              );
            }
          }
        }
      } else {
        $infos[]= $read_fws[0]. ':' .$xml;
      }
    }
    if(empty($infos)){
      $res['status'] = 'true';
    }else{
      $res['status'] = 'false';
      $res['msg'] = implode("\r\n", $infos);
    }
    $build = $this->formBuilder()->getForm('Drupal\fw_config\Form\FwLinkForm', $rows);
    $res['content'] = drupal_render($build);
    return new JsonResponse($res);
  }
  /**
   * 屏弊重置
   */
  public function fblinkReset() {
    $list = explode(",",$_POST['param_filter']);
    $fw_list =array();
    $fw=array();
    foreach($list as $value) {
      $fw[] = explode("|", $value);
    }
    foreach($fw as $k1=>$v1){
      $fw_list[$v1[0]][]=$v1[1];
    }
    $param =$_POST['param_filter'];
    if(empty($_POST)) {
      $res['status'] = false;
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    $conn = new \Drupal\fw_config\FwConnect();
    $msg = array();
    $status = false;
    foreach($fw_list as $key=>$item) {
      $param=implode(",", $item);
      $item_arr = explode("-", $key);
      $url = 'http://'. $item_arr[0] .'/cgi-bin/status_fblink.cgi';
      $login = $conn->login($item_arr[0], $item_arr[1], $item_arr[2]);
      if(!$login) {
        $msg[] = $item_arr[0] . ':登录失败';
        continue;
      }
      $xml = $conn->postData($url, array('param_submit_type' => 'reset', 'param_this_sort' => 1,'param_filter'=>$param));
      if (is_object($xml)) {
        $status = true;
        $msg[] = $item_arr[0] . (string)$xml->info;
      } else {
        $res[] = $item_arr[0] . $xml;
      }
    }
    $res['status'] = $status;
    $res['msg'] = implode("\r\n", $msg);
    return new JsonResponse($res);
  }
  /**
   * 更新IP库
   */
  public function ipLibrary() {
    $res = array();
    $config = \Drupal::configFactory()->getEditable('fw_config.settings');
    $read_fw = $config->get('read_fws');
    $read_fws = explode("-", $read_fw);
    $conn = new \Drupal\fw_config\FwConnect();
    $login = $conn->login($read_fws[0], $read_fws[1], $read_fws[2]);
    if(!$login) {
      $res['msg'] = '登录失败';
      return new JsonResponse($res);
    }
    $url = 'http://'. $read_fws[0] .'/cgi-bin/status_host.cgi';
    $xml = $conn->getRead($url);
    if(is_object($xml)) {
      $hosts = $xml->host;
      $ips = array();
      foreach($hosts as $host) {
        if($host->type == 'subnet') {
          $address = (string)$host->address;
          $arr = explode('.', $address);
          $ips[] = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
        }
      }
      $config->set('ip_library', $ips);
      $config->save();
      $res['msg'] = '更新成功';
    } else {
      $res['msg'] = $xml;
    }
    return new JsonResponse($res);
  }
  /**
   * 日志列表
   */
  public function logList() {
    $container = \Drupal::getContainer();    
    $database = $container->get('database');
    $query = $database->select('watchdog', 'w')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('w', array('wid','uid','timestamp','message'));
    $query->condition('type', 'fw_config');
    if(!empty($_SESSION['fw_log_filter']['uid'])) {
      $query->condition('uid', $_SESSION['fw_log_filter']['uid']);
    }
    if(!empty($_SESSION['fw_log_filter']['keyword'])) {
      $keyword = $_SESSION['fw_log_filter']['keyword'];
      $query->condition('message', '%' . $keyword . '%', 'LIKE');
    }
    $result = $query
      ->orderBy('wid', 'DESC')
      ->limit(50)
      ->execute()
      ->fetchAll();
    $rows = array();
    foreach($result as $item) {
      $message = $item->message;
      $title = Unicode::truncate(Html::decodeEntities(strip_tags($message)), 256, TRUE, TRUE);
      $log_text = Unicode::truncate($title, 56, TRUE, TRUE);
      $message = $this->l($log_text, new Url('admin.fw.log.info', array('event_id' => $item->wid), array(
        'attributes' => array(
          'title' => $title,
        ),
      )));
      $rows[] = array(
        date('Y-m-d H:i:s', $item->timestamp),
        entity_load('user', $item->uid)->label(),
        $message,
      );
    }
    $build['filter'] = $container->get('form_builder')->getForm('Drupal\fw_config\Form\LogListFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array('时间', '操作员', '内容'),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    return $build;
  }
  
  /**
   * 连接监控列表
   */
  public function conmonList() {
    $build['filter'] = array(
      '#type' => 'fieldset',
      '#attributes' => array(
        'class' => array('container-inline'),
      )
    );
    $build['filter']['ip'] = array(
      '#type' => 'textfield',
      '#id' => 'ip-fblink',
    );
    $build['filter']['query'] = array(
      '#type' => 'button',
      '#value' => '查询',
      '#id' => 'ip-fblink-query'
    );
    $build['content'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'query-content',
      )
    );
    $build['#attached'] = array(
      'library' => array('fw_config/drupal.conmon-list')
    );
    return $build;
  }

  /**
   * 连接监控查询
   */
  public function conmonQuery() {
    if(empty($_POST)) {
      $res['status'] = 'false';
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    if(empty($_POST['param'])) {
      $res['status'] = 'false';
      $res['msg'] = '操作错误!';
      return new JsonResponse($res);
    }
    $config = \Drupal::config('fw_config.settings');
    $ip = $_POST['param'];
    $ip_library = $config->get('ip_library');
    $arr = explode('.', $ip);
    $subnet_ip = $arr[0] . '.' . $arr[1] . '.' . $arr[2];
    if(!in_array($subnet_ip, $ip_library)) {
      $res['status'] = 'false';
      $res['msg'] = '无效IP';
      return new JsonResponse($res);
    }
    $conn = new \Drupal\fw_config\FwConnect();
    $readmore_fws = $config->get('readmore_fws');
    $readmore_fws = explode("\r\n", $readmore_fws);
    $rows = array();
    $infos = array();
    foreach ($readmore_fws as $readmore_fw){
      $read_fws=explode("-", $readmore_fw);
      $url = 'http://'. $read_fws[0] .'/cgi-bin/status_link.cgi';
      $login = $conn->login($read_fws[0], $read_fws[1], $read_fws[2]);
      if(!$login) {
        $infos[] = $read_fws[0] . ':登录失败';
        continue;
      }
      $xml = $conn->postData($url, array('param_submit_type' => 'select', 'link_conn_in' => 'on','link_conn_out' => 'on','param_this_sort' => 1,'param_filter'=>$ip));
      if(is_object($xml)){
        $fbconmon = $xml->link;
        foreach($fbconmon as $item) {
          $rows[]= array(
            (string)$item->local_address,
            (string)$item->remote_address,
            (string)$item->port_links,
            (string)$item->total_links
          );
        }
      } else {
        //全部或部分连接失败
        $infos[] = $read_fws[0] . $xml;
      }
    }
    if(empty($infos)) {
      $res['status'] = 'true';
    } else {
      $res['status'] = 'false';
      $res['msg'] = implode("\r\n", $infos);
    }
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('本地地址','远程地址','活动连接','全部连接'),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    $res['content'] = drupal_render($build);
    return new JsonResponse($res);
  }
  /**
   * 规则列表
   */
  public function ruleList() {
    $config = \Drupal::config('fw_config.settings');
    $conn = new \Drupal\fw_config\FwConnect();
    $readfw = $config->get('read_fws');
    $read_fws=explode("-", $readfw);
    $url = 'http://'. $read_fws[0] .'/cgi-bin/params_filter.cgi';
    $login = $conn->login($read_fws[0], $read_fws[1], $read_fws[2]);
    $rows = array();
    if($login) {
      $xml = $conn->postData($url, array('param_submit_type' => 'select', 'param_set_index' => 15));
      if (is_object($xml)) {
        $rulelist = $xml->filter;
        foreach($rulelist as $item){
          $rows[]= array(
            'op'=>array('data' => array(
              '#type' => 'operations',
              '#links' => array(
                'delete'=>array(
                  'title' => '删除',
                  'url' => new Url('admin.fw.rule.delete', array('param_index'=>$item->index, 'param_submit_type'=>'delete', 'param_set_index'=>15)),
                ),
              )
            )),
            'protocol'=> (string)$item->protocol,
            'address'=>(string)$item->address,
            'description'=>(string)$item->description,
            'stat'=>(string)$item->stat,
          );
        }
      }
    }
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array(
        'op'=>'操作',
        'protocol'=>'协议',
        'address'=>'地址',
        'description'=>'细节',
        'stat'=>'匹配'
      ),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    return $build;
  }

  /**
   * 删除规则
   */
  public function ruleDelete(){
    $config = \Drupal::config('fw_config.settings');
    $conn = new \Drupal\fw_config\FwConnect();
    $write_fw = $config->get('write_fws');
    $write_fws = explode("\r\n", $write_fw);
    $msg = array();
    foreach($write_fws as $item) {
      $item_arr = explode("-", $item);
      $url = 'http://'. $item_arr[0] .'/cgi-bin/params_filter.cgi';
      $login = $conn->login($item_arr[0], $item_arr[1], $item_arr[2]);
      if(!$login) {
        $msg[] = $item_arr[0] . 'false';
        continue;
      }
      $xml = $conn->postData($url, $_GET);
      if (!is_object($xml)) {
        $msg[]=$item_arr[0].'false';
      }
      if(!empty($msg)){
        $data['msg']=implode("\r\n", $msg);
      }
    }
    if(empty($msg)){
      return $this->redirect('admin.fw.rule.list');
    }else{
      return new JsonResponse($data['msg']);
    }
  }
   /**
   * 主机策略列表
   */
  public function hostpolicyList(){
    $rows = array();
    $wdservice = \Drupal::service('fw_config.wdfirewall');
    $datalist =$wdservice->selectAll();
    if(empty($datalist)){
      $conn = new \Drupal\fw_config\WdConnect();
      $url = 'http://162.212.181.3:10000/setting/hostpolicy/list';
      $conn->init();
      $result = $conn->login();
      $datalist2 = $conn->getRead($url);
      unset($datalist2[0]);
      $result = $wdservice->addlist($datalist2);
      $datalist =$wdservice->selectAll();
    }
    $i = 1;
    foreach ($datalist as $item){
      $policy = $item->policy;
      if($policy== 1){
        $policy='屏蔽';
      }elseif($policy== 2){
        $policy='忽略';
      }elseif($policy== 4096){
        $policy='宽松(4096)';
      }
      $rows[]= array(
        'op'=>array('data' => array(
          '#type' => 'operations',
          '#links' => array(
            'delete'=>array(
              'title' => '删除',
              'url' => new Url('admin.fw.hostpolicy.delete',array('id'=>$item->value
              )),
            ),
          )
        )),
        'id'=> $i,
        'ip'=>(string)$item->ip,
        'strategy'=>$policy,
      );
      $i++;
    }
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array(
        'op'=>'操作',
        'protocol'=>'编号',
        'address'=>'主机IP',
        'description'=>'策略',
      ),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    return $build;
  }
  /**
   * 主机策略删除
   */
  public function hostpolicyDelete(){
    $conn = new \Drupal\fw_config\WdConnect();
    $url = 'http://162.212.181.3:10000/setting/hostpolicy/del';
    $conn->init();
    $conn->login();
    $url = $url.'?'.http_build_query($_GET);
    $datalist = $conn->commonFunction($url,'','DELETE');
    $wdservice = \Drupal::service('fw_config.wdfirewall');
    $datadelete =$wdservice->delete($_GET['id']);
    if(empty($datalist)){
      drupal_set_message('删除成功');
    }else{
      drupal_set_message('删除失败');
    }
    return $this->redirect('admin.fw.hostpolicy.list');
  }
   /**
   * 同步主机策略
   */
  public function hostpolicyTongbu(){
    $url = 'http://162.212.181.3:10000/setting/hostpolicy/del?id=';
    $conn = new \Drupal\fw_config\WdConnect();
    $conn->init();
    $conn->login();
    $wdservice = \Drupal::service('fw_config.wdfirewall');
    $datalist = $wdservice->selectAll();
    $num =0;
    foreach ($datalist as $items){
      $url2 =$url.$items->value;
      $datadelete = $conn->commonFunction($url2,'','DELETE');
      if(empty($datadelete)){
        //删除成功的ip数据
        $iplist[] = $items->ip;
      }
    }
    //全部增加
    $urladd = 'http://162.212.181.3:10000/setting/hostpolicy/add';
    if(empty($datadelete)){
      foreach($datalist as $items){
        if (in_array($items->ip, $iplist)) {
          $list =  array(
            'ip' => $items->ip,
            'policy' =>$items->policy,
          );
          $html = $conn->commonFunction($urladd,$list,'POST');
          if(empty($html)){
            $num++;
          }
        }
      }
      $url = 'http://162.212.181.3:10000/setting/hostpolicy/list';
      $datalist = $conn->getRead($url);
      unset($datalist[0]);
      $row = $wdservice->deleteAll();
      $result = $wdservice->addlist($datalist);
      $num2 = count($datalist)-$num;
      $msg ='同步成功个数:'.$num.'失败个数：'.$num2;
    }
    drupal_set_message($msg);
    return $this->redirect('admin.fw.hostpolicy.list');
  }
  public function synctoWorksheet(){
    $wdservice = \Drupal::service('fw_config.wdfirewall');
    $conn = new \Drupal\fw_config\WdConnect();
    $url = 'http://162.212.181.3:10000/setting/hostpolicy/list';
    $conn->init();
    $result = $conn->login();
    $datalist2 = $conn->getRead($url);
    unset($datalist2[0]);
    $row = $wdservice->deleteAll();
    $result = $wdservice->addlist($datalist2);
    if($row && $result){
      drupal_set_message('同步成功');
      return $this->redirect('admin.fw.hostpolicy.list');
    }
  }
  
}