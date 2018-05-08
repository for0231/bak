<?php
/**
 * @file
 * Contains \Drupal\qy\FactoryAjaxListBuill.
 */

namespace Drupal\qy;


class FactoryAjaxListBuill {

  public static function getList($provider, $type) {
    $list = null;
    switch($type) {
      case 'policy':
      case 'policytmp':
        $class = 'Drupal\\' . $provider . '\\PolicyListBuilder';
        $list = $class::create($type);
        break;
      case 'traction':
      case 'tractionfilter':
      case 'ipstop':
        $class = 'Drupal\\' . $provider . '\\TractionListBuilder';
        $list = $class::create($type);
        break;
      case 'remote':
        $class = 'Drupal\\' . $provider . '\\RemoteTractionList';
        $list = $class::create($type); 
        break;
      case 'monitor':
        $class = 'Drupal\\' . $provider . '\\FlowMonitorListBuilder';
        $list = $class::create($type);
        break;
      case 'sendMail':
        $class = 'Drupal\\' . $provider . '\\QyMailSend';
        $list = $class::create();
        break;
      case 'qyemail':
        $class = 'Drupal\\' . $provider . '\\QyMailListBuilder';
        $list = $class::create();
        break;
      default:
        $class = 'Drupal\\' . $provider . '\\LogsListBuilder';
        $list = $class::create($type);
        break;
    }
    return $list;
  }
}
