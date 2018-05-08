<?php
/**
 * @file
 * Contains \Drupal\order\HostclientListUntreated.
 */

namespace Drupal\order;

use Drupal\Core\Url;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;

class HostclientListUntreated {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  protected $hostclient_service;

  public function __construct(EntityStorageInterface $storage) {
    $this->storage = $storage;
    $this->hostclient_service = \Drupal::service('hostclient.serverservice');
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('hostclient')
    );
  }

  /**
   * 数据查询
   */
  public function load() {
    $hids =  $this->hostclient_service->getServerByCondition(array(
      'status' => array('value' => array(0,1,2), 'op' => 'IN')
    ));
    return $this->storage->loadMultiple($hids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['ipm'] = t('Management ip');
    $header['ipb'] = t('Business ip');
    $header['server_code'] = t('Server code');
    $header['product_name'] = t('Product name');
    $header['client'] = t('Client');
    $header['shelf'] = t('Shelf time');
    $header['expired'] = t('Expiration time');
    $header['trial'] = t('Trial');
    $header['status'] = t('Status');
    $header['operations'] = t('Operations');
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $ipm = $entity->getObject('ipm_id');
    $ipb_values = $entity->get('ipb_id')->getValue();
    if(empty($ipm)) {
      $row['ipm'] = '';
    } else {
      if(empty($ipb_values[0])) {
        $row['ipm'] = $ipm->label();
      } else {
        $row['ipm'] = SafeMarkup::format($ipm->label() . '<span style="color: red;">('.count($ipb_values).') </span>', array());
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
          $html .= '<li>' . $ipb->label() . '</li>';
        } else {
          $html .= '<li class="more-ipb" style="display:none">' . $ipb->label() .  '</li>';
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
    $row['shelf'] = '';
    $row['expired'] = '';
    if($entity->getSimpleValue('equipment_date')) {
      $row['shelf'] = format_date($entity->getSimpleValue('equipment_date'), 'custom' ,'Y-m-d H:i:s');
      $row['expired'] = format_date($entity->getSimpleValue('service_expired_date'), 'custom' ,'Y-m-d H:i:s');
    }
    $row['trial'] = $entity->getSimplevalue('trial') ? t('Yes') : t('No');
    $row['status'] = hostClientStatus()[$entity->getSimplevalue('status')];
    $row['operations']['data'] = array(
      '#type' => 'operations',
      '#links' => $this->getDefaultOperations($entity),
    );
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    $operations['detail'] = array(
      'title' => t('Detail'),
      'weight' => 1,
      'url' => $entity->urlInfo('detail-form')
    );
    if($entity->getSimpleValue('status') == 3) {
      if($entity->access('stop') && $entity->hasLinkTemplate('stop-form')) {
        $operations['stop'] = array(
          'title' => t('Stop'),
          'weight' => 10,
          'url' => $entity->urlInfo('stop-form'),
        );
      }
      if(!$entity->getSimplevalue('trial')) {
        if($entity->access('remove_ip') && $entity->hasLinkTemplate('remove_ip-form')) {
          $operations['remove_ip'] = array(
            'title' => t('Remove ip'),
            'weight' => 15,
            'url' => $entity->urlInfo('remove_ip-form'),
          );
        }
      }
    }
    return $operations;
  }


	/**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => '没有未处理数据'
    );
    $data = $this->load();
    foreach ($data as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = $row;
      }
    }
    $build['pager'] = array('#type' => 'pager');
    
    $build['table']['#attached']['library'] = array('order/drupal.hostclient-list-builder');
    return $build;
  }
}
