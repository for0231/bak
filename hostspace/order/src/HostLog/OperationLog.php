<?php
namespace Drupal\order\HostLog;

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
    if(isset($entity->other_status)) {
      $other_data = $entity->other_data['data'];
      $other_status = $entity->other_status;
      switch ($other_status) {
        case 'change_price_apply':
          if($action == 'price_apply') {
            $message = strtr('用户【%user】为订单【%order】申请了改价，其折扣金额为￥%price', array(
              '%user' => \Drupal::currentUser()->getUsername(),
              '%order' => $entity->getSimpleValue('code'),
              '%price' => $other_data['change_price']
            ));
          } else {
            $audit = $other_data['status'] == 2 ? '同意' : '拒绝';
            $message = strtr('%user%audit了订单【%order】的改价申请', array(
              '%user' => \Drupal::currentUser()->getUsername(),
              '%audit' => $audit,
              '%order' => $entity->getSimpleValue('code')
            ));
          }
          break;
        case 'trial_apply':
          if($action == 'trial_apply') {
            $message = strtr('用户【%user】为订单【%order】申请了试用。', array(
              '%user' => \Drupal::currentUser()->getUsername(),
              '%order' => $entity->getSimpleValue('code'),
            ));
          } else {
            $audit = $other_data['status'] == 2 ? '同意' : '拒绝';
            $message = strtr('%user%audit了订单【%order】的试用申请。', array(
              '%user' => \Drupal::currentUser()->getUsername(),
              '%audit' => $audit,
              '%order' => $entity->getSimpleValue('code')
            ));
          }
          break;
        case 'distribution_server':
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('系统为订单【%order】生成了一条未处理服务器。', array(
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'business_dept_accept':
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('业务部接受了订单【%order】的未处理服务器。', array(
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'business_dept_move':
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('业务部转交了订单【%order】的未处理服务器。', array(
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'business_dept_handle':
          $handle = $other_data['busi_status'] ? '处理完' : '编辑';
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('业务部%action了订单【%order】的未处理服务器。', array(
            '%action' => $handle,
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'tech_dept_accept':
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('技术部接受了订单【%order】的未处理服务器。', array(
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'tech_dept_move':
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('技术部转交了订单【%order】的未处理服务器。', array(
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'tech_dept_rollback':
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('技术部退回了订单【%order】的未处理服务器。', array(
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'tech_dept_handle':
          $handle = $other_data['tech_status'] ? '处理完' : '编辑';
          $order = entity_load('order', $other_data['handle_order_id']);
          $message = strtr('技术部%action了订单【%order】的未处理服务器。', array(
            '%action' => $handle,
            '%order' => $order->getSimpleValue('code')
          ));
          break;
        case 'server_stop':
          $cabinet_server = $entity->getObject('cabinet_server_id');
          $cabinet = $cabinet_server->getObject('cabinet_id');
          $room = $cabinet->getObject('rid')->label();
          if($action == 'server_stop') {
            $message = strtr('停用了用户【%user】的服务器【%server】。所属机房：【%room】', array(
              '%user' => $entity->getObject('client_uid')->label(),
              '%server' => $entity->getObject('ipm_id')->label(),
              '%room' => $room
            ));
          } else {
            $handle = $other_data['status'] == 2 ? '恢复' : '入库';
            $message = strtr('%action用户【%user】的停用服务器【%server】。所属机房：【%room】', array(
              '%action' => $handle,
              '%user' => $entity->getObject('client_uid')->label(),
              '%server' => $entity->getObject('ipm_id')->label(),
              '%room' => $room
            ));
          }
          break;
        case 'renew_server':
          $cabinet_server = $entity->getObject('cabinet_server_id');
          $cabinet = $cabinet_server->getObject('cabinet_id');
          $room = $cabinet->getObject('rid')->label();
          $message = strtr('用户【%user】了续费服务器【%server】有效期至【%expired】。所属机房：【%room】', array(
            '%user' => \Drupal::currentUser()->getUsername(),
            '%server' => $entity->getObject('ipm_id')->label(),
            '%expired' => format_date($entity->getSimpleValue('service_expired_date'), 'custom', 'Y-m-d H:i:s'),
            '%room' => $room,
          ));
          break;
        case 'upgrade_server':
          $cabinet_server = $entity->getObject('cabinet_server_id');
          $cabinet = $cabinet_server->getObject('cabinet_id');
          $room = $cabinet->getObject('rid')->label();
          $message = strtr('用户【%user】了升级服务器【%server】并生成了一条未处理服务器。所属机房：【%room】', array(
           '%user' => \Drupal::currentUser()->getUsername(),
           '%server' => $entity->getObject('ipm_id')->label(),
           '%room' => $room,
          ));
          break;
      }
    } else {
      $type = $entity->getEntityTypeId();
      if($action == 'remove_ip') {
        $message = strtr('移出服务器【%server】的IP。', array(
          '%server' => $entity->getObject('ipm_id')->label()
        ));
      } else if ($type == 'order' && $action == 'insert') {
        $message = strtr('用户【%user】创建了一个订单，订单编码为：%code。', array(
          '%user' => \Drupal::currentUser()->getUsername(),
          '%code' => $entity->getSimpleValue('code')
        ));
      }
    }
    return $message;
  }

  /**
   * 字段差异比较
   */
  protected function diff($name, $current, $before, $type) {
    if(isset($current->other_status)) {
      $other_data = $current->other_data['data'];
      $other_status = $current->other_status;
      if($other_status == 'change_price_apply' && $type == 'other') {
        if($name == 'status') {
          $audit = $other_data['status'] == 2 ? '同意' : '拒绝';
          return '审核状态：【'. $audit .'】';
        }
        if($name == 'audit_stamp') {
          $time = format_date($other_data['audit_stamp'], 'custom', 'Y-m-d H:i:s');
          return '审核时间：【'. $time .'】';
        }
        if($name == 'audit_uid') {
          $audit_user = entity_load('user', $other_data['audit_uid']);
          return '审核人：【'. $audit_user->label() .'】';
        }
      }
      if($other_status == 'trial_apply' && $type == 'other') {
        if($name == 'status') {
          $audit = $other_data['status'] == 2 ? '同意' : '拒绝';
          return '审核状态：【'. $audit .'】';
        }
        if($name == 'audit_date') {
          $time = format_date($other_data['audit_date'], 'custom', 'Y-m-d H:i:s');
          return '审核时间：【'. $time .'】';
        }
        if($name == 'audit_uid') {
          $audit_user = entity_load('user', $other_data['audit_uid']);
          return '审核人：【'. $audit_user->label() .'】';
        }
      }
      if($other_status == 'business_dept_accept' && $type == 'other') {
        if($name == 'busi_uid') {
          $user = entity_load('user', $other_data['busi_uid']);
          return '接受人：【'. $user->label() .'】';
        }
        if($name == 'busi_accept_data') {
          $time = format_date($other_data['busi_accept_data'], 'custom', 'Y-m-d H:i:s');
          return '接受时间：【'. $time .'】';
        }
      }
      if($other_status == 'business_dept_move' && $type == 'other') {
        if($name == 'busi_uid') {
          $user = entity_load('user', $other_data['busi_uid']);
          $bef_user = entity_load('user', $before->other_data['data']['busi_uid']);
          return '接受人：【'. $bef_user->label() .'】变更为【'. $user->label() .'】';
        }
      }
      if($other_status == 'business_dept_handle' && $type == 'other') {
        if($name == 'busi_status') {
          return '业务部处理状态：完成';
        }
        if($name == 'busi_complete_data') {
          $time = format_date($other_data['busi_complete_data'], 'custom', 'Y-m-d H:i:s');
          return '业务部处理完成时间：【'. $time .'】';
        }
      }
      if($other_status == 'tech_dept_accept' && $type == 'other') {
        if($name == 'tech_uid') {
          $user = entity_load('user', $other_data['tech_uid']);
          return '接受人：【'. $user->label() .'】';
        }
        if($name == 'tech_accept_data') {
          $time = format_date($other_data['tech_accept_data'], 'custom', 'Y-m-d H:i:s');
          return '接受时间：【'. $time .'】';
        }
      }
      if($other_status == 'tech_dept_move' && $type == 'other') {
        if($name == 'tech_uid') {
          $user = entity_load('user', $other_data['tech_uid']);
          $bef_user = entity_load('user', $before->other_data['data']['tech_uid']);
          return '接受人：【'. $bef_user->label() .'】变更为【'. $user->label() .'】';
        }
      }
      if($other_status == 'tech_dept_handle' && $type == 'other') {
        if($name == 'tech_status') {
          return '技术部处理状态：完成';
        }
        if($name == 'tech_complete_data') {
          $time = format_date($other_data['tech_complete_data'], 'custom', 'Y-m-d H:i:s');
          return '技术部处理完成时间：【'. $time .'】';
        }
      }
      if($other_status == 'server_stop' && $type == 'other') {
        if($name == 'handle_uid') {
          $user = entity_load('user', $other_data['handle_uid']);
          return '处理人：【'. $user->label() .'】';
        }
        if($name == 'handle_date') {
          $time = format_date($other_data['handle_date'], 'custom', 'Y-m-d H:i:s');
          return '处理时间：【'. $time .'】';
        }
        if($name == 'status') {
          $handle = $other_data['status'] == 2 ? '恢复' : '入库'; 
          return '处理状态：【'. $handle .'】';
        }
      }
    }
    $type = $current->getEntityTypeId();
    if($type == 'order') {
      if($name == 'discount_price') {
        $curr = $current->getSimpleValue('discount_price');
        $bef = $before->getsimpleValue('discount_price');
        return '优惠金额：【￥'. $bef .'】变更为【￥'. $curr .'】';
      }
    } else if($type == 'hostclient') {
      if($name == 'status') {
        $curr = hostClientStatus()[$current->getSimpleValue('status')];
        $bef = hostClientStatus()[$before->getSimpleValue('status')];
        return '状态：【'. $bef .'】变更为【'. $curr .'】';
      }
      if($name == 'equipment_date') {
        $curr_value = $current->getSimpleValue('equipment_date');
        $curr_time = format_date($curr_value, 'custom', 'Y-m-d H:i:s');
        $bef_value = $before->getSimpleValue('equipment_date');
        $bef_time =  empty($bef_value) ? '' : format_date($bef_value, 'custom', 'Y-m-d H:i:s');        
        return '上架时间：【'. $bef_time .'】变更为【'. $curr_time .'】';
      }
      if($name == 'service_expired_date') {
        $curr_value = $current->getSimpleValue('service_expired_date');
        $curr_time = format_date($curr_value, 'custom', 'Y-m-d H:i:s');
        $bef_value = $before->getSimpleValue('service_expired_date');
        $bef_time =  empty($bef_value) ? '' : format_date($bef_value, 'custom', 'Y-m-d H:i:s');        
        return '到期时间：【'. $bef_time .'】变更为【'. $curr_time .'】';
      }
      if($name == 'server_manage_card') {
        $curr_value = $current->getSimpleValue('server_manage_card');
        $curr_label = $curr_value ? '有' : '无';
        $bef_value = $before->getSimpleValue('server_manage_card');
        $bef_label = $bef_value ? '有' : '无';
        return '管理卡：【'. $bef_label .'】变更为【'. $curr_label .'】';
      }
    }
    return null;
  }

    /**
   * 获取label
   */
  protected function getLabel($name) {
    if($name == 'tech_description') {
       return '描述';
    }
    return $name;
  }

  protected function diff_alter($result, $current, $before) {
    if(isset($current->other_status)) {
      $other_status = $current->other_status;
      if($other_status == 'change_price_apply' || $other_status == 'trial_apply') {
        unset($result['status']);
      }
      if($other_status == 'business_dept_handle') {
        unset($result['cabinet_server_id']);
      }
      if($other_status == 'tech_dept_handle') {
        unset($result['other_tech_check_item']);
      }
      if($other_status == 'server_stop') {
        unset($result['status']);
      }
      if($other_status=='tech_dept_rollback') {
        unset($result['other_busi_status']);
        unset($result['other_tech_uid']);
        unset($result['other_tech_accept_data']);
      }
    }
    return $result;
  }
}
