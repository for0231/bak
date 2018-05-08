<?php


namespace Drupal\online\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\online\OnlineContentListBuilder;

class OnlineController extends ControllerBase {
  public function like(Request $request){
    $uid = $request->request->get('uid');
    $online = entity_load('online_content', $uid);
    $num = $online->get('love_id')->value;
    $online->set('love_id',$num+1);
    $online->save();
    $str = '{ret:"ok"}';
    return new JsonResponse($str);    
   
  }
  public function dislike(Request $request){
    $uid = $request->request->get('uid');
    $online = entity_load('online_content', $uid);
    $num = $online->get('love_id')->value;
    $online->set('love_id',$num-1);
    $online->save();
    $str = '{ret:"ok"}';
    return new JsonResponse($str);
  }
  /**
   * 
   * @return multitype:string multitype:multitype:string   multitype:multitype:string  Ambigous <string, \Drupal\Core\GeneratedUrl>
   */
  public function onlineView() {   
    $build['contnet'] =  array(
      '#type' => 'container', 
      '#attributes' => array(
        'class' => array('ajax-content'),
        'content-path' => \Drupal::url('admin.online.list.content')
      ),
      '#attached' => array(
        'library' => array('online/admin-online-content')
      )
    );
    return $build;
  }
  /**
   * 
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function listContent() {
    $entity_type = \Drupal::entityTypeManager()->getDefinition('online_content');
    //调用listbuilder中的createInstance
    $listbuilder = OnlineContentListBuilder::createInstance(\Drupal::getContainer(), $entity_type);
    $build = $listbuilder->render();
    return new Response(drupal_render($build));
  }
  /**
   * 
   * 
   * @param Request $request
   */
  public function ajaxOnline(Request $request) {
    $uid =  $request->request->get('uid');
    $service = \Drupal::service('online.service');
    if(empty($uid)) {
      $name = $request->request->get('username');
      $email = $request->request->get('email');
      $entity = $service->insertOnline($name,$email);
      $uid = $entity->id();
    }
    $_SESSION['uid'] = $uid;
    $num = 0;
    if(!empty($request->request->get('num'))) {
      $num = $request->request->get('num');
    }
    $str = '';
    for($n=0; $n<180; $n++) { 
      $return = $service->selectOnlineBystatus($uid);
      if($return == 'ok') {
        $str = '{ret: "ok", uid: '. $uid .'}';
        break;
      } else {
        if($num != $return) {
          $str = '{ret:'. $return . ',uid: '. $uid .'}';
          break;
        }
      }
      sleep(1);
    }
    if(empty($str)) {
      $str = '{ret:"no"}';
    }
    return new JsonResponse($str);
  }

  /**
   * 
   * @param Request $request
   */
  public function adminSend(Request $request) {
    $service = \Drupal::service('online.service');
    $content = $request->request->get('userQuestion');
    //当前后台用户登录的ID对应的用户名
    $sender = \Drupal::currentUser()->id();
//     $sender = \Drupal::currentUser()->getAccount();
//     $sender = $sender->getUsername();
    
    //后台回复用户的ID
    $receiver = $request->request->get('uid');
    if(!empty($content)) {
      $entity = $service->insertContent($sender,$content,$receiver);
      $data['name'] = $entity->get('sender')->value;
      $data['time'] = date("Y-m-d H:i:s",$entity->get('created')->value);
      $data['content'] = $entity->get('content')->value;
    }
    return new JsonResponse($data);
  }  
  /**
   * 
   * @param Request $request
   */
  public function Send(Request $request) {
    $sender = $_SESSION['uid'];
    $service = \Drupal::service('online.service');
    $content = $request->request->get('userQuestion');
    $online_entity = entity_load('online_content', $sender);
    $receiver = $online_entity->get('uid')->value;
    if(!empty($content)) {
      $entity = $service->insertContent($sender,$content,$receiver);
      $uid = $entity->get('sender')->value;
      $onlinecontent = entity_load_multiple_by_properties('online_content',array('id'=>$uid));
      $data['name'] = $onlinecontent->get('ask_name')->value;
      $data['time'] = date("Y-m-d H:i:s",$entity->get('created')->value);
      $data['content'] = $entity->get('content')->value;
    }
    return new JsonResponse($data);
  }
  /**
   * 
   * @param Request $request
   */
  public function question(Request $request) {
    $uid = $request->request->get('uid');
    $homeentity = entity_load_multiple_by_properties('realy_content',array('sender'=> $uid));
    $adminentity = entity_load_multiple_by_properties('realy_content', array('receiver'=>$uid));
    $data = array();
    foreach ($homeentity as $key=>$home){
      $data[$key]['name'] = $home->get('sender')->value;
      $data[$key]['time'] = date("Y-m-d H:i:s",$home->get('created')->value);
      $data[$key]['content'] = $home->get('content')->value;
    }
    foreach ($adminentity as $key=>$admin){
      $data[$key]['name'] = $admin->get('sender')->value;
      $data[$key]['time'] = date("Y-m-d H:i:s",$admin->get('created')->value);
      $data[$key]['content'] = $admin->get('content')->value;
    }
    return new JsonResponse($data);
  }
  
}
