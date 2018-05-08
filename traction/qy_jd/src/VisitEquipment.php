<?php
/**
 * @file
 * Contains \Drupal\qy_jd\VisitEquipment.
 */

namespace Drupal\qy_jd;

use Drupal\Core\Url;

class VisitEquipment {
  /**
   * 登录墙
   */
  public function login($hosts) {
    //登陆jd.读取COOKIE
    $return = array();
    foreach ($hosts as $key => $host) {
      $cookie = array();
      $ip = $host['ip'];
      $post['param_type'] = "login";
      $post['param_username'] = $host['user'];
      $post['param_password'] = $host['pass'];
      $postdata = http_build_query($post);

      $url = "http://$ip/cgi-bin/login.cgi";
      $u = parse_url($url);
      $fp = fsockopen($u['host'], $u['port'], $errno, $errstr, 5);
      if (!$fp) {
        return array();
      }
      $send = "POST /cgi-bin/login.cgi HTTP/1.1\r\n";
      //不能删除空行
      $send .= "User-Agent: fwmon
Host: $ip
Content-Length: " . strlen($postdata) . "
Cache-Control: no-cache
Cookie: language=zh-cn

$postdata";

      fwrite($fp, $send);
      while ($f = fgets($fp, 1024)) {
        if (preg_match("/Set-Cookie: (\S+)=(\S+)/", $f, $match))
          $this->cookie[$match[1]] = $match[2];
        if ($f == "\r\n")
          break;
      }
      fclose($fp);
      $return[$key] = $this->cookie;
    }
    return $return;
  }

  /**
   * GET请求获取墙信息
   *  $host: 墙IP信息
   *  $params: 请求参数 array(
   *    hostaddr： '127.1.1./32'指定IP
   *    param_view：-1: 全部
   *                 0: 处于防御状态
   *                 1: 处于自动防御状态
   *                 2: 处于手动防御状态
   *                 3: 处于其它防御状态
   *                 4: 总流量前100
   *                 5: 输入流量前100
   *                 6: 输出流量前100
   *                 7: 攻击频率前100
   *                 8: 总连接前100
   *                 9: 新建连接前100
   *  );
   */
  public function getWallInfo($host, array $params = array(), $echoError = true) {
    $fwip = $host['ip'];
    $cookie_headers = '';
    foreach ($host['cookie'] as $cookieKey => $cookieVal) {
       $cookie_headers .= $cookieKey . "=" . $cookieVal . "; ";
    }
    $cookie = substr($cookie_headers, 0, -2);
    $url = 'http://' . $fwip . '/cgi-bin/status_host.cgi';
    if(!empty($params)) {
      $url .= '?' . http_build_query($params);
    }
    $u = parse_url($url);
    $fp = @fsockopen($u['host'], $u['port'], $errno, $errstr, 5);
    if (!$fp) {
      return false;
    }
    $send = "GET ". $u['path'] ."?". $u['query'] ." HTTP/1.1\r\n";
    $send .= "Host: $fwip\r\n";
    $send .= "User-Agent: fwmon\r\n";
    $send .= "Cache-Control: no-cache\r\n";
    $send .= "Cookie: $cookie\r\n\r\n";
    fwrite($fp, $send);

    $len = 102400;
    stream_set_timeout($fp, 4);
    $f = fgets($fp, 1024);
    while ($f = fgets($fp, 1024)) {
      if ($f == "\r\n")
        break;
      if (strstr($f, "Content-Length"))
        $len = str_replace('Content-Length: ', '', $f);
    }
    $r = '';
    do {
      $_data = fread($fp, $len);
      if (strlen($_data) == 0) {
        break;
      }
      $r .= $_data;
    } while (true);
    fclose($fp);

    $xml = simplexml_load_string($r,'SimpleXMLElement', LIBXML_NOERROR);
    if($xml === false && $echoError) {
      echo '错误数据：' . $r . "\r\n";
    }
    return $xml;
  }

  /**
   * 独立IP检查
   */
  public function checked_ip($host, $ip) {
    //hostaddr参数写成了192.168.1.0/24与直接写ip查询结果一样都查询整段的出来。
    $xml = $this->getWallInfo($host, array('hostaddr' => $ip), false);
    if($xml) {
      foreach ($xml->host as $x) {
        if ($x->address == $ip) {
          return $x;
        }
      }
    }
    return 0;
  }

  //--------墙加牵引--------------
  /**
   * 检查电信输入流量前100ip
   * $host: 检查的防火墙
   * $route: 检查的线路
   * $ln: 线路防火墙数
   * $temp_qy_ips: 临时存放牵引的IP
   */
  public function check_host($db_service, $route, $host, $ln, &$temp_qy_ips) {
    echo date("H:i:s"), '开始监听防火墙：', $host['ip'], "\r\n";
    $super_ips = array();
    $full = array();
    $hs = array();
    $xml = $this->getWallInfo($host, array('param_view' => 5));
    $this->getXmlParam($xml, $full, $hs);
    //保存数据流量信息
    if(empty($full)) {
      return;
    }
    $top_ip = array();
    foreach($hs as $unit_ip) {
      $top_ip[] = $unit_ip['ip'];
      if(count($top_ip) > 1) {
        break;
      }
    }
    $db_service->update_netcomByIp($full + array('top_ip' => implode(',', $top_ip)), $host['ip']);

    $full['in_pps'] = floor($full['in_pps'] / 10000);
    $full['out_pps'] = floor($full['out_pps'] / 10000);
    $al_bps = $full['in_bps'] * $ln;
    $al_pps = $full['in_pps'] * $ln;
    echo "总墙bps: $al_bps 和总pps: $al_pps \r\n";
    //判断是否超总墙
    $alarm_bps = $route->total_bps;
    $max_pps = $alarm_bps * 148 / 1000; //bps转pps
    if($al_bps > $alarm_bps || $al_pps > $max_pps) {
      $ps = 'in_bps';
      if($al_pps > $max_pps) {
        $ps = 'in_pps';
      }
      $volume = array();
      foreach ($hs as $key => $row) {
        $volume[$key] = $row[$ps];
      }
      array_multisort($volume, SORT_DESC, $hs);
      $out = array_shift($hs);
      if(!empty($out)) {
        $out_pps = floor($out['in_pps'] * $ln / 10000);
        $out_bps = $out['in_bps'] * $ln;
        $route->ls_note = 'total_' . $ps;
        $value = $this->do_limit($db_service, $route, $out['ip'], $out_bps, $out_pps, true);
        if($value) {
          $super_ips[] = $value;
        }
      }
    } else {
      //判断单墙
      echo "单墙bps:". $full['in_bps'] ." 和单墙pps:". $full['in_pps'] ." \r\n";
      $alarm_bps_one = $route->one_bps;
      $max_pps_one = $alarm_bps_one * 148 / 1000; //bps转pps
      if($full['in_bps'] > $alarm_bps_one || $full['in_pps'] > $max_pps_one) {
        $ps = 'in_bps';
        if($full['in_pps'] > $max_pps_one) {
          $ps = 'in_pps';
        }
        $volume = array();
        foreach ($hs as $key => $row) {
          $volume[$key] = $row[$ps];
        }
        array_multisort($volume, SORT_DESC, $hs);
        $out = array_shift($hs);
        if(!empty($out)) {
          $out_pps = floor($out['in_pps'] * $ln / 10000);
          $out_bps = $out['in_bps'] * $ln;
          $route->ls_note = 'one_' . $ps;
          $value = $this->do_limit($db_service, $route, $out['ip'], $out_bps, $out_pps, true);
          if($value) {
            $super_ips[] = $value;
          }
        }
      }
    }
    //检查每个IP是否超策略。
    foreach ($hs as $h) {
      $ip = $h['ip'];
      $bps = $h['in_bps'] * $ln;
      $pps = floor($h['in_pps'] * $ln / 10000);
      $value = $this->do_limit($db_service, $route, $ip, $bps, $pps, false, $hs, $ln);
      if($value) {
        $super_ips[] = $value;
      }
    }
    //判断连续超墙次数
    $config = \Drupal::config('qy_jd.settings');
    $flow_continue_sec = $config->get('flow_continue_sec');
    if($flow_continue_sec > 1) {
      $temp = array();
      foreach($super_ips as $value) {
        $ip = $value['ip'];
        if(array_key_exists($ip, $temp_qy_ips)) {
          $num = $temp_qy_ips[$ip]['number'] + 1;
          echo "ip:{$ip}连续超流量了{$num}次 \r\n";
          if($temp_qy_ips[$ip]['value']['bps'] > $value['bps']) {
            $value = $temp_qy_ips[$ip]['value'];
          }
          if($num >= $flow_continue_sec) {
            $this->writeDb($db_service, $value);
          } else {
            $temp[$ip]['number'] = $num; 
            $temp[$ip]['value'] = $value; 
          }
        } else {
          $temp[$ip]['number'] = 1;
          $temp[$ip]['value'] = $value;
          echo "ip:{$ip}连续超流量了1次 \r\n";
        }
      }
      $temp_qy_ips = $temp;
    } else {
      foreach($super_ips as $value) {
        $this->writeDb($db_service, $value);
      }
    }
  }

  /**
   * 得到xml常用参数
   */
  public function getXmlParam($xml, &$full, &$hs) { //分析jd数据
    if($xml === false) {
      $full = array(
        'in_bps' => 0,
        'in_pps' => 0,
        'out_bps' => 0,
        'out_pps' => 0,
        'syn_rate' => 0,
        'ack_rate' => 0,
        'udp_rate' => 0,
        'icmp_rate' => 0,
        'frag_rate' => 0,
        'nonip_rate' => 0,
        'new_tcp_rate' => 0,
        'new_udp_rate' => 0,
        'tcp_conn_in' => 0,
        'tcp_conn_out' => 0,
        'udp_conn' => 0,
        'icmp_conn' => 0
      );
      return;
    }
    $config = \Drupal::config('qy_jd.settings');
    $min_bps = $config->get('min_bps');
    foreach ($xml->host as $xv) {
      if ($xv->type == 'full') {
        $full = array(
          'in_bps' => (float)$xv->input_bps,
          'in_pps' => (int)$xv->input_pps,
          'out_bps' => (float)$xv->input_submit_bps,
          'out_pps' => (int)$xv->input_submit_pps,
          'syn_rate' => (int)$xv->syn_rate,
          'ack_rate' => (int)$xv->ack_rate,
          'udp_rate' => (int)$xv->udp_rate,
          'icmp_rate' => (int)$xv->icmp_rate,
          'frag_rate' => (int)$xv->frag_rate,
          'nonip_rate' => (int)$xv->nonip_rate,
          'new_tcp_rate' => (int)$xv->new_tcp_rate,
          'new_udp_rate' => (int)$xv->new_udp_rate,
          'tcp_conn_in' => (int)$xv->tcp_conn_in,
          'tcp_conn_out' => (int)$xv->tcp_conn_out,
          'udp_conn' => (int)$xv->udp_conn,
          'icmp_conn' => (int)$xv->icmp_conn
        );
      } else if ($xv->type == 'host') {
        if($xv->input_bps < $min_bps) {
          continue;
        }
        $hs[] = array(
          'ip' => (string)$xv->address,
          'in_bps' => (float)$xv->input_bps,
          'in_pps' => (int)$xv->input_pps,
          'out_bps' => (float)$xv->input_submit_bps,
          'out_pps' => (int)$xv->input_submit_pps,
          'syn_rate' => (int)$xv->syn_rate,
          'ack_rate' => (int)$xv->ack_rate,
          'udp_rate' => (int)$xv->udp_rate,
          'icmp_rate' => (int)$xv->icmp_rate,
          'frag_rate' => (int)$xv->frag_rate,
          'nonip_rate' => (int)$xv->nonip_rate,
          'new_tcp_rate' => (int)$xv->new_tcp_rate,
          'new_udp_rate' => (int)$xv->new_udp_rate,
          'tcp_conn_in' => (int)$xv->tcp_conn_in,
          'tcp_conn_out' => (int)$xv->tcp_conn_out,
          'udp_conn' => (int)$xv->udp_conn,
          'icmp_conn' => (int)$xv->icmp_conn
        );
      }
    }
  }

  /**
   * $ip: 要牵引的IP
   * $bps: 牵引ip的bps流量
   * $pps: 牵引ip的pps包流量
   * $force: 是否已经满足了牵引条件
   * $hs: 所有被攻击的ip数据
   * $ln: 同线路防火墙数量
   */
  private function do_limit($db_service, $route, $ip, $bps, $pps, $force, $hs = array(), $ln = 1) {
    $command_type = $route->id; //所属线路
    $l_time = 0; //策略设置牵引时间
    $type = ''; //牵引类型
    $note = ''; //备注
    $find = false; //是否设置了策略
    if($force) {
      $l_time = $route->time;
      $find = true;
      $note = isset($route->ls_note) ? $route->ls_note : 'Alarm';
      $type = 'Alarm';
    }
    //查找IP要使用策略
    $policy = array();
    $endIP = '';
    if(!$force) {
      $policy = getPolicyByIP($db_service, $route->id, $ip);
      if(!empty($policy)) {
        $endIp = $policy->ip;
        if($policy->mask_number < 32) {
          $endIp = $this->endIP($policy->ip, $policy->mask_number);
        }
        $find = true;
      }
    }
    //判断流量是否超策略
    if (!$force && !empty($policy)) {
      if($policy->ms == 2) {
        $out = $this->shareMode($policy, $hs, $endIp, $ln);
        if(!empty($out)) {
          $ip = $out['ip'];
          $bps = $out['in_bps'];
          $pps = $out['in_pps'];
          $doubling = 1;
          if($policy->timebyflow) {
            $doubling = ($bps/$policy->bps) * $policy->doublebase;
          }
          $l_time = round($doubling * $policy->time);
          $note = $out['str'];
          $type = 'Share';
          $force = true;
        }
      } else {
        if($bps > $policy->bps) {
          $doubling = 1;
          if($policy->timebyflow) {
            $doubling = ($bps/$policy->bps) * $policy->doublebase;
          }
          $l_time = round($doubling * $policy->time);
          $note = 'bps';
          $type = 'Long';
          $force = true;
        }
        if($pps > $policy->pps) {
          $doubling = 1;
          if($policy->timebyflow) {
            $doubling = ($bps/$policy->bps) * $policy->doublebase;
          }
          $l_time = round($doubling * $policy->time);
          $note = 'pps';
          $type = 'Long';
          $force = true;
        }
      }
    }
    //没有找到策略设置
    if(!$find) {
      $config = \Drupal::config('qy_jd.settings');
      if($bps > $config->get('bps')) {
        $l_time = $config->get('time');
        $note = 'bps';
        $type = 'PreDef';
        $force = true;
      } else if ($pps > $config->get('pps')) {
        $l_time = $config->get('time');
        $note = 'pps';
        $type = 'PreDef';
        $force = true;
      }
    }

    //不满足牵引条件
    if(!$force) {
      return false;
    }
    //判断是否要提示
    $prompt_tip = 0;
    if(!empty($policy) && $bps < $policy->bps) {
      $prompt_tip = $policy->traction_tip;
    }
    $now = time();
    return array(
      'ip' => $ip,
      'net_type' => $command_type,
      'bps' => $bps,
      'pps' => $pps,
      'start' => $now,
      'time' => $l_time,
      'type' => $type,
      'note' => $note,
      'opter' => 'SYS',
      'uid' => 0,
      'gjft' => 1,
      'prompt_tip' => $prompt_tip,
      'state' => $route->is_global
    );
  }

  /**
   * 写入数据库
   */
  private function writeDb($db_service, $value) {
    $ip = $value['ip'];
    $qy_arr = $db_service->load_qy(array('ip' => $ip, 'net_type' => $value['net_type']));
    if(!empty($qy_arr)) {
      $qy = reset($qy_arr);
      $up_value = array();
      if($value['bps'] > $qy->bps) {
        $up_value['bps'] = $value['bps'];
        $up_value['pps'] = $value['pps'];
      }
      if($qy->time < $value['time']) {
        $up_value['time'] = $value['time'];
      }
      if(!empty($up_value) &&  $qy->time > 0) {
        $db_service->update_qy($up_value, $qy->id);
      }
      echo "此IP已经存在了($ip).\r\n";
      return false;
    }
    $db_service->add_qy($value);
    echo "牵引了IP：$ip (". date("H:i:s") .")\r\n";
  }

  /**
   * 共享模式计算同段是否超出。
   */
  private function shareMode($policy, $hs, $endIp, $ln) {
    $start_ip_arr = explode(".", $policy->ip);
    $start_ip = $start_ip_arr[3];
    $start_str = $start_ip_arr[0] . '.' . $start_ip_arr[1] . '.' . $start_ip_arr[2];
    $end_ip_arr = explode(".", $endIp);
    $end_ip = $end_ip_arr[3];

    $rs_arr = array();
    $rs_bps_sum = 0;
    $rs_pps_sum = 0;
    foreach($hs as $item) {
      $check_arr = explode(".", $item['ip']);
      $check_str = $check_arr[0] . '.' . $check_arr[1] . '.' . $check_arr[2];
      if ($check_str == $start_str && $check_arr[3] >= $start_ip && $check_arr[3] <= $end_ip) {
        $rs_arr[] = $item;
        $rs_bps_sum += ($item['in_bps'] * $ln);
        $rs_pps_sum += ($item['in_pps'] * $ln);
      }
    }
    if($rs_arr) {
      $ps_tmp = '';
      if ($rs_bps_sum > $policy->bps) {
        $ps_tmp = 'in_bps';
      }
      if((($rs_pps_sum * $ln) / 10000) > $policy->pps) {
        $ps_tmp = 'in_pps';
      }
      if($ps_tmp) {
        echo "当前IP段总bps: $rs_bps_sum 和总pps:". (($rs_pps_sum * $ln) / 10000) ." \r\n";
        $volume = array();
        foreach ($rs_arr as $key => $rv) {
          $volume[$key] = $rv[$ps_tmp];
        }
        array_multisort($volume, SORT_DESC, $rs_arr);
        $out = array_shift($rs_arr);
        $out['in_bps'] = $out['in_bps'] * $ln;
        $out['in_pps'] = floor(($out['in_pps'] * $ln) / 10000);
        $out['str'] = $ps_tmp;
        return $out;
      }
    }
    return array();
  }

  /**
   * 得到192.168.1.0/24最大IP
   */
  private function endIp($ip, $mask_number) {
    $n = 32 - $mask_number;
    $ips = explode('.', $ip);
    $num = pow(2, $n);
    $max = $ips[3] + $num - 1;
    return $ips[0] .'.'. $ips[1]. '.' . $ips[2] . '.' . $max;
  }
}