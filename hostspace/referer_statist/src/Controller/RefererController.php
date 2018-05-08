<?php
/**
 * @file
 * Contains \Drupal\referer_statist\Controller\RefererController.
 */

namespace Drupal\referer_statist\Controller;

use Drupal\Core\Controller\ControllerBase;

class RefererController extends ControllerBase {

  public function showRefererStatistics() {
    $build['charts'] =  array(
      '#type' => 'container',
      '#attributes' => array(
        'id' => 'referer-charts',
        'style' => 'height:400px;'
      ),
      '#attached' => array(
        'library' => array('referer_statist/drupal.baidu-echarts', 'referer_statist/drupal.referer-statist-echarts')
      )
    );
    $service = \Drupal::service('referer_statist.service');
    $data = $service->loadCharts();
    $chart_data = array();
    foreach($data as $key => $item) {
      if($item['total']) {
        $chart_data['data1'][] = array('value' => $item['total'], 'name' => $key);
        if($item['reg']) {
          $chart_data['data2'][] = array('value' => $item['reg'], 'name' => $key . '(注册)');
        }
        if($item['noreg']) {
          $chart_data['data2'][] = array('value' => $item['noreg'], 'name' => $key . '(未注册)');
        }
      }
    }
    foreach($chart_data['data1'] as $data1) {
      $chart_data['title'][] = $data1['name'];
    }
    foreach($chart_data['data2'] as $data2) {
      $chart_data['title'][] = $data2['name'];
    }
    $build['chart_data'] = array(
      '#type' => 'hidden',
      '#value' => json_encode($chart_data),
      '#attributes' => array(
        'id' => 'chart-data'
      ),
    ); 
    $build['table'] = array(
      '#type' => 'table',
      '#header' => array('Ip', '访问时间', '来源网站', '访问地址', '注册情况'),
      '#rows' => array(),
      '#empty' => '无数据'
    );
    $items = $service->loadView();
    foreach($items as $item) {
      $build['table']['#rows'][] = array(
        $item->ip,
        date('Y-m-d h:i:s', $item->created),
        empty($item->referer_site) ? '浏览器输入' : $item->referer_site,
        $item->url,
        empty($item->user_name) ? '未注册' : '于' . date('Y-m-d h:i:s', $item->register_time) . '注册为['. $item->user_name .']'
      );
    }
    return $build;
  }
}
