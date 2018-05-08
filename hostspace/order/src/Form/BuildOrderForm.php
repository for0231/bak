<?php

/**
 * @file
 * Contains \Drupal\order\Form\BuildOrderForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\order\ServerDistribution;
use Drupal\hostlog\HostLogFactory;

class BuildOrderForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    if(empty($_SESSION['order_products'])) {
      throw new EnforcedResponseException($this->redirect('user.cart'));
    }
    $product_list = $this->afreshPrice($_SESSION['order_products']);
    $cart_service = \Drupal::service('user.cart');
    foreach($product_list as $product) {
      if($product->action != 2) {
        continue;
      }
      $change = $this->businessChange($product); //续费检查业务是否已经发生变化。
      if($change) {
        drupal_set_message(t('Business changes, please try to place an order'), 'warning');
        $_SESSION['order_products'] = array();
        $cart_service->delete($product->cid);
        throw new EnforcedResponseException($this->redirect('user.cart'));
      }
    }
    $form['product_list'] = array(
      '#type' => 'value',
      '#value' => $product_list
    );
    $form['alias_order'] = array(
      '#type' => 'textfield',
      '#title' => t('Order title'),
      '#title_display' => 'invisible',
      '#description' => t('If you leave a blank, we will automatically with the order number as the order header'),
      '#weight' => 1,
    );

    $form['details'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array('form-item')
      ),
      '#weight' => 5
    );
    $form['details']['label'] = array(
      '#type' => 'label',
      '#title' => t('Order Details'),
      '#prefix' => '<div class="title">',
      '#suffix' => '</div>'
    );
    $header = array(
      'product_name'=> array(
        'data' => t('Product name'),
        'weight' => 10,
      ),
      'room' => array(
        'data' => t('Room'),
      ),
      'product_info'=> t('Product info'),
      'number' => t('Number'),
      'limit' => t('Limit'),
      'price' => t('Price')
    );
    $form['details']['list'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => array(),
    );
    $sum_price = 0;
    foreach($product_list as $product) {
      $form['details']['list']['#rows'][] = $this->getRowInfo($product);
      $sum_price += ($product->base_price + $product->custom_price) * $product->product_num * $product->product_limit;
    }
    $form['order_price'] = array(
      '#type' => 'value',
      '#value' => round($sum_price, 2)
    );
    $form['order_price_label'] = array(
      '#type' => 'label',
      '#title' => '￥'. round($sum_price,2),
      '#prefix' => '<div class="item"><span>'. t('Total price') .'：</span>',
      '#suffix' => '</div>'
    );
    return $form;
  }

  /**
   * 查检业务是否发生变化
   */
  private function businessChange($product) {
    $hostclinet_service = \Drupal::service('hostclient.serverservice');
    $old_business = $hostclinet_service->loadHostclientBusiness($product->product_id);
    $business_list = order_cart_business_combine($product->business_list);
    foreach($old_business as $old_item) {
      $up_item = $business_list[$old_item->business_id];
      if(empty($up_item)) {
        return true;
      }
      if($old_item->business_content != $up_item['business_value']) {
        return true;
      }
    }
    return false;
  }

  /**
   * 重算价格
   */
  private function afreshPrice($product_list) {
    $list = array();
    foreach($product_list as $s_product) {
      $product = clone $s_product;
      if($product->action == 3) {
        $hostclient = entity_load('hostclient', $product->product_id);
        $expire = $hostclient->getSimpleValue('service_expired_date');
        $diff = $expire - REQUEST_TIME;
        $days = intval($diff/86400);
        $same_product = $this->getSameRenewProduct($product_list, $product->product_id); //订单产品中是否包含有续费
        if(empty($same_product)) {
          $price = order_upgrade_afresh_price($product->business_list, $hostclient, $days);
          $product->custom_price = round($price/$days, 4);
          $product->product_limit = $days;
        } else {
          $limit = $same_product->product_limit;
          $ren_expire = strtotime('+'. $limit .' month', $expire); //加上续费时间
          $ren_diff = $ren_expire - REQUEST_TIME;
          $ren_days = intval($ren_diff/86400); //加上续费的天数
          $price = order_upgrade_afresh_price($product->business_list, $hostclient, $days, $limit);
          $product->custom_price = round($price/$ren_days, 4);
          $product->product_limit = $ren_days;
        }
      }
      $list[] = $product;
    }
    return $list;
  }


  private function getSameRenewProduct($product_list, $product_id) {
    foreach($product_list as $product) {
      if($product->action == 2 && $product->product_id = $product_id) {
        return $product;
      }
    }
    return array();
  }

  private function getRowInfo($product) {
    if($product->action == 1) {
      $product_obj = entity_load('product', $product->product_id);
      $row['product_name'] = $product_obj->label();
    } else {
      $hostclient = entity_load('hostclient', $product->product_id);
      $product_obj = $hostclient->getObject('product_id');
      $server = $hostclient->getObject('server_id');
      $row['product_name'] = '('. $server->label() .')' . $product_obj->label();
    }
    $row['room'] = array(
      'data' => !empty($product->rid) ? entity_load('room', $product->rid)->label() : '',
      'class' => 'room',
    );

    $row['product_info'] = array(
      'data' => SafeMarkup::format($this->getBusinessContent($product->business_list), array()),
      'class' => 'business-info'
    );
    $row['number'] = $product->product_num;
    if($product->action == 3) {
      $row['limit'] = $product->product_limit . t('Days');
    } else {
      $row['limit'] = $product->product_limit . t('Month');
    }
    $price = ($product->base_price + $product->custom_price) * $product->product_num * $product->product_limit;
    $row['price'] = array(
      'data' =>'￥'. round($price),
      'class' => 'price'
    );
    return $row;
  }

  private function getBusinessContent(array $cart_defalut_business) {
    $list = order_cart_business_combine($cart_defalut_business);
    $html = '';
    foreach($list as $item) {
      $business = $item['business'];
      $business_values = $item['business_value'];
      $value_arr = explode(',', $business_values);
      $value_html = '';
      foreach($value_arr as $value) {
        $value_text = product_business_value_text($business, $value);
        $value_html .= '<span>'. $value_text .'</span>';
      }
      $html .= '<p><label>'. $business->label() . '</label>' . $value_html .'</p>';
    }
    return $html;
  }

  /**
   * Returns an array of supported actions for the current entity form.
   *
   * @todo Consider introducing a 'preview' action here, since it is used by
   *   many entity types.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
    $actions['submit']['#value'] = $this->t('Confirm the order');
    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // @todo 添加机房属性，考虑该机房下是否有该配置的足够的库存
    //判断库存
    $product_list = $form_state->getValue('product_list');
    $dis = ServerDistribution::createInstance();
    foreach($product_list as $product) {
      if($product->action == 1) {
        $value = $product->product_num;
        $entity_product = entity_load('product', $product->product_id);
        $idc_stock = $dis->getServerStock($entity_product->getObjectId('server_type'), $product->rid);
        if($value > $idc_stock) {
          $form_state->setErrorByName($product->product_id, $this->t('Sorry the product %product inventory shortage, the current stock of %number Units', array(
            '%product' => $entity_product->label(),
            '%number' => $idc_stock
          )));
        }
      } else {
        $hostclient = entity_load('hostclient', $product->product_id);
        $unpaid_order = $hostclient->getSimpleValue('unpaid_order');
        if($unpaid_order) {
          $product = $hostclient->getObject('product_id');
          $form_state->setErrorByName($product->product_id, $this->t('Sorry the product %product a untreated completed orders', array(
            '%product' => $product->label(),
          )));
        }
      }
    }
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
    $_SESSION['order_products'] = array();
    $products = $form_state->getValue('product_list');
    $order_products = array();
    $cartids = array();
    $exist_hostclient = array();
    foreach($products as $product) {
      $order_product = new \stdClass();
      $order_product->action = $product->action;
      $order_product->product_id = $product->product_id;
      $product_entity = null;
      if($product->action == 1) {
        $product_entity = entity_load('product', $product->product_id);
      } else {
        $hostclient = entity_load('hostclient', $product->product_id);
        if(!array_key_exists($hostclient->id(), $exist_hostclient)) {
          $exist_hostclient[$hostclient->id()] = $hostclient;
        }
        $product_entity = $hostclient->getObject('product_id');
      }
      $order_product->product_name = $product_entity->label();
      $order_product->product_type = $product_entity->getObjectId('server_type');
      $order_product->product_num = $product->product_num;
      $order_product->product_limit = $product->product_limit;
      $order_product->base_price = $product->base_price;
      $order_product->custom_price = $product->custom_price;
      $order_product->description = $product->description;
      $order_product->rid = $product->rid;

      $product_details = array();
      $business_list = $product->business_list;
      foreach($business_list as $business) {
        $detail = new \stdClass();
        $detail->business_id = $business->business_id;
        $business_entity = entity_load('product_business', $business->business_id);
        $detail->business_name = $business_entity->label();
        $detail->business_content = $business->business_content;

        $business_values = $business->business_content;
        $value_arr = explode(',', $business_values);
        $value_html = '';
        foreach($value_arr as $value) {
          $value_text = product_business_value_text($business_entity, $value);
          $value_html .= '<span>'. $value_text .'</span>';
        }
        $detail->business_content_name = $value_html;
        $detail->business_price = $business->business_price;
        $detail->business_default = $business->business_default;
        $detail->combine_mode = $business_entity->getSimpleValue('combine_mode');
        $product_details[] = $detail;
      }
      $order_product->details = $product_details;
      $cartids[] = $product->cid;
      $order_products[] = $order_product;
    }
    $entity = $this->entity;
    $entity->products = $order_products;
    $orderSn = getHostRandomCode();
    $entity->set('code', $orderSn);
    //设置客户
    $uid = $this->currentUser()->id();
    $entity->set('uid', $uid);
    //设置客服
    $client_obj = \Drupal::service('member.memberservice')->queryDataFromDB('client', $uid);
    if($client_obj && $client_obj->commissioner) {
      $entity->set('client_service', $client_obj->commissioner);
    }

    $alias_order = $entity->getSimpleValue('alias_order');
    if(empty($alias_order)) {
      $entity->set('alias_order', $orderSn);
    }
    $entity->save();
    HostLogFactory::OperationLog('order')->log($entity, 'insert');
    //保存服务器未处理的订单
    foreach($exist_hostclient as $hostclient) {
      $hostclient->set('unpaid_order', $entity->id());
      $hostclient->save();
    }
    \Drupal::service('user.cart')->deleteMultiple($cartids);
    $form_state->setRedirectUrl(new Url('user.order.payment', array('order' => $entity->id())));
  }
}
