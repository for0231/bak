<?php
/**
 * @file 
 * Contains \Drupal\contract\ContractUserListBuilder.
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

class ContractUserListBuilder extends EntityListBuilder {
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
    $entity_query = $this->queryFactory->get('contract_user');
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
       'data' => '用户ID',
       'field' => 'id',
       'specifier' => 'id'
    );
    $header['name'] = array(
       'data' => '公司/用户名称',
       'field' => 'name',
       'specifier' => 'name'
    );
    $header['type'] = array(
       'data' => '用户类型',
       'field' => 'type',
       'specifier' => 'type'
    );
    $header['contact'] = array(
       'data' => '联系人',
       'field' => 'contact',
       'specifier' => 'contact'
    );
    $header['mobile'] = array(
       'data' => '联系电话',
       'field' => 'mobile',
       'specifier' => 'mobile'
    );
   	$header['creator'] = array(
       'data' => '建立人',
       'field' => 'uid',
       'specifier' => 'uid'
     );
   return $header + parent::buildHeader();
  }

 /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['name'] = $entity->label(); 
    $row['type'] = contractUserStatus()[$entity->get('type')->value];
    $row['contact'] = $entity->get('contact')->value; 
    $row['mobile'] = $entity->get('mobile')->value; 
    $row['uid'] = getEmployeeName($entity->get('uid')->entity->id());	

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

         
