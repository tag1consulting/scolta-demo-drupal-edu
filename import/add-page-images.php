<?php
/**
 * Add hero images to About and Admissions pages.
 * About also gets a second inline image between sections.
 */

// About Meridian AI (nid 154)
$about = \Drupal\node\Entity\Node::load(154);
if ($about && !str_contains($about->get('body')->value, 'node-featured-image')) {
  $body = $about->get('body')->value;

  // Hero at the very top
  $hero = '<div class="node-featured-image"><img src="https://images.unsplash.com/photo-1498243691581-b145c3f54a5a?w=1200&h=480&fit=crop&q=80" alt="Meridian AI campus among coastal redwoods and mesa" loading="eager"></div>' . "\n\n";

  // Second image: students in seminar — insert before the Leadership section
  $seminar_img = "\n\n" . '<div class="node-inline-image"><img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=900&h=360&fit=crop&q=80" alt="Graduate students in interdisciplinary seminar" loading="lazy"></div>' . "\n\n";
  $body = str_replace('<h2>Leadership</h2>', $seminar_img . '<h2>Leadership</h2>', $body);

  $about->set('body', [
    'value'  => $hero . $body,
    'format' => 'full_html',
    'summary' => '',
  ]);
  $about->save();
  echo "Updated: About Meridian AI\n";
} else {
  echo "Skipping About (already has image or not found)\n";
}

// Admissions (nid 155)
$admissions = \Drupal\node\Entity\Node::load(155);
if ($admissions && !str_contains($admissions->get('body')->value, 'node-featured-image')) {
  $body = $admissions->get('body')->value;

  $hero = '<div class="node-featured-image"><img src="https://images.unsplash.com/photo-1543269865-cbf427effbad?w=1200&h=480&fit=crop&q=80" alt="Graduate students welcoming new cohort on campus" loading="eager"></div>' . "\n\n";

  $admissions->set('body', [
    'value'  => $hero . $body,
    'format' => 'full_html',
    'summary' => '',
  ]);
  $admissions->save();
  echo "Updated: Admissions\n";
} else {
  echo "Skipping Admissions (already has image or not found)\n";
}

echo "\nDone.\n";
