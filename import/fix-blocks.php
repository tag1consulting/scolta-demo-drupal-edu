<?php
/**
 * Remove duplicate blocks and misconfigured blocks.
 * Run with: ddev drush php:script import/fix-blocks.php
 */

$storage = \Drupal::entityTypeManager()->getStorage('block');

// Duplicate nav blocks — keep meridian_theme_main_navigation, remove meridian_theme_main_menu
$duplicates = [
  'meridian_theme_main_menu',        // duplicate of main_navigation
  'meridian_theme_site_branding',    // duplicate of branding
];

foreach ($duplicates as $bid) {
  $block = $storage->load($bid);
  if ($block) {
    $block->delete();
    echo "Deleted duplicate block: $bid\n";
  } else {
    echo "Block not found (already gone?): $bid\n";
  }
}

// Move the help block to 'none' region — it shows admin help text,
// shouldn't be in content_above where it affects all pages.
$help = $storage->load('meridian_theme_help');
if ($help) {
  $help->setRegion('none');
  $help->setStatus(FALSE);
  $help->save();
  echo "Disabled help block\n";
}

// Move the narrow search form block to 'none' — we use Scolta search instead.
$search = $storage->load('meridian_theme_search_form_narrow');
if ($search) {
  $search->setRegion('none');
  $search->setStatus(FALSE);
  $search->save();
  echo "Disabled narrow search form block\n";
}

$search_wide = $storage->load('meridian_theme_search_form_wide');
if ($search_wide) {
  $search_wide->setRegion('none');
  $search_wide->setStatus(FALSE);
  $search_wide->save();
  echo "Disabled wide search form block\n";
}

// Verify what remains active
echo "\n--- Active blocks by region ---\n";
$all = $storage->loadByProperties(['theme' => 'meridian_theme']);
$by_region = [];
foreach ($all as $b) {
  $region = $b->get('region');
  if ($region === 'none' || !$b->status()) continue;
  $by_region[$region][] = $b->id();
}
ksort($by_region);
foreach ($by_region as $region => $ids) {
  echo "$region:\n";
  foreach ($ids as $id) echo "  $id\n";
}
