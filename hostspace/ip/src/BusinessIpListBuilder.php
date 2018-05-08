<?php

/**
 * @file
 * Contains \Drupal\ip\BusinessIpListBuilder.
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
class BusinessIpListBuilder extends EntityListBuilder {

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
    $entity_query = $this->queryFactory->get('ipb');
    $entity_query->pager(PER_PAGE_COUNT);
    if(!empty($_SESSION['admin_ipb_filter'])) {
        if(!empty($_SESSION['admin_ipb_filter']['ip'])){
          $entity_query->condition('ip',$_SESSION['admin_ipb_filter']['ip'],'CONTAINS');
        }
        if(!empty($_SESSION['admin_ipb_filter']['puid'])){
          if($_SESSION['admin_ipb_filter']['puid'] == -1) {
            $entity_query->condition('puid',NULL,'is');
          }else {
            $entity_query->condition('puid',$_SESSION['admin_ipb_filter']['puid'],'=');
          }
        }
        if(!empty($_SESSION['admin_ipb_filter']['uid'])){
          $entity_query->condition('uid',$_SESSION['admin_ipb_filter']['uid'],'CONTAINS');
        }
        if(!empty($_SESSION['admin_ipb_filter']['ip_segment'])){
          $entity_query->condition('ip_segment',$_SESSION['admin_ipb_filter']['ip_segment'],'CONTAINS');
        }
        if(!empty($_SESSION['admin_ipb_filter']['description'])){
          $entity_query->condition('description__value',$_SESSION['admin_ipb_filter']['description'],'CONTAINS');
        }
        if(!empty($_SESSION['admin_ipb_filter']['status'])){
          $entity_query->condition('status',$_SESSION['admin_ipb_filter']['status'],'=');
        }
        if(!empty($_SESSION['admin_ipb_filter']['type'])){
          $entity_query->condition('type',$_SESSION['admin_ipb_filter']['type'],'=');
        }
        if(!empty($_SESSION['admin_ipb_filter']['classify'])){
          $entity_query->condition('classify',$_SESSION['admin_ipb_filter']['classify'],'=');
        }
        if(!empty($_SESSION['admin_ipb_filter']['room'])){
          $rids = explode('_', $_SESSION['admin_ipb_filter']['room']);
          if(isset($rids[1])) {
            $entity_query->condition('group_id', $rids[1], '=');
          } else {
            $entity_query->condition('rid', $rids[0], '=');
          }
        }
    }
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ipbid = $entity_query->execute();
    return $this->storage->loadMultiple($ipbid);
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
      'data' => $this->t('机房'),
      'field' => 'rid',
      'specifier' => 'rid'
    );
    $header['group_id'] = array(
      'data' => '所属分组'
    );
    $header['puid'] = array(
      'data' => $this->t('User'),
      'field' => 'puid',
      'specifier' => 'puid'
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
    $header['type'] = array(
      'data' => $this->t('Type'),
      'field' => 'type',
      'specifier' => 'type'
    );
    $header['classify'] = array(
      'data' => $this->t('IP classify'),
      'field' => 'classify',
      'specifier' => 'classify'
    );
    $header['ip_segment'] = array(
      'data' => $this->t('Ip segment'),
      'field' => 'ip_segment',
      'specifier' => 'ip_segment'
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
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->get('id')->value;
    $row['ip'] = $entity->get('ip')->value;
    $row['rid'] = $entity->get('rid')->value < 1 ? '公用' : entity_load('room', $entity->get('rid')->value)->label();
    $group_name = '';
    if(!empty($entity->get('group_id')->value)) {
      $groups = \Drupal::service('ip.ipservice')->loadIpGroup(array('gid' => $entity->get('group_id')->value));
      if(!empty($groups)) {
        $group_name = reset($groups)->name;
      }
    }
    $row['group_id'] = $group_name;
    //IP所属用户
    $client_obj = null;
    $client = '公共IP段';
    if($puser = $entity->get('puid')->entity) {
      $client_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client',$puser->id());
      if($client_obj) {
        $clients_name = $client_obj->client_name ? $client_obj->client_name : $puser->label();
        $client = $client_obj->corporate_name ? $client_obj->corporate_name : $clients_name;
      }
    }
    $row['puid'] = $client;
    //IP的创建者
    $user_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('uid')->entity->id());
    $creator = $user_obj ? $user_obj->employee_name : $entity->get('uid')->entity->getUsername();
    $row['uid'] = $creator;

    $row['status'] = ipbStatus()[$entity->get('status')->value]  ;
    $row['type'] =  $entity->get('type')->entity->label();
    $classify = $entity->get('classify')->entity;
    $row['classify'] =  $classify ? $classify->label() : '';
    $row['ip_segment'] = $entity->get('ip_segment')->value;
    $row['description'] = $entity->get('description')->value;
    $row['created'] = $this->dateFormatter->format($entity->get('created')->value, 'short');
    $row['changed'] = $this->dateFormatter->format($entity->get('changed')->value, 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['admin_ipb_filter'] = $this->formBuilder->getForm('Drupal\ip\Form\BusinessIpFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No business ip data.');
    return $build;
  }
}
