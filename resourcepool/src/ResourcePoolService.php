<?php
/**
 * @file
 *  统计服务类
 */
 
namespace Drupal\resourcepool;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class ResourcePoolService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  protected $cache_options = array();
  
  protected static $statisticTable = "work_sheet_statistic";

  protected $condtions = array();
 
  public function __construct(Connection $database) {
    $this->database = $database;
  }
  public function getVlanNum($room){
    $where ='';
    if(empty($room)){
      $where ='';
    }else{
      $where ="where room ='{$room}'";
    }
    $sql ="SELECT ye_vlan,COUNT(ye_vlan)as num from work_sheet_rackpart $where GROUP BY ye_vlan UNION ALL SELECT networkcard_vlan,COUNT(networkcard_vlan)as num from work_sheet_rackpart $where GROUP BY networkcard_vlan";
    $countlist = $this->database->query($sql)->fetchAll();
    return $countlist;
  }
  public function rackpartExportDataPage(array $conditions){
    $query = $this->database->select('work_sheet_rackpart', 't')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('t', array('id'));    
    if(!empty($conditions['manage_ip'])){
      $query->condition('t.manage_ip','%'.$conditions['manage_ip'].'%','LIKE');
    }
    if(!empty($conditions['rack'])){
      $query->condition('t.rack','%'.$conditions['rack'].'%','like');
    }
    if(!empty($conditions['ye_vlan'])){
      $query->condition('t.ye_vlan',$conditions['ye_vlan']);
    }
    if(!empty($conditions['networkcard_vlan'])){
      $query->condition('t.networkcard_vlan',$conditions['networkcard_vlan']);
    }
    $query->limit(20);
    return $query->execute()->fetchCol();
  }
  public function getOptionByid($id) {
    if(array_key_exists($id, $this->cache_options)) {
      return $this->cache_options[$id];
    }
    $object = $this->database->select('work_sheet_options', 't')
      ->fields('t')
      ->condition('t.id', $id)
      ->execute()
      ->fetchObject();
    $this->cache_options[$id] = $object;
    return $object;
  }
  public function rackpartExportData(array $conditions){
    $query = $this->database->select('work_sheet_rackpart', 't');
    $query->fields('t');
    if(isset($conditions['manage_ip'])){
      $query->condition('t.manage_ip','%'.$conditions['manage_ip'].'%','LIKE');
    }
    if(isset($conditions['rack'])){
      $query->condition('t.rack','%'.$conditions['rack'].'%','LIKE');
    }
    if(isset($conditions['ye_vlan'])){
      $query->condition('t.ye_vlan',$conditions['ye_vlan']);
    }
    if(isset($conditions['networkcard_vlan'])){
      $query->condition('t.networkcard_vlan',$conditions['networkcard_vlan']);
    }
    return $query->execute()->fetchAll();
  }
  public function rackpartDelete($condition){
    $rack = $condition['rack'];
    if(!empty($rack)){
      $sql ="DELETE from work_sheet_rackpart where rack='{$rack}'";
      $row = $this->database
       ->query($sql)
       ->execute();
      return $row;
    }else{
      return $row='';
    }
  }
  public function getswitch_byname($name){
    $query = $this->database->select('resource_switch_ip','t')->fields('t');
    $query->condition('t.switch_name',$name);
    return $query->execute()->fetchCol();
  }
  public function get_switch_ip(){
    $query = $this->database->select('resource_switch_ip','t')->fields('t');
    return $query->execute()->fetchAll();
  }
  public function insert_switchip($data){
    foreach($data as $value){
      $row = $this->getswitch_byname($value['A']);
      if(!empty($row)){
        $rs = $this->database->update('resource_switch_ip')
        ->fields(array('switch_ip'=>$value['B']))
        ->condition('switch_name',$value['A'])
        ->execute();
      }
    }
    return $rs;
  }
  /**
   * @description excel数据导入.
   */
  public function insert_rackpart($data){
    $count=0;
    if($data){
      foreach($data as $key=>$value){
        $a=$data[$key]['A'];
        $room =0;
        switch($a){
          case "LA":
            $room =49;
            break;
          case "HK":
            $room =50;
            break;
          case "DC":
            $room =51;
            break;
          case "SGP":
            $room =52;
            break;
          case "JP":
            $room =53;
            break;
          default:
            break;
        }
        if ($room ==0 || $data[$key]['A']==null){
          continue;
        }
        $manage_ip_list = $this->database->select('work_sheet_rackpart','c')
        ->fields('c',array('manage_ip'))
        ->condition('c.manage_ip', $data[$key]['D']. '%', 'LIKE')
        ->execute()
        ->fetchAll();
        $manlist = array();
        foreach($manage_ip_list as $items){
          $manlist[] = $items->manage_ip;
        }
        $port = trim($data[$key]['E']);
        $ye_network_card = trim($data[$key]['H']);
        $network_card = trim($data[$key]['K']);
        if(!empty($port)){
          if(empty(substr($port,0,4)==='Ethe')){
            continue;
          }
        }
        if(!empty($ye_network_card)){
          $num1 = substr($ye_network_card,0,2)=='Gi';
          $num2 = substr($ye_network_card,0,4)=='Ethe';
          $num3=$num1+$num2;
          if($num3<1){
            continue;
          }
        }
        if(!empty($network_card)){
          $numg = substr($network_card,0,2)=='Gi';
          $nume = substr($network_card,0,4)=='Ethe';
          $num4=$numg+$nume;
          if($num4<1){
            continue;
          }
        }
        if(in_array($data[$key]['D'],$manlist)){
          continue;
        }
        $count++;
        $rows = $this->database->insert('work_sheet_rackpart')->fields(array(
          'manage_ip'=> trim($data[$key]['D']),
          'room' => $room,
          'rack' => trim($data[$key]['B']),
          'serve_u' => trim($data[$key]['C']),
          'port' => trim($data[$key]['E']),
          'ma_ic_name' => trim($data[$key]['F']),
          'node' => trim($data[$key]['G']),
          'ye_network_card' => trim($data[$key]['H']),
          'ye_vlan' => trim($data[$key]['I']),
          'ye_icname' => trim($data[$key]['J']),
          'network_card' => trim($data[$key]['K']),
          'networkcard_vlan' => trim($data[$key]['L']),
          'networkcard_icname' => trim($data[$key]['M']),
          'part' => trim($data[$key]['N']),
          'href_network_card' => trim($data[$key]['O']),
          'href_network_card_two' => trim($data[$key]['P']),
          'href_manage_network_card' => trim($data[$key]['Q']),
        ))->execute();
      }
    }
    return $count;
  }
  
  
//增加专线
  public function add_entity($values,$table){
    return $this->database->insert($table)
      ->fields($values)
      ->execute();
  }

  public function loadEntityById($table,$no) {
    return $this->database->select($table, 't')
      ->fields('t')
      ->condition('no', $no)
      ->execute()
      ->fetchObject();
  }
  public function load_business($table){
    $query = $this->database->select($table, 't')
      ->fields('t');
    return $query->execute()
    ->fetchAll();
  }
  public function load_server_connection($table,array $conditions){
    $query = $this->database->select($table, 't')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->fields('t');
    if(!empty($conditions['server_id'])){
      $query->condition('t.server_id','%'.$conditions['server_id'].'%','LIKE');
    }
    if(!empty($conditions['rack_no'])){
      $query->condition('t.rack_no','%'.$conditions['rack_no'].'%','like');
    }
    if(!empty($conditions['nic1_address'])){
      $query->condition('t.nic1_address',$conditions['nic1_address']);
    }
    if(!empty($conditions['client_name'])){
      $query->condition('t.client_name',$conditions['client_name']);
    }
    if(!empty($conditions['onsale_status'])){
      $query->condition('t.onsale_status',$conditions['onsale_status']);
    }
    if(!empty($conditions['nic1_vlan'])){
      $query->condition(db_or()
      ->condition('t.nic1_vlan',$conditions['nic1_vlan'])
      ->condition('t.nic2_vlan',$conditions['nic2_vlan'])
      );
    }
    if(!empty($conditions['room'])){
      $query->condition('t.room',$conditions['room']);
    }
    $query->limit(20);
    return $query->execute()
    ->fetchAll();
  }
  public function load_dedicated($type){
    $query = $this->database->select('resource_dedicated_resources', 't')->fields('t');
    $query->condition('type',$type);
    return $query->execute()
    ->fetchAll();
  }
  public function load_dedicated2($type,array $conditions){
    $query = $this->database->select('resource_dedicated_resources', 't')->fields('t');
    if(!empty($conditions['link_id'])){
      $query->condition('t.link_id','%'.$conditions['link_id'].'%','LIKE');
    }
    if(!empty($conditions['client_name'])){
      $query->condition('t.client_name',$conditions['client_name']);
    }
    if(!empty($conditions['rent_time'])){
      $query->condition('t.rent_time',$conditions['rent_time'],'>=');
    }
    if(!empty($conditions['end_time'])){
      $query->condition('t.end_time',$conditions['end_time'],'<=');
    }
    $query->condition('type',$type,'IN');
    return $query->execute()
    ->fetchAll();
  }
  public function load_dedicated_linkid($type){
    $query = $this->database->select('resource_dedicated_resources', 't')->fields('t',array('link_id','no'));
    $query->condition('type',$type);
    return $query->execute()
    ->fetchAll();
  }
  public function get_resource_member(){
    $sql ="SELECT conn.room,conn.no,conn.rack_no,conn.server_id,conn.nic1_address,conn.onsale_status,conn.client_name,conn.server_part,conn.contract_no,conn.rent_time,conn.end_time,conn.note from resource_server_connection as conn where  conn.onsale_status='已售'";
    return $this->database->query($sql)->fetchAll();
  }
  public function get_serres_list($table,array $conditions){
    $query = $this->database->select($table, 't')->fields('t');
    if(!empty($conditions['server_id'])){
      $query->condition('t.server_id','%'.$conditions['server_id'].'%','LIKE');
    }
    if(!empty($conditions['rack_no'])){
      $query->condition('t.rack_no','%'.$conditions['rack_no'].'%','like');
    }
    if(!empty($conditions['nic1_address'])){
      $query->condition('t.nic1_address',$conditions['nic1_address']);
    }
    if(!empty($conditions['client_name'])){
      $query->condition('t.client_name',$conditions['client_name']);
    }
    if(!empty($conditions['rent_time'])){
      $query->condition('t.rent_time',$conditions['rent_time'],'>=');
    }
    if(!empty($conditions['end_time'])){
      $query->condition('t.end_time',$conditions['end_time'],'<=');
    }
    $query->condition('t.onsale_status','已售');
    return $query->execute()->fetchAll();
  }
  public function del_table($table,$no){
    return $this->database->delete($table)
        ->condition('no', $no)
        ->execute();
  }
  public function del_business($table,$no){
    return $this->database->delete($table)
        ->condition('dedicated_no', $no)
        ->execute();
  }
  public function load_by_linkid($link_id){
    $query = $this->database->select('resource_dedicated_resources', 't')
      ->fields('t',array('commit_bandwidth','brust_bandwidth'));
    $query->condition('link_id',$link_id);
    return $query->execute()
    ->fetchCol();
  }
  public function load_by_linkid2($link_id){
    $query = $this->database->select('resource_dedicated_resources', 't')
      ->fields('t');
    $query->condition('link_id',$link_id);
    return $query->execute()
    ->fetchAll();
  }
  public function get_split_res($link_id){
    $query = $this->database->select('resource_table3', 't')
      ->fields('t');
    $query->condition('affiliation_pro',$link_id);
    return $query->execute()
    ->fetchAll();
  }
  public function load_split($table,$aff_pro){
    $query = $this->database->select($table, 't')
      ->fields('t');
    if($aff_pro){
      $query->condition('affiliation_pro',$aff_pro);
    }
    return $query->execute()
    ->fetchAll();
  }
  public function del_bylinkid($table,$link_id){
    return $this->database->delete($table)
        ->condition('affiliation_pro', $link_id)
        ->execute();
  }
  public function del_by_affiliation_res($table,$no){
    return $this->database->delete($table)
        ->condition('affiliation_res', $no)
        ->execute();
  }
  public function get_contract($contract,$client_name){
    $where = '';
    if($contract){
      $this->condtions[] = "contract_no = '{$contract}'";
    }
    if($client_name){
      $this->condtions[] = "client_name = '{$client_name}'";
    }
    if(!empty($this->condtions)) {
      $where = "where " . implode(" and ", $this->condtions);
    }
    $sql="SELECT link_id,'共享专线客户' as type,''as server_type ,commit_bandwidth,brust_bandwidth,client_name,rent_time,end_time,price from resource_dedicated_resources $where
    UNION ALL
    SELECT server_id,'服务器资源' as type,server_part,'','',client_name,rent_time,end_time,price from resource_server_connection $where";
    return $this->database->query($sql)->fetchAll();
  }
  public function update_entity_byno($values,$no,$table){
    return $this->database->update($table)
      ->fields($values)
      ->condition('no', $no)
      ->execute();
  }
  public function get_supplier(){
    $sql ="SELECT supplier,supplier_info,note, type,notice,supplier_type from resource_dedicated_resources where type in (1,3) GROUP BY supplier";
    return $this->database->query($sql)->fetchAll();
  }
}

