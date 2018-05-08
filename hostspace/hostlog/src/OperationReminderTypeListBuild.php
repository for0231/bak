<?php
/**
 * @file
 * Contains Drupal\hostlog\OperationReminderTypeListBuild.
 */

namespace Drupal\hostlog;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OperationReminderTypeListBuild {

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


  public function buildFormHeader() {
    $header['id'] = array(
       'data' => t('ID'),
       'field' => 'id',
       'specifier' => 'id',
    );
    $header['type'] = array(
       'data' => t('Type'),
       'field' => 'type',
       'specifier' => 'type',
    );
    $header['description'] = array(
       'data' => t('Description'),
       'field' => 'description',
       'specifier' => 'description',
     );
    $header['uid'] = array(
       'data' => t('User'),
       'field' => 'uid',
       'specifier' => 'uid',
    );
    $header['timestamp'] = array(
       'data' => t('Created'),
       'field' => 'timestamp',
       'specifier' => 'timestamp',
    );
    return $header;
  }


  public function buildContentRow() {
    $types = $this->load();

    if (!empty($types)) {
      foreach ($types as $type) {
        $user = entity_load('user', $type->uid);
        $row_arr[$type->id] = array(
          'id' => $type->id,
          'type' => $type->type,
          'description' => $type->description,
          'uid' => $user->getUsername(),
          'timestamp' => format_date($type->timestamp, 'custom', 'Y-m-d H:i:s'),
        );
      }
    }
    return isset($row_arr) ? $row_arr : array();
  }

  /**
   *
   */
  public function load() {
    $query = db_select('xunyunreminder_type','log')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender')
      ->fields('log')
      ->limit(PER_PAGE_COUNT);
    $query->orderBy('id', 'DESC');
    return $query->execute()->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['host_log_reminder_type_form'] = $this->formBuilder->getForm('Drupal\hostlog\Form\ReminderTypeForm');
    $build['list'] = array(
      '#type' => 'table',
      '#header' => $this->buildFormHeader(),
      '#rows' => $this->buildContentRow(),
      '#empty' => t('There is no type data'),
    );

    $build['list_pager'] = array('#type' => 'pager');

    return $build;
  }
}
