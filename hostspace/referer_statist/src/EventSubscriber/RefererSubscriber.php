<?php
/**
 * @file
 * Contains \Drupal\referer_statist\EventSubscriber\RefererSubscriber.
 */

namespace Drupal\referer_statist\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\referer_statist\RefererStatistService;

class RefererSubscriber implements EventSubscriberInterface {

  protected $rs_server;

  public function __construct(RefererStatistService $rs) {
    $this->rs_server = $rs;
  }

  public function refererRecord(GetResponseEvent $event) {
    $user = \Drupal::currentUser();
    if($user->id() > 0) {
      return;
    }
    $referer = $this->getReferer();
    $http_host = $_SERVER['HTTP_HOST'];
    $ip = $this->getIPaddress();
    if(!empty($ip) && stripos($http_host, $referer) === false) {
      $items = $this->rs_server->load(array('ip' => $ip), 'id');
      if(!empty($items)) {
        $item = reset($items);
        if(!empty($item->user_name)) {
          return;
        }
      }
      $value = array();
      $value['ip'] = $ip;
      $value['referer_site'] = $referer;
      $value['created'] = REQUEST_TIME;
      $value['url'] = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']; 
      $this->rs_server->add($value);
    }
  }

  private function getIPaddress() {
    $IPaddress='';
    if (isset($_SERVER)){
      if (isset($_SERVER["HTTP_X_FORWARDED_FOR"])){
        $IPaddress = $_SERVER["HTTP_X_FORWARDED_FOR"];
      } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
        $IPaddress = $_SERVER["HTTP_CLIENT_IP"];
      } else {
        $IPaddress = $_SERVER["REMOTE_ADDR"];
      }
    } else {
      if (getenv("HTTP_X_FORWARDED_FOR")){
        $IPaddress = getenv("HTTP_X_FORWARDED_FOR");
      } else if (getenv("HTTP_CLIENT_IP")) {
        $IPaddress = getenv("HTTP_CLIENT_IP");
      } else {
        $IPaddress = getenv("REMOTE_ADDR");
      }
    }
    return $IPaddress;
  }

  private function getReferer() {
    if(isset($_SERVER['HTTP_REFERER'])) {
      $url = $_SERVER['HTTP_REFERER'];
      $u = parse_url($url);
      return $u['host'];
    }
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = array('refererRecord');
    return $events;
  }
}
