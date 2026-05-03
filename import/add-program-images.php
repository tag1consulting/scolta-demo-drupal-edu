<?php
/**
 * Prepend subject-appropriate featured images to each program node body.
 */

$images = [
  1  => ['photo-1677442135703-1787eea5ce01', 'Large language model neural network visualization'],
  2  => ['photo-1555952517-2e8e729e0b44', 'AI agent development and code interface'],
  3  => ['photo-1516116216624-53e697fedbea', 'Computer vision camera and optical system'],
  4  => ['photo-1618005182384-a83a8bd57fbe', 'Generative media and digital art'],
  5  => ['photo-1485827404703-89b55fcc595e', 'Autonomous systems and robotics'],
  6  => ['photo-1563986768609-322da13575f3', 'Industrial robot arm for AI robotics'],
  7  => ['photo-1635070041078-e363dbe005cb', 'Mathematical equations and theory on chalkboard'],
  8  => ['photo-1481627834876-b7833e8f5570', 'Academic library for AI theory research'],
  9  => ['photo-1573164574572-cb89e39749b4', 'AI ethics governance and policy discussion'],
  10 => ['photo-1550751827-4bd374c3f58b', 'AI safety and cybersecurity network'],
  11 => ['photo-1558494949-ef010cbdcc31', 'AI engineering infrastructure and server network'],
  12 => ['photo-1551288049-bebda4e38f71', 'AI-powered search and data analytics dashboard'],
];

$count = 0;
foreach ($images as $nid => $info) {
  [$photo_id, $alt] = $info;
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) { echo "Not found: $nid\n"; continue; }

  $body = $node->get('body')->value;

  // Skip if already has a featured image
  if (str_contains($body, 'node-featured-image')) {
    echo "Skipping (already has image): $nid " . $node->getTitle() . "\n";
    continue;
  }

  $img_html = '<div class="node-featured-image"><img src="https://images.unsplash.com/' . $photo_id . '?w=900&h=400&fit=crop&q=80" alt="' . htmlspecialchars($alt) . '" loading="lazy"></div>' . "\n\n";

  $node->set('body', [
    'value'  => $img_html . $body,
    'format' => 'full_html',
    'summary' => '',
  ]);
  $node->save();
  echo "Updated: $nid — " . $node->getTitle() . "\n";
  $count++;
}

echo "\nAdded images to $count program nodes.\n";
