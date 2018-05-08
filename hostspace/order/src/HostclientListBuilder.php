<?php

/**
 * @file
 * Contains \Drupal\order\HostclientListBuilder.
 */

namespace Drupal\order;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Component\Utility\SafeMarkup;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of switch ip entities.
 *
 * @see \Drupal\order\Entity\order
 */
class HostclientListBuilder extends EntityListBuilder {

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
   * Constructs a new BusinessIpListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *  The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *  The entity storage class.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *  The entity query factory.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage,  QueryFactory $query_factory, FormBuilderInterface $form_builder) {
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
    // 查询符合条件的服务器数据的编号(hid)数组
    $hids = array();
    if(empty($_SESSION['hostclient_filter'])) {
      $entity_query = $this->queryFactory->get('hostclient');
      $entity_query->sort('hid', 'DESC');
      $entity_query->pager(PER_PAGE_COUNT);
  	  $header = $this->buildHeader();
      $entity_query->tableSort($header);
      $hids = $entity_query->execute();

    } else {
      $hids = $this->FiterHostclient();
    }
    return $this->storage->loadMultiple($hids);
  }

  /**
   * 表单筛选
   *
   * @return $hid array
   *    符合条件的服务器数据编号数组
   */
  private function FiterHostclient() {
    $condition = array();   //服务器筛选条件
    $ip_condition = array();  // IP筛选条件

    if(!empty($_SESSION['hostclient_filter']['ipb'])) {
      $ip_condition['ipb'] = $_SESSION['hostclient_filter']['ipb'];
    }
    if(!empty($_SESSION['hostclient_filter']['ipm'])) {
      $ip_condition['ipm'] = $_SESSION['hostclient_filter']['ipm'];
    }
    if($_SESSION['hostclient_filter']['status'] != -1) {
      $condition['status'] = $_SESSION['hostclient_filter']['status'];
    }
    if(!empty($_SESSION['hostclient_filter']['client_uid'])) {
      $condition['client_uid'] = $_SESSION['hostclient_filter']['client_uid'];
    }
    if(!empty($_SESSION['hostclient_filter']['corporate_name'])) {
      $condition['client_uid'] = $_SESSION['hostclient_filter']['corporate_name'];
    }

    return \Drupal::service('hostclient.serverservice')->getServerByCondition($condition, $ip_condition);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['ipm'] = $this->t('Management ip');
    $header['ipb'] = $this->t('Business ip');
    $header['server_code'] = $this->t('Server code');
    $header['product_name'] = $this->t('Product name');
    $header['client'] = $this->t('Client');
    $header['shelf'] = $this->t('Shelf time');
    $header['expired'] = $this->t('Expiration time');
    $header['trial'] = $this->t('Trial');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
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
          'title' => $this->t('Double click the show all IP'),
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
    return $row + parent:: buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    $operations['detail'] = array(
      'title' => $this->t('Detail'),
      'weight' => 1,
      'url' => $entity->urlInfo('detail-form')
    );
    if($entity->getSimpleValue('status') == 3) {
      if($entity->access('stop') && $entity->hasLinkTemplate('stop-form')) {
        $operations['stop'] = array(
          'title' => $this->t('Stop'),
          'weight' => 10,
          'url' => $entity->urlInfo('stop-form'),
        );
      }
      if(!$entity->getSimplevalue('trial')) {
        if($entity->access('remove_ip') && $entity->hasLinkTemplate('remove_ip-form')) {
          /**
          $operations['remove_ip'] = array(
            'title' => $this->t('Remove ip'),
            'weight' => 15,
            'url' => $entity->urlInfo('remove_ip-form'),
          );
           */
        }
        $operations['remove'] = array(
          'title' => $this->t('更换IP'),
          'url' => new Url('admin.hostclient.remove_ip', array('hostclient'=>$entity->id()))
        );
      }

    }
    return $operations;
  }


	/**
   * {@inheritdoc}
   */
  public function render() {
    $build['hostclient_filter'] = $this->formBuilder->getForm('\Drupal\order\Form\HostclientFilterForm');
    $build += parent::render();
    $build['table']['#attached']['library'] = array('order/drupal.hostclient-list-builder');
    return $build;
  }
}

