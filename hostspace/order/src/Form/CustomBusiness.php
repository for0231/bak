<?php

/**
 * @file
 * Contains \Drupal\user\Form\CustomBusiness.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\order\ServerDistribution;

/**
 * Provides a user login form.
 */
class CustomBusiness extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_business_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $productId = null) {
    $user = $this->currentUser();
    $product = entity_load('product', $productId);
    $form['#title'] = $product->label(). 'Custom configure';
    if(empty($product) || !$product->getSimpleValue('front_Dispaly') || !$product->getSimpleValue('custom_business')) {
      return $this->redirect('server.product.list');
    }
    $base_price = product_user_price($product, $user);
    if($base_price == 0) {
      return $this->redirect('server.product.list');
    }

    $form['edit_block'] = array(
      '#type' => 'container',
      '#weight' => 1,
      '#attributes' => array(
        'class' => array('panel-edit')
      )
    );
    $form['edit_block']['product_name'] = array(
      '#type' => 'label',
      '#title' => $product->label(),
      '#prefix' => '<div class="title">',
      '#suffix' => '</div>'
    );
    $business_price_list = entity_load_multiple_by_properties('product_business_price', array('productId' => $productId));
    $user_business_price_list = array();//当前用户的业务价格列表
    $bids = array(); //设置了价格的业务Id列表
    foreach($business_price_list as $key => $business_price) {
      $bid = $business_price->getObjectId('businessId');
      if(!in_array($bid, $bids)) {
        $bids[] = $bid;
      }
      $user_business_price_list[$key] = $business_price;
    }
    $form['productId'] = array(
      '#type' => 'value',
      '#value' => $productId
    );
    $form['business_id_list'] = array(
      '#type' => 'value',
      '#value' => $bids
    );
    $form['user_business_price_id_list'] = array(
      '#type' => 'value',
      '#value' => array_keys($user_business_price_list)
    );
    //得到业务实体列表
    $business_list = entity_load_multiple('product_business', $bids);
    //构建业务分类
    $business_catalog = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree('product_business_Catalog');
    foreach($business_catalog as $catalog) {
      foreach($business_list as $business) {
        if($catalog->tid == $business->getObjectId('catalog')) {
          $form['edit_block']['catalog_' . $catalog->tid] = array(
            '#type' => 'fieldset',
            '#title' => t($catalog->name),
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
        $form['edit_block']['catalog_' . $catalogId]['business_' . $key] = array(
          '#type' => 'select',
          '#title' => $business->getSimpleValue('name'),
          '#options' => $this->getBusinessOptions($user_business_price_list, $business),
          '#ajax' => array(
            'callback' => '::loadCustomBusiness',
            'wrapper' => 'change_info_wrapper',
            'method' => 'html'
          )
        );
      } else if ($operate == 'select_and_number') {
        $form['edit_block']['catalog_' . $catalogId]['select_number'] = array(
          '#type' => 'container',
          '#attributes' => array(
            'class' => array('container-inline', 'select-group')
          ),
        );
        $form['edit_block']['catalog_' . $catalogId]['select_number']['business_' . $key] = array(
          '#type' => 'select',
          '#title' => $business->getSimpleValue('name'),
          '#options' => $this->getBusinessOptions($user_business_price_list, $business)
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
    $room_config = \Drupal::config('common.global');
    if ($room_config->get('is_district_room_id')) {
      $rids = $product->getSimplevalue('rids');
      $rids_arr = json_decode($rids);
      $option_room = array();
      if (!empty($rids_arr)) {
        foreach ($rids_arr as $k => $v) {
          if ($v) {
            $option_room[$k] = entity_load('room', $k)->label();
          }
        }
      }
      if (!empty($option_room)) {
        reset($option_room);
        $default_key = key($option_room);
        $form['edit_block']['room'] = array(
          '#type' => 'radios',
          '#title' => t('机房'),
          '#options' => $option_room,
          '#default_value' => $default_key,
        );
      }
    } else {
        $form['edit_block']['room'] = array(
          '#type' => 'hidden',
          '#value' => 1,
        );
    }
    $form['edit_block']['client_remark'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Guestbook'),
      '#max' => 1000
    );
    $form['edit_block']['buy_number'] = array(
      '#type' => 'number',
      '#title' => t('Purchase quantity'),
      '#default_value' => 1,
      '#required' => true,
      '#min' => 1,
      '#ajax' => array(
        'callback' => '::loadCustomBusiness',
        'wrapper' => 'change_info_wrapper',
        'method' => 'html',
        'event' => 'change'
      )
    );

    //显示详细信息
    $form['show_block'] = array(
      '#type' => 'container',
      '#weight' => 5,
      '#attributes' => array(
        'class' => array('panel-show')
      )
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
    //显示默认业务
    $form['show_block']['default_info'] = array(
      '#type' => 'fieldset',
      '#title' => t('Default business')
    );
    $default_business = \Drupal::service('product.default.business')->getListByProduct($productId);
    foreach($default_business as $item) {
      $business = entity_load('product_business', $item->businessId);
      $value = product_business_value_text($business, $item->business_content);
      $form['show_block']['default_info']['business_' . $business->id()] = array(
        '#type' => 'label',
        '#title' => $value,
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
      '#title' => t('Custom business')
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
        if(!empty($business_value)) {
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

    $buy_number_value = $form_state->getValue('buy_number');
    $buy_number = empty($buy_number_value) ? 1 : $buy_number_value;
    $form['show_block']['change_info']['info']['base_price'] = array(
      '#type' => 'label',
      '#title' => '￥'. ($base_price * $buy_number),
      '#prefix' => '<div class="item first"><span>'. t('Base price') .'：</span>',
      '#suffix' => '</div>'
    );
    $form['show_block']['change_info']['info']['custom_price'] = array(
      '#type' => 'label',
      '#title' => '￥' . ($custom_price * $buy_number),
      '#prefix' => '<div class="item"><span>'. t('Custom price') .'：</span>',
      '#suffix' => '</div>'
    );

    $form['show_block']['change_info']['info']['sum_price'] = array(
      '#type' => 'label',
      '#title' => '￥' . (($base_price + $custom_price) * $buy_number),
      '#prefix' => '<div class="item"><span>'. $this->t('Month price') .'：</span>',
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
   * 获取业务内容的options;
   */
  private function getBusinessOptions($business_price_list, $business) {
    $options = array('' => t('- Select -'));
    $business_id = $business->id();
    foreach($business_price_list as $business_price) {
      if($business_id == $business_price->getObjectId('businessId')) {
        $lib = $business->getSimpleValue('resource_lib');
        $entity_type = $lib == 'create' ? 'product_business_content' : 'product_business_entity_content';
        $content = entity_load($entity_type, $business_price->getSimpleValue('business_content')); 
        $price = $business_price->getSimpleValue('price');
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

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $dis = ServerDistribution::createInstance();
    $productId = $form_state->getValue('productId');
    $rid = $form_state->getValue('room');
    $product = entity_load('product', $productId);
    $idc_stock = $dis->getServerStock($product->getObjectId('server_type'), $rid);
    $buy_number = $form_state->getValue('buy_number');
    if($buy_number > $idc_stock) {
      $form_state->setErrorByName('buy_number',$this->t('Sorry the product %product inventory shortage, the current stock of %number Units', array(
        '%product' => $product->label(),
        '%number' => $idc_stock
      )));
    }
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
    $productId = $form_state->getValue('productId');
    $buy_number = $form_state->getValue('buy_number');
    $product = entity_load('product', $productId);
    $business_list = entity_load_multiple('product_business', $business_id_list);
    $user_business_price_list = entity_load_multiple('product_business_price', $price_id_list);
    $buyProduct = array();
    $buyProductBusiness = array();
    //构建默认业务
    $default_business = \Drupal::service('product.default.business')->getListByProduct($productId);
    foreach($default_business as $item) {
      $business_value = $item->business_content;
      $buyProductBusiness[] = array(
        'business_id' => $item->businessId,
        'business_content' => $business_value,
        'business_price' => 0,
        'business_default' => 1
      );
    }
    //构建自选业务
    $custom_price = 0;
    foreach($business_list as $key => $business) {
      $business_value = $form_state->getValue('business_'. $key);
      if(!empty($business_value)) {
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
          $price = $this->getBusinessContentPrice($user_business_price_list, $key, $business_value); 
        }
        $buyProductBusiness[] = array(
          'business_id' => $key,
          'business_content' => $business_value,
          'business_price' => $price,
          'business_default' => 0
        );
        $custom_price += $price;
      }
    }
    $buyProduct['action'] = 1;
    $buyProduct['product_id'] = $productId;
    $buyProduct['product_num'] = $buy_number;
    $buyProduct['base_price'] = product_user_price($product, $user);
    $buyProduct['custom_price'] = $custom_price;
    $buyProduct['description'] = $form_state->getValue('client_remark');
    $buyProduct['uid'] = $user->id();
    $buyProduct['created'] = REQUEST_TIME;
    $buyProduct['product_limit'] = 1;
    $buyProduct['rid'] = $form_state->getValue('room');
    \Drupal::service('user.cart')->add($buyProduct, $buyProductBusiness);

    $form_state->setRedirectUrl(new Url('user.cart'));
  }
}
