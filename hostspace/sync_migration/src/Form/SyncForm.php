<?php

/**
 * @file
 * Contains \Drupal\sync_migration\Form\SyncForm.
 */

namespace Drupal\sync_migration\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Http\Client;
use Drupal\sync_migration\SyncUser;
use Drupal\sync_migration\SynIpData;
use Drupal\sync_migration\SynServerData;
use Drupal\sync_migration\SyncIdcData;
use Drupal\sync_migration\SyncProductData;
use Drupal\sync_migration\SynPublishSystem;
use Drupal\sync_migration\SyncOnlineServer;

class SyncForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sync_data_hostclient_form';
  }

  private function getBaseUrl() {
    return 'http://www.hostspaces.net';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => '同步请求路径',
      '#default_value' => $this->getBaseUrl(),
      '#disabled' => true
    );
    $config = \Drupal::config('sync_migration.settings');
    $user_disable = true;
    if(!$config->get('sync_user')) {
       $user_disable = false;
    }
    $form['syn_user_data'] = array(
      '#type' => 'submit',
      '#name' => 'syncuserdata',
      '#value' => '同步用户数据',
      '#submit' => array('::userData'),
      '#disabled' => $user_disable
    );

    $ipm_disable = true;
    if($config->get('sync_user') && !$config->get('sync_mip')) {
      $ipm_disable = false;
    }
    $form['syn_ipm_data'] = array(
      '#type' => 'submit',
      '#name' => 'synipmdata',
      '#value' => '同步管理IP数据',
      '#submit' => array('::ipmData'),
      '#disabled' => $ipm_disable
    );

    $ips_disable = true;
    if($config->get('sync_user') && !$config->get('sync_sip')) {
      $ips_disable = false;
    }
    $form['syn_ips_data'] = array(
      '#type' => 'submit',
      '#name' => 'synipsdata',
      '#value' => '同步交换机IP数据',
      '#submit' => array('::ipsData'),
      '#disabled' => $ips_disable,
    );

    $ipb_disable = true;
    if($config->get('sync_user') && !$config->get('sync_bip')) {
      $ipb_disable = false;
    }
    $form['syn_ipb_data'] = array(
      '#type' => 'submit',
      '#name' => 'synipbdata',
      '#value' => '同步业务IP数据',
      '#submit' => array('::ipbData'),
      '#disabled' => $ipb_disable
    );

    $server_disable = true;
    if($config->get('sync_mip') && !$config->get('sync_server')) {
      $server_disable = false;
    }
    $form['syn_server_data'] = array(
      '#type' => 'submit',
      '#name' => 'synserverdata',
      '#value' => '同步服务器数据',
      '#submit' => array('::serverData'),
      '#disabled' => $server_disable
    );

    $idc_disable = true;
    if($config->get('sync_sip') && $config->get('sync_server') && !$config->get('sync_idc')) {
      $idc_disable = false;
    }
    $form['syn_idc_data'] = array(
      '#type' => 'submit',
      '#name' => 'synidcdata',
      '#value' => '同步机房数据',
      '#submit' => array('::idcData'),
      '#disabled' => $idc_disable
    );

    $product_disable = true;
    if($config->get('sync_server') && !$config->get('sync_product')) {
      $product_disable = false;
    }
    $form['syn_product_data'] = array(
      '#type' => 'submit',
      '#name' => 'synproductdata',
      '#value' => '同步产品数据',
      '#submit' => array('::productData'),
      '#disabled' => $product_disable
    );

    $sync_online_server = true;
    if($config->get('sync_product') && $config->get('sync_bip') && !$config->get('sync_online_server')) {
      $sync_online_server = false;
    }
    $form['sync_online_server'] = array(
      '#type' => 'submit',
      '#name' => 'synconlineserver',
      '#value' => '在线服务器',
      '#submit' => array('::onlineServerData'),
      '#disabled' => $sync_online_server
    );

    $news_disable = true;
    if($config->get('sync_user') && !$config->get('sync_news')) {
      $news_disable = false;
    }
    $form['syn_news_data'] = array(
      '#type' => 'submit',
      '#name' => 'synnewsdata',
      '#value' => '同步新闻数据',
      '#submit' => array('::newsData'),
      '#disabled' => $news_disable
    );

    $article_disable = true;
    if($config->get('sync_user') && !$config->get('sync_article')) {
      $article_disable = false;
    }
    $form['syn_article_data'] = array(
      '#type' => 'submit',
      '#name' => 'synarticledata',
      '#value' => '同步文章数据',
      '#submit' => array('::articleData'),
      '#disabled' => $article_disable
    );
    return $form;
  }

  /**
   * 同步用户数据
   */
  public function userData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $base_url = $this->getBaseUrl();
    $obj = new SyncUser($base_url);
    $obj->synUserData();
    drupal_set_message('用户数据迁移成功！');
  }

  /**
   * 同步管理IP数据
   */
  public function ipmData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $base_url = $this->getBaseUrl();
    $obj = new SynIpData($base_url);
    $obj->synIPMData();
    drupal_set_message('管理IP数据迁移成功！');
  }

  /**
   * 同步交换机IP数据
   */
  public function ipsData(array &$form, FormStateInterface $form_state) {
    $base_url = $this->getBaseUrl();
    $obj = new SynIpData($base_url);
    $obj->synIPSData();
    drupal_set_message('交换机IP数据迁移成功！');
  }

  /**
   * 同步业务IP数据
   */
  public function ipbData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $base_url = $this->getBaseUrl();
    $obj = new SynIpData($base_url);
    $obj->synIPBData();
    drupal_set_message('业务IP数据迁移成功！');
  }

  /**
   * 同步服务器数据
   */
  public function serverData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $base_url = $this->getBaseUrl();
    $obj = new SynServerData($base_url);
    $obj->synServerData();
    drupal_set_message('服务器数据迁移成功！');
  }

  /**
   * 同步机房数据
   */
  public function idcData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $config = \Drupal::config('sync_migration.settings');
    $json_str = $config->get('sync_server_ip_list');
    $ip_server = json_decode($json_str, true);
    $obj = new SyncIdcData();
    $obj->syncIdcData($ip_server);
    drupal_set_message('机房数据迁移成功！');
  }

  /**
   * 同步产品数据
   */
  public function productData(array &$form, FormStateInterface $form_state) {
    $base_url = $this->getBaseUrl();
    $obj = new SyncProductData($base_url);
    $obj->syncProductData();
    drupal_set_message('产品数据迁移成功！');
  }

  //同步在线服务器
  public function onlineServerData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $config = \Drupal::config('sync_migration.settings');
    //管理ip对应该的id
    $json_str = $config->get('sync_server_ip_list');
    $ip_server = json_decode($json_str, true);
    //产品业务对应的id
    $json_business = $config->get('sync_product_business_list');
    $business_ids = json_decode($json_business, true);
    //业务IP对应的id
    $json_bip = $config->get('sync_bip_id_list');
    $bip_id_list = json_decode($json_bip, true);

    $base_url = $this->getBaseUrl();
    $obj = new SyncOnlineServer($base_url);
    $obj->syncOnlineServerData($ip_server, $bip_id_list, $business_ids);
    drupal_set_message('在线服务器迁移成功！');
  }

  /**
   * 同步新闻数据
   */
  public function newsData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $base_url = $this->getBaseUrl();
    $obj = new SynPublishSystem($base_url);
    $obj->synNewsData();

    drupal_set_message('新闻数据迁移成功！');
  }

  /**
   * 同步文章数据
   */
  public function articleData(array &$form, FormStateInterface $form_state) {
    set_time_limit(0);
    $base_url = $this->getBaseUrl();
    $obj = new SynPublishSystem($base_url);
    $obj->synArticleData();

    drupal_set_message('文章数据迁移成功！');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
