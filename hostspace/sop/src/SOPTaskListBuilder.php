<?php

/**
 * @file
 * Contains \Drupal\sop\SOPTaskListBuilder.
 */

namespace Drupal\sop;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Defines a class to build a listing of business ip entities.
 *
 * @see \Drupal\ip\Entity\IPM.php
 */
class SOPTaskListBuilder extends EntityListBuilder {

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
   * Constructs a new PartListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, QueryFactory $query_factory, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $storage);
    $this->queryFactory = $query_factory;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('entity.query'),
      $container->get('form_builder')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->queryFactory->get('sop');
    $entity_query->pager(PER_PAGE_COUNT);
    $entity_query->sort('created', 'DESC');
    $header = $this->buildHeader();

    // 搜索条件为0时，无法区别新工单.
    if (!empty($_SESSION['sop_overview_filter'])) {
      // 增加查询条件.
      $search = $_SESSION['sop_overview_filter']['task_status'];
      if ($search != -1 && !empty($search)) {
        $entity_query->condition('sop_status', $search);
      }
      if (empty($search)) {
        $entity_query->condition('sop_status', 4, '<>');
      }
      $entity_query->condition('hid', 0, '<>');
    }

    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
      'data' => $this->t('ID'),
      'field' => 'id',
      'specifier' => 'id',
    );
    $header['sop_status'] = array(
      'data' => $this->t('状态'),
      'field' => 'sop_status',
      'specifier' => 'sop_status',
    );
    $header['sop_op_type'] = array(
      'data' => $this->t('操作'),
      'field' => 'sop_op_type',
      'specifier' => 'sop_op_type',
    );
    $header['sop_type'] = array(
      'data' => $this->t('类型'),
      'field' => 'sop_type',
      'specifier' => 'sop_type',
    );
    $header['mips'] = array(
      'data' => $this->t('管理IP'),
    );
    $header['code'] = array(
      'data' => $this->t('编码'),
    );
    $header['client_uid'] = array(
      'data' => $this->t('客户'),
      'specifier' => 'client_uid',
    );
    $header['solving_uid'] = array(
      'data' => $this->t('处理人'),
      'specifier' => 'solving_uid',
    );
    $header['presolving_uid'] = array(
      'data' => $this->t('上次处理人'),
      'specifier' => 'presolving_uid',
    );
    $header['difftime_bus'] = array(
      'data' => $this->t('业务耗时'),
    );
    $header['difftime_tech'] = array(
      'data' => $this->t('技术耗时'),
    );
    unset($header['operations']);
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $sop_task_statuses = sop_task_status();
    $sop_op_types = sop_task_op_status();
    $sop_types = sop_type_levels();
    if ($entity->get('module')->value != 'sop_task_room') {
      $hostclient = $entity->get('hid')->entity;
      $host_obj = \Drupal::service('hostclient.serverservice')->getManageIP4SopByHostclientId($hostclient->id());
      $entity_ipm = !empty($host_obj->ipm_id) ? entity_load('ipm', $host_obj->ipm_id) : '';
      $client_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client', $hostclient->get('client_uid')->target_id);
      // @todo 这里employee暂时改为client，方便测试，最终会还原成employee
      $hostspace_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('solving_uid')->target_id);
      // @todo 这里employee暂时改为client，方便测试，最终会还原成employee
      $hostspace_obj_pre = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('presolving_uid')->target_id);

      $handle_obj = \Drupal::service('hostclient.handleservice')->loadHandleInfo4sop($hostclient->id());
      // @todo 这里的时间需要再处理
      $diff_time_bus = timediff($handle_obj->busi_complete_data, $handle_obj->busi_accept_data);
      $diff_time_tech = timediff($handle_obj->tech_complete_data, $handle_obj->tech_accept_data);
    }
    else {
      $sop_module_type_id = $entity->get('sid')->value;
      $sop_room_entity = entity_load('sop_task_room', $sop_module_type_id);
    }

    $row['id'] = $entity->get('id')->value;
    $row['sop_status'] = $sop_task_statuses[$entity->get('sop_status')->value];
    $row['sop_op_type'] = @$sop_op_types[$entity->get('sop_op_type')->value];
    $row['sop_type'] = $sop_types[$entity->get('sop_type')->value];
    // sop_task_iband.
    if ($entity->get('module')->value != 'sop_task_room') {
      if ($entity_ipm instanceof \Drupal\ip\Entity\IPM) {
        $mip_label = $entity_ipm->label();
      }
      else {
        $mip_label = '-';
      }
      $server_entity = $entity->get('hid')->entity->get('server_id')->entity;
      if ($server_entity instanceof \Drupal\server\Entity\Server) {
        $code_label = $server_entity->label();
      }
      else {
        $code_label = '-';
      }
      $row['mips'] = $mip_label;
      $row['code'] = $code_label;
      $row['client_uid'] = getSopClientName($client_obj);
      // $solving_name;.
      $row['solving_uid'] = getSopClientName($hostspace_obj);
      // $presolving_name;.
      $row['presolving_uid'] = getSopClientName($hostspace_obj_pre);
      $row['difftime_bus'] = !empty($diff_time_bus['allmin']) ? $diff_time_bus['allmin'] . '分钟' : '-';
      $row['difftime_tech'] = !empty($diff_time_tech['allmin']) ? $diff_time_tech['allmin'] . '分钟' : '-';
    }
    else {
      // sop_task_room.
      // @todo 这里employee暂时改为client，方便测试，最终会还原成employee
      $solving_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('solving_uid')->target_id);
      $presolving_obj = \Drupal::service('member.memberservice')->queryDataFromDB('employee', $entity->get('presolving_uid')->target_id);
      $row['mips'] = $sop_room_entity->get('mip')->entity->label();
      $row['code'] = $entity->get('hid')->target_id != 0 ? $entity->get('hid')->entity->get('server_id')->entity->label() : '-';
      // 机房事务是由后台人员提出的，因此取消客户名称.
      $row['client_uid'] = '-';
      $row['solving_uid'] = getSopClientName($solving_obj);
      $row['presolving_uid'] = getSopClientName($presolving_obj);
      $row['difftime_bus'] = '-';
      $row['difftime_tech'] = '-';
    }
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    // $operations = parent::getOperations($entity);
    $operations['sop_task_detail'] = array(
      'title' => t('查看'),
      'url' => new Url('admin.sop_task.detail', array('sop' => $entity->id())),
      'weight' => -10,
    );
    uasort($operations, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $operations;
  }
  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['action_admin_filter_sop_form'] = \Drupal::formBuilder()->getForm('Drupal\sop\Form\SopFilterForm');
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => array(),
      '#empty' => $this->t('There is no @label yet.', array('@label' => $this->entityType->getLabel())),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
    );
    foreach ($this->load() as $entity) {
      if (empty($entity->get('hid')->entity) || $entity->get('hid')->target_id == 0) {
        continue;
      }
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = array(
        '#type' => 'pager',
      );
    }
    $build['description_markup'] = array('#markup' => SafeMarkup::checkPlain("|-中国"));
    $build['admin_sop_tech_night_setting_form'] = \Drupal::formBuilder()->getForm('Drupal\sop\Form\SopTaskNightSettingForm');
    return $build;
  }

}
