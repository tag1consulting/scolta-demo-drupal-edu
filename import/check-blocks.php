<?php
echo "--- primary_menu region ---\n";
$blocks = \Drupal::entityTypeManager()->getStorage('block')
  ->loadByProperties(['theme' => 'meridian_theme', 'region' => 'primary_menu']);
foreach ($blocks as $b) {
  echo $b->id() . " | " . $b->getPlugin()->getPluginId() . " | weight=" . $b->getWeight() . "\n";
}

echo "\n--- header region ---\n";
$hblocks = \Drupal::entityTypeManager()->getStorage('block')
  ->loadByProperties(['theme' => 'meridian_theme', 'region' => 'header']);
foreach ($hblocks as $b) {
  echo $b->id() . " | " . $b->getPlugin()->getPluginId() . " | weight=" . $b->getWeight() . "\n";
}

echo "\n--- content_above region ---\n";
$ablocks = \Drupal::entityTypeManager()->getStorage('block')
  ->loadByProperties(['theme' => 'meridian_theme', 'region' => 'content_above']);
foreach ($ablocks as $b) {
  echo $b->id() . " | " . $b->getPlugin()->getPluginId() . " | weight=" . $b->getWeight() . "\n";
}

echo "\n--- all regions (non-disabled) ---\n";
$all = \Drupal::entityTypeManager()->getStorage('block')
  ->loadByProperties(['theme' => 'meridian_theme']);
foreach ($all as $b) {
  if ($b->get('region') === 'none') continue;
  echo sprintf("%-45s %-35s %s\n", $b->id(), $b->getPlugin()->getPluginId(), $b->get('region'));
}
