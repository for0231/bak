<?php

namespace Drupal\part\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Url;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;


use Drupal\part\VocabularyListBuilder;

/**
 * Use Drupal\requirement\Entity\Requirement;.
 */
class PartController extends ControllerBase {

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
   * Constructs a PartController object.
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
   * Get all parts.
   * 需求池列表.
   */
  public function getPartCollection(Request $request) {
    $input = $request->request->all();
    if (isset($input['oper']) && ($input['oper'] == 'edit')) {
      \Drupal::service('part.partservice')->setSplitEntity($input);
    }

    $build = [];
    $build['part']['#theme'] = 'partspool';
    $build['#attached']['library'] = ['part/poollist'];
    return $build;
  }

  /**
   * Ajax get parts.
   *
   * @param $input
   *
   * @code
   *  $input = [
   *    '_search' => false,
   *    'nd' => 1496842536048,
   *    'rows' => 50,
   *    'page' => 1,
   *    'sidx' => "name",
   *    'sord' => "asc",
   *    'filters' => "",
   *  ];
   * @endcode
   * @see entity.part.pool.collection
   */
  public function getPartsAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'part');
    $i = 0;
    foreach ($entities as $entity) {
      $entity_require = requirement_load($entity->get('rno')->value);
      $locate = taxonomy_term_load($entity->get('locate_id')->target_id);
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'name' => $entity->label(),
        'parttype' => $entity->get('parttype')->value,
        'title' => \Drupal::l($entity_require->get('title')->value, new Url('entity.requirement.detail_form', ['requirement' => $entity->get('rno')->value], ['attributes' => ['target' => '_blank']])),
        'num' => $entity->get('num')->value,
        'requiredate' => isset($entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($entity->get('requiredate')->value, 'html_date') : '-',
        'locate' => $locate->label(),
      // @todo 采购数量,这个无需操作，用来手工拆分采购数量
        'ccsnum' => 0,
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * @param $request
   * @param $entity_type
   * @param  $status
   * @param 0 $op
   */
  private function getAjaxCollection(Request $request, $entity_type, $status = 1, $op = 1) {
    $input = $request->query->all();

    $page = $input['page'];
    $limit = $input['rows'];
    $sidx = $input['sidx'];
    $sord = $input['sord'];

    $storage = \Drupal::entityManager()->getStorage($entity_type);
    $storage_query = $storage->getQuery();

    switch ($entity_type) {
      case 'part':
        /**
         * @description 正常的需求池物品.
         */
        if ($status) {
          $storage_query->condition('save_status', 1);
          if ($op) {
            $storage_query->condition('rno', 0, '<>');
            $storage_query->condition('cno', 0);
            $storage_query->condition('fno', 0);
            $storage_query->condition('pno', 0);
            // 已通过需求单审批.
            $storage_query->condition('re_status', 5);
            // 已通过需求单审批.
            $storage_query->condition('re_audit', 3);
          }
          else {
            $storage_query->condition('id', 0, '<>');
          }
        }
        else {
          /**
           * @description 非正常的需求池物品.
           */
          $storage_query->condition('save_status', 0);
        }
        if (!empty($_SESSION['paypro_statis_filter'])) {
          if (!empty($_SESSION['paypro_statis_filter']['begin'])) {
            $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['begin']), '>');
          }
          if (!empty($_SESSION['paypro_statis_filter']['end'])) {
            $storage_query->condition('created', strtotime($_SESSION['paypro_statis_filter']['end']), '<');
          }
        }
        break;

      default:
        $storage_query->condition('id', 0, '<>');
    }
    // @todo 添加采购审批中，采购中，待处理等条件查询.

    // Count.
    $count_result = $storage_query->execute();

    $count = count($count_result);
    if ($page == 0 || $page == 1) {
      $page = 1;
    }

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
   * @description 统计配件物品的各种状态.
   * @return list
   *
   * @see entity.part.pool.collection.statis
   */
  public function getPartsStatisAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'part', 1, 0);
    $i = 0;
    $transform = \Drupal::service('part.partservice')->getSinglePartTransform($entities);
    foreach ($transform as $key => $entity) {
      $matches->rows[$i]['id'] = $i;
      $matches->rows[$i]['cell'] = [
        'name' => $key,
        'rnum' => \Drupal::service('part.partservice')->getSumPartAmount($entity),
        'csnum' => \Drupal::service('part.partservice')->getSumPartAmount($entity, 5),
        'cnum' => \Drupal::service('part.partservice')->getSumPartAmount($entity, 3),
        'wnum' => \Drupal::service('part.partservice')->getSumPartAmount($entity, 6),
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * 获取所有配件的数据.
   *
   * @description
   * 未用此函数
   */
  public function getPartsData($condition) {
    $storage_query->pager($condition['rows']);
    // $storage_query->pager(5);.
    $storage_query->sort($condition['sidx'], $condition['sord']);
    $ids = $storage_query->execute();

    return $storage->loadMultiple($ids);
  }

  /**
   * Get all un parts.
   * 清洗池列表-所以save_status=0的未启用的物品列表.
   */
  public function getUnPartCollection(Request $request) {
    $input = $request->request->all();
    if (isset($input['oper']) && ($input['oper'] == 'edit')) {
      // \Drupal::service('part.partservice')->setSplitEntity($input);
    }

    $build = [];
    $build['part']['#theme'] = 'unpartspool';
    $build['#attached']['library'] = ['part/unparts'];

    $build['tips'] = ['#markup' => '友情提醒: 该列表的数据是需求单添加配件时的非正常保存数据，可通过这里删除!'];
    return $build;
  }

  /**
   * Ajax get parts.
   *
   * @param $input
   *
   * @code
   *  $input = [
   *    '_search' => false,
   *    'nd' => 1496842536048,
   *    'rows' => 50,
   *    'page' => 1,
   *    'sidx' => "name",
   *    'sord' => "asc",
   *    'filters' => "",
   *  ];
   * @endcode
   * @see entity.part.pool.collection
   */
  public function getUnPartsAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'part', 0);
    $i = 0;
    foreach ($entities as $entity) {
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'name' => $entity->label(),
        'num' => $entity->get('num')->value,
        'requiredate' => isset($entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($entity->get('requiredate')->value, 'html_date') : '-',
        'locate' => '重庆',
        'rno' => $entity->get('rno')->value,
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   * 勾选删除需求的配件信息
   * 清洗池.
   */
  public function deletePartsPoolAutocomplete(Request $request) {
    $result = [];
    $items = $request->request->all();
    if ($items['oper'] == 'del') {
      $ids = explode(',', $items['id']);
      // Requirement ids.
      $ids = \Drupal::service('part.partservice')->delete($ids);
      $result = $ids;
      foreach ($ids as $key => $val) {
        \Drupal::service('requirement.requirementservice')->delete($key, $val);
      }
    }
    else {
      $result = ['false'];
    }

    return new JsonResponse($result);
  }

  /**
   * 采购单编辑时，为采购获取可用的需求池配件.
   *
   * @see entity.purchase.part.collection
   */
  public function getPurchasePartCollection(Request $request) {
    /*

    $input = $request->request->all();
    $result = [];
    if ($items['oper'] == 'del') {
    $ids = explode(',', $items['id']);
    // Requirement ids.
    $ids = [];//\Drupal::service('part.partservice')->delete($ids);
    $result = $ids;
    foreach ($ids as $key => $val) {
    //\Drupal::service('requirement.requirementservice')->delete($key, $val);
    }
    } else {
    $result = ['false'];
    }
     */
    $build = [];
    $build['part']['#theme'] = 'purchase_parts_pool';
    $build['#attached']['library'] = ['part/purchase_parts_pool_list'];
    return $build;
  }

  /**
   * Ajax get parts.
   *
   * @param $input
   *
   * @see ajax.purchase.part.collection
   */
  public function getPurchasePartsAutocomplete(Request $request) {
    list($entities, $matches) = $this->getAjaxCollection($request, 'part');
    $i = 0;
    foreach ($entities as $entity) {
      // $entity_requirement = requirement_load($entity->id());
      $matches->rows[$i]['id'] = $entity->id();
      $matches->rows[$i]['cell'] = [
        'id' => $entity->id(),
        'name' => $entity->label(),
        'num' => $entity->get('num')->value,
        'requiredate' => isset($entity->get('requiredate')->value) ? \Drupal::service('date.formatter')->format($entity->get('requiredate')->value, 'html_date') : '-',
        'locate_id' => $entity->get('locate_id')->entity->label(),
      ];
      $i++;
    }

    return new JsonResponse($matches);
  }

  /**
   *
   */
  public function getVocabularyList() {
    $list = VocabularyListBuilder::createInstance(\Drupal::getContainer());
    return $list->render();
  }

}
