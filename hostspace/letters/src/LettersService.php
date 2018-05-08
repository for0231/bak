<?php
/**
 * @file
 *  故障申报相关的数据库操作
 */
namespace Drupal\letters;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LettersService {

   /**
    * The database service.
    *
    * @var \Drupal\Core\Database\Connection
    */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   *  根据角色获取指定角色下所有的用户
   */
  public function getUserByRole($role) {
    $query = $this->database->select('user__roles','r')
      ->fields('r', array('entity_id', 'roles_target_id')) 
      ->condition('r.roles_target_id', $role);
    return $query->execute()->fetchAll();
  }

  /**
   * 存储站内信息
   *
   * @param $outbox array()
   *  发件箱字段数组
   *
   */
  public function saveStationLetter($outbox, $inbox) {
    $result = 0;
    $transaction = $this->database->startTransaction();
    try {
      $this->database->insert('letter_outbox')
        ->fields($outbox)
        ->execute();
      // 保存到收件箱
      $this->saveStationLetterToInbox($inbox);

      $result = 1;
    } catch (\Exception $e) {
      $transaction->rollback();
    }
    return $result;
  }

  /**
   * 将发送的邮件存储到收件箱
   *
   * @param $inbox array()
   *  收件箱字段数组
   */
  public function saveStationLetterToInbox($inbox) {
    foreach($inbox as $fields) {
      $this->database->insert('letter_inbox')
        ->fields($fields)
        ->execute();
    }
  }

  /**
   * 查询用户发送的邮件
   *
   * @param $uid integer
   *   用户编号
   */
  public function getOutboxData($uid) {
    $query = $this->database->select('letter_outbox','out_box')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
    $query->fields('out_box')
      ->condition('uid ', $uid);

    $query->limit(PER_PAGE_COUNT); 

    return $query->execute()->fetchAll();
  }

  /**
   * 查询收件箱/发件箱中的指定信件详情
   *
   * @param $id integer
   *   信件编号
   * @param $type
   *  类型  outbox 发件箱  inbox 收件箱
   */
  public function getLetter($id, $type) {
    $letter_type = ($type == 'inbox') ? 'letter_inbox' : 'letter_outbox';
    $query = $this->database->select($letter_type,'l')
      ->fields('l')
      ->condition('id ', $id);
    return $query->execute()->fetchObject();
  }

  /**
   * 从收件箱/发件箱中删除指定信件
   *
   * @param $letter_id integer
   *   信件编号
   *
   * @param $type
   *  类型  outbox 发件箱  inbox 收件箱
   */

  public function deleteLetter($letter_id, $type) {
   $letter_type = ($type == 'inbox') ? 'letter_inbox' : 'letter_outbox';
   $result = $this->database->delete($letter_type)
      ->condition('id', $letter_id)
      ->execute();
    return $result;
  }
  /**
   * 查询用户收到的邮件
   *
   * @param $uid integer
   *   用户编号
   */
  public function getinboxData($uid) {
     $query = $this->database->select('letter_inbox','l')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender');
     $query->fields('l')
       ->condition('uid ', $uid);;
     $query->orderBy('receive_time', 'DESC');
     $query->limit(PER_PAGE_COUNT);
    return $query->execute()->fetchAll();
  }

  /**
   * 信件设为已读
   *
   * @param $letter_id  integer
   *   信件编号
   */
  public function setLetterHasReaded($letter_id) {
    $this->database->update('letter_inbox')
      ->fields(array('is_read' => 1))
      ->condition('id', $letter_id)
      ->execute();
  }

  /**
   * 查询用户所有的未读邮件的数量
   *
   * @param $uid
   */
  public function getNotReadCountByUid($uid) {
    $query = $this->database->select('letter_inbox','l');
    $query->fields('l')
      ->condition('uid ', $uid)
      ->condition('is_read', 0);
    return $query->execute()->fetchAll();
  }

  /**
   * 给客户的交付信息
   */
  public function sendCustomer($hostclient) {
    $bip_options = array();
    $bips_info = '';
    $bips_values = $hostclient->get('ipb_id');
    foreach($bips_values as $value) {
      $ipb_obj = $value->entity;
      if($ipb_obj) {
        $entity_type = taxonomy_term_load($ipb_obj->get('type')->value);
        $bips_info .= '<li>' . $ipb_obj->label() . '-' . $ipb_obj->get('type')->entity->label() . '</li>';
      }
    }
    $title = "服务器[{$hostclient->get('server_id')->entity->label()}]开通通知";
    $business_info = '';
    $combine_list = \Drupal::service('hostclient.serverservice')->loadHostclientBusiness($hostclient->id());
    foreach($combine_list as $item) {
      $business = entity_load('product_business', $item->business_id);
      $business_values = $item->business_content;
      $value_arr = explode(',', $business_values);
      $value_html = '';
      foreach($value_arr as $value) {
        $value_text = product_business_value_text($business, $value);
        $value_html .= '<span>'. $value_text .'</span>';
      }
      $business_info .= '<li>'. $business->label() .'：'. $value_html .'</li>';
    }
    $content = <<<EOT
<hr />
<h4>亲爱的用户<font color=red>{$hostclient->get('client_uid')->entity->getUsername()}</font><strong>你好</strong></h4>
<div>服务器详情如下:</div>
<div>配置详情: <font color=red>{$hostclient->get('product_id')->entity->label()}</font></div>
<div>外网IP: <ul>{$bips_info}</ul></div>
<div>子网掩码: <font color=red>{$hostclient->getSimpleValue('server_mask')}</font></div>
<div>网&nbsp;&nbsp;&nbsp; 关: <font color=red>{$hostclient->getSimpleValue('server_gateway')}</font></div>
<div>系统类型: <font color=red>{$hostclient->getObjectId('server_system')}</font></div>
<div>系统账号: <font color=red>administrator/root</font></div>
<div>系统密码: <font color=red>{$hostclient->getSimpleValue('init_pwd')}</font></div>
<div>当前业务信息：<ul>{$business_info}</ul></div>
<div>收到请确认，有问题请及时反馈。</div>
<u class="fr"><strong>Itace</strong></u>
EOT;

    $inbox[$hostclient->get('client_uid')->target_id] = array(
      'uid' => $hostclient->get('client_uid')->target_id,
      'title' => $title,
      'content' => $content,
      'receive_time' => REQUEST_TIME,
      'from_uid' => \Drupal::currentUser()->id()
    );
    $outbox = array(
      'title' => $title,
      'content' => $content,
      'post_time' => REQUEST_TIME,
      'uid' => \Drupal::currentUser()->id(),
    );
    $outbox += array('to_uid' => $hostclient->get('client_uid')->target_id);
    $this->saveStationLetter($outbox, $inbox);
  }
}
