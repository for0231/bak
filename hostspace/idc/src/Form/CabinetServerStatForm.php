<?php

/**
 * @file
 * Contains \Drupal\idc\Form\RoomForm.
 */

namespace Drupal\idc\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CabinetServerStatForm extends FormBase {

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory.
   */
  protected $queryFactory;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'server_stat_form';
  }

  public function __construct(EntityStorageInterface $storage, QueryFactory $query_factory) {
    $this->storage = $storage;
    $this->queryFactory = $query_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('cabinet_server'),
      $container->get('entity.query')
    );
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['filters'] = array(
      '#type' => 'details',
      '#title' => $this->t('Filter'),
      '#open' => !empty($_SESSION['server_stat_form']),
      '#prefix' => '<div class="column">',
      '#suffix' => '</div>'
    );
    $server_type_options = array('' => $this->t('All'));
    $server_type = entity_load_multiple_by_properties('server_type', array());
    foreach($server_type as $key => $item) {
       $server_type_options[$key] = $item->label();
    }
    ksort($server_type_options);
    $form['filters']['server_type'] = array(
      '#type' => 'select',
      '#title' => $this->t('Server catalog'),
      '#options' => $server_type_options
    );
    $form['filters']['server_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Server code')
    );
    $form['filters']['manage_ip'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Management IP')
    );
    $form['filters']['cabinet'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Cabinet'), 
    );
    $form['filters']['status'] = array(
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => array('' => $this->t('All')) + ipmStatus()
    );
    $form['filters']['search_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
    );
    if(!empty($_SESSION['server_stat_form'])) {
      $allempty = true;
      $fields = array('server_type', 'server_code', 'manage_ip', 'cabinet', 'status');
      foreach($fields as $field) {
        if(!empty($_SESSION['server_stat_form'][$field])) {
          $form['filters'][$field]['#default_value'] = $_SESSION['server_stat_form'][$field];
          $allempty = false;
        }
      }
      if($allempty) {
        $_SESSION['server_stat_form'] = array();
      }
    }
    if (!empty($_SESSION['server_stat_form'])) {
      $form['filters']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array('::resetForm'),
      );
    }

    $form['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => $this->t('No data')
    );
     $list = $this->load();
    foreach($list as $key => $item) {
      if($row = $this->buildRow($item)) {
        $form['table']['#rows'][$key] = $row;
      }
    }
    $form['pager'] = array(
      '#type' => 'pager'
    );
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['server_stat_form']['server_type'] = $form_state->getvalue('server_type');
    $_SESSION['server_stat_form']['server_code'] = trim($form_state->getvalue('server_code'));
    $_SESSION['server_stat_form']['manage_ip'] = trim($form_state->getvalue('manage_ip'));
    $_SESSION['server_stat_form']['cabinet'] = trim($form_state->getvalue('cabinet'));
    $_SESSION['server_stat_form']['status'] = $form_state->getvalue('status');
  }

  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['server_stat_form'] = array();
  }

  private function load() {
    if(empty($_SESSION['server_stat_form'])) {
     $ids = \Drupal::service('idc.cabinet.server')->getServerByCondition(array());
     return $this->storage->loadMultiple($ids);
    }
    $condition = array();
    if(!empty($_SESSION['server_stat_form']['server_type'])) {
      $condition['server_type'] = $_SESSION['server_stat_form']['server_type'];
    }
    if(!empty($_SESSION['server_stat_form']['server_code'])) {
      $condition['server_code'] = $_SESSION['server_stat_form']['server_code'];
    }
    if(!empty($_SESSION['server_stat_form']['manage_ip'])) {
      $condition['manage_ip'] = $_SESSION['server_stat_form']['manage_ip'];
    }
    if(!empty($_SESSION['server_stat_form']['cabinet'])) {
      $condition['cabinet'] = $_SESSION['server_stat_form']['cabinet'];
    }
    if(!empty($_SESSION['server_stat_form']['status'])) {
      $condition['status'] = $_SESSION['server_stat_form']['status'];
    }
    $ids = \Drupal::service('idc.cabinet.server')->getServerByCondition($condition);
    return $this->storage->loadMultiple($ids);
  }

  private function buildHeader() {
    $header['server_type'] = $this->t('Server catalog');
    $header['server_code'] = $this->t('Server code');
    $header['manage_IP'] = $this->t('Management IP');
    $header['room'] = $this->t('Room');
    $header['cabinet'] = $this->t('Cabinet');
    $header['seat'] = $this->t('Start seat');
    $header['Model'] = $this->t('Model');
    $header['status'] = $this->t('Status');
    return $header;
  }

  private function buildRow($entity) {
    $server = $entity->getObject('server_id');
    $row['server_type'] = $server->get('type')->entity->label();
    $row['server_code'] = $server->get('server_code')->value;
    $ipm = $entity->getObject('ipm_id');
    $row['manage_IP'] = \Drupal::l($ipm->label(), new Url('admin.idc.cabinet.server.detail', array('cabinet_server' => $entity->id())));
    $cabinet = $entity->getObject('cabinet_id');
    $row['room'] = $cabinet->getObject('rid')->label();
    $row['cabinet'] = $cabinet->label();
    if($entity->getSimpleValue('parent_id') > 0) {
      $parent_id = $entity->getSimpleValue('parent_id');
      $parent = entity_load('cabinet_server', $parent_id);
      $row['seat'] = $parent->getSimpleValue('group_name');
      $row['Model'] = $entity->getSimpleValue('group_name');
    } else {
      $row['seat'] = $entity->getSimpleValue('start_seat');
      $row['Model'] = $entity->getSimpleValue('seat_size') . 'U';
    }
    $row['status'] = ipmStatus()[$ipm->get('status')->value];
    return $row;
  }
}
