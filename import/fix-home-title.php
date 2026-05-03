<?php
// Rename "Home" node to meaningful institutional title.
$node = \Drupal\node\Entity\Node::load(162);
if ($node && $node->getTitle() === 'Home') {
  $node->setTitle('Meridian Institute of Artificial Intelligence');
  $node->save();
  echo "Renamed home page node to: " . $node->getTitle() . "\n";
}
