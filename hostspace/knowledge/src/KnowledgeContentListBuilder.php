<?php

/**
 * @file
 * Contains \Drupal\knowledge\KnowledgeContentListBuilder
 */

namespace Drupal\knowledge;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of part entities.
 *
 */
class KnowledgeContentListBuilder extends EntityListBuilder {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('form_builder')
    );
  }
  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, FormBuilderInterface $form_builder) {
    $this->entityTypeId = $entity_type->id();
    $this->storage = $storage;
    $this->entityType = $entity_type;
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $query = $this->storage->getQuery()
      ->sort('id');
    if(isset($_SESSION['knowledge_content_filter']['key']) && $_SESSION['knowledge_content_filter']['key'] != '') {
      $query->condition('title', $_SESSION['knowledge_content_filter']['key'], 'CONTAINS');
    }
    if(isset($_SESSION['knowledge_content_filter']['type_id']) && $_SESSION['knowledge_content_filter']['type_id'] != '') {
      $query->condition('type_id', $_SESSION['knowledge_content_filter']['type_id'], '=');
    }
    if(isset($_SESSION['knowledge_content_filter']['user']) && $_SESSION['knowledge_content_filter']['user'] != '') {
      $query->condition('uid', $_SESSION['knowledge_content_filter']['user'], '=');
    }

    if ($this->limit) {
      $query->pager($this->limit);
    }
    $entity_ids = $query->execute();
    return $this->storage->loadMultiple($entity_ids);

  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'ID',
      '类型',
      '标题',
      '时间',
      '创建者'
    );
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row[] = $entity->id();
    $row[] = $entity->get('type_id')->entity->label();
    $row[] = $entity->label();
    $row[] = format_date($entity->get('created')->value, 'custom', 'Y-m-d H:i:s');
    $row[] = $entity->get('uid')->entity->label();
    return $row + parent::buildRow($entity);
  }
  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['knowledge_content_filter'] = $this->formBuilder->getForm('\Drupal\knowledge\Form\KnowledgeContentFilterForm');
    $build += parent::render();
    return $build;
  }

}
