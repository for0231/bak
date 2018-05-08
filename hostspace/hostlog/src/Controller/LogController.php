<?php

/**
 * @file
 * Contains \Drupal\hostlog\Controller\LogController.
 */

namespace Drupal\hostlog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\hostlog\OperationLogListBuild;
use Drupal\hostlog\Form\ViewLogForm;

class LogController extends ControllerBase {

  function logList() {
    $list = OperationLogListBuild::createInstance(\Drupal::getContainer());
    return $list->render();
  }

  function viewLog($log_id) {
    $view = new ViewLogForm();
    return $view->render($log_id);
  }
}
