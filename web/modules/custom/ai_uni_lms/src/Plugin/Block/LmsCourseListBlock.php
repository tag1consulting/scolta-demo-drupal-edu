<?php

declare(strict_types=1);

namespace Drupal\ai_uni_lms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Renders published lms_course groups as teaser cards.
 *
 * @Block(
 *   id = "ai_uni_lms_course_list",
 *   admin_label = @Translation("LMS Course List"),
 *   category = @Translation("AI Uni"),
 * )
 */
class LmsCourseListBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    string $plugin_id,
    mixed $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $storage = $this->entityTypeManager->getStorage('group');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'lms_course')
      ->condition('status', 1)
      ->sort('label', 'ASC')
      ->execute();

    if (empty($ids)) {
      return [];
    }

    $items = [];
    foreach ($storage->loadMultiple($ids) as $group) {
      $url = $group->toUrl('canonical')->setAbsolute(FALSE)->toString();

      $body = '';
      if ($group->hasField('body') && !$group->get('body')->isEmpty()) {
        $item = $group->get('body')->first();
        $raw = strip_tags((string) ($item->processed ?: $item->value));
        $body = mb_strlen($raw) > 200 ? mb_substr($raw, 0, 197) . '…' : $raw;
      }

      $lessonCount = 0;
      if ($group->hasField('lessons') && !$group->get('lessons')->isEmpty()) {
        $lessonCount = count($group->get('lessons')->getValue());
      }

      $items[] = [
        'id' => $group->id(),
        'title' => $group->label(),
        'url' => $url,
        'body' => $body,
        'lesson_count' => $lessonCount,
      ];
    }

    return [
      '#theme' => 'ai_uni_lms_course_list',
      '#courses' => $items,
      '#cache' => [
        'tags' => $this->getCacheTags(),
        'contexts' => $this->getCacheContexts(),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['group_list']);
  }

}
