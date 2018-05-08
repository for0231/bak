<?php

/**
 * @file
 * Contains \Drupal\ip\SwitchListBuilder.
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
 * Defines a class to build a listing of switch ip entities.
 *
 * @see \Drupal\ip\Entity\IPS.php
 */
class SwitchListBuilder extends EntityListBuilder {

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
    $entity_query = $this->queryFactory->get('ips');
    $entity_query->pager(20);
    if(!empty($_SESSION['admin_switch_filter'])) {
      if(!empty($_SESSION['admin_switch_filter']['ip'])){
        $entity_query->condition('ip',$_SESSION['admin_switch_filter']['ip'],'CONTAINS');
      }
      if(!empty($_SESSION['admin_switch_filter']['port'])){
        $entity_query->condition('port',$_SESSION['admin_switch_filter']['port'],'CONTAINS');
      }
      if(!empty($_SESSION['admin_switch_filter']['description'])){
        $entity_query->condition('description__value',$_SESSION['admin_switch_filter']['description'],'CONTAINS');
      }
      if(!empty($_SESSION['admin_switch_filter']['uid'])){
        $entity_query->condition('uid',$_SESSION['admin_switch_filter']['uid'],'CONTAINS');
      }
      if(!empty($_SESSION['admin_switch_filter']['status_equipment'])){
        $entity_query->condition('status_equipment',$_SESSION['admin_switch_filter']['status_equipment'],'CONTAINS');
      }
    }
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ipsid = $entity_query->execute();
    return $this->storage->loadMultiple($ipsid);
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
    $header['port'] = array(
       'data' => $this->t('Port'),
       'field' => 'port',
       'specifier' => 'port'
    );
    $header['uid'] = array(
       'data' => $this->t('Creator'),
       'field' => 'uid',
       'specifier' => 'uid'
    );
    $header['status_equipment'] = array(
       'data' => '上架状态',
       'field' => 'status_equipment',
       'specifier' => 'status_equipment'
    );
     $header['description'] = array(
       'data' => $this->t('Description'),
       'field' => 'description',
       'specifier' => 'description__value'
     );
    $header['created'] = array(
       'data' => $this->t('Created'),
       'field' => 'created',
       'specifier' => 'created'
    );
    $header['changed'] = array(
       'data' => $this->t('Changed'),
       'field' => 'changed',
       'specifier' => 'changed'
    );
    return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   * @todo 交换机IP待增加机房属性。
   *       交换机IP存在两种属性，一个是公网交换机，一个是内网交换机。
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->get('id')->value;
    $row['ip'] = $entity->get('ip')->value;
    $row['port'] = $entity->get('port')->value;
    $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('uid')->entity->id());
    $creator = $user_obj->employee_name ;
    $row['uid'] = $creator ? $creator : $entity->get('uid')->entity->getUsername();
    $row['status_equipment'] = $entity->get('status_equipment')->value;
    $row['description'] = $entity->get('description')->value;
    $row['created'] = $this->dateFormatter->format($entity->get('created')->value, 'short');
    $row['changed'] = $this->dateFormatter->format($entity->get('changed')->value, 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['admin_switch_filter'] = $this->formBuilder->getForm('Drupal\ip\Form\SwitchFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No switch data.');
    return $build;
  }
}
