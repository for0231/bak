<?php
namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * 升级表单
 */
class UserUpgradeForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'product_upgrade_form';
  }

    /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $hostclient_id = null) {
    $hostclient = entity_load('hostclient', $hostclient_id);
    if($hostclient->getSimpleValue('status') != 3) {
      return $this->redirect('user.server');
    }
    $user = $this->currentUser();
    $product  = $hostclient->getObject('product_id');
    $original_business = \Drupal::service('hostclient.serverservice')->loadHostclientBusiness($hostclient_id); //原有业务

    $business_price_list = entity_load_multiple_by_properties('product_business_price', array('productId' => $product->id()));
    $user_business_price_list = array();//当前用户的业务价格列表
    $bids = array(); //设置了价格的业务Id列表
    foreach($business_price_list as $key => $business_price) {
      $bid = $business_price->getObjectId('businessId');
      if(!in_array($bid, $bids)) {
        $bids[] = $bid;
      }
      $user_business_price_list[$key] = $business_price;
    }

    $cabinet = $hostclient->getObject('cabinet_server_id')->getObject('cabinet_id');
    $rid = $cabinet->getObjectId('rid');
    $form['server_rid'] = array(
      '#type' => 'value',
      '#value' => $rid
    );

    $form['productId'] = array(
      '#type' => 'value',
      '#value' => $hostclient_id
    );
    $form['business_id_list'] = array(
      '#type' => 'value',
      '#value' => $bids
    );
    $form['user_business_price_id_list'] = array(
      '#type' => 'value',
      '#value' => array_keys($user_business_price_list)
    );
    $form['edit_block'] = array(
      '#type' => 'container',
      '#weight' => 1,
      '#attributes' => array(
        'class' => array('panel-edit')
      )
    );
    $form['edit_block']['product_name'] = array(
      '#type' => 'label',
      '#title' => t('Upgrade') . '&nbsp;&nbsp;' . $product->label(),
      '#prefix' => '<div class="title">',
      '#suffix' => '</div>'
    );

    //得到可升级的业务实体列表
    $list = entity_load_multiple('product_business', $bids);
    $business_list = array();
    foreach($list as $key => $business) {
      if($business->getSimpleValue('upgrade') == '1') {
        $business_list[$key] = $business;
      }
    }
    //构建业务分类
    $business_catalog = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('product_business_Catalog');
    foreach($business_catalog as $catalog) {
      foreach($business_list as $business) {
        if($catalog->tid == $business->getObjectId('catalog')) {
          $form['edit_block']['catalog_' . $catalog->tid] = array(
            '#type' => 'fieldset',
            '#title' => $catalog->name,
            '#open' => TRUE
          );
          break;
        }
      }
    }
   //构建业务项
   foreach($business_list as $key => $business) {
      $operate = $business->getSimpleValue('operate');
      $catalogId = $business->getObjectId('catalog');
      if($operate == 'edit_number') {
        $price = $this->getBusinessContentPrice($user_business_price_list, $key);
        $form['edit_block']['catalog_' . $catalogId]['business_' . $key] = array(
          '#type' => 'number',
          '#title' => $business->getSimpleValue('name'),
          '#size' => 10,
          '#min' => 0,
          '#ajax' => array(
            'callback' => '::loadCustomBusiness',
            'wrapper' => 'change_info_wrapper',
            'method' => 'html',
            'event' => 'change'
          ),
          '#field_suffix' => t('￥%price/month', array('%price' => $price))
        );
      } else if ($operate == 'select_content') {
        $old_business = $this->getOriginalBusiness($original_business, $key); //原来购买此业务的信息
        if(empty($old_business)) {
          $options = $this->getBusinessOptions($user_business_price_list, $business);
        } else {
          $old_business_price = $this->originalBusinessPrice($key, $old_business->business_content); //原来买此业务的价格
          $options = $this->getBusinessOptions($user_business_price_list, $business, $old_business_price);
        }
        if(empty($options)) {
          continue;
        }
        $lib = $business->getSimpleValue('resource_lib');
        if($lib == 'part_lib') {
          $ctl_group = array(
            '#type' => 'container',
            '#attributes' => array(
              'class' => array('one-to-many', 'clearfix')
            )
          );
          $ctl_group['label'] = array(
            '#type' => 'label',
            '#title' => $business->getSimpleValue('name')
          );
          $entity_type = $business->getSimpleValue('entity_type');
          if($entity_type == 'part_memory') {
            $server = $hostclient->getObject('server_id');
            $memory_slot = $server->get('mainboard')->entity->get('memory_slot')->value;
            $slot_arr = explode('*', $memory_slot);
            $slot_number = $slot_arr[0];
            $memry_number = count($server->get('memory')->getValue());
            $number = $slot_number - $memry_number;
            for($i=0; $i< $number;$i++) {
              $ctl = $this->getSelectCtrl($options);
              $ctl['#parents'] = array('business_' . $key, $i);
              $ctl_group[] = $ctl;
              $form['edit_block']['catalog_' . $catalogId]['business_' . $key] = $ctl_group;
            }
          } else if ($entity_type == 'part_harddisc') {
            $server = $hostclient->getObject('server_id');
            $slot_number = $server->get('chassis')->entity ? $server->get('chassis')->entity->get('disk_number')->value : '';
            $hard_number = count($server->get('harddisk')->getValue());
            $number = $slot_number - $hard_number;
            for($i=0; $i< $number;$i++) {
              $ctl = $this->getSelectCtrl($options);
              $ctl['#parents'] = array('business_' . $key, $i);
              $ctl_group[] = $ctl;
              $form['edit_block']['catalog_' . $catalogId]['business_' . $key] = $ctl_group;
            }
          }
        } else {
          $ctl = $this->getSelectCtrl($options);
          $ctl['#title'] = $business->getSimpleValue('name');
          $form['edit_block']['catalog_' . $catalogId]['business_' . $key] = $ctl;
        }
      } else if ($operate == 'select_and_number') {
        $options = $this->getBusinessOptions($user_business_price_list, $business);
        if(empty($options)) {
          continue;
        }
        $form['edit_block']['catalog_' . $catalogId]['select_number'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('container-inline', 'select-group')
          ),
        );
        $form['edit_block']['catalog_' . $catalogId]['select_number']['business_' . $key] = array(
          '#type' => 'select',
          '#title' => $business->getSimpleValue('name'),
          '#options' => $options
        );
        $form['edit_block']['catalog_' . $catalogId]['select_number']['business_' . $key . '_number'] = array(
          '#type' => 'number',
          '#size' => 5,
          '#min' => 1,
          '#ajax' => array(
            'callback' => '::loadCustomBusiness',
            'wrapper' => 'change_info_wrapper',
            'method' => 'html',
            'event' => 'change'
          )
        );
      }
    }
    $form['edit_block']['client_remark'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Guestbook'),
      '#max' => 1000
    );

    //显示详细信息
    $form['show_block'] = array(
      '#type' => 'container',
      '#weight' => 5,
    );
    //显示服务器信息
    $form['show_block']['server_info'] = array(
      '#type' => 'fieldset',
      '#title' => t('Server info')
    );
    $form['show_block']['server_info']['cpu'] = array(
      '#type' => 'label',
      '#title' => $product->getSimpleValue('display_cpu'),
      '#prefix' => '<div class="item"><span>CPU：</span>',
      '#suffix' => '</div>'
    );
    $form['show_block']['server_info']['memory'] = array(
      '#type' => 'label',
      '#title' => $product->getSimpleValue('display_memory'),
      '#prefix' => '<div class="item"><span>'. t('RAM') .'：</span>',
      '#suffix' => '</div>'
    );
    $form['show_block']['server_info']['harddisk'] = array(
      '#type' => 'label',
      '#title' => $product->getSimpleValue('display_harddisk'),
      '#prefix' => '<div class="item"><span>'. t('HDD') .'：</span>',
      '#suffix' => '</div>'
    );

    //显示原有业务
    $form['show_block']['default_info'] = array(
      '#type' => 'fieldset',
      '#title' => t('Original business')
    );
    foreach($original_business as $item) {
      $business = entity_load('product_business', $item->business_id);
      $bus_text = '';
      $value = $item->business_content;
      $value_arr = explode(',', $value);
      foreach($value_arr as $b_value) {
        $bus_text .= product_business_value_text($business, $b_value) . '<br/>';
      }
      $form['show_block']['default_info']['business_' . $business->id()] = array(
        '#type' => 'label',
        '#title' => $bus_text,
        '#prefix' => '<div class="item"><span>'. $business->label() .'：</span>',
        '#suffix' => '</div>'
      );
    }

    //计算自选业务价格
    $form['show_block']['change_info'] = array(
      '#type' => 'container',
      '#id' => 'change_info_wrapper',
      '#attributes' => array(
        'class' => array('change-info')
      ) 
    );
    $form['show_block']['change_info']['info'] = array(
      '#type' => 'container'
    );
    $form['show_block']['change_info']['info']['custom_info'] = array(
      '#type' => 'fieldset',
      '#title' => t('New business')
    );
    $custom_price = 0;
    foreach($business_list as $key => $business) {
      $operate = $business->getSimpleValue('operate');
      if($operate == 'edit_number') {
        $business_value = $form_state->getValue('business_' . $key);
        if(!empty($business_value)) {
          $price = $this->getBusinessContentPrice($user_business_price_list, $key);
          $custom_price += $price * $business_value;
          $form['show_block']['change_info']['info']['custom_info']['business_' . $key] = array(
            '#type' => 'label',
            '#title' => $business_value,
            '#prefix' => '<div class="item"><span>'. $business->label() .'：</span>',
            '#suffix' => '</div>'
          );
        }
      } else if ($operate == 'select_content') {
        $business_value = $form_state->getValue('business_' . $key);
        if(empty($business_value)) {
          continue;
        }
        if(is_array($business_value)) {
          $value_text = '';
          foreach($business_value as $value) {
            if(empty($value)) {
              continue;
            }
            $value_text .= product_business_value_text($business, $value) . '<br/>';
            $custom_price += $this->getBusinessContentPrice($user_business_price_list, $key, $value);
          }
          if(!empty($value_text)) {
            $form['show_block']['change_info']['info']['custom_info']['business_' . $key] = array(
              '#type' => 'label',
              '#title' => $value_text,
              '#prefix' => '<div class="item one-to-many"><span>'. $business->label() .'：</span>',
              '#suffix' => '</div>'
            );
          }       
        } else {
          $value_text = product_business_value_text($business, $business_value);
          $custom_price += $this->getBusinessContentPrice($user_business_price_list, $key, $business_value);
          $form['show_block']['change_info']['info']['custom_info']['business_' . $key] = array(
            '#type' => 'label',
            '#title' => $value_text, 
            '#prefix' => '<div class="item"><span>'. $business->label() .'：</span>',
            '#suffix' => '</div>'
          );
        }        
      } else if ($operate == 'select_and_number') {
        $business_value = $form_state->getValue('business_' . $key);
        $business_value_number = $form_state->getValue('business_' . $key . '_number');
        if(!empty($business_value) && !empty($business_value_number)) {
          $value_text = product_business_value_text($business, $business_value . ':' . $business_value_number);
          $price = $this->getBusinessContentPrice($user_business_price_list, $key, $business_value);
          $form['show_block']['change_info']['info']['custom_info']['business_' . $key] = array(
            '#type' => 'label',
            '#title' => $value_text,
            '#prefix' => '<div class="item"><span>'. $business->label() .'：</span>',
            '#suffix' => '</div>'
          );
          $custom_price += $price * $business_value_number;
        }
      }
    }

    if(!$custom_price){
      unset($form['show_block']['change_info']['info']['custom_info']);
    }

    $form['show_block']['change_info']['info']['custom_price'] = array(
      '#type' => 'label',
      '#title' => '￥' . $custom_price, 
      '#prefix' => '<div class="item first"><span>'. t('New business price') .'：</span>',
      '#suffix' => '</div>'
    );
    $form['show_block']['change_info']['info']['sum_price'] = array(
      '#type' => 'label',
      '#title' => '￥' . $custom_price,
      '#prefix' => '<div class="item"><span>'. t('Month new price') .'：</span>',
      '#suffix' => '</div>'
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add to cart'),
      '#id' => 'add_to_cart',
      '#weight' => 10
    );
    $form['#attached']['library'] = array('order/drupal.custom-business');
    return $form;
  }

  public static function loadCustomBusiness(array $form, FormStateInterface $form_state) {
    return $form['show_block']['change_info']['info'];
  }

  /**
   * 选择控件
   */
  private function getSelectCtrl($options) {
    return array(
      '#type' => 'select',
      '#options' => $options,
      '#ajax' => array(
        'callback' => '::loadCustomBusiness',
        'wrapper' => 'change_info_wrapper',
        'method' => 'html'
      )
    );
  }

  /**
   * 获取原有业务
   */
  private function getOriginalBusiness($original_business, $business_id) {
    foreach($original_business as $business) {
      if($business->business_id == $business_id) {
        return $business;
      }
    }
    return null;
  }


  /**
   * 获取业务内容的options;
   */
  private function getBusinessOptions($business_price_list, $business, $original_business_price = null) {
    $options = array('' => t('- Select -'));
    $business_id = $business->id();
    foreach($business_price_list as $business_price) {
      if($business_id == $business_price->getObjectId('businessId')) {
        $price = $business_price->getSimpleValue('price');
        if($business->getSimpleValue('combine_mode') == 'replace' && !empty($original_business_price)) {
           $original_price = $original_business_price->getSimpleValue('price');
           if($price <= $original_price) {
             continue;
           }
        }
        $lib = $business->getSimpleValue('resource_lib');
        $entity_type = $lib == 'create' ? 'product_business_content' : 'product_business_entity_content';
        $content = entity_load($entity_type, $business_price->getSimpleValue('business_content')); 
        $price = $business_price->getSimpleValue('price'); //@todo替换类型的业务价格的计算，和排除出价格少于原来业务的选项
        $text = $content->label();
        if($lib=='ipb_lib' || $lib == 'part_lib') {
          $value_entity = entity_load($content->getSimpleValue('entity_type'),$content->getSimpleValue('target_id'));
          $text = $value_entity->label();
        }
        if($price == '0.00') {
          $options[$content->id()] = $text;
        } else {
          $options[$content->id()] = $text .' '. strip_tags(t('￥%price/month', array('%price' => $price)));
        }
      }
    }
    return $options;
  }

  /**
   * 各到替换类，已购买的业务价格
   */
  private function originalBusinessPrice($business_id, $content_value) {
    $filter['businessId'] = $business_id;
    $filter['business_content'] = $content_value;
    $entities = entity_load_multiple_by_properties('product_business_price', $filter);
    return reset($entities);
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

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $rid = $form_state->getValue('server_rid');
    $config = \Drupal::config('common.global');
    $rule_ip = $config->get('room_rule_ip' . $rid);
    if(!empty($rule_ip) && $rule_ip > 1) {
      $business_id_list = $form_state->getValue('business_id_list');
      $business_list = entity_load_multiple('product_business', $business_id_list);
      foreach($business_list as $key => $business) {
        if($business->getSimpleValue('resource_lib') == 'ipb_lib' && $business->getSimpleValue('operate') == 'edit_number') {
          $business_value = $form_state->getValue('business_'. $key);
          if($business_value % $rule_ip != 0) {
            $form_state->setErrorByName('business_'. $key, $this->t('IP number must be a multiple of %rule_ip.', array('%rule_ip' => $rule_ip)));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $business_id_list = $form_state->getValue('business_id_list');
    $price_id_list = $form_state->getValue('user_business_price_id_list');
    $hostclient_id = $form_state->getValue('productId');
    $server_rid = $form_state->getValue('server_rid');
    $business_list = entity_load_multiple('product_business', $business_id_list);
    $user_business_price_list = entity_load_multiple('product_business_price', $price_id_list);
    $buyProduct = array();
    $buyProductBusiness = array();

    //构建自选业务
    $custom_price = 0;
    foreach($business_list as $key => $business) {
      $business_value = $form_state->getValue('business_'. $key);
      if(empty($business_value)) {
        continue;
      }
      $price = 0;
      $operate = $business->getSimpleValue('operate');
      if ($operate == 'edit_number') {
        $price = $this->getBusinessContentPrice($user_business_price_list, $key) * $business_value;
      } else if($operate == 'select_and_number') {
        $price = $this->getBusinessContentPrice($user_business_price_list, $key, $business_value);
        $business_value_number = $form_state->getValue('business_' . $key . '_number');
        $business_value .= ':' . $business_value_number;
        $price *= $business_value_number;
      } else {
        if(is_array($business_value)) {
          $values = '';
          foreach($business_value as $value) {
            if(empty($value)) {
              continue;
            }
            $price += $this->getBusinessContentPrice($user_business_price_list, $key, $value);
            if(empty($values)) {
              $values = $value;
            } else {
              $values .= ',' . $value;
            }
          }
          if(empty($values)) {
            continue;
          }
          $business_value = $values;
        } else {
          $price = $this->getBusinessContentPrice($user_business_price_list, $key, $business_value);
        }
      }
      $buyProductBusiness[] = array(
        'business_id' => $key,
        'business_content' => $business_value,
        'business_price' => $price,
        'business_default' => 0
      );
      $custom_price += $price;
    }
    if(!empty($buyProductBusiness)) {
      $buyProduct['action'] = 3;
      $buyProduct['product_id'] = $hostclient_id;
      $buyProduct['product_num'] = 1;
      $buyProduct['base_price'] = 0;
      $buyProduct['custom_price'] = $custom_price;
      $buyProduct['description'] = $form_state->getValue('client_remark');
      $buyProduct['uid'] = $user->id();
      $buyProduct['created'] = REQUEST_TIME;
      $buyProduct['product_limit'] = 1;
      $buyProduct['rid'] = $server_rid;
      \Drupal::service('user.cart')->add($buyProduct, $buyProductBusiness);
      $form_state->setRedirectUrl(new Url('user.cart'));
    } else {
      drupal_set_message(t('Please choose to upgrade business'), 'warning');
    }
  }
}
