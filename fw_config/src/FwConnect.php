<?php
/**
 * @file
 * Contains \Drupal\fw_config\FwConnect.
 */

namespace Drupal\fw_config;

use Drupal\Core\Url;

class FwConnect {
  /**
   * 保存cookie
   */
  protected $cookie = '';
  /**
   * 登录墙
   */
  private function connect($hosts) {
    //登陆jd.读取COOKIE
    $return = array();
    foreach ($hosts as $key => $host) {
      $cookie = array();
      $ip = $host['ip'];
      $post['param_type'] = "login";
      $post['param_username'] = $host['user'];
      $post['param_password'] = $host['pass'];
      $postdata = http_build_query($post);

      $url = "http://$ip/cgi-bin/login.cgi";
      try {
        $response = \Drupal::httpClient()->post($url, array(
          'form_params' => $post
        ));
      } catch (\GuzzleHttp\Exception\RequestException $e) {
        continue;
      }
      $headers = $response->getHeaders();
      foreach($headers['Set-Cookie'] as $item) {
        if(stripos($item, 'history=') !== false) {
          continue;
        }
        if(stripos($item, 'sid=') !== false) {
          if($sid = substr($item, 4)) {
            $cookie['sid'] = $sid;
          }
        }
        if(stripos($item, 'page=') !== false) {
          $cookie['page'] = substr($item, 5);
        }
      }
      $return[$key] = $cookie;
    }
    return $return;
  }

  /**
   * 获取cookie
   */
  public function login($ip, $user, $pass) {
    $cookie_arr = array();
    if(isset($_SESSION['cookie'][$ip])) {
      $cookie_arr = $_SESSION['cookie'][$ip];
    } else {
      $cookies = $this->connect(array(array(
        'ip' => $ip,
        'user' => $user,
        'pass' => $pass
      )));
      if(!empty($cookies)) {
        $cookie_arr = $cookies[0];
        $_SESSION['cookie'][$ip] = $cookie_arr;
      }
    }
    $cookie_headers = '';
    foreach ($cookie_arr as $cookieKey => $cookieVal) {
      $cookie_headers .= $cookieKey . "=" . $cookieVal . "; ";
    }
    if(!empty($cookie_headers)) {
      $this->cookie = substr($cookie_headers, 0, -2);
      return true;
    }
    return false;
  }
  
  /**
   * 获取数据
   *  $url:获取数据的地址
   *  $cookie: 登录此防火墙的cookie
   */
  public function getRead($url) {
    try {
      $response = \Drupal::httpClient()->get($url, array(
        'headers' => array('cookie' => $this->cookie)
      ));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      return $e->getMessage();
    }
    $r = (string)$response->getBody();
    $xml = simplexml_load_string($r,'SimpleXMLElement', LIBXML_NOERROR);
    if($xml === false) {
      return '返回值错误';
    }
    return $xml;
  }
  /**
   * 保存数据到防火墙
   */
  public function postData($url, $form_params) {
    $options = array(
      'headers' => array(
        'Cookie' => $this->cookie
      ),
      'form_params' => $form_params
    );
    try {
      $response = \Drupal::httpClient()->post($url, $options);
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      return $e->getMessage();
    }
    $str = (string)$response->getBody();
    $xml = simplexml_load_string($str,'SimpleXMLElement', LIBXML_NOERROR);
    if($xml === false) {
      return '返回值错误';
    }
    return $xml;
  }
}
