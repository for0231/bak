<?php
namespace Drupal\hostlog;
/**
 * 返回日志基类
 */
class OperationLogBase {

  /**
   * 写入日志
   */
  public function log($entity, $action) {
    $msg = $this->message($entity, $action);
    if(empty($msg)) {
      return;
    }
    $data_name = $entity->getEntityTypeId();
    $data_id = $entity->id();
    if(isset($entity->other_data)) {
      $other_data = $entity->other_data;
      if(isset($other_data['data_name'])) {
         $data_name = $other_data['data_name'];
      }
      if(isset($other_data['data_id'])) {
        $data_id = $other_data['data_id'];
      }
    }
    $entity->view_callback = array($this, 'diff_contrast');
    \Drupal::service('operation.log')->log(array(
      'action' => $action,
      'message' => $msg,
      'entity_id' => $entity->id(),
      'entity_name' => $entity->getEntityTypeId(),
      'data_id' => $data_id,
      'data_name' => $data_name,
      'data' => serialize($entity)
    ));
  }

  /**
   * 差异对比
   */
  public function diff_contrast($current, $before) {
    $result = array();
    // 判断对象是否是实体
    if(is_subclass_of($current, 'Drupal\Core\Entity\EntityInterface')) {
      foreach ($current->getFields() as $name => $property) {
        $current_value = $property->getValue();
        $before_value = $before->get($name)->getValue();
        if($current_value != $before_value && $name !='changed' ) {
          $diff = $this->diff($name, $current, $before, 'entity');
          if(!empty($diff)) {
            $result[$name] = $diff;
            continue;
          }
          $current_item_value = '';
          foreach($current_value as $key => $item) {
            if(isset($item['target_id'])) {
              $current_item_value .= '【' . $current->get($name)->get($key)->entity->label() . '】';
            }
            if(isset($item['value'])) {
              $current_item_value .= '【' . $current->get($name)->get($key)->value . '】';
            }
          }
          $before_item_value = '';
          foreach($before_value as $key => $item) {
            if(isset($item['target_id'])) {
              $before_item_value .= '【' . $before->get($name)->get($key)->entity->label() . '】';
            }
            if(isset($item['value'])) {
              $before_item_value .= '【' . $before->get($name)->get($key)->value . '】';
            }
          }
          $label = $this->getlabel($name);
          if($label == $name) {
            $label = $property->getFieldDefinition()->getLabel();
          }
          if(empty($before_item_value)) {
            $before_item_value = '【】';
          }
          $diff =  $label . '：'. $before_item_value .'变更为'. $current_item_value;
          $result[$name] = $diff;
        }
      }
      if(isset($current->other_data)) {
        $current_arr = $current->other_data['data'];
        $before_arr = $before->other_data['data'];
        foreach($current_arr as $key => $value) {
          if($value != $before_arr[$key]) {
            $diff = $this->diff($key, $current, $before, 'other');
            if(empty($diff)) {
              $label = $this->getLabel($key);
              if(is_array($before_arr[$key])) {
                $diff = $label . '：【Array】变更为【Array】';
              } else {
                $diff = $label . '：【'. $before_arr[$key] .'】变更为【'. $value .'】';
              }
            }
            $result['other_' . $key] = $diff;
          }
        }
      }
    }
    $result = $this->diff_alter($result, $current, $before);
    return $result;
  }

  /**
   * 构建日志消息
   * @param
   *  - $entity 当前操作实体
   *  - $action 当前操作（如insert, update, delete等）
   */
  protected function message($entity, $action) {
    return null;
  }

  /**
   * 获取label
   */
  protected function getLabel($name) {
    return $name;
  }

  /**
   * 字段差异比较
   * @param
   *  -$type: 比较字段是来自实体(enity)还是其它数据(other)
   */
  protected function diff($name, $current, $before, $type) {
    return null;
  }

  protected function diff_alter($result, $current, $before) {
    return $result;
  }
}
