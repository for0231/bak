<?php

/**
 * @file
 * Contains \Drupal\common\CommonStatusUtility.
 */

namespace Drupal\common;

/**
 * Defines common utility tool functions for hostspace
 */
class CommonStatusUtility {

    /**
     * @param string $filename
     * @param string $variables
     * @return Object
     */
    function get_status_options($module, $variables) {
      $types = \Drupal::config($module.'.settings')->get($variables);
      foreach ($types as $key => $value) {
        $types[$key] = t($value);
      }
      return $types;
    }
}
