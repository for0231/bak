<?php
/**
 * @file
 * Contains \Drupal\order\user\MyProcessingList.
 */

namespace Drupal\order\user;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyProcessingList {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $qys = array()) {
    $tral_server = entity_load_multiple_by_properties('hostclient', array('client_uid' => \Drupal::currentUser()->id(), 'trial' => 0, 'status' => 1));
    if($tral_server) {
      $form['processing_table'] = array(
        '#type' => 'table',
        '#header' => $this->buildHeader(),
        '#empty' => t('No server')
      );
      foreach($tral_server as $key =>$hostclient) {
        $form['processing_table']['#rows'][$key] = $this->buildRow($hostclient, $qys);
      }
      return $form;
    }
    return array();
  }

  private function buildHeader() {
    $header['server_code'] = $this->t('Server code');
    $header['business_ip'] = $this->t('Business ip');
    $header['product_name'] = $this->t('Product name');
    $header['expired'] = $this->t('Expiration time');
    $header['status'] = $this->t('Status');
    $header['operations'] = $this->t('Operations');
    return $header;
  }

  /**
   * 构建行
   */
  private function buildRow($hostclient, $qys) {
    $row['server_code'] = $this->t('Waiting for the distribution of');
    if($server = $hostclient->getObject('server_id')) {
      $row['server_code'] = $server->label();
    }
    //业务IP
    $ipb_values = $hostclient->get('ipb_id')->getValue();
    if(empty($ipb_values[0])) {
      $row['business_ip'] = $this->t('Waiting for the distribution of');
    } else {
      $html = '<ul>';
      $i = 0;
      foreach($ipb_values as $value) {
        $ipb = entity_load('ipb', $value['target_id']);
        $str_ip = trim($ipb->label());
        if(array_key_exists($str_ip, $qys)) {
          $str_ip .= '<span>牵</span>'; 
        }
        if($i < 3) {
          $html .= '<li>' . $str_ip .'</li>';
        } else {
          $html .= '<li class="more-ipb" style="display:none">' . $str_ip. '</li>';
        }
        $i++;
      }
      $html .= '</ul>';
      if($i<=3) {
        $row['business_ip'] = SafeMarkup::format($html, array());
      } else {
        $row['business_ip'] = array(
          'class' => 'show-more',
          'js-open' => 'close',
          'style' => 'cursor: pointer;',
          'title' => $this->t('Double click the show all IP'),
          'data' => SafeMarkup::format($html, array())
        );
      }
    }
    $row['product_name'] = $hostclient->getObject('product_id')->label();
    $row['expired'] = '-';
    if($hostclient->getSimpleValue('service_expired_date')) {
      $row['expired'] = format_date($hostclient->getSimpleValue('service_expired_date'), 'custom' ,'Y-m-d H:i:s');
    }
    $row['status'] = hostClientStatus()[$hostclient->getSimplevalue('status')];
    $row['operations'] = array('data' => array(
      '#type' => 'operations',
      '#links' => $this->getOperations($hostclient)
    ));
    return $row;
  }

  /**
   * 操作列表
   */
  private function getOperations($hostclient) {
    $operations = array();
    $operations['detail'] = array(
      'title' => $this->t('Detail'),
      'url' => new Url('user.server.detail', array('hostclient'=>$hostclient->id()))
    );
    return $operations;
  }
}
