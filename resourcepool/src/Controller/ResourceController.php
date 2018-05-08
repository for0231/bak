<?php
/**
 * @file
 * Contains \Drupal\resourcepool\Controller\ResourceController.
 */

namespace Drupal\resourcepool\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\resourcepool\WorkSheetVlanBuilde;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\resourcepool\SerconnListBuilder;
use Drupal\resourcepool\SerresListBuilder;

class ResourceController extends ControllerBase {

  protected $cookie = "";
  protected $cookie_jar = "";
  protected $formBuilder;
  protected $db_service;

  public function __construct($db_service) {
    /*
    $this->cookie_jar = $cookie_jar;
    $this->cookie = $cookie;
    */
    $this->db_service = $db_service;
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
  }
  public static function create(ContainerInterface $container) {
    return new static(
      \Drupal::service('resourcepool.dbservice')
    );
  }
  public function VlanStatistic(){
    $vlanStatistic = new WorkSheetVlanBuilde();
    return $vlanStatistic->build();
  }
  public function exportRackpart(){
    $filename=realpath('sites/default/files/rackpart.xlsx'); //文件名
    $date=date("Ymd-H:i:m");
    Header( "Content-type:  application/octet-stream ");
    Header( "Accept-Ranges:  bytes ");
    Header( "Accept-Length: " .filesize($filename));
    header( "Content-Disposition:  attachment;  filename= {$date}.xlsx");
    readfile($filename);
    return new Response('ok');
  }
  public function rackpartExport(){
    $conditions = $this->filter();
    $filename = '机柜数据'.time().'.csv';
    header('Content-Type: application/vnd.ms-excel;');
    header('Content-Disposition: attachment;filename="'.$filename.'"');
    header('Cache-Control: max-age=0');
    $fp = fopen('php://output', 'a');
    fwrite($fp, chr(0xEF).chr(0xBB).chr(0xBF)); // 添加 BOM
    $head = array('机房', '机柜','服务器U位','管理IP','管理端口','管理交换机名称','Node','业务网卡', '业务VLAN', '业务交换机名称', '第二网卡','第二网卡VLAN','第二网卡交换机名称','配置');
    fputcsv($fp, $head);
    $datas = \Drupal::service('resourcepool.dbservice')->rackpartExportData($conditions);
    $i=0;
    foreach ($datas as $item){
      if($i > 500) {
        $i = 0;
        ob_flush();
        flush();
      }
      $i++;
      $room = $item->room;
      if ($room==49){
        $room ='LA';
      }elseif($room==50){
        $room ='HK';
      }elseif($room==51){
        $room ='DC';
      }
      $row =array(
        $room,
        $item->rack,
        "\t".$item->serve_u,
        $item->manage_ip,
        $item->port,
        $item->ma_ic_name,
        $item->node,
        $item->ye_network_card,
        $item->ye_vlan,
        $item->ye_icname,
        $item->network_card,
        $item->networkcard_vlan,
        $item->networkcard_icname,
        $item->notes
      ); 
      fputcsv($fp, $row);
    }
    return new Response('');
  }
  public function rackpartdelete(){
    $condition = array();
    //查询机柜批量删除数据
    if(isset($_SESSION['rack_part'])) {
      if(!empty($_SESSION['rack_part']['rack'])) {
        $condition['rack'] = $_SESSION['rack_part']['rack'];
      }
    }
    if(!empty($condition['rack'])){
      $row = \Drupal::service('resourcepool.dbservice')->rackpartDelete($condition);
      if($row){
        drupal_set_message('删除成功');
      }else{
        drupal_set_message('删除失败','error');
      }
    }else{
      drupal_set_message('删除失败','error');
    }
    return $this->redirect('admin.resourcepool.rackpart.list');
  }

  /**
   * @description 模拟登录usa,hk的cacti后台.
   */
  public function loginCacti($url, $type) {
    $entity_rackpart = resourcepool_load($url);

    /**
     * @todo 添加远程登录服务组
     */

    $remoteservice = \Drupal::service('resourcepool.remoteservice');

    if (empty($entity_rackpart->get($type)->value)) {
      return ['markup' => 'No data'];
    }

    $item_images = $remoteservice->getCactiGraphs($entity_rackpart->get($type)->value);

    foreach ($item_images as $key => $val) {
      switch($key) {
        case 6:
          $new_key = "Daily (1 Minute Average)@1 Min";
          break;
        case 7:
          $new_key = "Weekly (1 Minute Average)@1 min";
          break;
        case 8:
          $new_key = "Monthly (30 Minute Average)@ 1 Min";
          break;
        case 9:
          $new_key = "Yearly (1 Day Average)@1 Min";
          break;
      }
      $items[$new_key] = $val;
    }
    $build['item_cacti_images'] = array(
     '#theme' => 'item_cacti_images',
     '#item_cacti_images' => $items,
    );
    return $build;
  }


  public function getSwitchnameIp(){
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\RackpartSwitchForm');
    $data = \Drupal::service('resourcepool.dbservice')->get_switch_ip();
    foreach($data as $item){
      $rows[] = array(
        'id'=> $item->id,
        'switch_name'=> $item->switch_name,
        'switch_ip'=>$item->switch_ip,
      );
    }
    $rows[] = array();
    $build['list']= array(
      '#type' => 'table',
      '#header' => array('id'=>'ID','switch_name'=>'交换机名','switch_ip'=>'IP'),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    return $build;
  }
///增加专线
  public function getServerConnection(){
    $connect = new SerconnListBuilder();
    return $connect->build();
  }
  public function getServerResource(){
    $resource = new SerresListBuilder();
    return $resource->build();
  }
  public function deleteServerconn(Request $request, $no){
    $db_service = \Drupal::service('resourcepool.dbservice');
    $db_service->del_table('resource_server_connection',$no);
    drupal_set_message('删除成功');
    return $this->redirect('admin.resource.serverconn.list');
  }
  public function getDedicatedRes(){
    $statistic = \Drupal::service('resourcepool.dbservice');
    $tmp = $statistic->load_business('resource_dedicated_resources');
    $tmp_sharing=array();
    $tmp_dedicated=array();
    foreach($tmp as $item){
      if($item->type==1){
        $tmp_sharing[]=$item;
      }elseif($item->type==3){
        $tmp_dedicated[]=$item;
      }
    }
    $build['business'] = array(
      '#theme' => 'dedicated_list',
      '#tmp_sharing' =>$tmp_sharing,
      '#tmp_dedicated' => $tmp_dedicated,
    );
    return $build;
  }
  //专线资源删除
  public function deleteDedicatedRes(Request $request,$no) {
    $db_service = \Drupal::service('resourcepool.dbservice');
    $dedicated = $db_service->del_table('resource_dedicated_resources',$no);
    //删除客户产品列表,需要删除拆分列表和客户列表
    $type = $_GET['type'];
    $link_id = $_GET['link_id'];
    if($type==2){
      //客户产品,删除拆分列表
      $table3 = $db_service->del_bylinkid('resource_table3',$link_id);
      if($dedicated){
      drupal_set_message('删除成功');
      }else{
        drupal_set_message('删除失败','error');
      }
      return $this->redirect('admin.resourcepool.businesslist');
    }elseif($type==4){
      //删除客户专用产品
      if($dedicated){
      drupal_set_message('删除成功');
      }else{
        drupal_set_message('删除失败','error');
      }
      return $this->redirect('admin.resourcepool.businesslist');
    }elseif($type==3){
      //删除客户专用产品
      if($dedicated){
      drupal_set_message('删除成功');
      }else{
        drupal_set_message('删除失败','error');
      }
      return $this->redirect('admin.resourcepool.dedicatedres');
    }
    else{
      if($type == 1){
        //删除共享资源,删除拆分列表属于资源为当前共享资源的拆分产品
        $split = $db_service->del_by_affiliation_res('resource_table3',$no);
      }
      if($dedicated){
        drupal_set_message('删除成功');
      }else{
        drupal_set_message('删除失败','error');
      }
      return $this->redirect('admin.resourcepool.dedicatedres');
    }

  }
  //查询带宽分段记录表
  public function getBwSubsection(){
    $direct_re =array();
    $resolution_re=array();
    $direct_res= array();
    
    $db_service = \Drupal::service('resourcepool.dbservice');
    $direct_re = $db_service->load_dedicated(1);
    $business_re = $db_service->load_business('resource_table3');
    if(!empty($business_re)){
      foreach($business_re as $key=>$item){
        $client_list = $db_service->load_by_linkid2($item->affiliation_pro);
        if(!empty($client_list)){
          $resolution_re[$item->affiliation_res][] = array(
            'no'=>$client_list[0]->no,
            'link_id'=>$item->affiliation_pro,
            'client_name'=>$client_list[0]->client_name,
            'commit_bandwidth'=>$client_list[0]->commit_bandwidth,
            'brust_bandwidth'=>$client_list[0]->brust_bandwidth,
            'A_end'=>$client_list[0]->A_end,
            'Z_end'=>$client_list[0]->Z_end,
          );
        }else{
          $resolution_re=null;
        }
      }
    }
    if(!empty($resolution_re)){
      foreach($resolution_re as $key=>$item){
        foreach($item as $key2=>$value){
          foreach ($direct_re as $key3=>$value3){
            if ($key == $direct_re[$key3]->no){
              $direct_res[$key][]= $item[$key2]['commit_bandwidth'];
            }
          }
        }
      }
    }
    foreach($direct_res as  $key=>$value){
      $direct_res[$key]= array_sum($value);
    }
    $build['subsection'] = array(
      '#theme' => 'subsection_list',
      '#direct_re' =>$direct_re,
      '#resolution_re' => $resolution_re,
      '#direct_res'=>$direct_res,
    );
    return $build;
  }
  public function filter() {
    $condition = array();
    if(isset($_SESSION['rack_part'])) {
      if(!empty($_SESSION['rack_part']['manage_ip'])) {
        $condition['manage_ip'] = $_SESSION['rack_part']['manage_ip'];
      }
      if(!empty($_SESSION['rack_part']['rack'])) {
        $condition['rack'] = $_SESSION['rack_part']['rack'];
      }
      if(!empty($_SESSION['rack_part']['ye_vlan'])) {
        $condition['ye_vlan'] = $_SESSION['rack_part']['ye_vlan'];
      }
      if(!empty($_SESSION['rack_part']['networkcard_vlan'])) {
        $condition['networkcard_vlan'] = $_SESSION['rack_part']['networkcard_vlan'];
      }
      if(!empty($_SESSION['rack_part']['link_id'])) {
        $condition['link_id'] = $_SESSION['rack_part']['link_id'];
      }
      if(!empty($_SESSION['rack_part']['client_name'])) {
        $condition['client_name'] = $_SESSION['rack_part']['client_name'];
      }
      if(!empty($_SESSION['rack_part']['rent_time'])) {
        $condition['rent_time'] = $_SESSION['rack_part']['rent_time'];
      }
      if(!empty($_SESSION['rack_part']['end_time'])) {
        $condition['end_time'] = $_SESSION['rack_part']['end_time'];
      }
    }
    return $condition;
  }
  public function getBusinessList(){
    $conditions = $this->filter();
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\ZCustomerFilterForm');
    $db_service = \Drupal::service('resourcepool.dbservice');
    $type = array(2,4);
    $business_list = $db_service->load_dedicated2($type,$conditions);
    $build['subsection'] = array(
      '#theme' => 'business_lists',
      '#business_list' =>$business_list,
    );
    return $build;
  }
  //查询产品分段资源列表
  public function getSplitproductList(){
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\SplitFilterForm');
    $affiliation_pro = empty($_SESSION['split']['affiliation_pro'])?'':$_SESSION['split']['affiliation_pro'];
    $data = $this->db_service->load_split('resource_table3',$affiliation_pro);
    $rows[] = array();
    foreach($data as $item){
      $res_list = $this->db_service->loadEntityById('resource_dedicated_resources',$item->affiliation_res);
      $bandwidth = $this->db_service->load_by_linkid($item->affiliation_pro);
      $rows[] = array(
        'no'=> $item->no,
        'type'=> '产品分段资源',
        'commit_bandwidth'=>empty($bandwidth[0])?'':$bandwidth[0],
        'brust_bandwidth'=>empty($bandwidth[1])?'':$bandwidth[1],
        'affiliation_res'=>empty($res_list->link_id)?'':$res_list->link_id,
        'affiliation_pro'=>$item->affiliation_pro,
        'subsection_res'=>$item->subsection_res,
        'cacti_mbps'=>$item->cacti_mbps,
      );
    }
    $build['list']= array(
      '#type' => 'table',
      '#header' => array(
        'no'=>'编号','type'=>'类型','commit_bandwidth'=>'commit带宽',
        'brust_bandwidth'=>'brust带宽','affiliation_res'=>'归属资源','affiliation_pro'=>'归属产品','subsection_res'=>'分段资源编号','cacti_mbps'=>'计费流量'
      ),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    return $build;
  }
  public function deleteSplitProduct(Request $request,$no){
    $client_no = $_GET['client_no'];
    $dedicated = $this->db_service->del_table('resource_table3',$no);
    if(!empty($dedicated)){
      drupal_set_message('删除成功');
    }else{
      drupal_set_message('删除失败','error');
    }
    return $this->redirect('admin.resourcepool.splitproduct.add',array('client_no'=>$client_no));
  }
  public function getContractFind(Request $request){
    $build ['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\SplitFilterForm',$type='contract');
    $contract = empty($_SESSION['split']['contract'])?'':$_SESSION['split']['contract'];
    $client_name = empty($_SESSION['split']['client_name'])?'':$_SESSION['split']['client_name'];
    $rows[] = array();
    if(!empty($contract) || !empty($client_name)){
      $data = $this->db_service->get_contract($contract,$client_name);
      foreach($data as $item){
        $rows[] = array(
          'id'=> $item->link_id,
          'type'=> $item->type,
          'server_type'=> $item->server_type,
          'commit_bandwidth'=> $item->commit_bandwidth,
          'brust_bandwidth'=> $item->brust_bandwidth,
          'client_name'=> $item->client_name,
          'rent_time'=> empty($item->rent_time)?null:date("Y-m-d",$item->rent_time),
          'end_time'=> empty($item->end_time)?null:date("Y-m-d",$item->end_time),
          'price'=> $item->price,
        );
      }
    }
    $build['list']= array(
      '#type' => 'table',
      '#header' => array('id'=>'产品编号','type'=>'产品类型','server_type'=>'服务器类型','commit_bandwidth'=>'commit带宽','brust_bandwidth'=>'brust带宽','client_name'=>'客户名称','rent_time'=>'租用时间','end_time'=>'到期时间','price'=>'价格'),
      '#rows' => $rows,
      '#empty' => '无数据'
    );
    return $build;
  }
  public function editClientStatus(Request $request){
    $no =  $_GET['no'] ;
    $type = $_GET['type'];
    $server_id = $_GET['server_id'];
    $value =array(
      'onsale_status'=>$type
    );
    $row = $this->db_service->update_entity_byno($value,$no,'resource_server_connection');
    //保存服务器出售状态的日志  id，uid,time,comand,type
    $comand = "服务器编号为：".$server_id."的服务器转".$type;
    $row2 = $this->db_service->add_entity(array(
      'uid' => \Drupal::currentUser()->id(),
      'created' => time(),
      'command' => $comand,
      'type' => 'server_staus',
    ),'resource_log');
    if($row){
      drupal_set_message('状态修改成功');
    }else{
      drupal_set_message('状态修改失败','error');
    }
    if($type=='已售'){
      return $this->redirect('admin.resource.serverres.edit',array('no'=>$no));
    }else{
      return $this->redirect('admin.resource.serverconn.list');
    }
  }
  public function getSupplierList(){
    $data = $this->db_service->load_business('resource_supplier');
    $rows[] = array();
    foreach($data as $item){
      $rows[] = array(
        'supplier_name'=> $item->supplier_name,
        'supplier_type'=> $item->supplier_type,
        'supplier_info'=> $item->supplier_info,
        'notice'=> $item->notice,
        'note'=> $item->note,
        'op'=>array('data' => array(
          '#type' => 'operations',
          '#links' => array(
            'edit'=>array(
              'title' => '修改',
              'url' => new Url('admin.resource.supplier.edit', array('no'=>$item->no)),
            ),
            'delete'=>array(
              'title' => '删除',
              'url' => new Url('admin.resource.supplier.delete',array('no'=>$item->no)),
            ),
          )
        )),
      );
    }
    $build['list']= array(
      '#type' => 'table',
      '#header' => array('supplier_name'=>'供应商名称','supplier_type'=>'供应商类型','supplier_info'=>'供应商信息','notice'=>'注意事项','note'=>'备注','op'=>'操作'),
      '#rows' => $rows,
      '#empty' => '无数据',
    );
    return $build;
  }
  public function deleteSupplier(Request $request, $no){
    $this->db_service->del_table('resource_supplier',$no);
    drupal_set_message('删除成功');
    return $this->redirect('admin.resourcepool.supplierlist');
  }
  public function notesdelete(){
    //批量删除机柜实体的备注:
    $rack = empty($_SESSION['rack_part']['rack'])?null:$_SESSION['rack_part']['rack'];
    if(empty($rack)){
      drupal_set_message('请填写要删除的机柜','error');
    }else{
      $entitys = entity_load_multiple_by_properties('work_sheet_rackpart',array('rack'=>$rack));
      foreach($entitys as $entity){
        $entity->set('notes','');
        $entity->save();
      }
      drupal_set_message('修改成功'); 
    }
    return $this->redirect('admin.resourcepool.rackpart.list');
  }
}

