<?php
/**
 * Add subject-appropriate images to each lecture/course node.
 */

$images = [
  135 => ['photo-1555952517-2e8e729e0b44', 'Building a tokenizer — code and text processing'],
  136 => ['photo-1677442135703-1787eea5ce01', 'Transformer architecture neural network diagram'],
  137 => ['photo-1485827404703-89b55fcc595e', 'Reinforcement learning autonomous robot'],
  138 => ['photo-1516321318423-f06f85e504b3', 'Prompt engineering at a computer workstation'],
  139 => ['photo-1573164574572-cb89e39749b4', 'AI ethics case study discussion'],
  140 => ['photo-1451187580459-43490279c0fa', 'How AI-powered search engines work — global data network'],
  141 => ['photo-1618005182384-a83a8bd57fbe', 'Diffusion models and generative AI visuals'],
];

$count = 0;
foreach ($images as $nid => $info) {
  [$photo_id, $alt] = $info;
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) { echo "Not found: $nid\n"; continue; }

  $body = $node->get('body')->value;
  if (str_contains($body, 'node-featured-image')) {
    echo "Skipping (already has image): $nid\n"; continue;
  }

  $img = '<div class="node-featured-image"><img src="https://images.unsplash.com/' . $photo_id . '?w=900&h=400&fit=crop&q=80" alt="' . htmlspecialchars($alt) . '" loading="lazy"></div>' . "\n\n";
  $node->set('body', ['value' => $img . $body, 'format' => 'full_html', 'summary' => '']);
  $node->save();
  echo "Updated: $nid — " . $node->getTitle() . "\n";
  $count++;
}

echo "\nAdded images to $count course nodes.\n";
