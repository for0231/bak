<?php
namespace Drupal\hostlog;

use Drupal\Core\Database\Connection;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactoryInterface;
/**
 * 讯云版提醒功能服务
 */
class OperationReminderService {
  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  public function __construct(Connection $database, ConfigFactoryInterface $configFactory) {
    $this->database = $database;
    $this->configFactory = $configFactory;
  }

  /**
   * 记录操作日志
   * @code
   *  $context = array(
   *    'uid' => \Drupal::currentUser()->id(),
   *    'role' => '',
   *    'type' => '',
   *    'object' => '',
   *    'rank' => '',
   *  );
   *  @endcode
   */
  public function log($context) {
    $config = $this->configFactory->get('hostlog.settings');
    $this->database
      ->insert('xunyunreminder')
      ->fields(array(
        'uuid' => \Drupal::service('uuid')->generate(),
        'expiration' => REQUEST_TIME + $config->get('expiration_delay') * 60,
        'isaudio' => $config->get('isaudio'),
        'retips' => $config->get('retips'),
      ) + $context)
      ->execute();
  }

  /**
   * 保存提醒类型
   */
  public function typelog($context) {
    $query = $this->database->select('xunyunreminder_type', 'rt')
      ->fields('rt')
      ->condition('type', $context['type'])
      ->execute()
      ->fetchAll();
    if (empty($query)) {
      $this->database
        ->insert('xunyunreminder_type')
        ->fields($context)
        ->execute();
    }
  }

  /**
   * 获取声音提醒类型
   * @error 这个返回值有问题，不是数组或对象
   */
  public function getReminderAllTypes() {
    $query = $this->database->select('xunyunreminder_type', 'rt')
      ->fields('rt')
      ->orderBy('id', 'DESC')
      ->execute()
      ->fetchAll();
    return $query;
  }
}
