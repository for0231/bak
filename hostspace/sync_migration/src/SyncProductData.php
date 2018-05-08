<?php

namespace Drupal\sync_migration;

class SyncProductData {

  protected $base_url;

  public function __construct($domain_name) {
    $this->base_url = $domain_name;
  }

  public function syncProductData() {
   try {
      $uri = $this->base_url . '/syn/price_data_to_drupal.php';
      $client = \Drupal::httpClient(); 
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
    $json_str = (string)$response->getBody();
    $products = json_decode($json_str, true);

    //得到所有业务价格列表
    $business_price_list = array();
    foreach($products as $key => $product) {
      $business_price = isset($product['business_price']) ? $product['business_price'] : array();
      foreach($business_price as $item) {
        $cataloguetitle = $item['cataloguetitle'];
        $title = str_replace(' ', '', $item['title']);
        $business_price_list[$cataloguetitle][$title] = $item['price_month'];
      }
    }
    $business_list = $this->addBusiness(); //增加业务。
    $this->addBusinessPrice($business_list, $business_price_list); //增加业务价格

    foreach($products as $key => $product) {
      $product['pid'] = $key;
      $this->saveProduct($product, $business_list);
    }
    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_product', 1);
    $json_str = json_encode($business_list);
    $config->set('sync_product_business_list', $json_str);
    $config->save();
  }

  /**
   * 增加业务
   */
  private function addBusiness() {
    $business_list = array();
    $newwork_term = taxonomy_term_load_multiple_by_name('Network', 'product_business_Catalog');
    $newwork_term_id = reset($newwork_term)->id();
    $hardware_term = taxonomy_term_load_multiple_by_name('Hardware', 'product_business_Catalog');
    $hardware_term_id = reset($hardware_term)->id();
    $system_term = taxonomy_term_load_multiple_by_name('System', 'product_business_Catalog');
    $system_term_id = reset($system_term)->id();
    //IP
    $ip_value = array('name' => 'Ip', 'catalog' => $newwork_term_id, 'operate' => 'edit_number', 'resource_lib' =>'ipb_lib', 'entity_type' => 'ipb', 'combine_mode' => 'add', 'upgrade' =>true, 'locked' => true);
    $ip = entity_create('product_business', $ip_value);
    $ip->save();
    $business_list['ip']['id'] = $ip->id();
    //端口
    $port_value = array('name' => '端口', 'catalog' => $newwork_term_id, 'operate' => 'edit_number', 'resource_lib' =>'none', 'combine_mode' => 'add', 'upgrade' =>true, 'locked' => true);
    $port = entity_create('product_business', $port_value);
    $port->save();
    $business_list['port']['id'] = $port->id();
    //高防
    $huayu_value = array('name' => '防御', 'catalog' => $newwork_term_id, 'operate' => 'select_and_number', 'resource_lib' =>'ipb_lib', 'entity_type' => 'ipb', 'combine_mode' => 'add', 'upgrade' =>true, 'locked' => true);
    $huayu = entity_create('product_business', $huayu_value);
    $huayu->save();
    $business_list['huangyu']['id'] = $huayu->id();
    $terms = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('business_ip_type');
    foreach($terms as $term) {
      $value = array('entity_type' => 'taxonomy_term', 'target_id' => $term->tid);
      if($term->name == '无防御') {
        $value['businessId'] = $ip->id();
        entity_create('product_business_entity_content', $value)->save();
      } else {
        $value['businessId'] = $huayu->id();
        $content = entity_create('product_business_entity_content', $value);
        $content->save();
        $business_list['huangyu'][$term->name] = $content->id();
      }
    }
    //带宽
    $daikuan_value = array('name' => '带宽', 'catalog' => $newwork_term_id, 'operate' => 'select_content', 'resource_lib' =>'create', 'combine_mode' => 'replace', 'upgrade' =>true, 'locked' => true);
    $daikuan = entity_create('product_business', $daikuan_value);
    $daikuan->save();
    $business_list['daikuan']['id'] = $daikuan->id();
    $content_values = array('10M独享', '30M独享', '升级带宽至100M(优化线路)', '升级带宽至200M(优化线路)', '升级带宽至300M(优化线路)', '升级带宽至400M(优化线路)', '升级带宽至500M(优化线路)', '升级带宽至1G(优化线路)', '升级带宽至200M(普通线路)', '升级带宽至300M(普通线路)', '升级带宽至400M(普通线路)','升级带宽至500M(普通线路)','升级带宽至1G(普通线路)');
    foreach($content_values as $content_value) {
      $conent_entity = entity_create('product_business_content', array(
       'name' => $content_value,
       'businessId' => $daikuan->id()
      ));
      $conent_entity->save();
      $business_list['daikuan'][$content_value] = $conent_entity->id();
    }
    //其它
    $other_business = array(
      array('name' => '内存', 'catalog' => $hardware_term_id, 'operate' => 'select_content', 'resource_lib' =>'part_lib', 'entity_type' => 'part_memory', 'combine_mode' => 'add', 'upgrade' =>true, 'locked' => true),
      array('name' => '硬盘', 'catalog' => $hardware_term_id, 'operate' => 'select_content', 'resource_lib' =>'part_lib', 'entity_type' => 'part_harddisc', 'combine_mode' => 'add', 'upgrade' =>true, 'locked' => true),
      //array('name' => '操作系统', 'catalog' => $system_term_id, 'operate' => 'select_content', 'resource_lib' =>'create', 'combine_mode' => 'replace', 'upgrade' =>false, 'locked' => true),
      //array('name' => 'OS语言', 'catalog' =>$system_term_id, 'operate' => 'select_content', 'resource_lib' =>'create', 'combine_mode' => 'replace', 'upgrade' =>false, 'locked' => true),
    );
    foreach($other_business as $business_value) {
      entity_create('product_business', $business_value)->save();
    }
    return $business_list;
  }

  /**
   * 设置业务价格
   */
  private function addBusinessPrice($business_list, $business_price_list) {
    //设置IP价格
    entity_create('business_price', array(
      'businessId' => $business_list['ip']['id'],
      'price' => 20,
      'payment_mode' => 'month',
    ))->save();

    //设置端口价格
    entity_create('business_price', array(
      'businessId' => $business_list['port']['id'],
      'price' => 50,
      'payment_mode' => 'month',
    ))->save();
    //设置带宽价格
    $daikuan_list = $business_price_list['带宽'];
    foreach($daikuan_list as $key => $value) {
      $name = $this->getDaikuanName($key);
      if(isset($business_list['daikuan'][$name])) {
        entity_create('business_price', array(
          'businessId' => $business_list['daikuan']['id'],
          'business_content' => $business_list['daikuan'][$name],
          'price' => $value,
          'payment_mode' => 'month',
        ))->save();
      } 
    }
    //设置防御
    $huangyu_list = $business_price_list['防御'];
    foreach($huangyu_list as $key => $value) {
      $name = $this->getHuangyuName($key);
      if(isset($business_list['huangyu'][$name])) {
        entity_create('business_price', array(
          'businessId' => $business_list['huangyu']['id'],
          'business_content' => $business_list['huangyu'][$name],
          'price' => $value,
          'payment_mode' => 'month',
        ))->save();
      }
    }
  }

  
  //获取带宽name
  private function getDaikuanName($str) {
    $keyword_size = array('100M', '200M', '300M', '400M', '500M', '1G');
    $size = '';
    foreach($keyword_size as $value) {
      if(strpos($str, $value) !== false) {
         $size = $value;
         break;
      }
    }
    $keyword_type = array('普通', '优化');
    $type = '';
    foreach($keyword_type as $value) {
      if(strpos($str, $value) !== false) {
         $type = $value;
         break;
      }
    }
    return strtr('升级带宽至%size(%type线路)', array(
      '%size' => $size,
      '%type' => $type,
    ));
  }
  
  //获取防御name
  private function getHuangyuName($str) {
    $keywords = array('20G', '40G', '60G', '80G');
    $keyword = '';
    foreach($keywords as $value) {
      if(strpos($str, $value) !== false) {
        $keyword = $value;
        break;
      }
    }
    return strtr('%size防御', array('%size' => $keyword));
  }
  

  /**
   * 保存产品
   */
  private function saveProduct($product, $business_list) {
    //创建产品
    $name = $product['pid'];
    $server_type = entity_load_multiple_by_properties('server_type', array('name' => $name));
    if(empty($server_type)) {
      echo '服务器分类' . $name . '不存在。<br>'; 
      return;
    }
    $server_type_id = reset($server_type)->id();
    $product_entity = entity_create('product', array(
      'name' => $name,
      'server_type' => $server_type_id,
      'description' => array('format' => 'full_html','value' => '<style type="text/css">table.description { 
    width:100%; 
  } 
  table.description td.image { 
    width: 200px; 
    text-align: left;
    vertical-align: top;
  }
  table.description td.msg p { 
    font-size: 14px; 
    line-height: 30px; 
    text-indent: 2em;
    margin: 0;
  }
</style>
<table border="0" cellpadding="0" cellspacing="0" class="description">
	<tbody>
		<tr>
			<td class="image"><img alt="图片" data-entity-type="file" data-entity-uuid="d28cb0f3-8c92-4af6-b75b-91823e97f7f3" height="120" src="/sites/default/files/inline-images/pic_02_0.jpg" width="160" /></td>
			<td class="msg">
			<p>更快，更有效，更可靠的总结了英特尔酷睿2双核处理器。我们提供广泛的酷睿2的不同CPU速度，总线和缓存大小。所有的酷睿2的能力的64位操作系统，提供真正的并行计算的两个独立的CPU的轧制成一个包。</p>

			<p>如果你感到困惑，或不知道任何我们提供的服务在凯普特，随意使用即时聊天功能位于权。我们对所有客户提供高质量的服务感到自豪。你也可以要求自定义报价详细说明你需要精确的规格。更快，更有效，更可靠的总结了英特尔酷睿2双核处理器。我们提供广泛的酷睿2的不同CPU速度，总线和缓存大小。所有的酷睿2的能力的64位操作系统，提供真正的并行计算的两个独立的CPU的轧制成一个包。</p>
			</td>
		</tr>
	</tbody>
</table>'),
      'parameters' => array('format' => 'full_html','value' => '<style type="text/css">table.parameters {
    width:100%;
  }
  table.parameters tr.beij {
    background: none repeat scroll 0 0 #F2F4F5;
  }
  table.parameters tr td {
    font-size: 14px;
    height: 40px;
    line-height: 40px;
  }
  table.parameters tr td.name {
    font-weight: bold;
    text-align: right;
    width: 20%;
  }
  table.parameters tr td.value {
    text-align: left;
    width:30%;
    padding: 0 10px;
  }
</style>
<table border="0" cellpadding="0" cellspacing="0" class="parameters">
	<tbody>
		<tr>
			<td class="name">处理器家族:</td>
			<td class="value">Atom</td>
			<td class="name">VID 电压范围:</td>
			<td class="value">0.9V-1.1625V</td>
		</tr>
		<tr class="beij">
			<td class="name">处理器型号:</td>
			<td class="value">330</td>
			<td class="name">内存:</td>
			<td class="value">2GB RAM</td>
		</tr>
		<tr>
			<td class="name">内核数:</td>
			<td class="value">2</td>
			<td class="name">VID 硬盘:</td>
			<td class="value">250GB SATA HDD</td>
		</tr>
		<tr class="beij">
			<td class="name">线程数:</td>
			<td class="value">2</td>
			<td class="name">带宽:</td>
			<td class="value">10TB 带宽</td>
		</tr>
		<tr>
			<td class="name">总线频率:</td>
			<td class="value">1.6GB</td>
			<td class="name">IP地址:</td>
			<td class="value">3个可用IP</td>
		</tr>
		<tr class="beij">
			<td class="name">L2缓存:</td>
			<td class="value">2MB</td>
			<td class="name">支持RAID融灾:</td>
			<td class="value">否</td>
		</tr>
		<tr>
			<td class="name">指令集:</td>
			<td class="value">64位</td>
			<td class="name">Max Internal:</td>
			<td class="value">2</td>
		</tr>
		<tr class="beij">
			<td class="name">指令集:</td>
			<td class="value">8M</td>
			<td class="name">&nbsp;</td>
			<td class="value">&nbsp;</td>
		</tr>
	</tbody>
</table>
'),
      'display_cpu' => $product['cpu'],
      'display_memory' => $product['memory'],
      'display_harddisk' => $product['harddisk'],
      'display_system' => 'Windows/Linux',
      'custom_business' => false,
      'front_Dispaly' => false
    ));
    $options = array();
    $options[] = array(
      'businessId' => $business_list['ip']['id'],
      'business_content' => 1
    );
    if($name == '配置四') {
      $options[] = array(
        'businessId' => $business_list['daikuan']['id'],
        'business_content' => $business_list['daikuan']['10M独享']
      );
      $product_entity->default_business = $options;
    } else if (strpos($name, 'DELL') !== false) {
      $product_entity->default_business = $options;
    } else  {
      $options[] = array(
        'businessId' => $business_list['daikuan']['id'],
        'business_content' => $business_list['daikuan']['30M独享']
      );
      $product_entity->default_business = $options;
    }
    $product_entity->save();

    //创建产品价格
    if(empty($product['price'])) {
      return;
    }
    $product_price = $product['price'];
    $arr_level = array('authenticated', 'agent_I', 'agent_II', 'agent_3', 'agent_4');
    foreach($arr_level as $level) {
      entity_create('product_price', array(
        'productId' => $product_entity->id(),
        'user_level' => $level,
        'payment_mode' => 'month',
        'price' => $product_price
      ))->save();
      $product_price = $product_price * 0.8;
    }

    //创建产品业务价格
    if(!isset($product['business_price'])) {
      return;
    }

    $business_prices = $product['business_price'];
    foreach($business_prices as $business_price) {
      $values = array(
        'productId' => $product_entity->id(),
        'payment_mode' => 'month',
        'price' => $business_price['price_month']
      );
      $bus_type = $business_price['cataloguetitle'];
      if($bus_type == 'IP地址') {
        $values['businessId'] = $business_list['ip']['id'];
        entity_create('product_business_price', $values)->save();
      } else if ($bus_type == '端口') {
        $values['businessId'] = $business_list['port']['id'];
        entity_create('product_business_price', $values)->save();
      } else if ($bus_type == '带宽') {
        $values['businessId'] = $business_list['daikuan']['id'];
        $title = $business_price['title'];
        $name = $this->getDaikuanName($title);
        if(isset($business_list['daikuan'][$name])) {
          $values['business_content'] = $business_list['daikuan'][$name];
          entity_create('product_business_price', $values)->save();
        }
      } else if ($bus_type == '防御') {
        $values['businessId'] = $business_list['huangyu']['id'];
        $title = $business_price['title'];
        $name = $this->getHuangyuName($title);
        if(isset($business_list['huangyu'][$name])) {
          $values['business_content'] = $business_list['huangyu'][$name];
          entity_create('product_business_price', $values)->save();
        }
      }
    }
  }
}
