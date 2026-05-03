<?php
// Delete the "Powered by Drupal" block completely.
$block = \Drupal::entityTypeManager()->getStorage('block')->load('meridian_theme_powered');
if ($block) {
  $block->delete();
  echo "Deleted: meridian_theme_powered\n";
} else {
  echo "Not found\n";
}

// Also disable/delete the account menu from secondary_menu — won't show
// (page template doesn't render secondary_menu) but clean it up.
$acct = \Drupal::entityTypeManager()->getStorage('block')->load('meridian_theme_account_menu');
if ($acct) {
  $acct->setRegion('none');
  $acct->setStatus(FALSE);
  $acct->save();
  echo "Disabled: meridian_theme_account_menu\n";
}

// Turn off display_submitted (author/date) for all content types so it
// doesn't appear even in HTML source.
$types = ['program','course','faculty','research_project','resource_article','lecture_page','news','page'];
foreach ($types as $type) {
  $node_type = \Drupal\node\Entity\NodeType::load($type);
  if ($node_type) {
    $node_type->setDisplaySubmitted(FALSE);
    $node_type->save();
    echo "display_submitted=FALSE for: $type\n";
  }
}

echo "\nDone.\n";
