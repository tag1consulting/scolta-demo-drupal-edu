<?php
$node = \Drupal\node\Entity\Node::load(1);
if ($node) {
  echo "Node 1 title: " . $node->getTitle() . "\n";
  echo "Node 1 type: " . $node->bundle() . "\n";

  $generator = \Drupal::service('pathauto.generator');
  $result = $generator->createEntityAlias($node, 'insert');
  var_export($result);
  echo "\n";

  $pm = \Drupal::service('path_alias.manager');
  $alias = $pm->getAliasByPath('/node/1');
  echo "Alias after generate: $alias\n";
}
