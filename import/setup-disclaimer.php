<?php
/**
 * Creates footer disclaimer block.
 */
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\block\Entity\Block;

// Ensure block_content module is enabled.
\Drupal::service('module_installer')->install(['block_content'], TRUE);

// Create block content type 'basic' if it doesn't exist.
if (!BlockContentType::load('basic')) {
  BlockContentType::create([
    'id' => 'basic',
    'label' => 'Basic block',
  ])->save();
  block_content_add_body_field('basic');
}

// Create the disclaimer block content entity.
$existing = \Drupal::entityTypeManager()->getStorage('block_content')
  ->loadByProperties(['info' => 'Site Disclaimer']);

if (!$existing) {
  $block_content = BlockContent::create([
    'type'  => 'basic',
    'info'  => 'Site Disclaimer',
    'body'  => [
      'value'  => '<div class="site-footer__disclaimer"><strong>Meridian AI is a fictional institution created by Tag1 Consulting to demonstrate Scolta.</strong> All faculty, programs, research projects, and campus details are invented. The technical content is accurate and educational. <a href="/about/demo">Learn more about this demo.</a></div>',
      'format' => 'full_html',
    ],
  ]);
  $block_content->save();
  echo "Created block content: Site Disclaimer\n";
}
else {
  $block_content = reset($existing);
  echo "Block content exists: Site Disclaimer\n";
}

// Place the block in footer_bottom region.
$block_id = 'meridian_theme_site_disclaimer';
if (!Block::load($block_id)) {
  Block::create([
    'id'        => $block_id,
    'plugin'    => 'block_content:' . $block_content->uuid(),
    'theme'     => 'meridian_theme',
    'region'    => 'footer_bottom',
    'weight'    => 10,
    'settings'  => [
      'id'            => 'block_content:' . $block_content->uuid(),
      'label'         => 'Site Disclaimer',
      'label_display' => '0',
      'provider'      => 'block_content',
    ],
    'visibility' => [],
  ])->save();
  echo "Placed disclaimer block in footer_bottom\n";
}
else {
  echo "Disclaimer block already placed\n";
}
