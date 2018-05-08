<?php
/**
 * @file
 * Contains \Drupal\sync_migration\SynServerData.
 */

namespace Drupal\sync_migration;

class SynServerData{

  protected $base_url;

  public function __construct($domain_name) {
    $this->base_url = $domain_name;
  }

  public function synServerData() {
    try {
      $uri = $this->base_url . '/syn/server_data_to_drupal.php';
      $client = \Drupal::httpClient(); 
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
    $json_str = (string)$response->getBody();
    $servers = json_decode($json_str, true);

    $parts = $this->saveParts(); //保存配件
    $ip_server = array();
    foreach($servers as $key => $ips) {
      $this->addServer($key, $ips, $parts, $ip_server);
    }
    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $json_str = json_encode($ip_server);
    $config->set('sync_server_ip_list', $json_str);
    $config->set('sync_server', 1);
    $config->save();
  }

  private function addServer($typeName, $ips, $parts, &$ip_server) {
    $part = $this->getParts($parts, $typeName);
    if(empty($part)) {
      echo $typeName . '的' .count($ips) . '台服务器没有导入';
      return;
    }
    $server_type = entity_create('server_type', $part + array(
      'name' => $typeName,
      'server_number' => count($ips)
    ));
    $server_type->save();
    
    $value = array();
    $cpus = $part['cpu']; 
    foreach($cpus as $cpu) {
      $value['cpu'][] = array(
        'target_id' => $cpu,
        'value' => 1
      );
    }
    $memorys = $part['memory'];
    foreach($memorys as $memory) {
      $value['memory'][] = array(
        'target_id' => $memory,
        'value' => 1
      );  
    }
    $disks = $part['harddisk'];
    foreach($disks as $disk) {
      $value['harddisk'][] = array(
        'target_id' => $disk,
        'value' => 1
      );
    }
    if(!empty($part['mainboard'])) {
      $value['mainboard'] = $part['mainboard'];
    }
    if(!empty($part['chassis'])) {
      $value['chassis'] = $part['chassis'];
    }

    if($typeName == '配置一') {
      $cabinet = array(202,203,204,205,206);
      foreach($ips as $ip) {
        if(in_array($ip['cabinet'], $cabinet)) {
          $server = entity_create('server', array(
            'type' => $server_type->id(),
            'memory' => array(
              array('target_id' => $parts['memory']['4g'], 'value' => 1),
              array('target_id' => $parts['memory']['4g'], 'value' => 1)
            )
          ) + $value);
          $server->save();
          $ip_server[$ip['ip']] = array('sid' => $server->id(), 'cabinet' => $ip['cabinet']);
        } else {
          $server = entity_create('server', array(
            'type' => $server_type->id()
           ) + $value);
          $server->save();
          $ip_server[$ip['ip']] = array('sid' => $server->id(), 'cabinet' => $ip['cabinet']);
        }
      }
    } else {
      $server = entity_create('server', $value + array('type' => $server_type->id()));
      foreach($ips as $ip) {
        $duplicate = $server->createDuplicate();
        $duplicate->save();
        $ip_server[$ip['ip']] = array('sid' => $duplicate->id(), 'cabinet' => $ip['cabinet']);
      }
    }
  }

  /**
   * 获取分类的配件
   */
  private function getParts($parts, $type) {
    $partList = array(
      '配置一' => array(
        'cpu' => array($parts['cpu']['e3_1230v2']),
        'memory' => array($parts['memory']['8g']),
        'harddisk' => array($parts['harddisk']['xj500ghhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置二' => array(
        'cpu' => array($parts['cpu']['e3_1230v2']),
        'memory' => array($parts['memory']['4g'], $parts['memory']['4g']),
        'harddisk' => array($parts['harddisk']['xs1thhd']),
        'mainboard' => $parts['mainboard']['x9scm'],
        'chassis' => $parts['chassis']['833t'],
      ),
      '配置三' => array(
        'cpu' => array($parts['cpu']['e3_1230v2']),
        'memory' => array($parts['memory']['8g'], $parts['memory']['8g']),
        'harddisk' => array($parts['harddisk']['i240gssd'], $parts['harddisk']['xj1thhd']),
        'mainboard' => $parts['mainboard']['x9scm'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置三A' => array(
        'cpu' => array($parts['cpu']['e3_1230v2']),
        'memory' => array($parts['memory']['8g'], $parts['memory']['8g']),
        'harddisk' => array($parts['harddisk']['i240gssd'], $parts['harddisk']['xj1thhd']),
        'mainboard' => $parts['mainboard']['x9scm'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置四' => array(
        'cpu' => array($parts['cpu']['atom_d525']),
        'memory' => array($parts['memory']['2g']),
        'harddisk' => array($parts['harddisk']['xj500ghhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      'DELL R210' => array(
        'cpu' => array($parts['cpu']['xeon_l3406']),
        'memory' => array($parts['memory']['4g']),
        'harddisk' => array($parts['harddisk']['xj250ghhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      'DELL C610' => array(
        'cpu' => array($parts['cpu']['l5520'], $parts['cpu']['l5520']),
        'memory' => array($parts['memory']['16g']),
        'harddisk' => array($parts['harddisk']['xj1thhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置五' => array(
        'cpu' => array($parts['cpu']['e3_1271v3']),
        'memory' => array($parts['memory']['8g']),
        'harddisk' => array($parts['harddisk']['xj1thhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置六' => array(
        'cpu' => array($parts['cpu']['e5_2620']),
        'memory' => array($parts['memory']['32g']),
        'harddisk' => array($parts['harddisk']['i240gssd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置六V' => array(
        'cpu' => array($parts['cpu']['e5_2620']),
        'memory' => array($parts['memory']['32g'], $parts['memory']['32g']),
        'harddisk' => array($parts['harddisk']['xj1thhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置七' => array(
        'cpu' => array($parts['cpu']['e5_2620'], $parts['cpu']['e5_2620']),
        'memory' => array($parts['memory']['32g']),
        'harddisk' => array($parts['harddisk']['i240gssd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '配置七V' => array(
        'cpu' => array($parts['cpu']['e5_2620'], $parts['cpu']['e5_2620']),
        'memory' => array($parts['memory']['32g'], $parts['memory']['32g']),
        'harddisk' => array($parts['harddisk']['xj1thhd'], $parts['harddisk']['xj1thhd'], $parts['harddisk']['xj1thhd'], $parts['harddisk']['xj1thhd']),
        'mainboard' => $parts['mainboard']['x9scl'],
        'chassis' => $parts['chassis']['833t']
      ),
      '特殊配置' => array(
        'cpu' => array(),
        'memory' => array(),
        'harddisk' => array(),
        'mainboard' => '',
        'chassis' =>'' 
      )
    );
    if(isset($partList[$type])) {
      return $partList[$type];
    }
    return null;
  }


  /**
   * 保存配件
   */
  private function saveParts() {
    $parts = array();
    //CPU
    $part = entity_create('part_cpu', array(
      'brand' => 'E3-1230v2(四核八线程)',
      'model' => 'E3-1230v2(四核八线程)',
      'standard' => 'E3-1230v2(四核八线程)',
      'stock' => 0
    ));
    $part->save();
    $parts['cpu']['e3_1230v2'] = $part->id();

    $part = entity_create('part_cpu', array(
      'brand' => 'E3-1271v3(四核八线程)',
      'model' => 'E3-1271v3(四核八线程)',
      'standard' => 'E3-1271v3(四核八线程)',
      'stock' => 0
    ));
    $part->save();
    $parts['cpu']['e3_1271v3'] = $part->id();

    $part = entity_create('part_cpu', array(
      'brand' => 'ATOM D525(2核4线程)',
      'model' => 'ATOM D525(2核4线程)',
      'standard' => 'ATOM D525(2核4线程)',
      'stock' => 0
    ));
    $part->save();
    $parts['cpu']['atom_d525'] = $part->id();

    $part = entity_create('part_cpu', array(
      'brand' => 'XEON L3406(2核4线程)',
      'model' => 'XEON L3406(2核4线程)',
      'standard' => 'XEON L3406(2核4线程)',
      'stock' => 0
    ));
    $part->save();
    $parts['cpu']['xeon_l3406'] = $part->id();

    $part = entity_create('part_cpu', array(
      'brand' => 'L5520(4核8线程)',
      'model' => 'L5520(4核8线程)',
      'standard' => 'L5520(4核8线程)',
      'stock' => 0
    ));
    $part->save();
    $parts['cpu']['l5520'] = $part->id();

    $part = entity_create('part_cpu', array(
      'brand' => 'E5-2620(6核12线程)',
      'model' => 'E5-2620(6核12线程)',
      'standard' => 'E5-2620(6核12线程)',
      'stock' => 0
    ));
    $part->save();
    $parts['cpu']['e5_2620'] = $part->id();

    //内存
    $part = entity_create('part_memory', array(
      'brand' => '1G',
      'model' => '1G',
      'standard' => '1G',
      'stock' => 0,
      'capacity' => 1024
    ));
    $part->save();
    $parts['memory']['1g'] = $part->id();

    $part = entity_create('part_memory', array(
      'brand' => '2G',
      'model' => '2G',
      'standard' => '2G',
      'stock' => 0,
      'capacity' => 2048
    ));
    $part->save();
    $parts['memory']['2g'] = $part->id();

    $part = entity_create('part_memory', array(
      'brand' => '4G',
      'model' => '4G',
      'standard' => '4G',
      'stock' => 0,
      'capacity' => 4096
    ));
    $part->save();
    $parts['memory']['4g'] = $part->id();

    $part = entity_create('part_memory', array(
      'brand' => '8G',
      'model' => '8G ',
      'standard' => '8G',
      'stock' => 0,
      'capacity' => '8192'
    ));
    $part->save();
    $parts['memory']['8g'] = $part->id();

    $part = entity_create('part_memory', array(
      'brand' => '16G',
      'model' => '16G ',
      'standard' => '16G',
      'stock' => 0,
      'capacity' => '16384'
    ));
    $part->save();
    $parts['memory']['16g'] = $part->id();

    $part = entity_create('part_memory', array(
      'brand' => '32G',
      'model' => '32G ',
      'standard' => '32G',
      'stock' => 0,
      'capacity' => '23768'
    ));
    $part->save();
    $parts['memory']['32g'] = $part->id();

    //主板
    $part = entity_create('part_mainboard', array(
      'brand' => 'X9SCL+-F',
      'model' => 'X9SCL+-F',
      'standard' => 'X9SCL+-F',
      'memory_max' => 32,
      'memory_slot' => '4*_none',
      'stock' => 0
    ));
    $part->save();
    $parts['mainboard']['x9scl'] = $part->id();

    $part = entity_create('part_mainboard', array(
      'brand' => 'X9SCM',
      'model' => 'X9SCM',
      'standard' => 'X9SCM',
      'memory_max' => 32,
      'memory_slot' => '4*_none',
      'stock' => 0
    ));
    $part->save();
    $parts['mainboard']['x9scm'] = $part->id();

    $part = entity_create('part_mainboard', array(
      'brand' => 'X9DRL',
      'model' => 'X9DRL',
      'standard' => 'X9DRL',
      'memory_max' => 64,
      'memory_slot' => '4*_none',
      'stock' => 0
    ));
    $part->save();
    $parts['mainboard']['x9drl'] = $part->id();
    //硬盘
    $part = entity_create('part_harddisc', array(
      'brand' => '240G SSD',
      'model' => '240G SSD',
      'standard' => '240G SSD',
      'stock' => 0
    ));
    $part->save();
    $parts['harddisk']['i240gssd'] = $part->id();

    $part = entity_create('part_harddisc', array(
      'brand' => '512G SSD',
      'model' => '512G SSD',
      'standard' => '512G SSD',
      'stock' => 0
    ));
    $part->save();
    $parts['harddisk']['i512gssd'] = $part->id();

    $part = entity_create('part_harddisc', array(
      'brand' => '250G HHD',
      'model' => '250G HDD',
      'standard' => '250G HDD',
      'stock' => 0
    ));
    $part->save();
    $parts['harddisk']['xj250ghhd'] = $part->id();

    $part = entity_create('part_harddisc', array(
      'brand' => '500G HHD',
      'model' => '500G HDD',
      'standard' => '500G HDD',
      'stock' => 0
    ));
    $part->save();
    $parts['harddisk']['xj500ghhd'] = $part->id();

    $part = entity_create('part_harddisc', array(
      'brand' => '1T HDD',
      'model' => '1T HDD',
      'standard' => '1T HDD',
      'stock' => 0
    ));
    $part->save();
    $parts['harddisk']['xj1thhd'] = $part->id();

    $part = entity_create('part_harddisc', array(
      'brand' => '西数黑盘 1TB HDD',
      'model' => '西数黑盘 1TB HDD',
      'standard' => '西数黑盘 1TB HDD',
      'stock' => 0
    ));
    $part->save();
    $parts['harddisk']['xs1thhd'] = $part->id();

    //机箱
    $part = entity_create('part_chassis', array(
      'brand' => 'CSE-833T-653B BLACK',
      'model' => 'CSE-833T-653B BLACK',
      'standard' => 'CSE-833T-653B BLACK',
      'disk_number' => 4,
      'stock' => 0
    ));
    $part->save();
    $parts['chassis']['833t'] = $part->id();

    $part = entity_create('part_chassis', array(
      'brand' => 'CSE-813MTQ-350CB 350W 1U Rackmount',
      'model' => 'CSE-813MTQ-350CB 350W 1U Rackmount',
      'standard' => 'CSE-813MTQ-350CB 350W 1U Rackmount',
      'disk_number' => 4,
      'stock' => 0
    ));
    $part->save();
    $parts['chassis']['813mtq'] = $part->id();
    return $parts;
  }
}


