<?php

/**
 * @file
 * Contains \Drupal\test\Controller\TestController.
 */
namespace Drupal\test\Controller;


use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Url;


class TestController extends ControllerBase {

  public function testPolling() {
    set_time_limit(0);
    $pad = str_repeat(' ' ,1000);
    for($i; $i<10; $i++) {
      echo $i;
      //echo $pad . '<br>';
      ob_flush();
      flush();
      sleep(1);
    }
    return new Response('abc');
  }
}
