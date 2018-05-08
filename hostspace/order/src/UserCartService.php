<?php

namespace Drupal\order;

use Drupal\Core\Database\Connection;

class UserCartService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * 增加产品到购物车中
   *
   * @param $buyProduct array
   * 购买的产品信息
   * @param $buyProductBusiness array
   * 购买产品对应的业务信息
   */
  public function add(array $buyProduct, array $buyProductBusiness) {
   $transaction = $this->database->startTransaction();
    try {
      $action = $buyProduct['action'];
      if($action == 1) {
        $exist = $this->existProduct($buyProduct, $buyProductBusiness);
        if($exist) {
          $exist->product_num = $exist->product_num + $buyProduct['product_num'];
          $this->update($exist);
          return;
        }
      } else if ($action == 2 || $action == 3) {
        $cid = $this->database->select('cart', 'c')
          ->fields('c', array('cid'))
          ->condition('product_id', $buyProduct['product_id'])
          ->condition('action', $action)
          ->execute()
          ->fetchField();
        if($cid) {
          $this->delete($cid);
        }
      }
      $cartId = $this->database->insert('cart')
        ->fields($buyProduct)
        ->execute();
      foreach($buyProductBusiness as $business) {
        $this->database->insert('cart_business_data')
          ->fields(array('cartId' => $cartId) + $business)
          ->execute();
      }
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 判断是否存在
   */
  private function existProduct($buyProduct, array $buyProductBusiness) {
    $cart_products = $this->database->select('cart', 'c')
      ->fields('c')
      ->condition('product_id', $buyProduct['product_id'])
      ->condition('action', $buyProduct['action'])
      ->condition('uid', \Drupal::currentUser()->id())
      ->execute()
      ->fetchAll();

    foreach($cart_products as $cart_product) {
      $same = true;
      $cart_business = $this->loadProductBusiness($cart_product->cid);
      if(count($buyProductBusiness) != count($cart_business)) {
        continue;
      }
      foreach($buyProductBusiness as $new_business) {
        $b = false;
        foreach($cart_business as $old_business) {
          if($new_business['business_id'] == $old_business->business_id && $new_business['business_content'] == $old_business->business_content) {
            $b = true;
            break;
          }
        }
        if(!$b) {
          $same = false;
          break;
        }
      }
      if($same) {
        return $cart_product;
      }
    }
    return 0;
  }

  /**
   * 修改购物车数量和期限
   *
   * @param $cart /stdClass
   * 购买的产品信息
   */
  public function update($cart) {
    $this->database->update('cart')
      ->fields(array(
                'product_num' => $cart->product_num, 
                'product_limit' => $cart->product_limit,
                'rid' => $cart->rid))
      ->condition('cid', $cart->cid)
      ->execute();
  }

    /**
   * 删除购物车
   *
   * @param $cid int
   * 购物车项ID
   */
  public function delete($cid) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->delete('cart_business_data')
        ->condition('cartId', $cid)
        ->execute();
      $this->database->delete('cart')
        ->condition('cid', $cid)
        ->execute();
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 删除购物车
   *
   * @param $cids array()
   * 购物车项ID
   */
  public function deleteMultiple($cids) {
    $transaction = $this->database->startTransaction();
    try {
      $this->database->delete('cart_business_data')
        ->condition('cartId', $cids, 'in')
        ->execute();
      $this->database->delete('cart')
        ->condition('cid', $cids, 'in')
        ->execute();
    }
    catch (\Exception $e) {
      $transaction->rollback();
    }
  }

  /**
   * 获取多个购物车产品对象
   */
  public function loadMultipleById($cids) {
    return $this->database->select('cart', 'c')
      ->fields('c', array('cid','action','product_id','product_num','product_limit','base_price','custom_price','description','uid','created','rid'))
      ->condition('cid', $cids, 'IN')
      ->execute()
      ->fetchAll();
  }

  /**
   * 获取多个购物车产品对象
   *
   * @param $uid int
   * 用户ID
   */
  public function loadMultipleByUid($uid) {
    return $this->database->select('cart', 'c')
      ->fields('c', array('cid','action','product_id','product_num','product_limit','base_price','custom_price','description','uid','created','rid'))
      ->condition('uid', $uid)
      ->execute()
      ->fetchAll();
  }

  /**
   * 获取特定购物车项的业务列表
   *
   * @param $cid int
   * 购物车项ID
   */
  public function loadProductBusiness($cid) {
    return $this->database->select('cart_business_data', 'c')
      ->fields('c', array('cbid','business_id','business_content','business_price','business_default','cartId'))
      ->condition('cartId', $cid)
      ->execute()
      ->fetchAll();
  }
}
