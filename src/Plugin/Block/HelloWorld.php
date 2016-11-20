<?php

namespace Drupal\hello_world\Plugin\Block;

use Drupal\Core\Block\BlockBase;
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
    $query = $storage->getQuery();
    $query->condition('field_sections.entity.field_enabled', 1);
    $result = $query->execute();
    $entities = $storage->loadMultiple($result);

    $cache = new CacheableMetadata();
    $cache->setCacheTags($storage->getEntityType()->getListCacheTags());

    $build = [];
    if (!empty($entities)) {
      $links = [];

      foreach ($entities as $entity) {
        $cache = $cache->merge(CacheableMetadata::createFromObject($entity));

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
