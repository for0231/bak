<?php

namespace Drupal\kaoqin;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Defines a class to build a listing of kaoqin entities.
 *
 * @see \Drupal\kaoqin\Entity\Kaoqin
 */
class KaoqinUponListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $entity_query = $this->storage->getQuery();
    $entity_query->condition('id', 0, '<>');
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
    $header = [
      'id' => [
        'data' => $this->t('ID'),
        'field' => 'id',
        'specifier' => 'id',
      ],
      'user' => [
        'data' => $this->t('姓名'),
        'field' => 'user',
        'specifier' => 'user',
      ],
      'depart' => [
        'data' => $this->t('部门'),
        'field' => 'depart',
        'specifier' => 'depart',
      ],
      'type' => [
        'data' => $this->t('班次'),
        'field' => 'type',
        'specifier' => 'type',
      ],
      'datetime' => [
        'data' => $this->t('日期'),
      ],
      'morningsign' => [
        'data' => $this->t('上班'),
        'field' => 'morningsign',
        'specifier' => 'morningsign',
      ],
      'afternoonsign' => [
        'data' => $this->t('下班'),
        'field' => 'afternoonsign',
        'specifier' => 'afternoonsign',
      ],
      'uid' => [
        'data' => $this->t('创建人'),
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'created' => [
        'data' => $this->t('创建时间'),
        'field' => 'created',
        'specifier' => 'created',
      ],
    ];

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row['id'] = $entity->id();
    $row['user'] = $entity->get('user')->entity->get('realname')->value;
    $row['depart'] = $entity->get('depart')->entity->label();
    $row['type'] = $entity->get('type')->value == 1 ? '白班' : '夜班';
    $row['datetime'] = date('Y-m-d', $entity->get('datetime')->value);
    $row['morningsign'] = date('H:i', $entity->get('morningsign')->value);
    $row['afternoonsign'] = date('H:i', $entity->get('afternoonsign')->value);
    $row['uid'] = $entity->get('uid')->entity->getUsername();
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    $operations['delete'] = [
      'title' => $this->t('delete'),
      'weight' => 10,
      'url' => $entity->urlInfo('delete-form'),
    ];
    unset($operations['edit']);
    return $operations;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('没有可用的数据.');

    return $build;
  }

}
