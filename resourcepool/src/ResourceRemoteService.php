<?php
/**
 * @file
 *  远程服务类
 */

namespace Drupal\resourcepool;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;

class ResourceRemoteService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  protected $cookie;


  public function __construct(Connection $database) {
    $this->database = $database;
  }

  protected function setCactiCookie($cookie) {
    setcookie('cacti_cookie_tmp', $cookie, '/');
    $this->cookie = $_COOKIE['cacti_cookie_tmp'];
  }


  private function checkCactiCookieValidate($url) {
    $resources = \Drupal::httpClient()->get($url, array(
      'headers' => array(
        'Cookie' => "Cacti=". $_COOKIE['cacti_cookie_tmp'],
       )
     ));
    if ($resources->getStatusCode() != 200) {
      $ip_addr = substr($url, 0, strpos($url, 'cacti'));
      $this->loginCacti($ip_addr);
    }
  }


  /**
   * @description 远程登录cacti.
   */
  public function loginCacti($cacti_url) {
    $response = \Drupal::httpClient()->get($cacti_url . "cacti/index.php");
    $body = (string)$response->getBody();
    $headers = $response->getHeaders();

    $set_cookie = $headers['Set-Cookie'][0];
    $arr = explode(';', $set_cookie);
    $cookie['Cacti'] = substr($arr[0],6);

    preg_match('/value="sid:.*"/', $body, $matches);
    $len = strlen($matches[0]) - 8;

    $data['__csrf_magic'] = substr($matches[0], 7, $len);
    $data['action'] = 'login';
    $data['login_password'] = 'jKQMGnx3';
    $data['login_username'] = 'IT';
    $login_res = \Drupal::httpClient()->post($cacti_url . "cacti/index.php", array(
      'form_params' => $data,
      'headers' => array(
        'Cookie' => "Cacti=". $cookie['Cacti'],
       )
    ));
    $this->setCactiCookie($cookie['Cacti']);
  }


  /**
   * @description 获取指定地址的Cacti图片.
   * @param $url http地址
   */
  public function getCactiGraphs($url) {
    //$this->checkCactiCookieValidate($url);
    $ip_addr = substr($url, 0, strpos($url, 'cacti'));
    $this->loginCacti($ip_addr);
    // 获取指定页面内容.
    $resources = \Drupal::httpClient()->get($url, array(
      'headers' => array(
        'Cookie' => "Cacti=". $_COOKIE['cacti_cookie_tmp'],
       )
     ));

    $resource_body = (string)$resources->getBody();

    kint($resource_body);
    $items = $this->getCactiImgContent($resource_body, $ip_addr);

    return $items;
  }


  /**
   * @description 解析获取图片内容.
   * @param $html_body html内容.
   */
  public function getCactiImgContent($html_body, $ip_addr) {
    $dom = \DOMDocument::loadHTML($html_body);
    $xpath = new \DOMXPATH($dom);
    $xpath_query = $xpath->query('//*[@class="graphimage"]');
    $node_list = $xpath->evaluate('//*[contains(@class, "graphimage")]');
    foreach ($node_list as $node) {
      preg_match('/rra_id=\d+/', $node->attributes->getNamedItem('src')->nodeValue, $r);
      preg_match('/\d+/', $r[0], $num);
      $result_src[$num[0]] = $this->getImagefromUrl($node->attributes->getNamedItem('src')->nodeValue, $ip_addr);
    }
    return $result_src;
  }

  /**
   * @description 获取图片，并临时保存.
   */
  public function getImagefromUrl($url, $ip_addr) {
    $resources = \Drupal::httpClient()->get( $ip_addr . 'cacti/' . $url, array(
      'headers' => array(
        'Cookie' => "Cacti=". $_COOKIE['cacti_cookie_tmp'],
       )
    ));
    $resources_body = $resources->getBody();
    $file_path = 'public://cacti_' . rand(10000, 99999). '.jpg';
    $file = file_save_data($resources_body, $file_path, FILE_EXISTS_REPLACE);
    $real_file = file_create_url($file_path);
    return $real_file;
  }


}

