<?php
/**
 * Create a home page node and set it as the site front page.
 * Run with: ddev drush php:script import/setup-homepage.php
 */

use Drupal\node\Entity\Node;

$body = <<<HTML
<div class="home-intro">
  <p class="lead">Meridian AI is a graduate research institution dedicated to the science, engineering, and ethics of artificial intelligence. We train the next generation of AI researchers, engineers, and policy thinkers in an environment that prizes rigor, collaboration, and principled design.</p>
</div>

<div class="home-stats">
  <div class="stat-grid">
    <div class="stat-item"><span class="stat-number">8</span><span class="stat-label">Degree Programs</span></div>
    <div class="stat-item"><span class="stat-number">40+</span><span class="stat-label">Faculty</span></div>
    <div class="stat-item"><span class="stat-number">20</span><span class="stat-label">Research Projects</span></div>
    <div class="stat-item"><span class="stat-number">160+</span><span class="stat-label">Courses &amp; Resources</span></div>
  </div>
</div>

<h2>Areas of Study</h2>
<p>Our programs span the full depth of modern AI — from the mathematics of learning algorithms to the societal implications of deployed systems.</p>
<div class="card-grid">
  <div class="card">
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">LLM Engineering &amp; NLP</a></div>
    <div class="card__body">Transformer architectures, instruction tuning, RLHF, and production deployment at scale.</div>
  </div>
  <div class="card">
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">Computer Vision</a></div>
    <div class="card__body">Convolutional networks, diffusion models, video understanding, and 3D reconstruction.</div>
  </div>
  <div class="card">
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">AI Ethics &amp; Policy</a></div>
    <div class="card__body">Fairness, accountability, transparency, and the governance of AI systems in society.</div>
  </div>
  <div class="card">
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">Reinforcement Learning</a></div>
    <div class="card__body">Policy optimization, model-based RL, multi-agent systems, and safe exploration.</div>
  </div>
</div>

<h2>Research at Meridian AI</h2>
<p>Our faculty lead internationally recognized research programs across 20 active projects. From scalable training infrastructure to multilingual NLP and interpretability, Meridian AI sits at the frontier of AI research.</p>
<p><a href="/research">Explore our research projects →</a></p>

<h2>Latest News</h2>
<p>Stay current with Meridian AI's growing presence in academia, industry, and public discourse.</p>
<p><a href="/news">Read all news →</a></p>
HTML;

// Check if home page already exists
$existing = \Drupal::entityTypeManager()->getStorage('node')
  ->loadByProperties(['title' => 'Home', 'type' => 'page']);
if ($existing) {
  $node = reset($existing);
  echo "Updating existing home page node {$node->id()}\n";
} else {
  $node = Node::create(['type' => 'page', 'title' => 'Home']);
  echo "Creating new home page node\n";
}

$node->set('body', [
  'value'  => $body,
  'format' => 'full_html',
]);
$node->set('status', 1);
$node->save();

// Set URL alias to /home (not /, which Drupal uses for front page routing)
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
$existing_aliases = $alias_storage->loadByProperties(['path' => '/node/' . $node->id()]);
foreach ($existing_aliases as $a) { $a->delete(); }
$alias_storage->create([
  'path'  => '/node/' . $node->id(),
  'alias' => '/home',
])->save();

// Set as front page
\Drupal::configFactory()->getEditable('system.site')
  ->set('page.front', '/node/' . $node->id())
  ->save();

// Also set the "Welcome!" page title that Drupal uses for the front page
// (the page title block uses the route title for front page, not the node title)

echo "Home page node ID: {$node->id()}\n";
echo "Front page set to: /node/{$node->id()}\n";
echo "Done!\n";
