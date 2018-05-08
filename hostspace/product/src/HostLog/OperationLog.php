<?php
namespace Drupal\product\HostLog;

use Drupal\hostlog\OperationLogBase;

/**
 * 操作日志
 */
class OperationLog extends OperationLogBase {
  /**
   * 构建日志消息
   * @param
   *  - $entity 当前操作实体
   *  - $action 当前操作（如insert, update, delete等）
   */
  protected function message($entity, $action) {
    $message = '';
    $op = '操作';
    if($action == 'insert') {
      $op = '添加';
    } else if ($action == 'update') {
      $op = '编辑';
    } else if ($action == 'delete') {
      $op = '删除';
    }
    $type = $entity->getEntityTypeId();
    switch($type) {
      case 'product':
        $room = array();
        $rids = (Array)json_decode($entity->get('rids')->value);
        foreach($rids as $rid) {
          if($rid) {
            $room[] = entity_load('room', $rid)->label();
          }
        }
        $message = strtr('%action了产品。名称：%name, 服务器分类: %type, 机房：%room', array(
          '%action' => $op,
          '%name' => $entity->label(),
          '%type' => $entity->getObject('server_type')->label(),
          '%room' => implode('、', $room)
        ));
        break;
      case 'product_business':
        $message = strtr('%action了业务【%name】。', array(
          '%action' => $op,
          '%name' => $entity->label()
        ));
        break;
      case 'business_price':
        if($action == 'insert') {
          $message = strtr('设置了业务【%name】的价格为￥%price。', array(
            '%action' => $op,
            '%name' => $entity->getObject('businessId')->label(),
            '%price' => $entity->getSimpleValue('price')
          ));
        } else {
          $message = strtr('%action了业务【%name】的价格信息。价格：￥%price', array(
            '%action' => $op,
            '%name' => $entity->getObject('businessId')->label(),
            '%price' => $entity->getSimpleValue('price')
          ));
        }
        break;
      case 'product_business_content':
        if($action == 'insert') {
          $message = strtr('为业务【%name】添加了子项【%content】。', array(
            '%name' => $entity->getObject('businessId')->label(),
            '%content' => $entity->getSimpleValue('name')
          ));
        } else {
          $message = strtr('%action了业务【%name】的子项【%content】。', array(
            '%action' => $op,
            '%name' => $entity->getObject('businessId')->label(),
            '%content' => $entity->getSimpleValue('name')
          ));
        }
        break;
      case 'product_business_entity_content':
        $obj = entity_load($entity->getSimpleValue('entity_type'), $entity->label());
        $content = $obj->label();
        if($action == 'insert') {
          $message = strtr('为业务【%name】添加了子项【%content】。', array(
            '%name' => $entity->getObject('businessId')->label(),
            '%content' => $content
          ));
        } else {
          $message = strtr('%action了业务【%name】的子项【%content】。', array(
            '%action' => $op,
            '%name' => $entity->getObject('businessId')->label(),
            '%content' => $content
          ));
        }
        break;
      case 'product_price':
        if($action == 'insert') {
          $message = strtr('设置了产品【%product】的用户等级为【%level】的价格为：%price', array(
            '%product' => $entity->getObject('productId')->label(),
            '%level' => $entity->getObject('user_level')->label(),
            '%price' => $entity->getSimpleValue('price')
          ));
        } else {
          $message = strtr('%action了产品【%product】的用户等级为【%level】的价格信息', array(
            '%action' => $op,
            '%product' => $entity->getObject('productId')->label(),
            '%level' => $entity->getObject('user_level')->label()
          ));
        }
        break;
      case 'product_business_price':
        $message = strtr('为产品【%name】%action了一个业务【%business】', array(
          '%name' => $entity->getObject('productId')->label(),
          '%action' => $op,
          '%business' => $entity->getObject('businessId')->label()
        ));
        break;
    }
    return $message;
  }

  /**
   * 字段差异比较
   */
  protected function diff($name, $current, $before, $type) {
    if($name == 'target_id') {
      $current_trem = entity_load($current->getSimpleValue('entity_type'),$current->getSimpleValue('target_id'));
      $current_value = $current_trem->label(); 

      $before_trem = entity_load($before->getSimpleValue('entity_type'),$before->getSimpleValue('target_id'));
      $before_value = $before_trem->label(); 
      return '业务内容：【'. $before_value .'】变更为【'. $current_value .'】';
    }
    return null;
  }

  /**
   * 字段差异比较
   */
  protected function diff_alter($result, $current, $before) {
    $current_business = $current->default_business;
    $before_business = $before->default_business;
    if($current_business != $before_business) {
      $bufore_label = '';
      foreach($before_business as $item) {
        $businessId = $item['businessId'];
        $business_obj = entity_load('product_business', $businessId);
        $value = product_business_value_text($business_obj, $item['business_content']);
        $bufore_label .= '【'. $business_obj->label() .'：'. $value .'】';
      }
      $current_label = '';
      foreach($current_business as $item) {
        $businessId = $item['businessId'];
        $business_obj = entity_load('product_business', $businessId);
        $value = product_business_value_text($business_obj, $item['business_content']);
        $current_label .= '【'. $business_obj->label() .'：'. $value .'】';
      }
      $result['default_business'] = '默认业务：由'. $bufore_label .'变更为' . $current_label;
    }
    return $result;
  }
}
