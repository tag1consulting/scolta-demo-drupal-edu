<?php
/**
 * Set body summary for Dr. Osei-Mensah (nid 49) so teaser shows only intro.
 *
 * Run with: drush php:script import/fix-osei-mensah-summary.php
 */

$node = \Drupal\node\Entity\Node::load(49);
if (!$node) {
  echo "Node 49 not found.\n";
  return;
}

$body = $node->get('body');

$summary = '<div class="node-featured-image node-featured-image--portrait">'
  . '<img src="/sites/default/files/demo-images/photo-1494790108377-be9c29b29330.jpg"'
  . ' alt="Dr. Amara Osei-Mensah, School of Language and Reasoning" loading="lazy">'
  . '</div>' . "\n\n"
  . '<h2>About Dr. Osei-Mensah</h2>' . "\n"
  . '<p>Professor Amara Osei-Mensah is the founding Chair of the School of Language &amp; Reasoning at Meridian AI.'
  . ' Her research on multilingual LLMs and training dynamics has shaped both the academic literature and the practical'
  . ' design of production language models.</p>';

$node->set('body', [
  'value'   => $body->value,
  'format'  => $body->format,
  'summary' => $summary,
]);
$node->save();
echo "Updated body summary for nid 49 — " . $node->getTitle() . "\n";
