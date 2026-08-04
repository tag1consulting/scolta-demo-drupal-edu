<?php

declare(strict_types=1);

namespace Drupal\ai_uni_lms\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\scolta\Service\ScoltaContentGatherer;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;
use Tag1\Scolta\Export\ContentItem;
use Tag1\Scolta\Index\TimestampManifest;

/**
 * Decorates ScoltaContentGatherer to support non-node entity types.
 *
 * The base gather() sorts by 'nid' which only exists on node entities.
 * This decorator uses the generic entity ID field for all other types.
 */
class ScoltaContentGathererDecorator extends ScoltaContentGatherer {

  // Redeclared here because the parent declares it private (not accessible in child).
  private readonly EntityTypeManagerInterface $entityTypeManager;

  public function __construct(
    private readonly ScoltaContentGatherer $inner,
    EntityTypeManagerInterface $entityTypeManager,
    Connection $database,
    ModuleHandlerInterface $moduleHandler,
    ConfigFactoryInterface $configFactory,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    parent::__construct($entityTypeManager, $database, $moduleHandler, $configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public function gatherCount(string $entityType, string $bundle): int {
    return $this->inner->gatherCount($entityType, $bundle);
  }

  /**
   * {@inheritdoc}
   *
   * Delegates to inner for 'node'; uses generic id sort for other types.
   *
   * The fourth parameter is an entity ID to restart the walk at, inclusive, and
   * not a page offset. It was declared here as `int $startPage = 0` and kept
   * that way after scolta-drupal widened it to `int|string|null $resumeFromId`,
   * which narrows the parameter and is a fatal when PHP defines this class, not
   * a wrong answer at call time: `drush scolta:build` died before it could
   * index anything. Nothing here may narrow the parent's signature, so the
   * types are copied rather than restated.
   */
  public function gather(string $entityType, string $bundle, string $siteName, int|string|NULL $resumeFromId = NULL, ?TimestampManifest $manifest = NULL, bool $force = FALSE): \Generator {
    if ($entityType === 'node') {
      yield from $this->inner->gather($entityType, $bundle, $siteName, $resumeFromId, $manifest, $force);
      return;
    }

    // Generic gather for non-node entity types (e.g. group).
    $storage = $this->entityTypeManager->getStorage($entityType);
    $idKey = $this->entityTypeManager->getDefinition($entityType)->getKey('id');
    $batch = 100;

    // Keyset pagination, matching the parent. The resume boundary has to be
    // honoured here and not merely accepted: a resumed build that silently
    // restarted this walk at the first row would renumber pages the previous
    // segment had already committed, which is the defect the parameter was
    // widened to fix. It is inclusive, so the first query uses `>=` and every
    // later one advances with `>`.
    $lastId = NULL;
    $resumeBoundary = $resumeFromId !== NULL && $resumeFromId !== '' ? $resumeFromId : NULL;

    while (TRUE) {
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->range(0, $batch)
        ->sort($idKey, 'ASC');

      if ($lastId !== NULL) {
        $query->condition($idKey, $lastId, '>');
      }
      elseif ($resumeBoundary !== NULL) {
        $query->condition($idKey, $resumeBoundary, '>=');
      }

      if ($bundle) {
        $bundleKey = $this->entityTypeManager->getDefinition($entityType)->getKey('bundle');
        if ($bundleKey) {
          $query->condition($bundleKey, $bundle);
        }
      }

      $ids = $query->execute();
      if (empty($ids)) {
        break;
      }

      // The query sorts ascending, so the last value is the high-water mark
      // this page advances the cursor to.
      $lastId = end($ids);
      reset($ids);

      $entities = $storage->loadMultiple($ids);

      foreach ($entities as $entity) {
        if (!$entity instanceof FieldableEntityInterface) {
          continue;
        }

        foreach ($entity->getTranslationLanguages() as $langcode => $language) {
          $translation = $entity->getTranslation($langcode);

          $body = '';
          foreach (['body', 'field_body', 'field_content', 'description'] as $field) {
            if ($translation->hasField($field) && !$translation->get($field)->isEmpty()) {
              $item = $translation->get($field)->first();
              if ($item instanceof TextItemBase) {
                $body = strip_tags((string) $item->processed) ?: strip_tags((string) $item->value);
              }
              else {
                $body = (string) ($item->value ?? '');
              }
              if ($body) {
                break;
              }
            }
          }

          if (empty($body)) {
            // For group entities with no body, use the label as minimal content.
            $body = $translation->label() ?? '';
            if (empty($body)) {
              continue;
            }
          }

          $changedTime = $translation instanceof EntityChangedInterface
            ? (int) $translation->getChangedTime()
            : (int) ($translation->get('changed')->value ?? time());

          $languages = $entity->getTranslationLanguages();
          $itemId = ($langcode === 'en' || count($languages) === 1)
            ? $entityType . '-' . $entity->id()
            : $entityType . '-' . $entity->id() . '-' . $langcode;

          yield new ContentItem(
            id: $itemId,
            title: $translation->label() ?: 'Untitled',
            bodyHtml: $body,
            url: $translation->toUrl()->setAbsolute(TRUE)->toString(),
            date: date('Y-m-d', $changedTime),
            siteName: $siteName,
            language: $langcode,
          );
        }
      }

      $storage->resetCache($ids);
      unset($entities);
    }
  }

}
