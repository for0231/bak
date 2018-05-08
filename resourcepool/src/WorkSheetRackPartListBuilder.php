<?php
namespace Drupal\resourcepool;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class WorkSheetRackPartListBuilder extends EntityListBuilder {
  public $formBuilder;
  public $switchip_list=array();
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->formBuilder = \Drupal::getContainer()->get('form_builder');
    $service = \Drupal::service('resourcepool.dbservice');
    $iplist = $service->get_switch_ip();
    foreach($iplist as $item){
      $this->switchip_list[$item->switch_name]=$item->switch_ip;
    }
  }
  public function buildHeader() {
    $header['manage_ip'] = '管理IP';
    $header['room'] = '机房';
    $header['rack'] = '机柜';
    $header['serve_u'] = '服务器U位';
    $header['node'] = 'Node';
    $header['ma_ic_name'] = '管理交换机名';
    $header['port'] = '管理端口';
    $header['ye_icname'] = '业务交换机名';
    $header['ye_network_card'] = '业务网卡';
    $header['ye_vlan'] = '业务vlan';
    $header['networkcard_icname'] = '第二网卡交换机名';
    $header['network_card'] = '第二网卡';
    $header['networkcard_vlan'] = '第二网卡vlan';
    $header['notes'] = '备注';
    $header['operations'] = '操作';
    return $header + parent::buildHeader();
  }
  public function buildRow(EntityInterface $entity) {
    $row['manage_ip'] = $entity->get('manage_ip')->value;
    $row['room'] = getRoom()[$entity->get('room')->value];
    $row['rack'] = $entity->get('rack')->value;
    $row['serve_u'] = $entity->get('serve_u')->value;
    $row['node'] = $entity->get('node')->value;
    $row['ma_ic_name'] = $entity->get('ma_ic_name')->value;
    $row['port']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('port')->value) ? '-' : $entity->get('port')->value,
      '#url' => new Url('admin.resourcepool.mock.login.cacti', ['url' => $entity->id(), 'type' => 'href_manage_network_card']),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['ye_icname'] = $entity->get('ye_icname')->value;
    $row['ye_network_card']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('ye_network_card')->value) ? '-' : $entity->get('ye_network_card')->value,
      '#url' => new Url('admin.resourcepool.mock.login.cacti', ['url' => $entity->id(), 'type' => 'href_network_card']),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['ye_vlan'] = $entity->get('ye_vlan')->value;
    $row['networkcard_icname'] = $entity->get('networkcard_icname')->value;
    $row['network_card']['data'] = [
      '#type' => 'link',
      '#title' => empty($entity->get('network_card')->value) ? '-' : $entity->get('network_card')->value,
      '#url' => new Url('admin.resourcepool.mock.login.cacti', ['url' => $entity->id(), 'type' => 'href_network_card_two']),
      '#attributes' => [
        'target' => '_blank',
      ],
    ];
    $row['networkcard_vlan'] = $entity->get('networkcard_vlan')->value;
    $row['notes'] = $entity->get('notes')->value;
    return $row  + parent::buildRow($entity);
  }
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    $operations['edit-form'] = array(
      'title' => '编辑',
      'weight' => 1,
      'url' => $entity->urlInfo('edit-form'),
      'attributes' => array(
          'class' => array('btn btn-danger'),
      )
    );
    $operations['delete_form'] = array(
      'title' => '删除',
      'weight' => 2,
      'url' => $entity->urlInfo('delete-form'),
      'attributes' => array(
          'class' => array('btn btn-danger'),
      )
    );
    if(!empty($this->switchip_list[$entity->get('ma_ic_name')->value])){
      $operations['manage'] = array(
        'title' => '管理交换机端口配置',
        'weight' => 3,
        'url' => new url('admin.utils.port.config',
          array(
            'manageip'=>$entity->get('manage_ip')->value,
            'port'=>$entity->get('port')->value,
            'switchname'=>$entity->get('ma_ic_name')->value,
            'switchip'=> empty($this->switchip_list[$entity->get('ma_ic_name')->value])?0:$this->switchip_list[$entity->get('ma_ic_name')->value],
          )
        ),
      );
    }
    if(!empty($this->switchip_list[$entity->get('ye_icname')->value])){
      $operations['business'] = array(
        'title' => '业务交换机端口配置',
        'weight' => 4,
        'url' => new url('admin.utils.port.config',
          array(
            'manageip'=>$entity->get('manage_ip')->value,
            'port'=>$entity->get('ye_network_card')->value,
            'switchname'=>$entity->get('ye_icname')->value,
            'switchip'=>empty($this->switchip_list[$entity->get('ye_icname')->value])?0:$this->switchip_list[$entity->get('ye_icname')->value],
          )
        ),
      );
    }
    if(!empty($this->switchip_list[$entity->get('networkcard_icname')->value])){
      $operations['networkcard'] = array(
        'title' => '第二网卡交换机端口配置',
        'weight' => 5,
        'url' => new url('admin.utils.port.config',
          array(
            'manageip'=>$entity->get('manage_ip')->value,
            'port'=>$entity->get('network_card')->value,
            'switchname'=>$entity->get('networkcard_icname')->value,
            'switchip'=>empty($this->switchip_list[$entity->get('networkcard_icname')->value])?0:$this->switchip_list[$entity->get('networkcard_icname')->value],
          )
        ),
      );
    }
    return $operations + parent::getDefaultOperations($entity);
  }
  public function getEntityIds() {
    if(!empty($_SESSION['rack_part'])) {
      $db_server = \Drupal::service('resourcepool.dbservice');
      return $db_server->rackpartExportDataPage($_SESSION['rack_part']);    //存在条
    } else {
      return parent::getEntityIds();
    }
  }
  public function render() {
    $this->limit = 20;
    $build['filter'] = $this->formBuilder->getForm('Drupal\resourcepool\Form\RackPartFilterForm');
    $build += parent::render();
    return $build;
  }
}
?>


