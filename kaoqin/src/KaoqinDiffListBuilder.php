<?php

namespace Drupal\kaoqin;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Construct ListBuilder.
 */
class KaoqinDiffListBuilder {
  /**
   * @description 自定义load.
   */
  protected function load() {
    $storage = \Drupal::entityManager()->getStorage('kaoqin');
    $entity_query = $storage->getQuery();
    $entity_query->condition('id', 0, '<>');
    $entity_query->sort('created', 'DESC');

    $entity_query->pager(20);
    $header = $this->buildHeader();
    $entity_query->tableSort($header);

    $ids = $entity_query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   *
   */
  public function buildHeader() {
    $header = [
      'id' => [
        'data' => 'ID',
        'field' => 'id',
        'specifier' => 'id',
      ],

      'code' => [
        'data' => '人员编号',
        'field' => 'code',
        'specifier' => 'code',
      ],
      'emname' => [
        'data' => '姓名',
        'field' => 'emname',
        'specifier' => 'emname',
      ],
      'logdate' => [
        'data' => '考勤日期',
        'field' => 'logdate',
        'specifier' => 'logdate',
      ],
      'weekday' => [
        'data' => '星期',
        'field' => 'weekday',
        'specifier' => 'weekday',
      ],
      'banci' => [
        'data' => '班次',
        'field' => 'banci',
        'specifier' => 'banci',
      ],
      'morningsign' => [
        'data' => '上班',
        'field' => 'morningsign',
        'specifier' => 'morningsign',
      ],
      'afternoonsign' => [
        'data' => '下班',
        'field' => 'afternoonsign',
        'specifier' => 'afternoonsign',
      ],
      'uid' => [
        'data' => '创建人',
        'field' => 'uid',
        'specifier' => 'uid',
      ],
      'created' => [
        'data' => '创建时间',
        'field' => 'created',
        'specifier' => 'created',
      ],
      'diff' => [
        'data' => '考勤状态',
      ],
    ];

    return $header;
  }

  /**
   *
   */
  protected function buildRow($entity) {

    $row['id'] = $entity->id();
    $row['code'] = $entity->get('code')->value;
    $row['emname'] = $entity->get('emname')->value;
    $row['logdate'] = date('Y-m-d', $entity->get('logdate')->value);
    $row['weekday'] = $entity->get('weekday')->value;
    $row['banci'] = $entity->get('banci')->value;
    $row['morningsign'] = date('H:i', $entity->get('morningsign')->value);
    $row['afternoonsign'] = date('H:i', $entity->get('afternoonsign')->value);
    $row['uid'] = $entity->get('uid')->entity->getUsername();
    $row['created'] = date('Y-m-d H:i', $entity->get('created')->value);
    $row['diff'] = 'fd';
    return $row;
  }


  /**
   * @description 根据考勤设置进行判定.
   * 考勤异常的，行内添加红线.
   */
  public function getDiffKaoqinData() {
    $config = \Drupal::configFactory()->getEditable('kaoqin.settings');
    error_log(print_r($config->get('simple_kaoqin_type'), 1));
  }

  /**
   * @descripiton 添加操作按钮.
   */
  private function getOperations($entity) {
    $operations = [];


    $this->getDiffKaoqinData();
    return $operations;
  }

  /**
   *
   */
  public function build() {
    $datas = $this->load();
    $items = $datas;

    $build['list'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => [],
      '#empty' => '无数据',
    ];

    foreach ($items as $item) {
      if ($row = $this->buildRow($item)) {
        $build['list']['#rows'][$item->id()] = $row;
      }
    }
    $build['pager'] = ['#type' => 'pager'];
    $build['#markup'] = '考勤差异列表: 添加月份搜索功能和数据导出功能，并显示各个用户考勤记录是否按时上下班，并用红字标记出差异的考勤记录。';
    return $build;
  }

}
