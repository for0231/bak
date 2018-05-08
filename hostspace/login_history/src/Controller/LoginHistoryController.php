<?php

/**
 * @file
 * Contains \Drupal\login_history\Controller\LoginHistoryController.
 */

namespace Drupal\login_history\Controller;

use Drupal\Core\Access\AccessInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\user\UserInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller routines for Login history routes.
 */
class LoginHistoryController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;
  public function __construct(Connection $database) {
    $this->database = $database;
  }
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('database')
    );
  }


  /**
   * Displays a report of user logins.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function report() {
    $user = \Drupal::currentUser();
    $header = array(
      array('data' => t('Date'), 'field' => 'lh.login', 'sort' => 'desc'),
      array('data' => t('Username'), 'field' => 'u.name'),
      array('data' => t('IP Address'), 'field' => 'lh.hostname'),
      array('data' => t('Login status'), 'field' => 'lh.one_time'),
      //array('data' => t('User Agent')),
    );

    $query = $this->database->select('login_history', 'lh')
      ->extend('Drupal\Core\Database\Query\TableSortExtender')
      ->extend('Drupal\Core\Database\Query\PagerSelectExtender');

    $query->join('users_field_data', 'u', 'lh.uid = u.uid');
    // 仅显示出了个人账户的登录日志
    $query->fields('lh')
      ->fields('u', array('name'))
      ->condition('lh.uid', $user->id())
      ->limit(50)
      ->orderByHeader($header);
    $result = $query->execute()->fetchAll();
    return $this->generateReport($result, 'table', $header);
  }

  /**
   * Render login histories.
   *
   * @param $history
   *   A list of login history objects to output.
   * @param $format
   *   (optional) The format to output log entries in; one of 'table', 'list' or
   *   'text'.
   * @param $header
   *   (optional) An array containing header data for $format 'table'.
   *
   */
  function generateReport(array $history, $format = 'table', array $header = array()) {
    // Load all users first.
    $uids = array();
    foreach ($history as $entry) {
      $uids[] = $entry->uid;
    }
    $users = user_load_multiple($uids);

    switch ($format) {
      case 'text':
        // Output delimiter in first line, since this may change.
        $output = '\t' . "\n";

        foreach ($history as $entry) {
          $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
          $row = array(
            format_date($entry->login, 'small'),
            SafeMarkup::checkPlain($users[$entry->uid]->getUsername()),
            SafeMarkup::checkPlain($entry->hostname),
            empty($entry->one_time) ? t('常规登录') : t('一次性登录'),
            //SafeMarkup::checkPlain($entry->user_agent),
          );
          $output .= implode("\t", $row) . "\n";
        }
        break;

      case 'list':
        $output = '';
        foreach ($history as $entry) {
          $one_time = empty($entry->one_time) ? t('Regular login') : t('One-time login');
          $output .= '<li>';
          $output .= '<span class="login-history-info">' . SafeMarkup::checkPlain($users[$entry->uid]->getUsername()) . ' ' . format_date($entry->login, 'small') . ' ' . SafeMarkup::checkPlain($entry->hostname) . '</span>';//' ' . $one_time . ' ' . SafeMarkup::checkPlain($entry->user_agent) . '</span>';
          $output .= '</li>';
        }
        if ($output) {
          $output = '<ul id="login-history-backlog">' . $output . '</ul>';
        }
        break;

      case 'table':
      default:
        $rows = array();
        foreach ($history as $entry) {
          $rows[] = array(
            format_date($entry->login, 'custom', 'Y-m-d H:i:s'),
            SafeMarkup::checkPlain($users[$entry->uid]->getUsername()),
            SafeMarkup::checkPlain($entry->hostname),
            empty($entry->one_time) ? t('常规登录') : t('一次性登录'),
            //SafeMarkup::checkPlain($entry->user_agent),
          );
        }
        $output['history'] = array(
          '#theme' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#attributes' => array('class' => array('user-table')),
          '#empty' => t('No login history available.'),
        );
        $output['pager'] = array(
          '#type' => 'pager',
        );
        break;
    }

    return $output;
  }

  /**
   * Checks access for the user login report.
   *
   * @param string $user
   *   The request to check access for.
   */
  public function checkUserReportAccess() {
    return AccessResult::allowedIf(\Drupal::currentUser()->hasPermission('view own login history'));
  }

}
