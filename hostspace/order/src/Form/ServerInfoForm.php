<?php

/**
 * @file
 * Contains \Drupal\order\Form\BusinessDeptForm.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class ServerInfoForm extends FormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'server_product_info';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $productId = null) {
    $product = entity_load('product', $productId);
    $form['#title'] = $product->label();
    if(empty($product) || !$product->getSimpleValue('front_Dispaly')) {
      return $this->redirect('server.product.list');
    }
    $user = $this->currentUser();
    $format = $product->get('parameters')->format;
    $form['productId'] = array(
      '#type' => 'value',
      '#value' => $productId
    );
    $form['product_name'] = array(
      '#type' => 'label',
      '#title' => $product->label(),
    );
    $form['parameters'] =  array(
      '#type' => 'processed_text',
      '#text' => $product->getSimpleValue('parameters'),
      '#format' => $format,
    );
    $config = \Drupal::config('common.global');
    if ($config->get('is_district_room_id')) {
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
      $label_room_name = implode('<br/>', $option_room);
      $form['room_name'] = array(
        '#type' => 'label',
        '#title' => $label_room_name,
        '#prefix' => '<h4>',
        '#suffix' => '</h4>',
      );
    }
    $form['description'] =  array(
      '#type' => 'processed_text',
      '#text' => $product->getSimpleValue('description'),
      '#format' => $format,
    );
    if($user->id()) {
      $base_price = product_user_price($product, $user);
      $form['price'] = array(
        '#type' => 'label',
        '#title' => '￥' . $base_price
      );
    }
    $form['op'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add to cart')
    );
    return $form;
  }

  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $productId = $form_state->getValue('productId');
    $user = $this->currentUser();
    if($user->id()) {
      $product = entity_load('product', $productId);
      $base_price = product_user_price($product, $user);
      if(!$base_price) {
        drupal_set_message('Price setting error, waiting for the administrator to set');
        return;
      }
      $buyProduct = array();
      $buyProductBusiness = array();
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

      $buyProduct['action'] = 1;
      $buyProduct['product_id'] = $productId;
      $buyProduct['product_num'] = 1;
      $buyProduct['base_price'] = $base_price;
      $buyProduct['custom_price'] = 0;
      $buyProduct['description'] = '';
      $buyProduct['uid'] = $user->id();
      $buyProduct['created'] = REQUEST_TIME;
      $buyProduct['product_limit'] = 1;
      // 默认使用los机房
      $buyProduct['rid'] = 1;
      \Drupal::service('user.cart')->add($buyProduct, $buyProductBusiness);
      $form_state->setRedirectUrl(new Url('user.cart'));
    } else {
      $destination = array('destination' => \Drupal::url('server.info', array('productId' => $productId)));
      $form_state->setRedirectUrl(new Url('user.login', array(), array('query' => $destination)));
    }
  }
}
