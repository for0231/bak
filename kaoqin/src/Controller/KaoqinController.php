<?php

namespace Drupal\kaoqin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

use Drupal\kaoqin\KaoqinDiffListBuilder;
/**
 *
 */
class KaoqinController extends ControllerBase {

  /**
   * @description 模板导出.
   */
  public function exportKaoqin(){
    $filename=realpath('sites/default/files/kaoqin.xlsx'); //文件名
    $date=date("Ymd-H:i:m");
    Header( "Content-type:  application/octet-stream ");
    Header( "Accept-Ranges:  bytes ");
    Header( "Accept-Length: " .filesize($filename));
    header( "Content-Disposition:  attachment;  filename= {$date}.xlsx");
    readfile($filename);
    return new Response('success');
  }

  /**
   * @description 排班设置页面.
   */
  public function settingUpon() {
    $build = [];
    $build['upon']['#theme'] = 'upon_update';
    $build['#attached']['library'] = ['kaoqin/paibanconfig'];
    return $build;
  }

  /**
   * @description Ajax添加考勤排班数据.
   */
  public function createUpon(Request $request) {

    $params = $request->request->all();

    $title = $params['title'];
    $classname = $params['className'];
    $description = $params['description'];
    $icon = $params['icon'];
    $start = substr($params['event_start'], 0, -3);
    if (empty($title) || empty($classname) || empty($description) || empty($icon) || empty($start)) {
      return new JsonResponse(0);
    }
    // 重名的无法识别
    // @todo 处理重名问题.
    $users = \Drupal::entityTypeManager()->getStorage('user')
                ->loadByProperties(['realname' => $title]);
    $user =  $users ? reset($users) : FALSE;
    if (!$user) {
      return new JsonResponse(0);
    }

    $data = [
      'icontype' => $classname,
      'allday' => TRUE,
      'iconcolor' => $icon,
      'datetime' => strtotime(date('Y-m-d', $start)), //保存年月日
      'morningsign' => 9 * 3600,
      'afternoonsign' => 18 * 3600,
      'description' => $description,
      'depart' => $user->get('depart')->value,
      'user' => $user->id(),
    ];

    $status = \Drupal::service('kaoqin.kaoqinservice')->saveKaoqinUpon($data);


    return new JsonResponse($status);
  }

  /**
   * @description Ajax更新排班事件.
   */
  public function updateUpon(Request $request) {
    $params = $request->request->all();
    $id = substr($params['_id'],3);

    $start = $this->transformDatetime(array_filter($params['event_start']));

    $end = 0;
    if (isset($params['event_end']) && !empty($params['event_end'])) {
      $end = $this->transformDatetime(array_filter($params['event_end']));
    }

    $date = strtotime(date('Y-m-d', $start));

    $shangban = $start - $date;//上班时间戳.
    $xiaban   = 0;
    if ($end) {
      $xiaban   = $end - $date < 0 ? 0 : $end - $date; //下班时间戳.
    }
    $data = [
      'datetime' => $date,
      'morningsign' => $shangban,
      'afternoonsign' => $xiaban
    ];
    $status = \Drupal::service('kaoqin.kaoqinservice')->updateKaoqinUpon($id, $data);

    return new JsonResponse($status);
  }

  /**
   * @description for updateUpon事件.
   * @return 返回格式 2017-10-21 10:30 返回时间戳.
   */
  private function transformDatetime($data) {
    $date = $data[0] . "-" . ($data[1] + 1) . "-" . $data[2];
    if (!empty($data[3])) {
      $date .= " " . $data[3];
    } else {
      $date .= " 09";
    }
    if (!empty($data[4])) {
      $date .= ":" . $data[4] . ":00";
    } else {
      $date .= ":00:00";
    }
    return strtotime($date);
  }

  /**
   * @description Ajax考勤排班列表.
   */
  public function listUpon(Request $request) {
    $entities = $this->getAjaxCollectionforKaoqinUpon($request);

    $upons = [];
    foreach ($entities as $entity) {
      $upons[] = [
        "_id" => '_fc'.$entity->id(),
        "title" => $entity->get('user')->entity->get('realname')->value,
        "start" => date('Y-m-d H:i', $entity->get('datetime')->value + $entity->get('morningsign')->value),
        "end" => date('Y-m-d H:i', $entity->get('datetime')->value + $entity->get('afternoonsign')->value),
        "description" => $entity->get('description')->value,
        "className" => $entity->get('icontype')->value,
        "icon" => $entity->get('iconcolor')->value,
      ];
    }

    return new JsonResponse($upons);
  }

  /**
   * @description Ajax get kaoqin upon list.
   */
  public function getAjaxCollectionforKaoqinUpon(Request $request) {
    $input = $request->query->all();
    $start = $input['start'];
    $end   = $input['end'];
    $curr  = $input['_'];

    $storage = \Drupal::entityManager()->getStorage('upon');
    $storage_query = $storage->getQuery();
    $group = $storage_query->andConditionGroup()
      ->condition('datetime', strtotime($start), '>')
      ->condition('datetime', strtotime($end), '<');
    $storage_query->condition($group);

    $ids = $storage_query->execute();

    $entities = $storage->loadMultiple($ids);

    return $entities;
  }

  /**
   * @description Ajax考勤排班列表.
   */
  public function detailUpon(Request $request) {

    return new JsonResponse('fd');
  }

  /**
   * @description 考勤统计表.
   */
  public function getStatistic() {

    $build = ['#markup' => '考勤统计列表'];
    return $build;
  }

  /**
   * @description 考勤差异比对.
   */
  public function getDiffKaoqin() {
    $list = new KaoqinDiffListBuilder();
    return $list->build();
  }


}
