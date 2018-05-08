<?php

/**
 * @file
 * Contains \Drupal\order\Form\OrderDetail.
 */

namespace Drupal\order\user;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

class OrderDetail extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $form['summary'] = array(
      '#type' => 'fieldset',
      '#title' =>'订单摘要',
    );
    $form['summary']['detail'] = array(
      '#type' => 'table',
      '#rows' => $this->buildSummaryRows(),
    );

     $form['details'] = array(
      '#type' => 'fieldset',
      '#title' =>'订单详情',
    );
    $form['details']['detail'] = array(
      '#type' => 'table',
      '#header' => $this->buildDetailHeader(),
      '#rows' => $this->buildDetailRows(),
    );
        

    return $form;
  }

  /**
   * 给显示订单详情的表格构建行
   *
   */
  private function buildSummaryRows() {
    $entity = $this->entity;
    $client = \Drupal::service('member.memberservice')->queryDataFromDB('client',$entity->get('uid')->entity->id());
    
    $rows = array();
    
    $rows[1] = array(
      'client'=> '公司/单位名称:',
      'cleint_value'=> $client ? $client->corporate_name : $entity->get('uid')->entity->getUsername(),
      'link_man'=> '联络人:',
      'link_man_value'=> $client ? $client->client_name : $entity->get('uid')->entity->getUsername(),
      'phone'=> '联系电话:',
      'phone_value'=> $client ? $client->telephone : '',
    );
    $rows[2] = array(
      'price'=> '订单金额(RMB):',
      'price_value'=> $entity->getSimpleValue('order_price'),
      'service'=> '状态:',
      'service_man_value'=> orderStatus()[$entity->getSimpleValue('status')],
      'phone'=> '',
      'phone_value'=> '',
    );
    return $rows;
  }
  
  /**
   * 给显示订单详情的表格构建表头
   *
   */
  private function buildDetailHeader() {
    $header['id'] = array(
      'data' => $this->t('ID'),
    );
    $header['product'] = array(
      'data' => $this->t('Product'),
    );
    $header['product_detail'] = array(
      'data' => $this->t('Detail'),
    );
    $header['unit_price'] = array(
      'data' => $this->t('Unit price'),
    );
    $header['count'] = array(
      'data' => $this->t('Count'),
    );
    $header['price'] = array(
      'data' => $this->t('Price'),
    );

    return $header;
  }

  /**
   * 给显示订单详情的表格构建行数据
   *
   */
  private function buildDetailRows() {
    $rows = array();
    $entity = $this->entity;
    $products = \Drupal::service('order.product')->getProductByOrderId($entity->id());
    if(is_array($products)) {
      $i = 1;
      foreach($products as $key=>$product) {
        $business = \Drupal::service('order.product')->getOrderBusiness($product->opid);
        $detail = '';
        $business_arr =array();
        foreach($business as $k=>$v) {
          $business_arr[] = $v->business_name . '：' .$v->business_content_name;
        }
        $rows[$key] = array(
          'id' => $i,
          'product' => $product->product_name ,
        );
         $rows[$key]['detail']['data'] = array(
           '#theme' => 'item_list',     
           '#items' => $business_arr
        );
        $rows[$key] = $rows[$key] + array(
          'unit_price' => $product->base_price + $product->custom_price,
          'count' => $product->product_num,
          'price' => ($product->base_price + $product->custom_price)*$product->product_num
        );
        
        $i ++;
      }
    }
    return $rows;
  }

  protected function actions(array $form, FormStateInterface $form_state) {
    return array();
  }
  /**
   * Overrides Drupal\Core\Entity\EntityForm::submit().
   */
  public function save(array $form, FormStateInterface $form_state) {
  }

}
