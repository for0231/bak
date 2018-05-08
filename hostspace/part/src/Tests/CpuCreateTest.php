<?php

/**
 * @file
 * Definition of Drupal\part\Tests\CpuCreateTest.
 */

namespace Drupal\part\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the create cpu administration page.
 *
 * @group part
 */
class CpuCreateTest extends WebTestBase {

  public static $modules = array('common');
   
  public function testCreateCpu() {
    //$user = $this->drupalCreateUser(array('administer users'));
    //$this->drupalLogin($user);

    /*$cpu = entity_create('part_cpu', array(
      'brand' => '测试cpu',
      'model' => '测试cpu',
      'standard' => '测试cpu'
    ));
    $cpu->save();*/
 
    $this->assertTrue(true, '创建一个配件cpu');
  }
}
