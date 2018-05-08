<?php
/**
 * @file
 * Contains \Drupal\sync_migration\SyncUser.
 */

namespace Drupal\sync_migration;

class SyncUser{

  protected $base_url;

  public function __construct($domain_name) {
    $this->base_url = $domain_name;
  }

  public function synUserData() {
    try {
      $uri = $this->base_url . '/syn/data_output_to_drupal.php';
      $client = \Drupal::httpClient(); 
      $response = $client->get($uri, array('headers' => array('Accept' => 'application/json')));
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      echo 'Uh oh!' . $e->getMessage();
    }
    $json_str = (string)$response->getBody();
    $users = json_decode($json_str, true);

    foreach($users as $user) {
      if( $user['username'] == 'admin') {
        continue;
      }
      $entity = entity_create('user', array(
        'name' => $user['username'],
        'mail' => $user['mail'],
        'status' => $user['status'],
        'user_type' => $user['type'],
        'created' => $user['regdate'],
      ));
      $role = $user['role'];
      $dept_drupal = '';
      switch($role) {
        case '一级代理':
          $role_drupal = 'agent_I';
          break;
        case '二级代理':
          $role_drupal = 'agent_II';
          break;
        case '三级代理':
          $role_drupal = 'agent_3';
          break;
        case '四级代理':
          $role_drupal = 'agent_4';
          break;
        case '超级管理员':
          $role_drupal = 'administrator';
          $dept_drupal = '软件部';
          break;
        case '财务部':
          $role_drupal = 'finance';
          $dept_drupal = '财务部';
          break;
        case '客服部':
          $role_drupal = 'customer';
          $dept_drupal = '客服部';
          break;
        case '技术部':
          $role_drupal = 'technical';
          $dept_drupal = '技术部';
          break;
        case '网站推广':
          $role_drupal = 'website_promotion';
          $dept_drupal = '软件部';
          break;
        case '网维':
          $role_drupal = 'operation';
          $dept_drupal = '技术部';
          break;
        case '机房主管':
          $role_drupal = 'management';
          $dept_drupal = '技术部';
          break;
        default:
          $role_drupal = '';
          $dept_drupal = '';
      }
      $entity->addRole($role_drupal);
      if($user['type'] == 'client') {
        $entity->qq = $user['qq'];
        $entity->telephone = $user['telephone'];
        $entity->corporate_name = $user['corporate_name'];
        $entity->client_type = $user['client_type'];
        $entity->client_name = empty($user['client_name']) ? $user['username'] : $user['client_name'];
      } elseif($user['type'] == 'employee') {
        $entity->qq = $user['qq'];
        $entity->telephone = $user['telephone'];
        $entity->employee_name = $user['client_name'];
        $entity->qq = $user['qq'];
        $entity->telephone = $user['telephone'];
        if($dept_drupal) {
          $a = taxonomy_term_load_multiple_by_name($dept_drupal);
          $depart = '';
          foreach($a as $key=>$v) {
            $depart = $key;
          }
          $entity->department = $depart;
        }
      }
      $entity->save();
    }

    $config = \Drupal::configFactory()->getEditable('sync_migration.settings');
    $config->set('sync_user', 1);
    $config->save();
  }
}
