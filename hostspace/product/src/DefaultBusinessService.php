<?php
namespace Drupal\product;
use Drupal\Core\Database\Connection;

class DefaultBusinessService {

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
   * 获取指定业务Id的默认业务
   */
  public function getBusinessById($businessId) {
    return $this->database->select('product_default_business', 'db')
      ->fields('db', array('productId', 'businessId', 'business_content'))
      ->condition('businessId', $businessId)
      ->execute()
      ->fetchAll();
  } 

  /**
   * 获取指定产品的默认业务
   */
  public function getListByProduct($productId) {
    return $this->database->select('product_default_business', 'db')
      ->fields('db', array('productId', 'businessId', 'business_content'))
      ->condition('productId', $productId)
      ->execute()
      ->fetchAll();
  }

  /**
   * 删除指定产品下的默认业务
   */
  public function deleteByProduct($productId) {
    $this->database->delete('product_default_business')
      ->condition('productId', $productId)
      ->execute();
  }
  
  /**
   * 重新增加特定产品的默认业务，即删除原来的业务
   */
  public function reAdd(array $data, $productId) {
    if(count($data) > 0) {
      $this->deleteByProduct($productId);
    }
    foreach($data as $item) {
      $this->database->insert('product_default_business')
        ->fields(array('productId', 'businessId', 'business_content'))
        ->values(array($productId, $item['businessId'], $item['business_content']))
        ->execute();
    }
  }
}
