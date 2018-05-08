<?php
/**
 * @file 
 * Contains \Drupal\member\Form\RechargeRecordForm.
 */

namespace Drupal\member\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RechargeRecordForm {
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

  private function header() {
    $header['id'] = array(
      'data' => 'ID',
    );
    $header['user'] = array(
      'data' => '用户',
    );

    $header['amount'] = array(
      'data' => '金额(:RMB)',
      'field' => 'amount',
      'specifier' => 'amount'
    );
    $header['date']= array(
      'data' => '时间',
      'field' => 'created',
      'specifier' => 'created'
    );
    $header['order']= array(
      'data' => '单号',
    );

    return $header;
  }

  private function load($type) {
    // 如果hc_alipay没有启用，下面将会报错
    // 而且现在的代码也看不出是已启用还是没有充值数据
    if (\Drupal::moduleHandler()->moduleExists('hc_alipay'))
      $records = \Drupal::service('member.memberservice')->getAllCashRecord($type, $this->header());
    else
      $records = new \stdClass;
    $rows = array();
    $i=0;
    foreach($records as $record) {
      $i ++;
      $rows[$record->id] = array(
        'id' => $i,
        'user' => $record->client_uid,
        'amount' => $record->amount,
        'date' => format_date($record->created, 'custom', 'Y-m-d H:i:s'),
        'order' => $record->order_code
      );
    }
    return $rows;
  }

  /**
   * 渲染表单
   */
  public function render($type) {
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->header($type),
      '#rows' => $this->load($type),
      '#empty' => t('No data available.'),
    );
    $build['pager']['#type'] = 'pager';
    return $build;
  }
}
