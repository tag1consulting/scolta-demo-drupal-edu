<?php

declare(strict_types=1);

namespace Drupal\ai_uni_lms\Service;

use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\scolta\Service\ScoltaContentGatherer;
use Drupal\text\Plugin\Field\FieldType\TextItemBase;
use Tag1\Scolta\Export\ContentItem;

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
  ) {
    $this->entityTypeManager = $entityTypeManager;
    parent::__construct($entityTypeManager);
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
   */
  public function gather(string $entityType, string $bundle, string $siteName): \Generator {
    if ($entityType === 'node') {
      yield from $this->inner->gather($entityType, $bundle, $siteName);
      return;
    }

    // Generic gather for non-node entity types (e.g. group).
    $storage = $this->entityTypeManager->getStorage($entityType);
    $idKey = $this->entityTypeManager->getDefinition($entityType)->getKey('id');
    $batch = 100;
    $offset = 0;

    while (TRUE) {
      $query = $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('status', 1)
        ->range($offset, $batch)
        ->sort($idKey, 'ASC');

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
      $offset += count($ids);
      unset($entities);
    }
  }

}
