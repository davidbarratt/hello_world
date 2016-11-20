<?php

namespace Drupal\Tests\hello_world\Unit\Plugin\Block;

use Drupal\Core\Link;
use Drupal\Core\Url;
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
    $node_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $node_type->expects($this->exactly(2))
      ->method('getListCacheTags')
      ->willReturn(['node_list']);
    $taxonomy_term_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $taxonomy_term_type->expects($this->exactly(2))
      ->method('getListCacheTags')
      ->willReturn(['taxonomy_term_list']);

    $node_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $node_storage->expects($this->exactly(2))
      ->method('getQuery')
      ->willReturn($query);
    $node_storage->expects($this->exactly(2))
      ->method('getEntityType')
      ->willReturn($node_type);

    $taxonomy_term_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');
    $taxonomy_term_storage->expects($this->exactly(2))
      ->method('getEntityType')
      ->willReturn($taxonomy_term_type);

    $entity_type_manager = $this->getMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_type_manager->expects($this->exactly(4))
      ->method('getStorage')
      ->will($this->returnValueMap([
        ['node', $node_storage],
        ['taxonomy_term', $taxonomy_term_storage],
      ]));

    // Test an empty query result.
    $block = new HelloWorld($configuration, $plugin_id, $plugin_definition, $entity_type_manager);
    $build = $block->build();

    $this->assertInternalType('array', $build);
    $this->assertCount(1, $build);
    $this->assertArrayHasKey('#cache', $build);
    $this->assertArrayHasKey('tags', $build['#cache']);
    $this->assertEquals(['node_list', 'taxonomy_term_list'], $build['#cache']['tags']);

    // Test a simple simple result set.
    $denied = $this->getMock('Drupal\Core\Access\AccessResultInterface');
    $denied->expects($this->once())
      ->method('isAllowed')
      ->willReturn(FALSE);
    $first = $this->getMock('Drupal\node\NodeInterface');
    $first->expects($this->once())
      ->method('access')
      ->willReturn($denied);
    $first->expects($this->once())
      ->method('getCacheTags')
      ->willReturn(['node:1']);

    $link = new Link('Second', new Url('entity.node.canonical', ['node' => 2]));
    $allowed = $this->getMock('Drupal\Core\Access\AccessResultInterface');
    $allowed->expects($this->once())
      ->method('isAllowed')
      ->willReturn(TRUE);
    $second = $this->getMock('Drupal\node\NodeInterface');
    $second->expects($this->once())
      ->method('access')
      ->willReturn($allowed);
    $second->expects($this->once())
      ->method('getCacheTags')
      ->willReturn(['node:2']);
    $second->expects($this->once())
      ->method('toLink')
      ->willReturn($link);

    $node_storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([
        $first,
        $second,
      ]);

    $build = $block->build();

    $this->assertInternalType('array', $build);
    $this->assertCount(3, $build);
    $this->assertArrayHasKey('#cache', $build);
    $this->assertArrayHasKey('tags', $build['#cache']);
    $this->assertEquals(['node:1', 'node:2', 'node_list', 'taxonomy_term_list'], $build['#cache']['tags']);
    $this->assertArrayHasKey('#links', $build);
    $this->assertCount(1, $build['#links']);
    $this->assertArrayHasKey(0, $build['#links']);
    $this->assertArrayHasKey('title', $build['#links'][0]);
    $this->assertEquals('Second', $build['#links'][0]['title']);
    $this->assertArrayHasKey('url', $build['#links'][0]);
    $this->assertEquals('Second', $build['#links'][0]['title']);
    $this->assertInstanceOf(Url::class, $build['#links'][0]['url']);
  }

}
