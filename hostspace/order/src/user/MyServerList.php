<?php
/**
 * @file
 * Contains \Drupal\order\user\UserServerListForm.
 */

namespace Drupal\order\user;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Url;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\EntityStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class MyServerList {
  use StringTranslationTrait;

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  public function __construct(QueryFactory $query_factory, EntityStorageInterface $storage) {
    $this->queryFactory = $query_factory;
    $this->storage = $storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.query'),
      $container->get('entity.manager')->getStorage('hostclient')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $qys = array()) {
    $form['search'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('server-search')
      )
    );
    $form['search']['date_limit'] = array(
      '#type' => 'select',
      '#options' => array(
        '' => $this->t('Please select a time range'),
        '7' => $this->t('In 7 days expire'),
        '15' => $this->t('In 15 days expire')
      )
    );

    $product_options = array('' => t('All server'));
    $hostServer = \Drupal::service('hostclient.serverservice');
    $product_ids = $hostServer->getMyHaveProduct();
    if(!empty($product_ids)) {
      $product_list = entity_load_multiple('product', $product_ids);
      foreach($product_list as $key =>$product) {
        $product_options[$key] = $product->label();
      }
    }
    $form['search']['product_kind'] = array(
      '#type' => 'select',
      '#options' => $product_options
    );

    $form['search']['server_ip'] = array(
      '#type' => 'textfield',
      '#placeholder' => $this->t('Server ip'),
      '#size' => 20
    );
    $form['search']['search_submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#submit' => array(array($this, 'searchSubmitForm'))
    );

    if (!empty($_SESSION['user_hostclient_filter'])) {
      $allempty = true;
      $fields = array('date_limit', 'product_kind', 'server_ip');
      foreach($fields as $field) {
        if($_SESSION['user_hostclient_filter'][$field] != '') {
          $form['search'][$field]['#default_value'] = $_SESSION['user_hostclient_filter'][$field];
          $allempty = false;
        }
      }
      if($allempty) {
        $_SESSION['user_hostclient_filter'] = array();
      }
    }
    if (!empty($_SESSION['user_hostclient_filter'])) {
      $form['search']['reset'] = array(
        '#type' => 'submit',
        '#value' => $this->t('Reset'),
        '#limit_validation_errors' => array(),
        '#submit' => array(array($this, 'resetForm')),
      );
    }
    $form['servar_table'] = array(
      '#type' => 'tableselect',
      '#header' => $this->buildHeader(),
      '#empty' => t('No server')
    );

    $hostclients = $this->loadHostclient();
    foreach($hostclients as $key => $hostclient) {
      $form['servar_table']['#options'][$key] = $this->buildRow($hostclient, $qys);
    }
    $form['pager'] = array('#type' => 'pager');
    $form['server_op'] = array(
      '#type' => 'actions'
    );
    $form['server_op']['renew'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Renew'),
      '#validate' => array(array($this, 'renewValidate')),
      '#submit' => array(array($this, 'renewSubmitForm'))
    );
    $form['server_op']['stop'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Stop'),
      '#id' => 'stop-multi',
      '#validate' => array(array($this, 'stopValidate')),
      '#submit' => array(array($this, 'stopServerSubmit'))
    );
    return $form;
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
   * 查询数据
   */
  private function loadHostclient() {
    $hids = array();
    if(empty($_SESSION['user_hostclient_filter'])) {
      $conditions = array(
        'client_uid' => \Drupal::currentUser()->id(),
        'trial' => 0
      );
      $conditions['status'] = array('value' => array(2,3), 'op' => 'IN');
      $hids = \Drupal::service('hostclient.serverservice')->getServerByCondition($conditions, array());
    } else {
      $date_limit = $_SESSION['user_hostclient_filter']['date_limit'];
      $product_kind = $_SESSION['user_hostclient_filter']['product_kind'];
      $server_ip = $_SESSION['user_hostclient_filter']['server_ip'];
      $conditions = array(
        'client_uid' => \Drupal::currentUser()->id(),
        'trial' => 0
      );
      $ip_condition = array();
      if($date_limit) {
        $time = strtotime('+'. $date_limit .' day', REQUEST_TIME);
        $conditions['service_expired_date'] = array('value' => $time, 'op' => '<=');
      }
      if($product_kind) {
        $conditions['product_id'] = $product_kind;
      }
      if($server_ip) {
        $ip_condition['ipb'] = $server_ip;
      }
      $conditions['status'] = array('value' => array(2,3), 'op' => 'IN');
      $hids = \Drupal::service('hostclient.serverservice')->getServerByCondition($conditions, $ip_condition);
    }
    return $this->storage->loadMultiple($hids);
  }

  /**
   * 构建行
   */
  private function buildRow($hostclient, $qys = array()) {
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
          $str_ip .= '<span class="qy-logo">牵</span>';
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
    $config = \Drupal::config('common.global');
    $operations = array();
    $operations['detail'] = array(
      'title' => $this->t('Detail'),
      'url' => new Url('user.server.detail', array('hostclient'=>$hostclient->id()))
    );

    if($hostclient->getSimpleValue('status') == 3) {
      if ($config->get('server_panel')) {
        $operations['panel'] = array(
          'title' => $this->t('Panel'),
          'url' => new Url('user.server.panel', array('hostclient' => $hostclient->id()))
        );
      }
      $operations['upgrade'] = array(
        'title' => $this->t('Upgrade'),
        'url' => new Url('user.server.upgrade', array('hostclient_id' => $hostclient->id()))
       );
      /*$operations['declare_malfunction'] = array(
        'title' => $this->t('Declare malfunction'),
        'url' => new Url('question.add_form', array('hid'=>$hostclient->id()))
       );*/
      $operations['remove'] = array(
        'title' => $this->t('Change IP'),
        'url' => new Url('user.server.remove_ip', array('hostclient' => $hostclient->id()))
       );
    }
    return $operations;
  }

  public function searchSubmitForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['user_hostclient_filter']['date_limit'] = $form_state->getvalue('date_limit');
    $_SESSION['user_hostclient_filter']['product_kind'] = $form_state->getvalue('product_kind');
    $_SESSION['user_hostclient_filter']['server_ip'] = $form_state->getvalue('server_ip');
  }
  /**
   * Resets the filter form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $_SESSION['user_hostclient_filter'] = array();
  }

  /**
   * {@inheritdoc}
   */
  public function renewValidate(array &$form, FormStateInterface $form_state) {
    $hids = $form_state->getValue('servar_table');
    $select_hids = array();
    foreach($hids as $hid) {
      if($hid) {
        $select_hids[] = $hid;
      }
    }
    if(empty($select_hids)) {
      $form_state->setErrorByName('servar_table',$this->t('Please select the renew to server')); //请选择要续费的服务器
    } else {
      foreach($select_hids as $hid) {
        $hostclient = entity_load('hostclient', $hid);
        if($hostclient->getSimpleValue('status') != 3) {
          $form_state->setErrorByName('servar_table['. $hid .']',$this->t('The server:%server has not hit the shelves, cannot renew', array(
            '%server' => $hostclient->getObject('server_id')->label()
          ))); //服务器没有上架，不能续费
        }
      }
      $form_state->select_server = $select_hids;
    }
  }

  /**
   * 续费提交
   * {@inheritdoc}
   */
  public function renewSubmitForm(array &$form, FormStateInterface $form_state) {
    $hostclinet_service = \Drupal::service('hostclient.serverservice');
    $default_service = \Drupal::service('product.default.business');

    $user = \Drupal::currentUser();
    $roles = $user->getRoles();

    $renew_hids = $form_state->select_server;
    foreach($renew_hids as $hid) {
      $hostclient = entity_load('hostclient', $hid);
      $product_id = $hostclient->getObjectId('product_id');

      //当前用户的业务价格列表
      $user_business_price_list = array();
      $bids = array();
      $business_price_list = entity_load_multiple_by_properties('product_business_price', array('productId' => $product_id));
      foreach($business_price_list as $key => $business_price) {
        $bid = $business_price->getObjectId('businessId');
        if(!in_array($bid, $bids)) {
          $bids[] = $bid;
        }
        $user_business_price_list[$key] = $business_price;
      }

      $default_business = $default_service->getListByProduct($product_id);
      $business_list = $hostclinet_service->loadHostclientBusiness($hid);
      $custom_business = $this->removeDefaultBusiness($business_list, $default_business); 
      //组装数据
      $buyProduct = array();
      $buyProductBusiness = array(); 
      foreach($default_business as $item) {
        $buyProductBusiness[] = array(
          'business_id' => $item->businessId,
          'business_content' => $item->business_content,
          'business_price' => 0,
          'business_default' => 1
        );
      } 
      $custom_price = 0;
      foreach($custom_business as $business) {
        $business_id = $business->business_id;
        $business_obj = entity_load('product_business', $business_id);
        $price = 0;
        $operate = $business_obj->getSimpleValue('operate');
        if ($operate == 'edit_number') {
          $price = $this->getBusinessContentPrice($user_business_price_list, $business_id) * $business->business_content;
        } else if($operate == 'select_and_number') {
          $value = $business->business_content;
          $value_arr = explode(',', $value);
          foreach($value_arr as $item) {
            $item_arr = explode(':', $item);
            $unit_price = $this->getBusinessContentPrice($user_business_price_list, $business_id, $item_arr[0]);
            $unit_price *= $item_arr[1];
            $price += $unit_price;
          }
        } else {
          $value = $business->business_content;
          $value_arr = explode(',', $value);
          foreach($value_arr as $item) {
            $price += $this->getBusinessContentPrice($user_business_price_list, $business_id, $item);
          }
        }
        $buyProductBusiness[] = array(
          'business_id' => $business_id,
          'business_content' => $business->business_content,
          'business_price' => $price,
          'business_default' => 0
        );
        $custom_price += $price;
      }

      $product = $hostclient->getObject('product_id');
      $buyProduct['action'] = 2;
      $buyProduct['product_id'] = $hostclient->id();
      $buyProduct['product_num'] = 1;
      $buyProduct['base_price'] = product_user_price($product, $user);
      $buyProduct['custom_price'] = $custom_price; 
      $buyProduct['description'] = '';
      $buyProduct['uid'] = $user->id();
      $buyProduct['created'] = REQUEST_TIME;
      $buyProduct['product_limit'] = 1;

      \Drupal::service('user.cart')->add($buyProduct, $buyProductBusiness);
    }
    $form_state->setRedirectUrl(new Url('user.cart'));
  }

  /**
   * {@inheritdoc}
   */
  public function stopValidate(array &$form, FormStateInterface $form_state) {
    $hids = $form_state->getValue('servar_table');
    $select_hids = array();
    foreach($hids as $hid) {
      if($hid) {
        $select_hids[] = $hid;
      }
    }
    if(empty($select_hids)) {
      $form_state->setErrorByName('servar_table',$this->t('Please choose to stop the server')); //请选择要续费的服务器
    } else {
      foreach($select_hids as $hid) {
        $hostclient = entity_load('hostclient', $hid);
        if($hostclient->getSimpleValue('status') != 3) {
          $form_state->setErrorByName('servar_table['. $hid .']',$this->t('The server:%server has not hit the shelves, cannot stop', array(
            '%server' => $hostclient->getObject('server_id')->label()
          ))); //服务器没有上架，不能续费
        }
      }
      $form_state->select_server = $select_hids;
    }
  }

  /**
   * 停用
   * {@inheritdoc}
   */
  public function stopServerSubmit(array &$form, FormStateInterface $form_state) {
    $hostclient_service = \Drupal::service('hostclient.serverservice');
    $hids = $form_state->select_server;
    foreach($hids as $hid) {
      $entity = entity_load('hostclient', $hid);
      $entity->set('status', 4);

      $stop_info['apply_uid'] = \Drupal::currentUser()->id();
      $stop_info['apply_date'] = REQUEST_TIME;
      $stop_info['operation'] = 0;
      $stop_info['client_uid'] = $entity->getObjectId('client_uid');
      $stop_info['storage_date'] = REQUEST_TIME;
      $stop_info['status'] = 0;
      $stop_info['hostclient_id'] = $entity->id();
      $hostclient_service->addStopInfo($stop_info);
      $entity->save();
      // 添加服务器停用工单
    }
    drupal_set_message(t('The server stop success.'), 'warning');
  }

  /**
   * 去掉默认业务(默认业务不能大于用户所购买的业务)
   */
  private function removeDefaultBusiness($business_list, $default_business) {
    $custom_business = array();
    //转为数组
    $def_arr = array();
    foreach($default_business as $def_business) {
      $def_arr[$def_business->businessId] = $def_business;
    }
    //去掉默认
    foreach($business_list as $cur_business) {
      if(array_key_exists($cur_business->business_id, $def_arr)) {
        $def_business = $def_arr[$cur_business->business_id];
        $business_obj = entity_load('product_business', $def_business->businessId);
        $operate = $business_obj->getSimpleValue('operate');
        if($operate == 'edit_number') {
          $number = $cur_business->business_content - $def_business->business_content;
          if($number > 0) {
            $cur_business->business_content = $number;
            $custom_business[] = $cur_business;
          }
        } else if ($operate == 'select_content') {
          $combine_mode = $business_obj->getSimpleValue('combine_mode');
          if($combine_mode == 'add') {
            $business_values = $cur_business->business_content;
            $value_arr = explode(',', $business_values);
            $c_values =array();
            foreach($value_arr as $value) { //需要测试有三个值的
              if($value != $def_business->business_content) {
                $c_values[] =  $value;
              }
            }
            if(!empty($c_values)) {
              $cur_business->business_content = implode(',', $c_values);;
              $custom_business[] =  $cur_business;
            }
          } else {
            if($cur_business->business_content != $def_business->business_content) { //当前业务不是默认业务
              $custom_business[] = $cur_business;
            }
          }
        } else if ($operate == 'select_and_number') {
          $combine_mode = $business_obj->getSimpleValue('combine_mode');
          if($combine_mode == 'add') {
            $business_values = $cur_business->business_content;
            $value_arr = explode(',', $business_values);
            $def_business_value = $def_business->business_content;
            $def_value_arr = explode(':', $def_business_value);
            $c_values = array();
            foreach($value_arr as $value) {
              $item_value = explode(':', $value);
              if($def_value_arr[0] == $item_value[0]) {
                $number = $item_value[1] - $def_value_arr[1];
                if($number > 0) {
                  $c_values[] = $item_value[0] . ':' . $number;
                }
              } else {
                $c_values[] = $value;
              }
            }
            if(!empty($c_values)) {
              $cur_business->business_content = implode(',', $c_values);
              $custom_business[] =  $cur_business;
            }
          } else {
            if($cur_business->business_content != $def_business->business_content) {
              $custom_business[] = $cur_business;
            }
          }
        }
      } else {
        $custom_business[] = $cur_business;
      }
    }
    return $custom_business;
  }

  /**
   * 获取特定业务特定内容的价格
   */
  private function getBusinessContentPrice($business_price_list, $business_id, $value = '') {
    $price = 0;
    foreach($business_price_list as $business_price) {
      $businessId = $business_price->getObjectId('businessId');
      $business_value = $business_price->getSimpleValue('business_content');
      if($business_id == $businessId && $value == $business_value) {
        $price = $business_price->getSimpleValue('price');
        break;
      }
    }
    return $price;
  }
}
