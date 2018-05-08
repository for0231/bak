<?php
/**
 * @file
 * Contains \Drupal\order\user\UserServerListForm.
 */

namespace Drupal\order\user;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class UserServerListForm extends FormBase {
  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  public function __construct(QueryFactory $query_factory, EntityStorageInterface $storage) {
    $this->queryFactory = $query_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager')->getStorage('hostclient')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_server_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $qys = get_current_ip_traction_info(); //当前牵引IP信息
    //服务器列表
    $serverList = MyServerList::create(\Drupal::getContainer());
    $server_number = \Drupal::service('hostclient.serverservice')->myServerNumber(\Drupal::currentUser()->id());
    $form['server'] = array(
      '#type' => 'vertical_tabs'
    );
    $form['server_list'] = array(
      '#type' => 'details',
      '#group' => 'server',
      '#title' => $this->t('Server list') . '('. $server_number .')',
    ) + $serverList->buildForm($qys);

    //试用列表
    $trialList = new MyTrialList();
    $trial_form = $trialList->buildForm($qys);
    if(!empty($trial_form)) {
      $trial_number = count($trial_form['trial_table']['#rows']);
      $form['trial_list'] = array(
        '#type' => 'details',
        '#group' => 'server',
        '#title' => t('Trial list') . '('. $trial_number .')',
      ) + $trial_form;
    }
    //已停用
    $stopList = new MyStopList();
    $stop_form = $stopList->buildForm();
    if(!empty($stop_form)) {
      $stop_number = count($stop_form['stop_table']['#rows']);
      $form['stop_list'] = array(
        '#type' => 'details',
        '#group' => 'server',
        '#title' => t('Stop list') . '('. $stop_number .')',
      ) + $stop_form;
    }
    //处理中
    $processing = new MyProcessingList();
    $processing_form = $processing->buildForm();
    if(!empty($processing_form)) {
      $processing_number = count($processing_form['processing_table']['#rows']);
      $form['processing_list'] = array(
        '#type' => 'details',
        '#group' => 'server',
        '#title' => t('Processing') . '('. $processing_number .')',
      ) + $processing_form;
    }
    //待处理
    $pending = new MyPendingList();
    $pending_form = $pending->buildForm();
    if(!empty($pending_form)) {
      $pending_number = count($pending_form['pending_table']['#rows']);
      $form['pending_list'] = array(
        '#type' => 'details',
        '#group' => 'server',
        '#title' => t('Pending treatment') . '('. $pending_number .')',
      ) + $pending_form;
    }
    //牵引列表
    $tractionList = new MyTractionList();
    $traction_form = $tractionList->buildForm($qys);
    if(!empty($traction_form)) {
      $form['traction_list'] = array(
        '#type' => 'details',
        '#group' => 'server',
        '#title' => t('Traction list'),
      ) + $traction_form;
    }
    $form['#attached']['library'] = array('order/drupal.hostclient-list-builder');
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
