<?php
/**
 * @file
 * Contains \Drupal\fw_config\WdConnect.
 */

namespace Drupal\fw_config;

use Drupal\Core\Url;

class WdConnect{

  /**
   * 保存cookie
   */
  public $cookie_jar ="/tmp/pic.cookie";
  //public $cookie_jar ="pic.cookie";
  public $loginurl ="http://162.212.181.3:10000/login";
  
  public function init(){
      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $this->loginurl);
      curl_setopt($ch, CURLOPT_HEADER, 0);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie_jar);
      $content = curl_exec($ch);
      curl_close($ch);
  }
  public function login(){
    $post = "username=admin&password=open.xunyun.2017";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$this->loginurl);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
  }

  public function getRead($url){
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
    $html=curl_exec($ch);
    $result = $this->get_td_array($html);

    return $result;
  }
  public function commonFunction($url,$params,$method){
    if(!empty($params)){
      $params = http_build_query($params);
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL,$url);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    switch ($method){  
      case "GET" :
        curl_setopt($ch, CURLOPT_HTTPGET, true);
        break;
      case "POST":
        curl_setopt($ch, CURLOPT_POST,true);   
        curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
        break;
      case "PUT" :
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");   
        curl_setopt($ch, CURLOPT_POSTFIELDS,$params);
        break;
      case "DELETE":
        curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        break;
    }
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie_jar);
    $result=curl_exec($ch);
    curl_close($ch);
    return $result;
  }
  public function get_td_array($table) {
    $td_array = array();
    $table = preg_replace("'<table[^>]*?>'si","",$table);
    $table = preg_replace("'<td[^>]*?>'si","",$table);
    $table = str_replace("</td>","{td}",$table);
    $regex1="/.*?<tr .*?id=\"(.*?)\">.*?/";
    if(preg_match_all($regex1, $table, $matches)){
      $list = $matches[1];
      array_unshift($list,'');
    }
    $table = preg_replace("'<tr[^>]*?>'si","",$table);
    $table = str_replace("</tr>","{tr}",$table);
    //去掉 HTML 标记 
    $table = preg_replace("'<[/!]*?[^<>]*?>'si","",$table);
    //去掉空白字符
    $table = preg_replace("'([rn])[s]+'","",$table);
    $table = str_replace(" ","",$table);
    $table = str_replace(" ","",$table);
    $table = explode('{tr}', $table);
    array_pop($table);
    foreach ($table as $key=>$tr) {
      $td = explode('{td}', $tr);
      array_pop($td);
      $td_array[]= $td;
    }
    foreach($td_array as $key1=>$tdvalue){
      foreach($list as $key2 =>$listvalue){
        if($key1==$key2){
          $td_array[$key2][5]=$listvalue;
        }
      }
    }
    return $td_array;
  }
}
?>