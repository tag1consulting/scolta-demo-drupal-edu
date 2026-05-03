<?php
/**
 * Convert plain-text news bodies to HTML paragraphs.
 * News articles were imported as plain text with \n\n paragraph breaks.
 */

$query = \Drupal::database()->select('node_field_data', 'n')
  ->fields('n', ['nid'])
  ->condition('n.type', 'news')
  ->condition('n.status', 1);
$query->join('node__body', 'b', 'b.entity_id = n.nid');
$nids = $query->execute()->fetchCol();

$count = 0;
foreach ($nids as $nid) {
  $node = \Drupal\node\Entity\Node::load($nid);
  $body = $node->get('body')->value;

  // Skip if already has HTML tags
  if (str_contains($body, '<p>') || str_contains($body, '<h2>')) {
    echo "Skipping (already HTML): nid $nid\n";
    continue;
  }

  // Split on double newlines, wrap each paragraph, handle inline markdown
  $paragraphs = preg_split('/\n{2,}/', trim($body));
  $html_parts = [];
  foreach ($paragraphs as $para) {
    $para = trim($para);
    if ($para === '') continue;

    // Convert inline Markdown: **bold**, *italic*, [link](url)
    $para = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $para);
    $para = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $para);
    $para = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $para);

    // Convert single newlines within a paragraph to spaces
    $para = str_replace("\n", ' ', $para);

    $html_parts[] = "<p>$para</p>";
  }

  $html = implode("\n", $html_parts);

  $node->set('body', [
    'value'  => $html,
    'format' => 'full_html',
    'summary' => '',
  ]);
  $node->save();
  echo "Converted: nid $nid — " . $node->getTitle() . "\n";
  $count++;
}

echo "\nConverted $count news articles to HTML.\n";
