<?php
/**
 * @file
 * Contains Drupal\hostlog\OperationLogListBuild.
 */

namespace Drupal\hostlog;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OperationLogListBuild {

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  public function __construct(FormBuilderInterface $form_builder) {
    $this->formBuilder = $form_builder;
  }
  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
			$container->get('form_builder')
    );
  }

  /**
   * 构建表头
   */
  private function buildFormHeader() {
    return array('ID', '操作者', '信息', '时间', '');
  }

  /**
   * 构建内容行
   */
  private function buildContentRow() {
    // 查询到所有的日志内容
    $condition = array();
    if(!empty($_SESSION['operation_log_filter']['search'])) {
      $condition['message'] = $_SESSION['operation_log_filter']['search'];
    }
    $logs = \Drupal::service('operation.log')->getAllLogData($condition);
    if(!empty($logs)) {
      foreach ($logs as $log) {
        $user = entity_load('user', $log->uid);
        $row_arr[$log->lid] = array(
          'ID' => $log->lid,
          'Operator' => $user->label(),
          'Content' => $log->message,
          'Date' => format_date($log->timestamp, 'custom', 'Y-m-d H:i:s'),
        );
        $row_arr[$log->lid]['operations']['data'] = array(
          '#type' => 'operations',
          '#links' => array(
            'view' => array(
              'title' => 'View',
              'url' => new Url('host.log.view', array('log_id' => $log->lid))
            ),
          )
       );
      }
    }
    return isset($row_arr) ? $row_arr : array();
  }

  /*
   * 渲染表单
   */
  public function render() {
    $build['filter'] = $this->formBuilder->getForm('\Drupal\hostlog\Form\OperationLogFilterForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildFormHeader(),
      '#rows' => $this->buildContentRow(),
      '#empty' => t('There have no log data to show.')
    );
    $build['list_pager'] = array('#type' => 'pager');
    return $build;

  }

}

