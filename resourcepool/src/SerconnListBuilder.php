<?php
/**
 * @file
 * Contains \Drupal\resourcepool\SerconnListBuilder
 */

namespace Drupal\resourcepool;

use Drupal\Core\Url;

class SerconnListBuilder {
  protected $formBuilder;
  public function __construct(){
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  protected function buildHeader() {
    $header['room']  = '机房';
    $header['rack_no']  = '机柜号';
    $header['server_id']  = '服务器编号';
    $header['server_part'] = '服务器配置';
    $header['location_u'] = 'U位';
    $header['node'] = 'Node';
    $header['nic1_address'] = 'NIC1 IP地址';
    $header['ic_address'] = 'NIC1链接交换机名称';
    $header['nic1'] = 'NIC1链接交换机端口';
    $header['nic1_bandwidth'] = 'NIC1带宽';
    $header['nic1_vlan'] = 'NIC1 VLAN';
    $header['ic2_address'] = 'NIC2链接交换机名称';
    $header['nic2'] = 'NIC2链接交换机端口';
    $header['nic2_bandwidth'] = 'NIC2带宽';
    $header['nic2_vlan'] = 'NIC2 VLAN';
    $header['contract_no'] = '合同编号';
    $header['onsale_status'] = '在售状态';
    $header['client_name'] = '客户';
    $header['op'] = '操作';
    return $header;
  }

  protected function buildRow() {
    $page= empty($_GET['page'])?0:$_GET['page'];
    $conditions = $this->filter();
    $statistic = \Drupal::service('resourcepool.dbservice');
    $tmp = $statistic->load_server_connection('resource_server_connection',$conditions);
    $rows = array();
    foreach($tmp as $item){
      $rows[] = array(
        'room' => $item->room,
        'rack_no'=> $item->rack_no,
        'server_id' => $item->server_id,
        'server_part' => $item->server_part,
        'location_u' => $item->location_u,
        'node' => $item->node,
        'nic1_address' => $item->nic1_address,
        'ic_address'=> $item->ic_address,
        'nic1' =>array('data'=>array(
          '#type' => 'link',
          '#title' => empty($item->nic1)?'-':$item->nic1,
          '#url' => empty($item->cacti1)?null: Url::fromUri($item->cacti1),
          '#attributes' => [
            'target' => '_blank',
          ])
        ),
        'nic1_bandwidth' => $item->nic1_bandwidth,
        'nic1_vlan' => $item->nic1_vlan,
        'ic2_address' => $item->ic2_address,
        'nic2' => array('data'=>array(
          '#type' => 'link',
          '#title' => empty($item->nic2)?'-':$item->nic2,
          '#url' => empty($item->cacti2)?null: Url::fromUri($item->cacti2),
          '#attributes' => [
            'target' => '_blank',
          ])
        ),
        'nic2_bandwidth' => $item->nic2_bandwidth,
        'nic2_vlan' => $item->nic2_vlan,
        'contract_no' => $item->contract_no,
        'onsale_status' => $item->onsale_status,
        'client_name' => $item->client_name,
        'op'=>array('data' => array(
          '#type' => 'operations',
          '#links' => array(
            'edit'=>array(
              'title' => '修改',
              'url' => new Url('admin.resource.serverconn.edit', array('no'=>$item->no,'page'=>$page)),
            ),
            'delete'=>array(
              'title' => '删除',
              'url' => new Url('admin.resource.serverconn.delete', array('no'=>$item->no)),
            ),
            'soldout'=>array(
              'title' => '转已售',
              'url' => new Url('admin.resourcepool.client.soldoutedit', array('no'=>$item->no,'type'=>'已售','server_id'=>$item->server_id)),
            ),
            'soldin'=>array(
              'title' => '转可售',
              'url' => new Url('admin.resourcepool.client.soldoutedit', array('no'=>$item->no,'type'=>'可售','server_id'=>$item->server_id)),
            ),
            'soldself'=>array(
              'title' => '转自用',
              'url' => new Url('admin.resourcepool.client.soldoutedit', array('no'=>$item->no,'type'=>'自用','server_id'=>$item->server_id)),
            ),
          )
        ))
      );
    }
    return $rows;
  }
  public function filter() {
    $condition = array();
    if(isset($_SESSION['resource_pool'])) {
      if(!empty($_SESSION['resource_pool']['server_id'])) {
        $condition['server_id'] = $_SESSION['resource_pool']['server_id'];
      }
      if(!empty($_SESSION['resource_pool']['rack_no'])) {
        $condition['rack_no'] = $_SESSION['resource_pool']['rack_no'];
      }
      if(!empty($_SESSION['resource_pool']['nic1_address'])) {
        $condition['nic1_address'] = $_SESSION['resource_pool']['nic1_address'];
      }
      if(!empty($_SESSION['resource_pool']['client_name'])) {
        $condition['client_name'] = $_SESSION['resource_pool']['client_name'];
      }
      
      if(!empty($_SESSION['resource_pool']['onsale_status'])) {
        $condition['onsale_status'] = $_SESSION['resource_pool']['onsale_status'];
      }
      if(!empty($_SESSION['resource_pool']['vlan'])) {
        $condition['nic1_vlan'] = $_SESSION['resource_pool']['vlan'];
        $condition['nic2_vlan'] = $_SESSION['resource_pool']['vlan'];
      }
      if(!empty($_SESSION['resource_pool']['room'])) {
        $condition['room'] = $_SESSION['resource_pool']['room'];
      }
    }
    return $condition;
  }
  /**
   * 列表
   */
  public function build() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\SerconnFilterForm',$type='Serconn');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    $build['list_pager'] = array('#type' => 'pager');
    return $build;
  }
}