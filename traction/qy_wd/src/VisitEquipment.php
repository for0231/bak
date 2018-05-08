<?php
/**
 * @file
 * Contains \Drupal\qy_wd\VisitEquipment.
 */

namespace Drupal\qy_wd;

use Drupal\Core\Url;

class VisitEquipment {

  /**
   * 获取所有防火墙单元信息
   */
  public function fwshow() {
    $body = array();
    $url = 'http://162.212.181.3:13579/fwshow';
    $u = parse_url($url);
    $fwip = $u['host'];
    $fp = @fsockopen($fwip, $u['port'], $errno, $errstr, 5);
    if (!$fp) {
      return $body;
    }
    $send = "GET ". $u['path'] ." HTTP/1.1\r\n";
    $send .= "Host: $fwip\r\n\r\n";

    fwrite($fp, $send);
    stream_set_timeout($fp, 4);
    $is_body = false;
    while ($f = fgets($fp)) {
      if ($f == "\r\n") {
        $is_body = true;
        continue;
      }
      if($is_body) {
        $items = explode(',', $f);
        if($items['0'] == 'id') {
          continue;
        }
        $body[] = array(
          'id' => trim($items[0]),
          'die_time' => trim($items[1]),
          'in_packet' => trim($items[2]),
          'out_packet' => trim($items[3]),
          'in_byte' => trim($items[4]),
          'out_byte' => trim($items[5]),
          'runtime' => trim($items[6])
        );
      }
    }
    fclose($fp);
    return $body;
  }

  /**
   * 获取指定防火墙单元信息
   *  $firewall_str: 指定要获取的防火墙单元字符串如(0000100000000011)
   */
  public function fwlist($firewall_str) {
    $body = array();
    $url = 'http://162.212.181.3:13579/fwlist?' . $firewall_str;
    $u = parse_url($url);
    $fwip = $u['host'];
    $fp = @fsockopen($fwip, $u['port'], $errno, $errstr, 5);
    if (!$fp) {
      return $body;
    }
    $send = "GET ". $u['path'] ."?". $u['query'] ."# HTTP/1.1\r\n";
    $send .= "Host: $fwip\r\n\r\n";

    fwrite($fp, $send);
    stream_set_timeout($fp, 4);
    $is_body = false;
    while ($f = fgets($fp)) {
      if ($f == "\r\n") {
        $is_body = true;
        continue;
      }
      if($is_body) {
        $items = explode(',', $f);
        if($items['0'] == 'id') {
          continue;
        }
        $body[] = array(
          'id' => trim($items[0]),
          'die_time' => trim($items[1]),
          'in_packet' => trim($items[2]),
          'out_packet' => trim($items[3]),
          'in_byte' => trim($items[4]),
          'out_byte' => trim($items[5]),
          'runtime' => trim($items[6])
        );
      }
    }
    fclose($fp);
    return $body;
  }

  /**
   * 获取指定防火墙上的IP列表
   *  $min_bps: 移出小于$min_bps/Mbps的ip
   */
  public function iplist($firewall_str, $min_bps = 10) {
    $body = array();
    $byte_bps = ($min_bps / 8) * 1024; //转化为KByte/s;
    $url = 'http://162.212.181.3:13579/iplist?' . $firewall_str;
    $u = parse_url($url);
    $fwip = $u['host'];
    $fp = @fsockopen($fwip, $u['port'], $errno, $errstr, 5);
    if (!$fp) {
      return $body;
    }
    $send = "GET ". $u['path'] ."?". $u['query'] ."# HTTP/1.1\r\n";
    $send .= "Host: $fwip\r\n\r\n";

    fwrite($fp, $send);
    stream_set_timeout($fp, 4);
    $is_body = false;
    $in_pps = 0;
    $in_bps = 0;
    while ($f = fgets($fp)) {
      if ($f == "\r\n") {
        $is_body = true;
        continue;
      }
      if($is_body) {
        $items = explode(',', $f);
        if(count($items) < 7) {
          continue;
        }
        $in_pps += trim($items[2]);
        $in_bps += trim($items[3]);
        if($items[3] > $byte_bps) {
          $ip_arr = explode('.', long2ip($items[0]));
          $ip =implode('.', array_reverse($ip_arr));
          $body[] = array(
            'ip' => $ip,
            'status' => trim($items[1]),
            'in_bps' => trim($items[3]),
            'in_pps' => trim($items[2]),
            'in_tcp' => $items[4],
            'in_udp' => $items[5],
            'in_icmp' => $items[6],
            'out_pps' => trim($items[7]),
            'out_bps' => trim($items[8])
          );
        }
      }
    }
    $body['full'] = array(
      'in_bps' => $in_bps,
      'in_pps' => $in_pps,
    );
    fclose($fp);
    return $body;
  }

  /**
   * 获取指定防火墙的特定IP信息
   */
  public function oneIpList($firewall_str, $ip) {
    $body = array();
    $url = 'http://162.212.181.3:13579/iplist?' . $firewall_str;
    $u = parse_url($url);
    $fwip = $u['host'];
    $fp = @fsockopen($fwip, $u['port'], $errno, $errstr, 5);
    if (!$fp) {
      return $body;
    }
    $send = "GET ". $u['path'] ."?". $u['query'] ."# HTTP/1.1\r\n";
    $send .= "Host: $fwip\r\n\r\n";

    fwrite($fp, $send);
    stream_set_timeout($fp, 4);
    $is_body = false;
    while ($f = fgets($fp)) {
      if ($f == "\r\n") {
        $is_body = true;
        continue;
      }
      if($is_body) {
        $items = explode(',', $f);
        if(count($items) < 7) {
          continue;
        }
        $ip_arr = explode('.', long2ip($items[0]));
        $item_ip =implode('.', array_reverse($ip_arr));
        if($item_ip == $ip) {
          $body = array(
            'ip' => $ip,
            'status' => trim($items[1]),
            'in_bps' => trim($items[3]),
            'in_pps' => trim($items[2]),
            'in_tcp' => $items[4],
            'in_udp' => $items[5],
            'in_icmp' => $items[6],
            'out_pps' => trim($items[7]),
            'out_bps' => trim($items[8])
          );
          break;
        }
      }
    }
    fclose($fp);
    return $body;
  }

  /**
   * 保存流量图表数据
   */
  public function saveChartDate($base_path, $units, $log_ips) {
    $month = date('Ym');
    $firewall_str = $this->getFirewallStr($units);
    $url = 'http://162.212.181.3:13579/iplist?' . $firewall_str;
    $u = parse_url($url);
    $fwip = $u['host'];
    $fp = @fsockopen($fwip, $u['port'], $errno, $errstr, 5);
    if (!$fp) {
      return array();
    }
    $send = "GET ". $u['path'] ."?". $u['query'] ."# HTTP/1.1\r\n";
    $send .= "Host: $fwip\r\n\r\n";
    fwrite($fp, $send);
    stream_set_timeout($fp, 4);
    $is_body = false;
    $count = count($log_ips);
    $n = 0;
    $data = array();
    while ($f = fgets($fp)) {
      if ($f == "\r\n") {
        $is_body = true;
        continue;
      }
      if($is_body) {
        $items = explode(',', $f);
        $logip = $items[0];
        if(array_key_exists($logip, $log_ips)) {
          $path = $base_path . '/'. $month .'/' . $log_ips[$logip];
          if(!is_dir($path)) {
            mkdir($path, 0777, true);
          }
          $content = time().":{$items[3]}\r\n";
          $data[$logip] = array('content' => $content, 'file' => $path . "/{$logip}.txt");
          $n++;
        }
        if($count == $n) {
          break;
        }
      }
    }
    fclose($fp);
    return $data;
  }

  /**
   * 墙单元转化为字符串
   * @param unknown $route
   */
  public function getFirewallStr(array $units) {
    $firewall = str_split('0000000000000000');
    foreach ($units as $unit) {
      $firewall[$unit-1] = 1;
    }
    return implode($firewall);
  }

  //--------监听防火墙--------------
  /**
   * 检查电信输入流量是否超策略
   *  $db_service: 数据库操作对象
   *  $route: 检查线路
   */
  public function check_host($db_service, $route, $min_bps = 10) {
    echo '开始监听线路：'. $route->routename, "\r\n";
    $units = explode(',', $route->firewall_unit);
    $ln = count($units);
    $firewall_str = $this->getFirewallStr($units);
    $unit_ips = array(); //保存各单元IP列表
    $hs = $this->iplist($firewall_str, $min_bps);
    if(empty($hs)) {
      return;
    }
    $full = $hs['full'];
    unset($hs['full']);
    if($ln == 1) {
      $unit_ips = $hs;
    }
    $al_bps = floor($full['in_bps'] / 128); //将kbyte/s转换成Mbps
    $al_pps = floor($full['in_pps'] /10000);
    echo "总墙bps: $al_bps 和总pps: $al_pps \r\n";
    //判断是否超总墙
    $alarm_bps = $route->total_bps;
    $alarm_pps = $alarm_bps * 148 / 1000; //bps转pps
    $is_alarm_qy = false;
    if($al_bps > $alarm_bps || $al_pps > $alarm_pps) {
      $ps = 'in_pps';
      if($al_bps > $alarm_bps) {
        $ps = 'in_bps';
      }
      $volume = array();
      foreach ($hs as $key => $row) {
        $volume[$key] = $row[$ps];
      }
      array_multisort($volume, SORT_DESC, $hs);
      $out = array_shift($hs);
      if(empty($out)) return false;
      $pps = floor($out['in_pps'] / 10000);
      $bps = floor($out['in_bps'] / 128);
      $route->ls_note = 'total_' . $ps;
      $this->do_limit($db_service, $route, $out['ip'], $bps, $pps, true);
      $is_alarm_qy = true;
    }
    //存储各单元的信息及单墙的判断
    $wall_units = $this->fwlist($firewall_str);
    foreach($wall_units as $wall_unit) {
      $unit_id = $wall_unit['id'];
      if($ln > 1) {
        $unit_str = $this->getFirewallStr(array($unit_id));
        $unit_ips = $this->iplist($unit_str, $min_bps);
        if(empty($unit_ips)) {
          continue;
        }
        unset($unit_ips['full']);
      }
      $volume = array();
      foreach ($unit_ips as $key => $row) {
        $volume[$key] = $row['in_bps'];
      }
      array_multisort($volume, SORT_DESC, $unit_ips);
      $top_ip = array();
      foreach($unit_ips as $unit_ip) {
        $top_ip[] = $unit_ip['ip'];
        if(count($top_ip) > 1) {
          break;
        }
      }
      $db_service->update_unit_flow(array(
        'die_time' => $wall_unit['die_time'],
        'run_time' => $wall_unit['runtime'],
        'in_pps' => $wall_unit['in_packet'],
        'out_pps' => $wall_unit['out_packet'],
        'in_bps' => $wall_unit['in_byte'],
        'out_bps' => $wall_unit['out_byte'],
        'top_ip' => implode(',', $top_ip)
      ), $unit_id);
      $one_bps = $wall_unit['in_byte'] / 128;
      $one_pps = $wall_unit['in_packet'] / 10000;
      echo "$unit_id 单元墙bps: $one_bps 和pps: $one_pps \r\n";
      $one_alarm_bps = $route->one_bps;
      $one_alarm_pps = $one_alarm_bps * 148 / 1000; //bps转pps
      if($is_alarm_qy == false && $ln > 1 && ($one_bps > $one_alarm_bps || $one_pps > $one_alarm_pps)) {
        $ps = 'in_pps';
        if($one_bps > $one_alarm_bps) {
          $ps = 'in_bps';
        }
        $out = array();
        $out_value = 0;
        foreach ($unit_ips as $key => $row) {
          if($row[$ps] > $out_value) {
            $out_value = $row[$ps];
            $out = $row;
          }
        }
        if(empty($out)) return false;
        $pps = floor($out['in_pps'] * $ln / 10000);
        $bps = floor($out['in_bps'] * $ln / 128);
        $route->ls_note = 'one_total_' . $ps . '_' . $unit_id;
        $this->do_limit($db_service, $route, $out['ip'], $bps, $pps, true);
      }
    }
    //检查每个IP是否超策略。
    foreach ($hs as $h) {
      $ip = $h['ip'];
      $pps = floor($h['in_pps'] / 10000);
      $bps = floor($h['in_bps'] / 128);
      if($bps > 300) {
        $this->do_limit($db_service, $route, $ip, $bps, $pps, false, $hs);
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
        $out = $this->shareMode($policy, $hs, $endIp);
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
      $config = \Drupal::config('qy_wd.settings');
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
    //判断要牵引的ip是否已经牵引,@todo不同线路的牵引存在多条数据
    $qy_arr = $db_service->load_qy(array('ip' => $ip, 'net_type' => $command_type));
    if(!empty($qy_arr)) {
      $qy = reset($qy_arr);
      $up_value = array();
      if($bps > $qy->bps || $pps > $qy->pps) {
        $up_value['bps'] = $bps;
        $up_value['pps'] = $pps;
      }
      if($qy->time < $l_time) {
        $up_value['time'] = $l_time;
      }
      if(!empty($up_value) && $qy->time > 0) {
        $up_value['type'] = $type;
        $up_value['note'] = $note;
        $db_service->update_qy($up_value, $qy->id);
        echo "更新了{$ip}的信息{$bps}({$type})-({$note}).\r\n";
      }
      return false;
    }
    $now = time();
    $db_service->add_qy(array(
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
    ));
    echo "牵引了IP：$ip (". date("H:i:s") .")-({$type})-({$note})\r\n";
  }

  /**
   * 共享模式计算同段是否超出。
   */
  private function shareMode($policy, $hs, $endIp) {
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
        $rs_bps_sum += $item['in_bps'];
        $rs_pps_sum += $item['in_pps'];
      }
    }
    if($rs_arr) {
      $ps_tmp = '';
      if (($rs_bps_sum / 128) > $policy->bps) {
        $ps_tmp = 'in_bps';
      }
      if(($rs_pps_sum / 10000) > $policy->pps) {
        $ps_tmp = 'in_pps';
      }
      if($ps_tmp) {
        echo "当前IP段总bps: ". ($rs_bps_sum / 128) ." 和总pps:". ($rs_pps_sum / 10000) ." \r\n";
        $volume = array();
        foreach ($rs_arr as $key => $rv) {
          $volume[$key] = $rv[$ps_tmp];
        }
        array_multisort($volume, SORT_DESC, $rs_arr);
        $out = array_shift($rs_arr);
        $out['in_bps'] = floor($out['in_bps'] /128);
        $out['in_pps'] = floor($out['in_pps'] / 10000);
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
