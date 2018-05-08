<?php

/**
 * @file
 * Contains \Drupal\part\PartListBuilder.
 */

namespace Drupal\part;

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
 * Defines a class to build a listing of part entities.
 *
 * @see \Drupal\part\Entity\Part
 */
class PartListBuilder extends EntityListBuilder {

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
    $entity_query = $this->queryFactory->get('part');
    $entity_query->pager(PER_PAGE_COUNT);

    if (!empty($_SESSION['admin_part_filter'])) { //增加查询条件
      $search = $_SESSION['admin_part_filter']['search'];
      if(!empty($search)) {
        $group = $entity_query->orConditionGroup()
                     ->condition('brand', $search, 'CONTAINS')
                     ->condition('model', $search, 'CONTAINS')
                     ->condition('standard', $search, 'CONTAINS');
        $entity_query->condition($group);
      }

      $type = $_SESSION['admin_part_filter']['type'];
      if(!empty($type)) {
        $entity_query->condition('type', $type, '=');
      }
    }
    $header = $this->buildHeader();
    $entity_query->tableSort($header);
    $pids = $entity_query->execute();
    return $this->storage->loadMultiple($pids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = array(
       'data' => $this->t('ID'),
       'field' => 'pid',
       'specifier' => 'pid'
    );
    $header['part_type'] = array(
       'data' => $this->t('Part type'),
       'field' => 'type',
       'specifier' => 'type'
    );
    $header['brand'] = array(
       'data' => $this->t('Brand'),
       'field' => 'brand',
       'specifier' => 'brand'
    );
    $header['model'] = array(
       'data' => $this->t('Model'),
       'field' => 'model',
       'specifier' => 'model'
    );
    $header['label'] = array(
       'data' => $this->t('Standard'),
       'field' => 'standard',
       'specifier' => 'standard'
    );
    $header['stock_used_rent'] = $this->t('Stock used(Rent)');
    $header['stock_used_free'] = $this->t('Stock used(Free)');
    $header['stock_balances'] = $this->t('Stock balances');
    $header['stock_fault'] = $this->t('Stock falut');
    $header['stock_total'] = $this->t('Stock total');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->get('pid')->value;
    $type = $entity->get('type')->value;
    $row['part_type'] = part_entity_type_name($type);
    $row['brand'] = $entity->get('brand')->value;
    $row['model'] = $entity->get('model')->value;
    $row['label'] = $this->getLabel($entity);
    $total = $entity->get('stock')->value;
    $used_rent = $entity->get('stock_used_rent')->value;
    $used_free = $entity->get('stock_used_free')->value;
    $fault = $entity->get('stock_fault')->value;
    $row['stock_used_rent'] = $used_rent;
    $row['stock_used_free'] = $used_free;
    $row['stock_balances'] = $total- $used_rent - $used_free - $fault;
    $row['stock_fault'] = $fault;
    $row['stock_total'] = $total;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['part_filter_form'] = $this->formBuilder->getForm('Drupal\part\Form\PartFilterForm');
    $build += parent::render();
    $build['table']['#empty'] = $this->t('No parts available.');
    return $build;
  }

}
