<?php
/**
 * Fix heading levels in page nodes. The Markdown converter had an off-by-one
 * error: ## became <h3> instead of <h2>. Shift all headings down one level.
 */

$page_nids = [154, 155, 156, 157, 158, 159, 160, 161];

foreach ($page_nids as $nid) {
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) {
    echo "Not found: $nid\n";
    continue;
  }

  $body = $node->get('body')->value;
  if (!$body) {
    echo "Empty body: $nid\n";
    continue;
  }

  // Shift headings: h4→h3, h3→h2 (do in order to avoid double-shift)
  // Use a sentinel first to avoid double-replacement
  $fixed = $body;
  $fixed = str_replace(['<h5>', '</h5>'], ['<h4>', '</h4>'], $fixed);
  $fixed = str_replace(['<h4>', '</h4>'], ['<h3>', '</h3>'], $fixed);
  $fixed = str_replace(['<h3>', '</h3>'], ['<h2>', '</h2>'], $fixed);

  if ($fixed === $body) {
    echo "No headings changed: $nid " . $node->getTitle() . "\n";
    continue;
  }

  $node->set('body', [
    'value'  => $fixed,
    'format' => 'full_html',
    'summary' => '',
  ]);
  $node->save();
  echo "Fixed headings: $nid " . $node->getTitle() . "\n";
}

echo "\nDone.\n";
