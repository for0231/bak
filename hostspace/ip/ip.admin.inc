<?php

/**
 * @file
 * Administrative page callbacks for the ip Logging module.
 */

/**
 * Creates a list of ip log administration filters that can be applied.
 *
 * @return array
 *   Associative array of filters. The top-level keys are used as the form
 *   element names for the filters, and the values are arrays with the following
 *   elements:
 *   - title: Title of the filter.
 *   - where: The filter condition.
 *   - options: Array of options for the select list for the filter.
 */
function ip_log_filters() {
  $filters = array();

  foreach (_iplog_get_message_types() as  $value) {
    foreach ($value as $key => $type) { 
      $types[$key] = t($type);
    }
  }

  if (!empty($types)) {
    $filters['type'] = array(
      'title' => t('Type'),
      'where' => "w.type = ?",
      'options' => $types,
    );
  }
   return $filters;
}

