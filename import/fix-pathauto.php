<?php
/**
 * Fixes pathauto patterns with correct type and bundles format.
 */

// Delete all existing meridian pathauto patterns first.
$pattern_ids = [
  'program_pattern', 'course_pattern', 'faculty_pattern',
  'research_project_pattern', 'resource_article_pattern',
  'lecture_page_pattern', 'news_pattern',
];

$storage = \Drupal::entityTypeManager()->getStorage('pathauto_pattern');
foreach ($pattern_ids as $id) {
  $existing = $storage->load($id);
  if ($existing) {
    $existing->delete();
    echo "Deleted: $id\n";
  }
}

// Re-create with correct structure.
$patterns = [
  [
    'id'      => 'meridian_program',
    'label'   => 'Program',
    'type'    => 'canonical_entities:node',
    'pattern' => '/programs/[node:title]',
    'bundle'  => 'program',
  ],
  [
    'id'      => 'meridian_course',
    'label'   => 'Course',
    'type'    => 'canonical_entities:node',
    'pattern' => '/courses/[node:title]',
    'bundle'  => 'course',
  ],
  [
    'id'      => 'meridian_faculty',
    'label'   => 'Faculty',
    'type'    => 'canonical_entities:node',
    'pattern' => '/faculty/[node:title]',
    'bundle'  => 'faculty',
  ],
  [
    'id'      => 'meridian_research',
    'label'   => 'Research Project',
    'type'    => 'canonical_entities:node',
    'pattern' => '/research/[node:title]',
    'bundle'  => 'research_project',
  ],
  [
    'id'      => 'meridian_article',
    'label'   => 'Resource Article',
    'type'    => 'canonical_entities:node',
    'pattern' => '/resources/[node:title]',
    'bundle'  => 'resource_article',
  ],
  [
    'id'      => 'meridian_lecture',
    'label'   => 'Lecture Page',
    'type'    => 'canonical_entities:node',
    'pattern' => '/learn/[node:title]',
    'bundle'  => 'lecture_page',
  ],
  [
    'id'      => 'meridian_news',
    'label'   => 'News',
    'type'    => 'canonical_entities:node',
    'pattern' => '/news/[node:title]',
    'bundle'  => 'news',
  ],
];

foreach ($patterns as $p) {
  $existing = $storage->load($p['id']);
  if ($existing) {
    echo "Exists: {$p['id']}\n";
    continue;
  }

  $pattern = $storage->create([
    'id'              => $p['id'],
    'label'           => $p['label'],
    'type'            => $p['type'],
    'pattern'         => $p['pattern'],
    'selection_logic' => 'and',
    'weight'          => 0,
    'selection_criteria' => [
      \Drupal\Component\Uuid\Uuid::isValid('') ? '' : \Drupal::service('uuid')->generate() => [
        'id'              => 'entity_bundle:node',
        'negate'          => FALSE,
        'context_mapping' => ['node' => 'node'],
        'bundles'         => [$p['bundle'] => $p['bundle']],
      ],
    ],
  ]);
  $pattern->save();
  echo "Created: {$p['id']}\n";
}

// Now generate aliases for all nodes.
echo "\nGenerating aliases...\n";
$nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
$generator = \Drupal::service('pathauto.generator');
$count = 0;
foreach ($nodes as $node) {
  if ($node->bundle() === 'page') continue; // Already has manual aliases.
  $result = $generator->createEntityAlias($node, 'insert');
  if ($result) {
    $count++;
  }
}
echo "Generated $count aliases.\n";
