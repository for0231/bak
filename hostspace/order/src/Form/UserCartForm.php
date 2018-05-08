<?php

/**
 * @file
 * Contains \Drupal\user\Form\CustomBusiness.
 */

namespace Drupal\order\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html as HtmlUtility;
use Drupal\order\ServerDistribution;

class UserCartForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'user_cart_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = $this->currentUser();
    $class = get_class($this);
    $cart = \Drupal::service('user.cart');
    $cart_products = $cart->loadMultipleByUid($user->id());
    $header = array(
      'header_info' => array(
        'data' => array(
          '#type' => 'table',
          '#attributes' => array(
            'class' => array('cart-header')
          ),
          '#header' => $this->getCartHeader()
        )
      )
    );
    $form['carts'] = array(
      '#type' => 'table',
      '#tableselect' => TRUE,
      '#header' => $header,
      '#attributes' => array(
        'class' => array('cart')
      ),
      '#process' => array(
        array($class, 'processCartTable'),
      ),
      '#empty' => t('No product')
    );
    $product_info = $form_state->getValue('product_info');
    foreach($cart_products as $cart_product) {
      $action = $cart_product->action;
      if(($action==1 || $action==2) && !empty($product_info)) {
         $number = $product_info['price_info'][$cart_product->cid]['number'];
         $limit = $product_info['price_info'][$cart_product->cid]['limit'];
         $rid = $product_info['price_info'][$cart_product->cid]['room'];
         if($cart_product->product_num != $number || $cart_product->product_limit != $limit || $cart_product->rid != $rid ) {
           $cart_product->product_num = $number;
           $cart_product->product_limit = $limit;
           $cart_product->rid = $rid;
           $cart->update($cart_product);
         }
      }
      $form['carts'][$cart_product->cid] = $this->getCartRow($cart_product);
    }
    $form['#attached']['library'] = array('order/drupal.user-cart');
    $form['order_price'] = array(
      '#type' => 'label',
      '#title' => '￥0',
      '#prefix' => '<div class="order-price">'. $this->t('Order price') .'：</span>',
      '#suffix' => '</div>'
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Create order')
    );
    return $form;
  }

  public static function processCartTable(&$element, FormStateInterface $form_state, &$complete_form) {
    if ($element['#tableselect']) {
      if ($element['#multiple']) {
        $value = is_array($element['#value']) ? $element['#value'] : array();
      }
      // Advanced selection behavior makes no sense for radios.
      else {
        $element['#js_select'] = FALSE;
      }
      // Add a "Select all" checkbox column to the header.
      // @todo D8: Rename into #select_all?
      if ($element['#js_select']) {
        $element['#attached']['library'][] = 'core/drupal.tableselect';
        array_unshift($element['#header'], array('class' => array('select-all')));
      }
      // Add an empty header column for radio buttons or when a "Select all"
      // checkbox is not desired.
      else {
        array_unshift($element['#header'], '');
      }

      if (!isset($element['#default_value']) || $element['#default_value'] === 0) {
        $element['#default_value'] = array();
      }
      // Create a checkbox or radio for each row in a way that the value of the
      // tableselect element behaves as if it had been of #type checkboxes or
      // radios.
      foreach (Element::children($element) as $key) {
        $row = &$element[$key];
        // Prepare the element #parents for the tableselect form element.
        // Their values have to be located in child keys (#tree is ignored),
        // since Table::validateTable() has to be able to validate whether input
        // (for the parent #type 'table' element) has been submitted.
        $element_parents = array_merge($element['#parents'], array($key));

        // Since the #parents of the tableselect form element will equal the
        // #parents of the row element, prevent FormBuilder from auto-generating
        // an #id for the row element, since
        // \Drupal\Component\Utility\Html::getUniqueId() would automatically
        // append a suffix to the tableselect form element's #id otherwise.
        $row['#id'] = HtmlUtility::getUniqueId('edit-' . implode('-', $element_parents) . '-row');

        // Do not overwrite manually created children.
        if (!isset($row['select'])) {
          // Determine option label; either an assumed 'title' column, or the
          // first available column containing a #title or #markup.
          // @todo Consider to add an optional $element[$key]['#title_key']
          //   defaulting to 'title'?
          unset($label_element);
          $title = NULL;
          if (isset($row['title']['#type']) && $row['title']['#type'] == 'label') {
            $label_element = &$row['title'];
          }
          else {
            if (!empty($row['title']['#title'])) {
              $title = $row['title']['#title'];
            }
            else {
              foreach (Element::children($row) as $column) {
                if (isset($row[$column]['#title'])) {
                  $title = $row[$column]['#title'];
                  break;
                }
                if (isset($row[$column]['#markup'])) {
                  $title = $row[$column]['#markup'];
                  break;
                }
              }
            }
            if (isset($title) && $title !== '') {
              $title = t('Update @title', array('@title' => $title));
            }
          }

          // Prepend the select column to existing columns.
          $r = array('select' => array()) + $row;
          $row = $r;
          $row['select'] += array(
            '#type' => $element['#multiple'] ? 'checkbox' : 'radio',
            '#id' => HtmlUtility::getUniqueId('edit-' . implode('-', $element_parents)),
            // @todo If rows happen to use numeric indexes instead of string keys,
            //   this results in a first row with $key === 0, which is always FALSE.
            '#return_value' => $key,
            '#attributes' => $element['#attributes'],
            '#wrapper_attributes' => array(
              'class' => array('table-select'),
            ),
          );
          if ($element['#multiple']) {
            $row['select']['#default_value'] = isset($value[$key]) ? $key : NULL;
            $row['select']['#parents'] = $element_parents;
          }
          else {
            $row['select']['#default_value'] = ($element['#default_value'] == $key ? $key : NULL);
            $row['select']['#parents'] = $element['#parents'];
          }
          if (isset($label_element)) {
            $label_element['#id'] = $row['select']['#id'] . '--label';
            $label_element['#for'] = $row['select']['#id'];
            $row['select']['#attributes']['aria-labelledby'] = $label_element['#id'];
            $row['select']['#title_display'] = 'none';
          }
          else {
            $row['select']['#title'] = $title;
            $row['select']['#title_display'] = 'invisible';
          }
        }
      }
    }

    return $element;
  }

  private function getCartHeader() {
    $header['product_name'] = array('data' => $this->t('Product name'), 'class' => 'config-name wid20');
    $header['product_num'] = array('data' => $this->t('Number'), 'class' => 'number');
    $header['price'] = array('data' => $this->t('Price/month'), 'class' => 'price');
    $header['property'] = array('data' => $this->t('Property'), 'class' => 'property');
    $header['operations'] = array('data' => $this->t('Operation'), 'class' => 'operation');
    return $header;
  }

  private function getCartRow($cart_product) {
    $row['product_info'] = array(
      '#type' => 'table',
      '#parents' => array('product_info'),
      '#attributes' => array(
        'class' => 'cart-item'
      ),
      '#wrapper_attributes' => array(
        'id' => 'product_info_' . $cart_product->cid . '_wrapper',
      )
    );
    $action = $cart_product->action;
    if($action == 2) {
      $child = $this->buildRenewRow($cart_product);
      $row['product_info']['base_info'] = $child['base_info'];
      $row['product_info']['price_info'] = $child['price_info'];
    } else if ($action == 3) {
      $child = $this->buildUpgradeRow($cart_product);
      $row['product_info']['base_info'] = $child['base_info'];
      $row['product_info']['price_info'] = $child['price_info'];
    } else {
      $child = $this->buildHireRow($cart_product);
      $row['product_info']['base_info'] = $child['base_info'];
      $row['product_info']['price_info'] = $child['price_info'];
    }
    return $row;
  }

  /**
   * 购物车显示租用产品
   */
  private function buildHireRow($cart_product) {
    $product = entity_load('product', $cart_product->product_id);
    $price = ($cart_product->base_price + $cart_product->custom_price) * $cart_product->product_num * $cart_product->product_limit;
    $row['base_info'] = array(
      '#attributes' => array(
        'class' => array('base-info')
      ),
      array(
        '#markup' => SafeMarkup::format('<h2>' . $product->label() . '</h2><a href="javascript:void(0)" class="look-config">' . $this->t('Configuration info') . '</a>' . $this->getBusinesContent($cart_product->cid), array()),
        '#wrapper_attributes' => array(
          'class' => array('config-name')
        )
      ),
      array(
        '#markup' => $cart_product->product_num,
        '#wrapper_attributes' => array(
          'class' => array('number')
        )
      ),
      array(
        '#markup' =>'￥' . $price,
        '#wrapper_attributes' => array(
          'class' => array('price')
        )
      ),
      array(
        '#markup' => $this->t('Hire'),
        '#wrapper_attributes' => array(
          'class' => array('property')
        )
      ),
      array(
        '#markup' => \Drupal::l(t('Delete'), new Url('user.cart.delete', array('cartId' => $cart_product->cid))),
        '#wrapper_attributes' => array(
          'class' => array('operation')
        )
      )
    );
    $row['price_info'] = array(
      '#attributes' => array(
        'class' => array('price-info')
      ),
      $cart_product->cid => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('price-item')
        ),
        '#wrapper_attributes' => array(
          'colspan' => 6,
        ),
        'title' => array(
          '#type' => 'label',
          '#title' => t('Price details'),
          '#prefix' => '<div class="price-details"></span>',
          '#suffix' => '</div>',
          '#weight' => 1,
        ),
        'number'=> array(
          '#type' => 'textfield',
          '#title' => t('Number'),
          '#size' => 4,
          '#default_value' => $cart_product->product_num,
          '#ajax' => array(
            'callback' => '::loadCartProduct',
            'wrapper' => 'product_info_' . $cart_product->cid . '_wrapper',
            'method' => 'html',
            'event' => 'change'
          ),
          '#attributes' => array(
            'class' => array('edit-by-number')
          ),
          '#weight' => 5,
          '#suffix' => '<div class = "symbol">X</div>'
        ),
        'limit' => array(
          '#type' => 'select',
          '#title' => t('Limit'),
          '#options' => order_cart_buy_options(),
          '#default_value' => $cart_product->product_limit,
          '#ajax' => array(
            'callback' => '::loadCartProduct',
            'wrapper' => 'product_info_' . $cart_product->cid . '_wrapper',
            'method' => 'html'
          ),
          '#weight' => 7,
          '#suffix' => '<div class = "symbol">X&nbsp;&nbsp;(</div>'
        ),
        'base_price'   => array(
          '#type' => 'number',
          '#title' => t('Base price'),
          '#step' => 'any',
          '#size' => 7,
          '#disabled' => true,
          '#default_value' => $cart_product->base_price,
          '#suffix' => '<div class = "symbol">＋</div>',
          '#weight' => 9,
        ),
        'custom_price' => array(
          '#type' => 'number',
          '#title' => t('Custom price'),
          '#step' => 'any',
          '#size' => 7,
          '#disabled' => true,
          '#default_value' => $cart_product->custom_price,
          '#suffix' => '<div class = "symbol">)&nbsp;&nbsp;= <span>￥'. $price .'</span></div>',
          '#weight' => 11,
        )
      )
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
      $row['price_info'][$cart_product->cid]['room'] = array(
        '#type' => 'select',
        '#title' => t('机房'),
        '#options' => $option_room,
        '#ajax' => array(
          'callback' => '::loadCartProduct',
          'wrapper' => 'product_info_' . $cart_product->cid . '_wrapper',
          'method' => 'html'
        ),
        '#prefix' => '<div class="room-details"></span>',
        '#suffix' => '</div>',
        '#weight' => 3,
        '#default_value' => $cart_product->rid,
      );
    }

    return $row;
  }

  /**
   * 购物车显示续费产品
   */
  private function buildRenewRow($cart_product) {
    $hostclient = entity_load('hostclient', $cart_product->product_id);
    $product = $hostclient->getObject('product_id');
    $server = $hostclient->getObject('server_id');
    $price = ($cart_product->base_price + $cart_product->custom_price) * $cart_product->product_num * $cart_product->product_limit;
    $row['base_info'] = array(
      '#attributes' => array(
        'class' => array('base-info')
      ),
      array(
        '#markup' => SafeMarkup::format('('. $server->label() .')'. $product->label() . '<a href="javascript:void(0)" class="look-config">' . $this->t('Configuration info') . '</a>' . $this->getBusinesContent($cart_product->cid), array()),
        '#wrapper_attributes' => array(
          'class' => array('config-name')
        )
      ),
      array(
        '#markup' => $cart_product->product_num,
        '#wrapper_attributes' => array(
          'class' => array('number')
        )
      ),
      array(
        '#markup' =>'￥' . $price,
        '#wrapper_attributes' => array(
          'class' => array('price')
        )
      ),
      array(
        '#markup' => $this->t('Renew'),
        '#wrapper_attributes' => array(
          'class' => array('property')
        )
      ),
      array(
        '#markup' => \Drupal::l(t('Delete'), new Url('user.cart.delete', array('cartId' => $cart_product->cid))),
        '#wrapper_attributes' => array(
          'class' => array('operation')
        )
      )
    );

    $row['price_info'] = array(
      '#attributes' => array(
        'class' => array('price-info')
      ),
      $cart_product->cid => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('price-item')
        ),
        '#wrapper_attributes' => array(
          'colspan' => 4,
        ),
        'title' => array(
          '#type' => 'label',
          '#title' => t('Price details'), 
          '#prefix' => '<div class="price-details"></span>',
          '#suffix' => '</div>',
          '#weight' => 1,
        ),
        'number'=> array(
          '#type' => 'number',
          '#title' => t('Number'),
          '#step' => 'any',
          '#size' => 3,
          '#min' => 1,
          '#default_value' => $cart_product->product_num,
          '#disabled' => true,
          '#suffix' => '<div class = "symbol">X</div>',
          '#weight' => 5,
        ),
        'limit' => array(
          '#type' => 'select',
          '#title' => t('Limit'),
          '#options' => order_cart_buy_options(),
          '#default_value' => $cart_product->product_limit,
          '#ajax' => array(
            'callback' => '::loadCartProduct',
            'wrapper' => 'product_info_' . $cart_product->cid . '_wrapper',
            'method' => 'html'
          ),
          '#suffix' => '<div class = "symbol">X&nbsp;&nbsp;(</div>',
          '#weight' => 7,
        ),
        'base_price'   => array(
          '#type' => 'number',
          '#title' => t('Base price'),
          '#step' => 'any',
          '#size' => 7,
          '#disabled' => true,
          '#default_value' => $cart_product->base_price,
          '#suffix' => '<div class = "symbol">＋</div>',
          '#weight' => 9,
        ),
        'custom_price' => array(
          '#type' => 'number',
          '#title' => t('Custom price'),
          '#step' => 'any',
          '#size' => 7,
          '#disabled' => true,
          '#default_value' => $cart_product->custom_price,
          '#suffix' => '<div class = "symbol">)&nbsp;&nbsp;= <span>￥'. $price .'</span></div>',
          '#weight' => 11,
        )
      )
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
      $row['price_info'][$cart_product->cid]['room'] = array(
        '#type' => 'select',
        '#title' => t('机房'),
        '#options' => $option_room,
        '#prefix' => '<div class="room-details"></span>',
        '#suffix' => '</div>',
        '#weight' => 3,
        '#disabled' => true,
        '#default_value' => $cart_product->rid,
      );
    }
    return $row;
  }

  /**
   * 购物车显示升级产品
   */
  private function buildUpgradeRow($cart_product) {
    $hostclient = entity_load('hostclient', $cart_product->product_id);
    $product = $hostclient->getObject('product_id');
    $server = $hostclient->getObject('server_id');
    $expire = $hostclient->getSimpleValue('service_expired_date');
    $diff = $expire - REQUEST_TIME;
    $days = intval($diff/86400);
    $business_list = \Drupal::service('user.cart')->loadProductBusiness($cart_product->cid);
    $price = order_upgrade_afresh_price($business_list, $hostclient, $days);
    $row['base_info'] = array(
      '#attributes' => array(
        'class' => array('base-info')
      ),
      array(
        '#markup' => SafeMarkup::format('('. $server->label() .')'. $product->label() . '<a href="javascript:void(0)" class="look-config">' . $this->t('New configuration info') . '</a>' . $this->getBusinesContent($cart_product->cid), array()),
        '#wrapper_attributes' => array(
          'class' => array('config-name')
        )
      ),
      array(
        '#markup' => $cart_product->product_num,
        '#wrapper_attributes' => array(
          'class' => array('number')
        )
      ),
      array(
        '#markup' =>'￥' . round($price, 2),
        '#wrapper_attributes' => array(
          'class' => array('price')
        )
      ),
      array(
        '#markup' => $this->t('Upgrade'),
        '#wrapper_attributes' => array(
          'class' => array('property')
        )
      ),
      array(
        '#markup' => \Drupal::l(t('Delete'), new Url('user.cart.delete', array('cartId' => $cart_product->cid))),
        '#wrapper_attributes' => array(
          'class' => array('operation')
        )
      )
    );

    $row['price_info'] = array(
      '#attributes' => array(
        'class' => array('price-info')
      ),
      $cart_product->cid => array(
        '#type' => 'container',
        '#attributes' => array(
          'class' => array('price-item')
        ),
        '#wrapper_attributes' => array(
          'colspan' => 4,
        ),
        'title' => array(
          '#type' => 'label',
          '#title' => t('Price details'),
          '#prefix' => '<div class="price-details"></span>',
          '#suffix' => '</div>',
          '#weight' => 1,
        ),
        'number'=> array(
          '#type' => 'number',
          '#title' => t('Number'),
          '#step' => 'any',
          '#size' => 3,
          '#min' => 1,
          '#default_value' => $cart_product->product_num,
          '#disabled' => true,
          '#suffix' => '<div class = "symbol">X</div>',
          '#weight' => 5,
        ),
        'limit' => array(
          '#type' => 'textfield',
          '#title' => t('Limit'),
          '#default_value' => $days . ' ' . t('days'),
          '#disabled' => true,
          '#size' => 8,
          '#suffix' => '<div class = "symbol">X&nbsp;&nbsp;(</div>',
          '#weight' => 7,
        ),
        'base_price'   => array(
          '#type' => 'number',
          '#title' => t('Base price'),
          '#step' => 'any',
          '#size' => 7,
          '#disabled' => true,
          '#default_value' => $cart_product->base_price,
          '#suffix' => '<div class = "symbol">＋</div>',
          '#weight' => 9,
        ),
        'custom_price' => array(
          '#type' => 'textfield',
          '#title' => t('Custom price'),
          '#size' => 7,
          '#disabled' => true,
          '#default_value' => '￥' . round($price/$days, 2) . '/'. t('days'),
          '#suffix' => '<div class = "symbol">)&nbsp;&nbsp;= <span>￥'. round($price,2) .'</span></div>',
          '#weight' => 11,
        )
      )
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
      $row['price_info'][$cart_product->cid]['room'] = array(
        '#type' => 'select',
        '#title' => t('机房'),
        '#options' => $option_room,
        '#prefix' => '<div class="room-details"></span>',
        '#suffix' => '</div>',
        '#disabled' => true,
        '#weight' => 3,
        '#default_value' => $cart_product->rid,
      );
    }

    return $row;
  }

  /**
   * 获取产品业务内容
   */
  private function getBusinesContent($cartId) {
    $business_list = \Drupal::service('user.cart')->loadProductBusiness($cartId);
    $list = order_cart_business_combine($business_list);
    $html = '<div class="business-list" style= "display:none"><table>';
    foreach($list as $item) {
      $business = $item['business'];
      $business_values = $item['business_value'];
      $value_arr = explode(',', $business_values);
      $value_html = '';
      foreach($value_arr as $value) {
        $value_text = product_business_value_text($business, $value);
        $value_html .= '<div>'. $value_text .'</div>';
      }
      $html .= '<tr><td>'. $business->label().'：</td><td>'. $value_html .'</td><td>'. $item['business_price'] .'</td></tr>';
    }
    $html .= '</table></div>';
    return $html;
  }

  /**
   * ajax回调函数
   */
  public static function loadCartProduct(array $form, FormStateInterface $form_state) {
    $ajax_name = $form_state->getTriggeringElement();
    $parents = $ajax_name['#parents'];
    $cartId = $parents[2];
    return $form['carts'][$cartId]['product_info'];
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $carts = $form_state->getValue('carts');
    $cart_order = array();
    foreach($carts as $key => $cart) {
      if($cart) {
        $cart_order[] = $key;
      }
    }
    if(empty($cart_order)) {
      $form_state->setErrorByName('op',$this->t('Please select a product'));
    } else {
      $cart = \Drupal::service('user.cart');
      $product_list = $cart->loadMultipleById($cart_order);
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
        } 
      }
      $form_state->cart_product_list = $product_list;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $product_list = $form_state->cart_product_list;
    $cart = \Drupal::service('user.cart');
    foreach($product_list as &$product) {
      $product->business_list = $cart->loadProductBusiness($product->cid);
    }
    $_SESSION['order_products'] = $product_list;
    $form_state->setRedirectUrl(new Url('user.build.order'));
  }
}
