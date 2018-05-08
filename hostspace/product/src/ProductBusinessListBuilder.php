<?php

/**
 * @file 
 * Contains \Drupal\idc\ProductBusinessListBuilder
 */

namespace Drupal\product;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of part entities.
 *
 * @see \Drupal\product\Entity\ProductBusiness
 */
class ProductBusinessListBuilder extends EntityListBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['catalog'] = $this->t('catalog name');
    $header['name'] = $this->t('Business name');
    $header['content'] = $this->t('Business content');
    $header['use'] = $this->t('Use the number');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['catalog'] = $entity->getObject('catalog')->label();
    $row['name'] = $entity->getSimpleValue('name');
    $lib = $entity->getSimpleValue('resource_lib');
    if($lib == 'none') {
      $row['content'] = '';
    } else {
      $routing = $lib == 'create' ? 'entity.product_business.content_form' : 'entity.product_business.entity_content_form';  
      $link = \Drupal::url($routing, array('product_business' => $entity->id()));
      $row['content'] = array(
        'class' => 'child-table',
        'data' => array(
          '#type' => 'table',
          '#rows' =>  $this->buildContentRow($entity),
          '#empty' => $this->t('There is no business content yet. <a href="@link">Add business content</a>', array(
            '@link' => $link
          )
        ))
      );
    }
    $row['use'] = '';
    return $row + parent::buildRow($entity);
  }
 
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    $lib = $entity->getSimpleValue('resource_lib');
    if($lib == 'none') {
       return parent::getDefaultOperations($entity);  
    }
    if($lib == 'create') {
      if ($entity->access('update') && $entity->hasLinkTemplate('content-form')) {
        $operations['content_from'] = array(
          'title' => $this->t('Add business content'),
          'weight' => 0,
          'url' => $entity->urlInfo('content-form')
        );
      }
    } else {
      if ($entity->access('update') && $entity->hasLinkTemplate('entity-content-form')) {
        $operations['entity_content_from'] = array(
          'title' => $this->t('Add business content'),
          'weight' => 0,
          'url' => $entity->urlInfo('entity-content-form')
        );
      }
    }
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['edit'] = array(
        'title' => $this->t('Edit'),
        'weight' => 10,
        'url' => $entity->urlInfo('edit-form'),
      );
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form') && !$entity->getSimpleValue('locked')) {
      $operations['delete'] = array(
        'title' => $this->t('Delete'),
        'weight' => 100,
        'url' => $entity->urlInfo('delete-form'),
      );
    }
    return $operations;
  }

  /**
   * 业务内容列数据
   */
  public function buildContentRow(EntityInterface $entity) {
    $rows = array();
    $lib = $entity->getSimpleValue('resource_lib');
    $child_entity = array();
    $entity_type = $lib == 'create' ? 'product_business_content' : 'product_business_entity_content';
    $child_entity = entity_load_multiple_by_properties($entity_type, array('businessId' => $entity->id())); 
    foreach($child_entity as $child) {
      $name = '';
      if($lib == 'create') {
        $name = $child->getSimpleValue('name');
      } else {
        $part = entity_load($child->getSimpleValue('entity_type'), $child->label());
        $name = $part->label();
      }
      $rows[$child->id()] = array(
        'content_name' => $name,
        'content_use' => array(
          'style' => 'width:50px',
          'data' => $child->getSimpleValue('use_number')
        ),
        'content_op' => array(
          'style' => 'width:80px',
          'data' => array(
            '#type' => 'operations',
            '#links' => $this->getContentOperations($child)
          )
        )
      );
    }
    return $rows;
  }
  
  /**
   * 业务内容行的操作
   */
  public function getContentOperations(EntityInterface $entity) {
    $operations = array();
    if ($entity->access('update') && $entity->hasLinkTemplate('edit-form')) {
      $operations['content_edit'] = array(
        'title' => $this->t('Edit content'),
        'weight' => 1,
        'url' => $entity->urlInfo('edit-form')
      );
    }
    if ($entity->access('delete') && $entity->hasLinkTemplate('delete-form')) {
      $operations['content_delete'] = array(
        'title' => $this->t('Delete content'),
        'weight' => 2,
        'url' => $entity->urlInfo('delete-form')
      );
    }
    
    return $operations; 
  }
}
