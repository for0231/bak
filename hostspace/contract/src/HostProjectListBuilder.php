<?php

/**
 * @file 
 * Contains \Drupal\contract\HostProjectListBuilder.
 */

namespace Drupal\contract;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;

class HostProjectListBuilder extends EntityListBuilder {
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
    $entity_query = $this->queryFactory->get('host_project');
    $entity_query->pager(20);	
		$header = $this->buildHeader();
    $entity_query->tableSort($header);
    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }
 /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
       'data' => '项目ID',
       'field' => 'id',
       'specifier' => 'id'
    );
    $header['name'] = array(
       'data' => '项目名称',
       'field' => 'name',
       'specifier' => 'name'
    );
    $header['code'] = array(
       'data' => '项目编号',
       'field' => 'code',
       'specifier' => 'code'
    );
    $header['client'] = array(
       'data' => '项目客户',
       'field' => 'client',
       'specifier' => 'client'
    );
    $header['type'] = array(
       'data' => '项目类型',
       'field' => 'type',
       'specifier' => 'type'
    );
   	$header['creator'] = array(
       'data' => '建立人',
       'field' => 'uid',
       'specifier' => 'uid'
     );
		$header['created'] = array(
       'data' => '建立时间',
       'field' => 'created',
       'specifier' => 'created'
    );
		$header['status'] = array(
       'data' => '项目状态',
       'field' => 'status',
       'specifier' => 'status'
    );

   return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = $entity->getProjectproperty('name'); 
    $row['code'] = $entity->label();
    $row['client'] = $entity->get('client')->entity->label(); 
    $row['type'] = ip_server_type()[$entity->getProjectproperty('type')]; 
    $row['uid'] = getEmployeeName($entity->get('uid')->entity->id());
		$row['created'] = $this->dateFormatter->format($entity->getProjectproperty('created'), 'short');
    $status = $entity->getProjectproperty('status') ? $entity->getProjectproperty('status') : 1;
    $row['status'] = projectStatus()[$status];

		return $row + parent::buildRow($entity);
  }

	/**
   * {@inheritdoc}
   */
  public function render() {
        
    $build = parent::render();
    $build['table']['#empty'] = $this->t('No data to show.');
    return $build;
  }

}

         
