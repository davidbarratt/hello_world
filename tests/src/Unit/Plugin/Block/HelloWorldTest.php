<?php

namespace Drupal\Tests\hello_world\Unit\Plugin\Block;

use Drupal\hello_world\Plugin\Block\HelloWorld;
use Drupal\Tests\UnitTestCase;

/**
 * Tests Hello World Block.
 *
 * @group hello_world
 */
class HelloWorldTest extends UnitTestCase {

  /**
   * Tests the build method.
   */
  public function testBuild() {
    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [
      'provider' => 'hello_world',
    ];
    $query = $this->getMock('Drupal\Core\Entity\Query\QueryInterface');
    $entity_query = $this->getMockBuilder('Drupal\Core\Entity\Query\QueryFactory')
      ->disableOriginalConstructor()
      ->getMock();
    $entity_query->method('get')
      ->with($this->equalTo('node'))
      ->willReturn($query);

    $block = new HelloWorld($configuration, $plugin_id, $plugin_definition, $entity_query);
    $build = $block->build();

    $this->assertInternalType('array', $build);
  }

}
