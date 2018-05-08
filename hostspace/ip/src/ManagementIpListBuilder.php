<?php

/**
 * @file
 * Contains \Drupal\ip\ManagementIpListBuilder.
 */

namespace Drupal\ip;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Defines a class to build a listing of business ip entities.
 *
 * @see \Drupal\ip\Entity\IPM.php
 */
class ManagementIpListBuilder extends EntityListBuilder {

    /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory.
   */
   protected $queryFactory;

   /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
   protected $formBuilder;

   /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Constructs a new BusinessIpListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *  The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *  The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  The entity query factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage,  QueryFactory $query_factory, FormBuilderInterface $form_builder, DateFormatter $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->formBuilder = $form_builder;
    $this->dateFormatter = $date_formatter;
  }

   /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
      $container->get('form_builder'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('ipm');
    $entity_query->pager(PER_PAGE_COUNT);
    if(!empty($_SESSION['admin_ip_filter'])) {
      if(!empty($_SESSION['admin_ip_filter']['ip'])){
        $entity_query->condition('ip',$_SESSION['admin_ip_filter']['ip'],'CONTAINS');
      }
      if(!empty($_SESSION['admin_ip_filter']['uid'])){
        $entity_query->condition('uid', $_SESSION['admin_ip_filter']['uid'],'CONTAINS');
      }
      if(!empty($_SESSION['admin_ip_filter']['rid'])) {
        $rids = explode('_', $_SESSION['admin_ip_filter']['rid']);
        if(isset($rids[1])) {
          $entity_query->condition('group_id', $rids[1]);
        } else {
          $entity_query->condition('rid', $rids[0]);
        }
      }
      if(!empty($_SESSION['admin_ip_filter']['status'])){
        $entity_query->condition('status',$_SESSION['admin_ip_filter']['status'],'=');
      }
      if(!empty($_SESSION['admin_ip_filter']['server_type'])){
        $entity_query->condition('server_type',$_SESSION['admin_ip_filter']['server_type'],'=');
      }
    }
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ipmid = $entity_query->execute();
    return $this->storage->loadMultiple($ipmid);
  }

 /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
       'data' => $this->t('ID'),
       'field' => 'id',
       'specifier' => 'id'
    );
    $header['ip'] = array(
       'data' => $this->t('Ip'),
       'field' => 'ip_number',
       'specifier' => 'ip_number'
    );
    $header['rid'] = array(
      'data' => $this->t('所属机房'),
    );
    $header['group_id'] = array(
      'data' => '所属分组'
    );
    $header['cid'] = array(
      'data' => $this->t('机柜'),
    );
    $header['port'] = array(
      'data' => $this->t('Port'),
      'field' => 'port',
      'specifier' => 'port'
    );
    $header['uid'] = array(
      'data' => $this->t('Creator '),
      'field' => 'uid',
      'specifier' => 'uid'
    );
    $header['status'] = array(
      'data' => $this->t('Status'),
      'field' => 'status',
      'specifier' => 'status'
    );
    $header['equipment'] = array(
      'data' => $this->t('已上柜'),
      'field' => 'status_equipment',
      'specifier' => 'status_equipment'
    );
    $header['server_type'] = array(
      'data' => $this->t('Server type'),
      'field' => 'server_type',
      'specifier' => 'server_type'
     );
    $header['description'] = array(
      'data' => $this->t('Description'),
      'field' => 'description',
      'specifier' => 'description__value'
    );
    return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $cabinet_server_array = entity_load_multiple_by_properties('cabinet_server', array('ipm_id' => $entity->id()));
    $room_label = $cabinet_label = '';
    if(!empty($cabinet_server_array)) {
      $cabinet_server_entity = reset($cabinet_server_array);
      $cabinet_label = $cabinet_server_entity->get('cabinet_id')->entity->label();
    }
    if(!empty($entity->get('rid')->value)) {
      $room = entity_load('room', $entity->get('rid')->value);
      $room_label = $room->label();
    }
    $group_name = '';
    if(!empty($entity->get('group_id')->value)) {
      $groups = \Drupal::service('ip.ipservice')->loadIpGroup(array('gid' => $entity->get('group_id')->value));
      if(!empty($groups)) {
        $group_name = reset($groups)->name;
      }
    }
    $row['id'] = $entity->get('id')->value;
    $row['ip'] = $entity->get('ip')->value;
    $row['rid'] = $room_label;
    $row['group_id'] = $group_name;
    $row['cid'] = $cabinet_label;
    $row['port'] = $entity->get('port')->value;
    $row['uid'] = $entity->get('uid')->entity->getUsername();
    $row['status'] = ipmStatus()[$entity->get('status')->value];
    $row['equipment'] =  $entity->get('status_equipment')->value;
    $row['server_type'] =  ip_server_type()[$entity->get('server_type')->value];
    $row['description'] = $entity->get('description')->value;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['admin_ip_filter'] = $this->formBuilder->getForm('Drupal\ip\Form\ManagementIpFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No mamagement ip data.');
    return $build;
  }
}