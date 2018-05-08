<?php


namespace Drupal\online;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

class OnlineContentListBuilder extends EntityListBuilder {
  public function buildHeader() {
    $header['id'] = $this->t('ID');
    $header['name'] = $this->t('用户名');
    $header['email'] = $this->t('邮箱');
    $header['remote_ip'] = $this->t('IP地址');
    $header['time'] = $this->t('创建时间');
    $header['status'] = $this->t('接收状态');
    $header['reply_name'] = $this->t('接收者');
    return $header + parent::buildHeader();
  }
  public function buildRow(EntityInterface $entity){
    $row['id'] = $entity->id();
    $row['name'] = $entity->label();
    $row['email'] = $entity->get('email')->value;
    $row['remote_ip'] = $_SERVER["REMOTE_ADDR"];
    $row['time'] = format_date($entity->get('created')->value,'custom','Y-m-d H:i:s');
    $status = $entity->get('status')->value;
    if(!$status){
      $row['status'] = '未接受';
    }
    else{
      $row['status'] = '已接受';
    }
    $uid = $entity->get('uid')->target_id;
    $userentity = entity_load('user', $uid);
    $adminname = $userentity->get('name')->value;
    
    if(!$uid){
      $row['reply_name'] = '无回复';
    }else{
      $row['reply_name'] = $adminname;
    }
    return $row + parent::buildRow($entity);
  }

  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = array();
    if($entity->hasLinkTemplate('show-form')){
      $operations['show_form'] = array(
        'title' => '接受',
        'weight' => 1,
        'url' => $entity->urlInfo('show-form'),
        'attributes' => array(
          'class' => array('btn btn-danger'),
        ),
        '#attached' => array(
          'library' => array('online/online-styling')
        )
      );
    }
    if($entity->access('delete')&& $entity->hasLinkTemplate('delete-form')){
      $operations['delete_form'] = array(
        'title' => '屏蔽',
        'weight' => 2,
        'url' => $entity->urlInfo('delete-form'),
        'attributes' => array(
          'class' => array('btn btn-danger'),
        ),
        '#attached' => array(
          'library' => array('online/online-styling')
        )
      );  
    }  
    return $operations + parent::getDefaultOperations($entity);
  }

}

