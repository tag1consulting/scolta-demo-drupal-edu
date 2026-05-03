<?php
/**
 * Convert Markdown body content to HTML for page nodes.
 * Only processes nodes whose body starts with ## or contains \n## patterns.
 */

function markdown_to_html(string $md): string {
  $lines = explode("\n", $md);
  $html  = '';
  $in_ul = FALSE;
  $in_ol = FALSE;
  $in_pre = FALSE;
  $para_lines = [];

  $flush_para = function() use (&$para_lines, &$html) {
    if (empty($para_lines)) return;
    $text = inline_markdown(implode(' ', $para_lines));
    $html .= "<p>$text</p>\n";
    $para_lines = [];
  };

  $close_lists = function() use (&$in_ul, &$in_ol, &$html) {
    if ($in_ul) { $html .= "</ul>\n"; $in_ul = FALSE; }
    if ($in_ol) { $html .= "</ol>\n"; $in_ol = FALSE; }
  };

  foreach ($lines as $line) {
    // Code fence
    if (preg_match('/^```/', $line)) {
      if (!$in_pre) {
        $flush_para();
        $close_lists();
        $html .= "<pre><code>";
        $in_pre = TRUE;
      } else {
        $html .= "</code></pre>\n";
        $in_pre = FALSE;
      }
      continue;
    }
    if ($in_pre) {
      $html .= htmlspecialchars($line) . "\n";
      continue;
    }

    // Headings
    if (preg_match('/^(#{1,4})\s+(.+)$/', $line, $m)) {
      $flush_para();
      $close_lists();
      $level = strlen($m[1]) + 1; // Shift levels up (## → h2)
      $level = min($level, 4);
      $text  = inline_markdown($m[2]);
      $html .= "<h$level>$text</h$level>\n";
      continue;
    }

    // Unordered list
    if (preg_match('/^[-*]\s+(.+)$/', $line, $m)) {
      $flush_para();
      if ($in_ol) { $html .= "</ol>\n"; $in_ol = FALSE; }
      if (!$in_ul) { $html .= "<ul>\n"; $in_ul = TRUE; }
      $html .= "<li>" . inline_markdown($m[1]) . "</li>\n";
      continue;
    }

    // Ordered list
    if (preg_match('/^\d+\.\s+(.+)$/', $line, $m)) {
      $flush_para();
      if ($in_ul) { $html .= "</ul>\n"; $in_ul = FALSE; }
      if (!$in_ol) { $html .= "<ol>\n"; $in_ol = TRUE; }
      $html .= "<li>" . inline_markdown($m[1]) . "</li>\n";
      continue;
    }

    // Blank line
    if (trim($line) === '') {
      $flush_para();
      $close_lists();
      continue;
    }

    // Blockquote
    if (preg_match('/^>\s*(.*)$/', $line, $m)) {
      $flush_para();
      $close_lists();
      $html .= "<blockquote><p>" . inline_markdown($m[1]) . "</p></blockquote>\n";
      continue;
    }

    // Horizontal rule
    if (preg_match('/^---+$/', $line)) {
      $flush_para();
      $close_lists();
      $html .= "<hr>\n";
      continue;
    }

    // Normal paragraph line
    $close_lists();
    $para_lines[] = trim($line);
  }

  $flush_para();
  $close_lists();
  if ($in_pre) $html .= "</code></pre>\n";

  return $html;
}

function inline_markdown(string $text): string {
  // Bold italic
  $text = preg_replace('/\*\*\*(.+?)\*\*\*/', '<strong><em>$1</em></strong>', $text);
  // Bold
  $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
  // Italic
  $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);
  // Inline code
  $text = preg_replace('/`(.+?)`/', '<code>$1</code>', $text);
  // Links
  $text = preg_replace('/\[(.+?)\]\((.+?)\)/', '<a href="$2">$1</a>', $text);
  return $text;
}

// Load all nodes with Markdown bodies
$query = \Drupal::database()->select('node__body', 'nb')
  ->fields('nb', ['entity_id', 'body_value', 'body_format', 'body_summary'])
  ->condition('body_value', '## %', 'LIKE');
$results = $query->execute()->fetchAll();

if (empty($results)) {
  // Also try newline pattern
  $query2 = \Drupal::database()->query("SELECT entity_id, body_value, body_format, body_summary FROM node__body WHERE body_value LIKE :pat", [':pat' => '%' . "\n" . '## %']);
  $results = $query2->fetchAll();
}

$count = 0;
foreach ($results as $row) {
  $nid = $row->entity_id;

  // Skip if it already looks like HTML
  if (str_starts_with(trim($row->body_value), '<')) {
    echo "Skipping nid $nid — already HTML\n";
    continue;
  }

  $converted = markdown_to_html($row->body_value);

  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) { echo "Could not load nid $nid\n"; continue; }

  $node->set('body', [
    'value'   => $converted,
    'format'  => 'full_html',
    'summary' => '',
  ]);
  $node->save();
  echo "Converted nid $nid: " . $node->getTitle() . "\n";
  $count++;
}

echo "\nConverted $count nodes.\n";
