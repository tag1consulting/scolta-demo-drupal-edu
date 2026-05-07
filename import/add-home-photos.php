<?php
/**
 * Add imagery to the homepage node.
 * Hero banner + card photos + photo strip section.
 */

$node = \Drupal\node\Entity\Node::load(162);
if (!$node) {
  echo "ERROR: node 162 not found.\n";
  return;
}

$body = <<<'HTML'
<div class="home-hero">
  <img src="/sites/default/files/demo-images/photo-1562774053-701939374585.jpg"
       alt="Meridian AI campus"
       loading="eager">
  <div class="home-hero__caption">Advancing AI Research &amp; Education</div>
</div>

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
    <div class="card__image">
      <img src="/sites/default/files/demo-images/photo-1677442135703-1787eea5ce01.jpg"
           alt="Neural network visualization" loading="lazy">
    </div>
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">LLM Engineering &amp; NLP</a></div>
    <div class="card__body">Transformer architectures, instruction tuning, RLHF, and production deployment at scale.</div>
  </div>
  <div class="card">
    <div class="card__image">
      <img src="/sites/default/files/demo-images/photo-1516116216624-53e697fedbea.jpg"
           alt="Camera and imaging research" loading="lazy">
    </div>
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">Computer Vision</a></div>
    <div class="card__body">Convolutional networks, diffusion models, video understanding, and 3D reconstruction.</div>
  </div>
  <div class="card">
    <div class="card__image">
      <img src="/sites/default/files/demo-images/photo-1573164574572-cb89e39749b4.jpg"
           alt="Students in discussion" loading="lazy">
    </div>
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">AI Ethics &amp; Policy</a></div>
    <div class="card__body">Fairness, accountability, transparency, and the governance of AI systems in society.</div>
  </div>
  <div class="card">
    <div class="card__image">
      <img src="/sites/default/files/demo-images/photo-1485827404703-89b55fcc595e.jpg"
           alt="Robotics and autonomous systems" loading="lazy">
    </div>
    <div class="card__label">Graduate Programs</div>
    <div class="card__title"><a href="/programs">Reinforcement Learning</a></div>
    <div class="card__body">Policy optimization, model-based RL, multi-agent systems, and safe exploration.</div>
  </div>
</div>

<h2>Research at Meridian AI</h2>
<p>Our faculty lead internationally recognized research programs across 20 active projects. From scalable training infrastructure to multilingual NLP and interpretability, Meridian AI sits at the frontier of AI research.</p>
<p><a href="/research">Explore our research projects →</a></p>

<div class="home-photo-strip">
  <figure class="home-photo-strip__item">
    <a href="/learn">
      <img src="/sites/default/files/demo-images/photo-1541339907198-e08756dedf3f.jpg"
           alt="Lecture hall" loading="lazy">
      <figcaption>Graduate Lectures</figcaption>
    </a>
  </figure>
  <figure class="home-photo-strip__item">
    <a href="/research">
      <img src="/sites/default/files/demo-images/photo-1522202176988-66273c2fd55f.jpg"
           alt="Students collaborating" loading="lazy">
      <figcaption>Collaborative Research</figcaption>
    </a>
  </figure>
  <figure class="home-photo-strip__item">
    <a href="/research">
      <img src="/sites/default/files/demo-images/photo-1507413245164-6160d8298b31.jpg"
           alt="Research laboratory" loading="lazy">
      <figcaption>Research Labs</figcaption>
    </a>
  </figure>
</div>

<h2>Latest News</h2>
<p>Stay current with Meridian AI's growing presence in academia, industry, and public discourse.</p>
<p><a href="/news">Read all news →</a></p>
HTML;

$node->set('body', [
  'value'   => $body,
  'format'  => 'full_html',
  'summary' => '',
]);
$node->save();
echo "Updated homepage with hero image, card photos, and photo strip.\n";
