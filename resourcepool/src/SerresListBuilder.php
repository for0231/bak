<?php
/**
 * @file
 * Contains \Drupal\resourcepool\SerresListBuilder
 */

namespace Drupal\resourcepool;

use Drupal\Core\Url;

class SerresListBuilder {
  protected $formBuilder;
  public function __construct(){
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  protected function buildHeader() {
    $header['room']  = '机房';
    $header['rack_no']  = '机柜号';
    $header['server_id']  = '服务器编号';
    $header['nic1_address'] = '公网地址';
    $header['onsale_status'] = '在售状态';
    $header['client_name'] = '客户';
    $header['server_part'] = '服务器配置';
    $header['contract_no'] = '合同编号';
    $header['rent_time'] = '租用时间';
    $header['end_time'] = '到期时间';
    $header['note'] = '备注';
    $header['price'] = '价格';
    $header['op'] = '操作';
    return $header;
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
      if(!empty($_SESSION['resource_pool']['rent_time'])) {
        $condition['rent_time'] = $_SESSION['resource_pool']['rent_time'];
      }
      if(!empty($_SESSION['resource_pool']['end_time'])) {
        $condition['end_time'] = $_SESSION['resource_pool']['end_time'];
      }
    }
    return $condition;
  }
  protected function buildRow() {
    $conditions = $this->filter();
    $statistic = \Drupal::service('resourcepool.dbservice');
    $tmp = $statistic->get_serres_list('resource_server_connection',$conditions);
    $rows = array();
    foreach($tmp as $item){
      $rows[] = array(
        'room' => $item->room,
        'rack_no'=> $item->rack_no,
        'server_id' => $item->server_id,
        'nic1_address'=> $item->nic1_address,
        'onsale_status' => $item->onsale_status,
        'client_name'=> $item->client_name,
        'server_part' => $item->server_part,
        'contract_no'=> $item->contract_no,
        'rent_time'=> empty($item->rent_time)?null:date("Y-m-d",$item->rent_time),
        'end_time'=> empty($item->end_time)?null:date("Y-m-d",$item->end_time),
        'note'=> $item->note,
        'price'=> $item->price,
        'op'=>array('data' => array(
          '#type' => 'operations',
          '#links' => array(
            'edit'=>array(
              'title' => '修改',
              'url' => new Url('admin.resource.serverres.edit', array('no'=>$item->no)),
            )
          )
        )),
      );
    }
    return $rows;
  }
  
  /**
   * 列表
   */
  public function build() {
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\SerconnFilterForm',$type='Serres');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => $this->buildRow(),
      '#empty' => '无数据',
    );
    return $build;
  }
}