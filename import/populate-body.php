<?php
/**
 * Loads existing nodes and saves body field from YAML sources.
 * Run after setup-body-field.php to populate body data.
 */
use Drupal\node\Entity\Node;
use Symfony\Component\Yaml\Yaml;

$base = '/var/www/html/import/';
$file_map = [
  'content-programs.yaml',
  'content-courses.yaml',
  'content-faculty.yaml',
  'content-research.yaml',
  'content-articles-batch1.yaml',
  'content-articles-batch2.yaml',
  'content-articles-batch3.yaml',
  'content-articles-batch4.yaml',
  'content-lectures.yaml',
  'content-news.yaml',
  'content-pages.yaml',
];

$updated = 0;
$skipped = 0;

foreach ($file_map as $filename) {
  $path = $base . $filename;
  if (!file_exists($path)) {
    echo "Missing: $filename\n";
    continue;
  }

  $items = Yaml::parse(file_get_contents($path));
  if (!is_array($items)) continue;

  // Unwrap top-level key (news:, pages:, etc.)
  if (count($items) === 1 && is_array(reset($items))) {
    $first = reset($items);
    if (isset($first[0]) && is_array($first[0])) {
      $items = $first;
    }
  }

  foreach ($items as $item) {
    if (empty($item['title']) || empty($item['body'])) continue;

    $ids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
      ->accessCheck(FALSE)
      ->condition('title', $item['title'])
      ->execute();

    if (!$ids) {
      echo "  [miss] {$item['title']}\n";
      continue;
    }

    $nid = reset($ids);
    $node = Node::load($nid);
    if (!$node) continue;

    if ($node->hasField('body')) {
      $existing = $node->get('body')->value;
      if (!empty($existing)) {
        $skipped++;
        continue;
      }
      $node->set('body', ['value' => $item['body'], 'format' => 'full_html']);
      $node->save();
      $updated++;
      echo "  [body] {$item['title']}\n";
    }
  }
}

echo "\nUpdated: $updated\nSkipped: $skipped\n";
