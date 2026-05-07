<?php
/**
 * Prepend contextually appropriate featured images to each news article.
 */

$images = [
  143 => ['photo-1541339907198-e08756dedf3f', 'University lecture hall for faculty announcement'],
  144 => ['photo-1551288049-bebda4e38f71', 'Multilingual benchmark research data and results'],
  145 => ['photo-1522202176988-66273c2fd55f', 'Students celebrating AI safety hackathon win'],
  146 => ['photo-1576671081837-49000212a0b9', 'Health systems research partnership'],
  147 => ['photo-1475721027785-f74eccf877e2', 'NSF CAREER Award presentation and recognition'],
  148 => ['photo-1513258496099-48168024aec0', 'Online AI literacy certificate program learning'],
  149 => ['photo-1540575467063-178a50c2df87', 'ICML conference machine learning research presentations'],
  150 => ['photo-1555952517-2e8e729e0b44', 'Scolta AI-powered search interface and technology'],
  151 => ['photo-1523580846011-d3a5bc25702b', 'PhD student Google fellowship in machine learning'],
  152 => ['photo-1477959858617-67f85cf4f1df', 'Municipal government and AI governance policy'],
  153 => ['photo-1491975474562-1f4e30bc9468', 'Spring research symposium PhD program event'],
];

$count = 0;
foreach ($images as $nid => $info) {
  [$photo_id, $alt] = $info;
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) { echo "Not found: $nid\n"; continue; }

  $body = $node->get('body')->value;

  if (str_contains($body, 'node-featured-image')) {
    echo "Skipping (already has image): $nid " . $node->getTitle() . "\n";
    continue;
  }

  $img_html = '<div class="node-featured-image"><img src="/sites/default/files/demo-images/' . $photo_id . '.jpg" alt="' . htmlspecialchars($alt) . '" loading="lazy"></div>' . "\n\n";

  $node->set('body', [
    'value'  => $img_html . $body,
    'format' => 'full_html',
    'summary' => '',
  ]);
  $node->save();
  echo "Updated: $nid — " . $node->getTitle() . "\n";
  $count++;
}

echo "\nAdded images to $count news articles.\n";
