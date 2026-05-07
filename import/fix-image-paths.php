<?php
/**
 * Migrate existing node bodies from external Unsplash URLs to local paths.
 *
 * Run with: drush php:script import/fix-image-paths.php
 *
 * This replaces all images.unsplash.com references with local
 * /sites/default/files/demo-images/{photo-id}.jpg paths.
 * The local images must already exist in that directory.
 */

$storage = \Drupal::entityTypeManager()->getStorage('node');

$query = $storage->getQuery()
  ->condition('body.value', 'images.unsplash.com', 'CONTAINS')
  ->accessCheck(FALSE);

$nids = $query->execute();

if (empty($nids)) {
  echo "No nodes with external Unsplash image URLs found.\n";
  return;
}

echo "Found " . count($nids) . " nodes with Unsplash URLs. Updating...\n";

$updated = 0;
$failed  = 0;

foreach ($nids as $nid) {
  $node = $storage->load($nid);
  if (!$node) {
    echo "Could not load nid $nid\n";
    $failed++;
    continue;
  }

  $body = $node->get('body')->value;
  // Replace every images.unsplash.com/{photo-id}?...params... with the local path.
  // The photo ID always starts with "photo-" and is followed by an optional hash.
  // Query string parameters vary per image; we strip them all.
  $new_body = preg_replace(
    '|https://images\.unsplash\.com/(photo-[a-zA-Z0-9-]+)\?[^"]*|',
    '/sites/default/files/demo-images/$1.jpg',
    $body
  );

  if ($new_body === $body) {
    echo "No change: nid $nid (" . $node->getTitle() . ")\n";
    continue;
  }

  $node->set('body', [
    'value'   => $new_body,
    'format'  => $node->get('body')->format,
    'summary' => $node->get('body')->summary ?? '',
  ]);
  $node->save();
  echo "Updated: nid $nid — " . $node->getTitle() . "\n";
  $updated++;
}

echo "\nDone. Updated: $updated, Failed: $failed\n";
