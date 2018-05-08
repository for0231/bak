<?php

/**
 * @file
 * Contains \Drupal\Tests\part\Unit\PartManagerTest.
 */

namespace Drupal\Tests\part\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * 测试配件管理
 *
 * @group part
 */
class PartManagerTest extends UnitTestCase {
  
  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
   // $this->account = $this-> 
  }

  public function testCreateCpu() {
   
    $cpu = entity_create('part_cpu', array(
      'brand' => '测试cpu',
      'model' => '测试cpu',
      'standard' => '测试cpu'
    ));
    $cpu->save();
    $this->assertTrue($cpu->id(), '成功');
  }
}
