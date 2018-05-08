<?php
/**
 * @file
 * Contains \Drupal\order\UntreatedListBuilder.
 */

namespace Drupal\order;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\SafeMarkup;

class UntreatedListBuilder {
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
   * 加载数据
   */
  private function load($dept) {
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    return $hostclient_service->loadHandleInfoUntreated($dept);
  }

  /**
   * 创建表头
   */
  private function buildHeader() {
    $header['ipm'] = t('Management ip');
    $header['ipb'] = t('Business ip');
    $header['server_code'] = t('Server code');
    $header['product_name'] = t('Product name');
    $header['client'] = t('Client');
    $header['type'] = t('Type');
    $header['shelf'] = t('Shelf time');
    $header['expired'] = t('Expiration time');
    $header['status'] = t('Status');
    $header['operations'] = t('Operations');
    return $header;
  }

  /**
   * 构建一行数据
   */
  private function buildRow($handle_info, $dept) {
    $entity = entity_load('hostclient', $handle_info->hostclient_id);
    if(empty($entity)) {
      return null;
    }
    $ipm = $entity->getObject('ipm_id');
    $ipb_values = $entity->get('ipb_id')->getValue();
    if(empty($ipm)) {
      $row['ipm'] = '';
    } else {
      if(empty($ipb_values[0])) {
        $row['ipm'] = $ipm->label();
      } else {
        $row['ipm'] = SafeMarkup::format(array($ipm->label() . '<span style="color: red;">('. count($ipb_values) .')</span>'), array());
      }
    }
    if(empty($ipb_values[0])) {
      $row['ipb'] = '';
    } else {
      $html = '<ul>';
      $i = 0;
      foreach($ipb_values as $value) {
        $ipb = entity_load('ipb', $value['target_id']);
        if($i < 3) {
          $html .= '<li>' . $ipb->label() . '--'. $ipb->get('type')->entity->label() . '</li>';
        } else {
          $html .= '<li class="more-ipb" style="display:none">' . $ipb->label() . '--' . $ipb->get('type')->entity->label() . '</li>';
        }
        $i++;
      }
      $html .= '</ul>';
      if($i<=3) {
        $row['ipb'] = SafeMarkup::format($html, array());
      } else {
        $row['ipb'] = array(
          'class' => 'show-more',
          'js-open' => 'close',
          'style' => 'cursor: pointer;',
          'title' => t('Double click the show all IP'),
          'data' => SafeMarkup::format($html, array())
        );
      }
    }
    $server = $entity->getObject('server_id');
    if(empty($server)) {
      $row['server_code'] = '';
    } else {
      $row['server_code'] = $server->label();
    }
    $row['product_name'] = $entity->getObject('product_id')->label();
    $row['client'] = $entity->getObject('client_uid')->label();
    $row['type'] = order_product_action()[$handle_info->handle_action];
    $row['shelf'] = '';
    $row['expired'] = '';
    if($entity->getSimpleValue('equipment_date')) {
      $row['shelf'] = format_date($entity->getSimpleValue('equipment_date'), 'custom' ,'Y-m-d H:i:s');
      $row['expired'] = format_date($entity->getSimpleValue('service_expired_date'), 'custom' ,'Y-m-d H:i:s');
    }
    $row['status'] = hostClientStatus()[$entity->getSimplevalue('status')];
    $row['operations']['data'] = array('#type' => 'operations', '#links' => $this->getOperations($handle_info, $dept));
    return $row;
  }

  /**
   * 获取操作
   */
  private function getOperations($handle_info, $dept) {
    $operations = array();
    if($dept == 'business') {
      $operations['business_dept'] = array(
        'title' =>t('Handle'),
        'url' => new Url('admin.hostclient.business.dept', array('handle_id' => $handle_info->hid))
      );
    } else {
      $operations['technology_dept'] = array(
        'title' => t('Handle'),
        'url' => new Url('admin.hostclient.technology.dept', array('handle_id' => $handle_info->hid))
      );
    }

    return $operations;
  }

  /**
   * 显示列表
   */
  public function render($dept){
    $data = $this->load($dept);
    $build['hostclient'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $handle_info) {
      if($row = $this->buildRow($handle_info, $dept)) {
        $build['hostclient']['#rows'][$handle_info->hid] = $row;
      }
    }
    $build['hostclient']['#attached']['library'] = array('order/drupal.hostclient-list-builder');
    return $build;
  }
}
