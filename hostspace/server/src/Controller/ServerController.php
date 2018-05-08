<?php

/**
 * @file
 * Contains \Drupal\server\Controller\ServerController.
 */

namespace Drupal\server\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Controller\ControllerBase;
use Drupal\taxonomy\TermInterface;
use Drupal\taxonomy\VocabularyInterface;

/**
 * Provides route responses for server.module.
 */
class ServerController extends ControllerBase {

  /**
   * 服务器详细信息
   */ 
  public function serverDetails() {
    return array('#markup'=> '未实现');
  }
}
