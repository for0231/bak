<?php
/**
 * @file
 * Contains \Drupal\qy_jd\RouteListBuilder.
 */

namespace Drupal\qy_jd;

use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;

class RouteListBuilder {

  protected $db_service;

  public function __construct() {
    $this->db_service = \Drupal::service('qy_jd.db_service');
  }

  private function load() {
    $db_service= \Drupal::service('qy_jd.db_service');
    return $this->db_service->load_route();
  }

  private function buildHeader() {
    $header['routname'] = '名称';
    $header['firewall'] = '防火墙';
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
    $data = $this->db_service->load_netcom(array('type'=>$item->id));
    $link = \Drupal::url('admin.jd.route.firewall.add', array('route_id' => $item->id));
    $row['firewall'] = array(
      'class' => 'child-table',
      'data' => array(
        '#type' => 'table',
        '#rows' =>  $this->buildContentRow($data),
        '#empty' => SafeMarkup::format('还没有防火墙信息，点击<a href="@link">增加防火墙</a>', array(
          '@link' => $link
        ))
      )
    );
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
    $op['add'] = array(
      'title' => '增加防火墙',
      'url' => new Url('admin.jd.route.firewall.add', array('route_id' => $item->id))
    );
    $op['edit'] = array(
      'title' => '编辑',
      'url' => new Url('admin.jd.route.edit', array('id' => $item->id))
    );
    $op['open_wall'] = array(
      'title' => '开启防火墙监听',
      'url' => new Url('admin.jd.listen.wall', array('route_id' => $item->id), array('attributes' => array('target' => '_blank')))
    );
    $op['open_blackhole'] = array(
      'title' => '开启写黑洞',
      'url' => new Url('admin.jd.listen.blackhole', array('route_id' => $item->id), array('attributes' => array('target' => '_blank')))
    );
    $op['close'] = array(
      'title' => '关闭防火墙监听及牵引',
      'url' => new Url('admin.jd.listen.close', array('route_id' => $item->id))
    );
    $op['delete'] = array(
      'title' => '删除',
      'url' => new Url('admin.jd.route.delete', array('id' => $item->id))
    );
    return $op;
  }

  private function buildContentRow($data) {
    $rows = array();
    foreach($data as $key => $item) {
      $rows[$key] = array(
        $item->ip,
        array(
          'data' => array(
            '#type' => 'operations',
            '#links' => array(
              'edit' => array(
                'title' => '编辑',
                'url' => new Url('admin.jd.firewall.edit', array('id' => $item->id))
              ),
              'delete' => array(
                'title' => '删除',
                'url' => new Url('admin.jd.firewall.delete', array('id' => $item->id))
              )
            )
          )
        )
      );
    }
    return $rows;
  }

  public function render() {
    $routes = $this->db_service->load_route();
    $build['table'] = array(
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#rows' => array(),
      '#empty' => t('No data.'),
      '#attached' => array('library' => array('qy_jd/drupal.route-view'))
    );
    foreach($routes as $key => $route) {
      if($row = $this->buildRow($route)) {
        $build['table']['#rows'][$route->id] = $row;
      }
    }
    return $build;
  }
}
