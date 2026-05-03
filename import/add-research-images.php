<?php
/**
 * Prepend topic-appropriate featured images to each research project node.
 */

$images = [
  69 => ['photo-1451187580459-43490279c0fa', 'Global language network and multilingual data'],
  70 => ['photo-1485827404703-89b55fcc595e', 'Indoor robot navigation and world models'],
  71 => ['photo-1454165804606-c3d57bc86b40', 'Ethical AI audit and impact assessment documents'],
  72 => ['photo-1531746790731-6c087fecd65a', 'Open source collaboration and LLM training'],
  73 => ['photo-1558494949-ef010cbdcc31', 'Sparse transformer network architecture nodes'],
  74 => ['photo-1507413245164-6160d8298b31', 'Scientific discovery with multimodal AI'],
  75 => ['photo-1518770660439-4636190af475', 'Neural architecture search and circuit design'],
  76 => ['photo-1589998059171-988d887df646', 'Causal fairness and algorithmic justice scales'],
  77 => ['photo-1576671081837-49000212a0b9', 'Surgical robotics and medical AI systems'],
  78 => ['photo-1535378917042-10a22c95931a', 'Continuous learning and adaptive AI systems'],
  79 => ['photo-1551288049-bebda4e38f71', 'AI fairness benchmark evaluation dashboard'],
  80 => ['photo-1516116216624-53e697fedbea', 'Alignment evaluation optics and precision measurement'],
  81 => ['photo-1530026405186-ed1f139313f8', 'Federated learning distributed medical data network'],
  82 => ['photo-1563986768609-322da13575f3', 'Safe robotic manipulation and grasping arm'],
  83 => ['photo-1589829545856-d10d557cf95f', 'Constitutional AI and alignment policy document'],
  84 => ['photo-1607799279861-4dd421887fb3', 'LLM API production infrastructure server rack'],
  85 => ['photo-1549317661-bd32c8ce0db2', 'Autonomous driving NeRF highway road scene'],
  86 => ['photo-1555421689-d68471e189f2', 'AI regulation and EU policy governance'],
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

echo "\nAdded images to $count research project nodes.\n";
