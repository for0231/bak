<?php

/**
 * @file
 * Contains \Drupal\voice\Controller\VoiceController.
 */

namespace Drupal\voice\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

class VoiceController extends ControllerBase {

  public function getVoice(){
    $cuid = \Drupal::currentUser()->id();
    $fileName = 'sites/default/files/voice_task/task'.$cuid;
    $tmpPath = 'sites/default/files/voice/';
    if(file_exists($fileName)){
      $jInfo = file($fileName);
    }
    if(!empty($jInfo)){
      foreach($jInfo as $k=>$v){
        if($v == "\n"){
          unset($jInfo[$k]);
        }
      }
    }
    if(!empty($jInfo)){
      $jInfo = array_values($jInfo);
      $info = json_decode($jInfo[0],true);
      if(file_exists($tmpPath.$info['name'])){
        if(isset($info['uids']) && in_array($cuid, $info['uids'])){
          $resp = '/'.$tmpPath.$info['name'];
          unset($jInfo[0]);
          $str = implode("\n",$jInfo);
          file_put_contents($fileName,$str);
          if( (time() - $info['created']) > 10){
            return new Response('false');
          }
          return new Response($resp);
        }
      }
    }
    return new Response('false');
  }
}
