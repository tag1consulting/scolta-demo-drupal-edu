<?php

/**
 * Imports Meridian AI content from YAML files into Drupal.
 * Run with: ddev drush php:script import/import-content.php
 *
 * Pass TYPE env var to import specific content types:
 *   TYPE=programs ddev drush php:script import/import-content.php
 *   Or run without argument to import all.
 */

use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Yaml;

$type_arg = getenv('TYPE');

$file_map = [
  'programs'  => 'content-programs.yaml',
  'courses'   => 'content-courses.yaml',
  'faculty'   => 'content-faculty.yaml',
  'research'  => 'content-research.yaml',
  'articles1' => 'content-articles-batch1.yaml',
  'articles2' => 'content-articles-batch2.yaml',
  'articles3' => 'content-articles-batch3.yaml',
  'articles4' => 'content-articles-batch4.yaml',
  'lectures'  => 'content-lectures.yaml',
  'news'      => 'content-news.yaml',
  'pages'     => 'content-pages.yaml',
];

$base = '/var/www/html/import/';
$files = [];

if ($type_arg && isset($file_map[$type_arg])) {
  $files[] = $base . $file_map[$type_arg];
} else {
  foreach ($file_map as $key => $filename) {
    $path = $base . $filename;
    if (file_exists($path)) {
      $files[] = $path;
    }
  }
}

if (empty($files)) {
  echo "No YAML files found to import.\n";
  exit(1);
}

// ============================================================
// TAXONOMY TERM ID CACHE
// ============================================================
$term_cache = [];

function get_term_id(string $vocabulary, string $name): ?int {
  global $term_cache;
  $key = "{$vocabulary}:{$name}";
  if (!isset($term_cache[$key])) {
    $terms = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vocabulary, 'name' => $name]);
    $term_cache[$key] = $terms ? (int) reset($terms)->id() : NULL;
  }
  return $term_cache[$key];
}

// ============================================================
// NODE ID CACHE (for cross-references)
// ============================================================
$node_cache = [];

function get_node_id(string $title, string $bundle = ''): ?int {
  global $node_cache;
  $key = "{$bundle}:{$title}";
  if (!isset($node_cache[$key])) {
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('title', $title);
    if ($bundle) {
      $query->condition('type', $bundle);
    }
    $ids = $query->execute();
    $node_cache[$key] = $ids ? (int) reset($ids) : NULL;
  }
  return $node_cache[$key];
}

// ============================================================
// IMPORT FUNCTION
// ============================================================
function import_node(array $data): ?Node {
  $bundle = $data['type'] ?? 'resource_article';

  // Check for existing node by title + type
  $existing = \Drupal::entityTypeManager()
    ->getStorage('node')
    ->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', $bundle)
    ->condition('title', $data['title'])
    ->execute();

  if ($existing) {
    echo "  [skip] {$data['title']}\n";
    return Node::load(reset($existing));
  }

  $values = [
    'type'     => $bundle,
    'title'    => $data['title'],
    'langcode' => 'en',
    'status'   => 1,
    'uid'      => 1,
  ];

  // Body
  if (!empty($data['body'])) {
    $values['body'] = ['value' => $data['body'], 'format' => 'full_html'];
  }

  // Taxonomy fields
  $taxonomy_fields = [
    'school'         => 'school',
    'degree_level'   => 'degree_level',
    'course_level'   => 'course_level',
    'difficulty'     => 'difficulty',
    'news_category'  => 'news_category',
  ];

  foreach ($taxonomy_fields as $data_key => $vocab) {
    $field_name = 'field_' . $data_key;
    if (!empty($data[$data_key])) {
      $tid = get_term_id($vocab, $data[$data_key]);
      if ($tid) {
        $values[$field_name] = [['target_id' => $tid]];
      } else {
        echo "    [warn] Term not found: {$data[$data_key]} in {$vocab}\n";
      }
    }
  }

  // Multi-value topic field
  if (!empty($data['topics'])) {
    $topic_refs = [];
    foreach ((array) $data['topics'] as $topic_name) {
      $tid = get_term_id('topic', $topic_name);
      if ($tid) {
        $topic_refs[] = ['target_id' => $tid];
      }
    }
    if ($topic_refs) {
      $values['field_topic'] = $topic_refs;
    }
  }

  // Scalar string fields
  $scalar_fields = [
    'duration'          => 'field_duration',
    'format'            => 'field_format',
    'credits'           => 'field_credits',
    'course_number'     => 'field_course_number',
    'semester'          => 'field_semester',
    'title_role'        => 'field_title_role',
    'specialization'    => 'field_specialization',
    'status'            => 'field_status',
    'funding'           => 'field_funding',
    'estimated_time'    => 'field_estimated_time',
    'news_date'         => 'field_news_date',
  ];

  foreach ($scalar_fields as $data_key => $field_name) {
    if (!empty($data[$data_key])) {
      $values[$field_name] = $data[$data_key];
    }
  }

  // Long text fields
  $text_long_fields = [
    'prerequisites'     => 'field_prerequisites',
    'syllabus_summary'  => 'field_syllabus_summary',
    'publications'      => 'field_publications',
    'bio'               => 'field_bio',
  ];

  foreach ($text_long_fields as $data_key => $field_name) {
    if (!empty($data[$data_key])) {
      $values[$field_name] = ['value' => $data[$data_key], 'format' => 'full_html'];
    }
  }

  // Node reference: program (single)
  if (!empty($data['program_title'])) {
    $nid = get_node_id($data['program_title'], 'program');
    if ($nid) {
      $values['field_program'] = [['target_id' => $nid]];
    }
  }

  // Node reference: principal_investigator (single faculty)
  if (!empty($data['pi_title'])) {
    $nid = get_node_id($data['pi_title'], 'faculty');
    if ($nid) {
      $values['field_principal_investigator'] = [['target_id' => $nid]];
    }
  }

  // Node reference: related_program (single)
  if (!empty($data['related_program_title'])) {
    $nid = get_node_id($data['related_program_title'], 'program');
    if ($nid) {
      $values['field_related_program'] = [['target_id' => $nid]];
    }
  }

  $node = Node::create($values);
  $node->save();
  echo "  [created] {$data['title']} (nid:{$node->id()})\n";
  return $node;
}

// ============================================================
// MAIN IMPORT LOOP
// ============================================================
$total = 0;
$created = 0;
$skipped = 0;

foreach ($files as $file) {
  echo "\n=== Importing: " . basename($file) . " ===\n";
  $raw = file_get_contents($file);
  $items = Yaml::parse($raw);

  if (!is_array($items)) {
    echo "ERROR: Could not parse $file\n";
    continue;
  }

  foreach ($items as $item) {
    $total++;
    $before = \Drupal::entityTypeManager()->getStorage('node')
      ->getQuery()->accessCheck(FALSE)
      ->condition('title', $item['title'])->count()->execute();

    import_node($item);

    $after = \Drupal::entityTypeManager()->getStorage('node')
      ->getQuery()->accessCheck(FALSE)
      ->condition('title', $item['title'])->count()->execute();

    if ($after > $before) {
      $created++;
    } else {
      $skipped++;
    }
  }
}

echo "\n=== Import Summary ===\n";
echo "Total processed: $total\n";
echo "Created:         $created\n";
echo "Skipped:         $skipped\n";
