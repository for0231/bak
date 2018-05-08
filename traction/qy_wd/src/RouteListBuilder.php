<?php
/**
 * @file
 * Contains \Drupal\qy_wd\RouteListBuilder.
 */

namespace Drupal\qy_wd;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class RouteListBuilder {

  protected $db_service;

  public function __construct() {
    $this->db_service = \Drupal::service('qy_wd.db_service');
  }

  private function buildHeader() {
    $header['routname'] = '名称';
    $header['firewall'] = '防火墙单元';
    $header['blackhole'] = '黑洞';
    $header['total_bps'] = '总墙bps';
    $header['one_bps'] = '单墙bps';
    $header['time'] = '牵引时间';
    $header['status'] = '状态';
    $header['op'] = '操作';
    return $header;
  }

  private function buildRow($item) {
    $row['routname'] = $item->routename;
    $firewall = str_split('0000000000000000');
    $units = explode(',', $item->firewall_unit);
    foreach ($units as $unit) {
      $firewall[$unit-1] = 1;
    }
    $row['firewall'] = implode($firewall) . '('. $item->firewall_unit .')';
    $arr = explode(',', $item->blackhole);
    $row['blackhole'] = SafeMarkup::format(implode("<br>", $arr), array());
    $row['total_bps'] = $item->total_bps . ' Mbps';
    $row['one_bps'] = $item->one_bps . ' Mbps';
    $row['time'] = $item->time . '分钟';
    $row['status'] = qy_route_status()[$item->status];
    $row['op']['data'] = array(
      '#type' => 'operations',
      '#links' => $this->getOperations($item)
    );
    return $row;
  }

  /**
   * 构建操作
   */
  private function getOperations($item) {
    $op['edit'] = array(
      'title' => '编辑',
      'url' => new Url('admin.wd.route.edit', array('id' => $item->id))
    );
    $op['open_wall'] = array(
      'title' => '开启防火墙监听',
      'url' => new Url('admin.wd.listen.wall', array('route_id' => $item->id), array('attributes' => array('target' => '_blank')))
    );
    $op['open_blackhole'] = array(
      'title' => '开启写黑洞',
      'url' => new Url('admin.wd.listen.blackhole', array('route_id' => $item->id), array('attributes' => array('target' => '_blank')))
    );
    $op['close'] = array(
      'title' => '关闭防火墙监听及牵引',
      'url' => new Url('admin.wd.listen.close', array('route_id' => $item->id))
    );
    $op['delete'] = array(
      'title' => '删除',
      'url' => new Url('admin.wd.route.delete', array('id' => $item->id))
    );
    return $op;
  }

  public function render() {
    $routes = $this->db_service->load_route();
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.'),
      '#attached' => array('library' => array('qy_wd/drupal.route-view'))
    );   
    foreach($routes as $key => $route) {
      if($row = $this->buildRow($route)) {
        $build['table']['#rows'][$route->id] = $row;
      }
    }
    return $build;
  }
}
