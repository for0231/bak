<?php

namespace Drupal\requirement\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;


use Drupal\requirement\Entity\Requirement;
use Drupal\requirement\RequirementDataListBuilder;


use Drupal\requirement\RequirementPlanListBuilder;
use Drupal\requirement\RequirementCircleListBuilder;
use Drupal\requirement\RequirementHistoryListBuilder;
use Drupal\requirement\RequirementAuditHistoryListBuilder;

/**
 *
 */
class RequirementController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter'),
      $container->get('form_builder')
    );
  }

  /**
   * Constructs a PartLogController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   A module handler.
   * @param \Drupal\Core\Datetime\DateFormatter $date_formatter
   *   The date formatter service.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form builder service.
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler, DateFormatter $date_formatter, FormBuilderInterface $form_builder) {
    $this->database      = $database;
    $this->moduleHandler = $module_handler;
    $this->dateFormatter = $date_formatter;
    $this->formBuilder   = $form_builder;
  }

  /**
   *
   */
  private function getAjaxCollection(Request $request, $entity_type, $ids = []) {
    $input = $request->query->all();
    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();
    switch ($entity_type) {
      case 'part':
        $storage_query->condition('id', $ids, 'IN');
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    if ($page == 0 || $page == 1) {
      $page = 1;
    }
    // Count.
    $count_result = $storage_query->execute();
    $count = count($count_result);
    if ($count > 0) {
      $total_pages = ceil($count / $limit);
    }
    else {
      $total_pages = 0;
    }
    if ($page > $total_pages) {
      $page = $total_pages;
    }

    $start = $limit * $page - $limit;

    $storage_query->sort($sidx, $sord);
    $storage_query->range($start, $limit);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    $matches = new \stdClass();
    $matches->page = $page;
    $matches->total = $total_pages;
    $matches->records = $count;

    return [$entities, $matches];
  }

  /**
   * 需求编辑页面.
   *
   * @see entity.requirement.edit_form
   *      admin/requirement/{requirement}/edit
   */
  public function getRequirementPartsAutocomplete(Request $request, $requirement) {
    $param_require = \Drupal::entityTypeManager()->getStorage('requirement');
    $entity_requirement = $param_require->load($requirement);
    $matches = [];
    if ($entity_requirement instanceof Requirement) {
      $parts = $entity_requirement->get('pids');
      $ids = [];
      foreach ($parts as $row) {
        $ids[] = $row->entity->id();
      }

      list($entities, $matches) = $this->getAjaxCollection($request, 'part', $ids);
      $i = 0;
      foreach ($entities as $entity) {
        $part = $entity;
        $name = $entity->get('name')->value;
        $unit = taxonomy_term_load($entity->get('unit')->target_id);
        $locate = taxonomy_term_load($entity->get('locate_id')->target_id);

        $matches->rows[$i]['id'] = $entity->id();
        $matches->rows[$i]['cell'] = [
          'id' => $entity->id(),
          'name' => $entity->get('name')->value,
          'type' => $entity->get('parttype')->value,
          'caiwunos' => !empty($entity->get('caiwunos')->value) ? $entity->get('caiwunos')->value : '-',
          'sdate' => $entity->get('plandate')->value == 0 ? '-' : date('Y-m-d', $entity->get('plandate')->value),
          'num' => $entity->get('num')->value,
          'unit' => $unit->label(),
          'locate' => $locate->label(),
        ];
        $i++;
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * @description requirement edit page
   * @see ajax.requirement.parts.collection
   */
  public function getRequirementPartsDetailAutocomplete(Request $request, $requirement) {
    $param_require = \Drupal::entityTypeManager()->getStorage('requirement');
    $entity_requirement = $param_require->load($requirement);
    $matches = [];
    if ($entity_requirement instanceof Requirement) {
      $parts = $entity_requirement->get('pids');
      $ids = [];
      foreach ($parts as $row) {
        $ids[] = $row->entity->id();
      }

      list($entities, $matches) = $this->getAjaxCollection($request, 'part', $ids);
      $i = 0;
      foreach ($entities as $entity) {
        $name = $entity->get('name')->value;
        $unit = taxonomy_term_load($entity->get('unit')->target_id);
        $locate = taxonomy_term_load($entity->get('locate_id')->target_id);
        if (!empty($entity->get('ship_supply_id')->target_id)) {
          $taxonomy_ship_com = taxonomy_term_load($entity->get('ship_supply_id')->target_id);
          $ship_com = $taxonomy_ship_com->label();
        }
        else {
          $ship_com = '-';
        }

        if (!empty($entity->get('ship_supply_no')->value) || !empty($entity->get('ship_supply_id')->value)) {
          $wuliu_status = '物流中';
        }
        else {
          $wuliu_status = '-';
        }
        $matches->rows[$i]['id'] = $entity->id();
        $matches->rows[$i]['cell'] = [
          'id' => $entity->id(),
          'name' => $entity->get('name')->value,
          'type' => $entity->get('parttype')->value,
          'caiwunos' => !empty($entity->get('caiwunos')->value) ? $entity->get('caiwunos')->value : '-',
          'sdate' => $entity->get('requiredate')->value == 0 ? '-' : date('Y-m-d', $entity->get('requiredate')->value),
          'num' => $entity->get('num')->value,
          'unit' => $unit->label(),
          'locate' => $locate->label(),
          'status' => $wuliu_status,
          'wuliu' => $ship_com,
          'wuliuno' => $entity->get('ship_supply_no')->value,
        ];
        $i++;
      }
    }
    return new JsonResponse($matches);
  }

  /**
   * 勾选删除需求的配件信息.
   */
  public function deleteRequirementPartsAutocomplete(Request $request, $requirement) {
    $result = [];
    $items = $request->request->all();
    if ($items['oper'] == 'del') {
      $ids = explode(',', $items['id']);
      \Drupal::service('requirement.requirementservice')->delete($requirement, $ids);
    }
    else {
      $result = ['false'];
    }

    return new JsonResponse($result);
  }

  /**
   * @description 获取列表数据.
   */
  public function getRequirementDataCollection() {
    $list = new RequirementDataListBuilder();

    $mode = isset($_GET['mode']) ? $_GET['mode'] : 4;

    if (empty($_SESSION['collection_data_list'])) {
      $_SESSION['collection_data_list'] = [
        'mode' => $mode,
      ];
    }
    else {
      if ($_SESSION['collection_data_list']['mode'] != $mode) {
        $_SESSION['collection_data_list']['mode'] = $mode;
      }
    }

    $list->setMode($mode);
    $data = $list->build();
    return new Response($data);
  }

  /**
   *
   */
  public function getHistoryData() {
    $build['description'] = ['#markup' => '当前列表将会列出需求状态为非待审批和审批中的数据'];
    $history = RequirementHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

  /**
   * @description 获取周期性需求数据列表.
   */
  public function getRequirementCircleData() {
    $circle = RequirementCircleListBuilder::createInstance(\Drupal::getContainer());
    $build['circle'] = $circle->render();
    return $build;
  }

  /**
   * @description 获取计划性需求数据列表.
   */
  public function getRequirementPlanData() {
    $plan = RequirementPlanListBuilder::createInstance(\Drupal::getContainer());
    $build['plan'] = $plan->render();
    return $build;
  }

  /**
   * @description 需求单审批历史.
   */
  public function getAuditHistoryData() {
    $history = RequirementAuditHistoryListBuilder::createInstance(\Drupal::getContainer());
    $build['history'] = $history->render();
    return $build;
  }

}
