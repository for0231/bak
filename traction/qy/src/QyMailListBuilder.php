<?php
/**
 * @file
 * Contains \Drupal\qy\QyMailListBuilder.
 */
namespace Drupal\qy;

use Drupal\Core\Url;

class QyMailListBuilder {
  /**
   * 查询条件
   */
  protected $conditions = array();

  protected $mail_service;
  /**
   * {@inheritdoc}
   */
  public static function create() {
    return new static();
  }

  private function __construct() {
    $this->mail_service = \Drupal::service('qy.emial_service');
  }

  public function setFilter(array $filter) {
    $this->conditions = $filter;
  }

  /**
   * 查询数据
   */
  private function load() {
    $ip_str = '';
    if(isset($this->conditions['ip'])) {
      $ip = $this->conditions['ip'];
      $ip_arr = explode('.', $ip['value']);
      if(count($ip_arr) == 4) {
        $this->conditions['ip']['value'] = $ip_arr[0] .'.'. $ip_arr[1]. '.' . $ip_arr[2];
        $ip_str = $ip['value'];
      }
    }
    $result = $this->mail_service->load_email($this->conditions);
    if(!empty($ip_str)) {
      $ip_arr = explode('.', $ip_str);
      foreach($result as $key => $item) {
        $ips = explode('.', $item->ip);
        $mask_number = $item->mask_number;
        $n = 32 - $mask_number;
        $num = pow(2, $n);
        $max = $ips[3] + $num - 1;
        if($ip_arr[3] < $ips[3] || $ip_arr[3] > $max) {
          unset($result[$key]);
        }
      }
    }
    return $result;
  }

  private function buildRow($item) {
    $row[] = $item->username;
    $row[] = $item->ip . '/' . $item->mask_number;
    $row[] = $item->email;
    $row['op']['data'] = array('#type' => 'operations', '#links' => $this->getOperations($item));
    return $row;
  }

  private function getOperations($item) {
    $operations['update'] = array(
      'title' =>'修改',
      'url' => new Url('admin.qy.mail.edit', array('mail_id' => $item->id))
    );
    $operations['delete'] = array(
      'title' =>'删除',
      'url' => new Url('admin.qy.mail.delete', array('mail_id' => $item->id))
    );
    return $operations;
  }

  /**
   * 显示table
   */
  public function render() {
    $data = $this->load();
    $build['list'] = array(
      '#type' => 'table',
      '#header' => array('用户名', 'IP段', '邮箱', '操作'),
      '#rows' => array(),
      '#empty' => t('No data.')
    );
    foreach($data as $item) {
      if($row = $this->buildRow($item)) {
        $build['list']['#rows'][$item->id] = $row;
      }
    }
    $build['list_pager'] = array('#type' => 'pager', '#route_name' => '<current>');
    return drupal_render($build);
  }
}