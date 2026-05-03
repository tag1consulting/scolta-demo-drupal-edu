<?php
/**
 * Add images to the Campus Life (nid 156) and Research (nid 158) page nodes.
 */

// ── Campus Life (nid 156) ──────────────────────────────────────────────────
$campus = \Drupal\node\Entity\Node::load(156);
if ($campus && !str_contains($campus->get('body')->value, 'node-featured-image')) {
  $body = $campus->get('body')->value;

  $hero = '<div class="node-featured-image"><img src="https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?w=1200&h=480&fit=crop&q=80" alt="Meridian AI campus coastal headland and redwood forest" loading="eager"></div>' . "\n\n";

  // Insert a library/reading-room photo after The Physical Campus section
  $reading_room = "\n\n" . '<div class="node-inline-image"><img src="https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=1000&h=380&fit=crop&q=80" alt="The Crossing — Meridian AI library reading room overlooking Meridian Sea" loading="lazy"></div>' . "\n\n";
  $body = str_replace('<h2>Meridian in Every Season</h2>', $reading_room . '<h2>Meridian in Every Season</h2>', $body);

  // Insert an outdoor/students photo before Community and Culture
  $students = "\n\n" . '<div class="node-inline-image"><img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?w=1000&h=380&fit=crop&q=80" alt="Graduate students on campus during spring symposium" loading="lazy"></div>' . "\n\n";
  $body = str_replace('<h2>Community and Culture</h2>', $students . '<h2>Community and Culture</h2>', $body);

  $campus->set('body', ['value' => $hero . $body, 'format' => 'full_html', 'summary' => '']);
  $campus->save();
  echo "Updated: Campus Life\n";
} else {
  echo "Skipping Campus Life (already has image or not found)\n";
}

// ── Research page (nid 158) ────────────────────────────────────────────────
$research = \Drupal\node\Entity\Node::load(158);
if ($research && !str_contains($research->get('body')->value, 'node-featured-image')) {
  $body = $research->get('body')->value;

  $hero = '<div class="node-featured-image"><img src="https://images.unsplash.com/photo-1507413245164-6160d8298b31?w=1200&h=480&fit=crop&q=80" alt="Meridian AI research laboratory and scientific equipment" loading="eager"></div>' . "\n\n";

  // Insert a collaboration/whiteboard photo before Active Research Projects
  $collab = "\n\n" . '<div class="node-inline-image"><img src="https://images.unsplash.com/photo-1531746790731-6c087fecd65a?w=1000&h=380&fit=crop&q=80" alt="Research team collaboration across Meridian AI schools" loading="lazy"></div>' . "\n\n";
  $body = str_replace('<h2>Active Research Projects</h2>', $collab . '<h2>Active Research Projects</h2>', $body);

  $research->set('body', ['value' => $hero . $body, 'format' => 'full_html', 'summary' => '']);
  $research->save();
  echo "Updated: Research page\n";
} else {
  echo "Skipping Research (already has image or not found)\n";
}

echo "\nDone.\n";
