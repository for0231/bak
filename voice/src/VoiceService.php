<?php

/**
 * @file
 * Contains \Drupal\voice\VoiceService.
 */

namespace Drupal\voice;

use Drupal\Core\Entity\EntityInterface;

class VoiceService {

  protected $voice_dir;
  
  protected $task_dir;

 /**
  * String $content 声音内容
  * array $uids 提示的用户id
  */
  public function openVioce($content,$uids){
    $basePath = 'sites/default/files/';
    $this->voice_dir = $basePath.'voice';
    $this->task_dir = $basePath.'voice_task';
    if(!is_dir($this->voice_dir)){
      mkdir($this->voice_dir);
    }
    if(!is_dir($this->task_dir)){
      mkdir($this->task_dir);
    }
    $title = md5($content);
    $info = array(
      'name' => $this->getVoice($title,$content),
      'uids' => $uids,
      'created' => time()
    );
    $jInfo = json_encode($info);
    foreach($uids as $k=>$v){
      $fileName = $this->task_dir.'/task'.$v;
      if(!file_exists($fileName)){
        $file = fopen($fileName,'w');
        fclose($file);
      }
      $res = file_put_contents($fileName,$jInfo."\n",FILE_APPEND|LOCK_EX);
    }
    return $this;
  }
   
  public function getVoice($title,$content){
    if( !file_exists($this->voice_dir.'/'.$title.'.mp3') ){
      if($this->getVoiceByText($title,$content)){
        return $title.'.mp3';
      }
    }
    return $title.'.mp3';
  }

  public function getVoiceByText($title,$content){
    /*
     App ID: 8596258
     API Key: 7MxLNUYXLtCzbe6Ag42K7ewn
     Secret Key: d8cd43fd5abd0ed4d335e8ad1390479a
    */
    $ch = curl_init();
    //curl_setopt($ch, CURLOPT_TIMEOUT, $second);
    $url = 'https://openapi.baidu.com/oauth/2.0/token';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $post = array(
      'grant_type' => 'client_credentials',
      'client_id'  => '7MxLNUYXLtCzbe6Ag42K7ewn',
      'client_secret' => 'd8cd43fd5abd0ed4d335e8ad1390479a', 
    );
    $post = http_build_query($post);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    $data = curl_exec($ch);
    $resp = curl_multi_getcontent($ch);
    curl_close($ch);
    $res = json_decode($resp,true);
    $ch = curl_init();
    $url = 'http://tsn.baidu.com/text2audio';
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $post = array(
      'tex'  => $content,
      'lan'  => 'zh',
      'tok'  => $res['access_token'],
      'ctp'  => 1,
      'cuid' => microtime(),
      'per'  => 0,
      'pit'  => 6,
      'spd'  => 6
    );
    $post = http_build_query($post);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$post);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $data = curl_exec($ch);
    $resp = curl_multi_getcontent($ch);
    $fileName = $this->voice_dir.'/'.$title.'.mp3';
    if( !file_put_contents($fileName,$resp) ){
      return false;
    }
    return true;
  }

}
