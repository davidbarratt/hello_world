<?php

namespace Drupal\hello_world\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Lists the Hello World Articles.
 *
 * @Block(
 *   id = "hello_world_list",
 *   admin_label = @Translation("Hello World List"),
 * )
 */
class HelloWorld extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Entity Query.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * {@inheritdoc}
   *
   * @param \rupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager Service.
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition,
                              EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $storage = $this->entityTypeManager->getStorage('node');

    // Get the Hello World Articles that are in enabled sections.
    $query = $storage->getQuery();
    $query->condition('type', 'hello_world_article');
    $query->condition('field_sections.entity.field_enabled', 1);
    $query->sort('created', 'DESC');
    $result = $query->execute();
    $entities = $storage->loadMultiple($result);

    // Generate the initial cacheable metadata.
    $cache = new CacheableMetadata();

    // Any node or taxonomy term that is updated should bust the cache.
    // Ideally we would only purge when updates are made to the known bundles,
    // but we'll have to wait until #2145751
    // ({@link https://www.drupal.org/node/2145751}) is reolsved.
    $node_tags = $storage->getEntityType()->getListCacheTags();
    // Since a taxonomy term could be changed from 'enabled' to 'disabled',
    // we'll need to bush the cache when any taxonomy term is updated.
    $taxonomy_tags = $this->entityTypeManager->getStorage('taxonomy_term')->getEntityType()->getListCacheTags();
    $cache->setCacheTags(Cache::mergeTags($node_tags, $taxonomy_tags));

    $build = [];
    if (!empty($entities)) {
      $links = [];

      foreach ($entities as $entity) {
        // Individual entities could have their own cacheable metadata and
        // should be merged with the the overall cache object.
        $cache = $cache->merge(CacheableMetadata::createFromObject($entity));

        // Access results could have their own cacheable metadata and
        // should be merged with the the overall cache object.
        $access = $entity->access('view', NULL, TRUE);
        $cache = $cache->merge(CacheableMetadata::createFromObject($access));

        // Only Show Links the user has access to.
        if ($access->isAllowed()) {
          $link = $entity->toLink();
          $links[] = [
            'title' => $link->getText(),
            'url' => $link->getUrl(),
          ];
        }
      }

      $build = [
        '#theme' => 'links',
        '#links' => $links,
      ];
    }

    $cache->applyTo($build);

    return $build;
  }

}
