<?php

/**
 * @file
 * Contains \Drupal\server\ServerListBuilder.
 */

namespace Drupal\server;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\part\Form\ServerForm;

/**
 * Defines a class to build a listing of server entities.
 *
 * @see \Drupal\server\Entity\Part
 */
class ServerListBuilder extends EntityListBuilder {

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
   *  The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *  The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  The entity query factory.
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
    $entity_query = $this->queryFactory->get('server');
    $entity_query->pager(PER_PAGE_COUNT);
    $header = $this->buildHeader();

    if (!empty($_SESSION['server_overview_filter'])) { //增加查询条件
      $search = $_SESSION['server_overview_filter']['keywords'];
      $status = $_SESSION['server_overview_filter']['status'];
      if (!empty($search)) {
        $entity_query->condition('server_code', $search, 'CONTAINS');
      }
      if (!empty($status)) {
        $entity_query->condition('status_equipment', $status);
      }
    }
    $entity_query->tableSort($header);
    $sids = $entity_query->execute();

    return $this->storage->loadMultiple($sids);
  }


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
  	$header = array(
  		'server_code' => $this->t('Code'),
  		'type' => array(
  			'data' => $this->t('Type'),
  			'field' => 'type',
  			'specifier' => 'type',
  		),
  		'name' => $this->t('Name|Description'),
  	  'status_equipment' => array(
  	  	'data' => $this->t('Status'),
  	  	'field' => 'status_equipment',
  	  	'specifier' => 'status_equipment',
  	  ),
  	  'uid' => array(
  	  	'data' => $this->t('User'),
  	  	'field' => 'uid',
  	  	'specifier' => 'uid',
  	  ),
  	  'created' => array(
  	  	'data' => $this->t('Created'),
  	  	'field' => 'created',
  	  	'specifier' => 'created',
  	  ),
  	  'changed' => array(
  	  	'data' => $this->t('Modified'),
  	  	'field' => 'changed',
  	  	'specifier' => 'changed',
  	  ),
      'rid' => array(
        'data' => $this->t('所属机房'),
        'field' => 'rid',
        'specifier' => 'rid',
      ),
  	);

		return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $username = array(
      '#theme' => 'username',
      '#account' => user_load($entity->get('uid')->getString()),
    );
    $row['server_code'] = $this->getLabel($entity);
    $row['type'] = $entity->get('type')->entity->label();
    $row['name'] = $entity->get('name')->value;
		$row['status_equipment'] = $entity->get('status_equipment')->value == 'on' ? '已上柜' : '待上柜';
		$row['uid'] = array('data' => $username);
		$row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
		$row['changed'] = date('Y-m-d H:i', $entity->get('changed')->value);
    $row['rid'] = ($entity->get('rid')->value > 0) ? entity_load('room', $entity->get('rid')->value)->label() : '';

    return $row + parent::buildRow($entity);
  }


  /**
   * Gets this list's default operations.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity the operations are for.
   *
   * @return array
   *   The array structure is identical to the return value of
   *   self::getOperations().
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      );
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form') && $entity->get('status_equipment')->value == 'off') {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->urlInfo('delete-form'),
      );
    }

    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['action_admin_filter_server_form'] = \Drupal::formBuilder()->getForm('Drupal\server\Form\ServerFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No server available.');
    return $build;
  }

}
