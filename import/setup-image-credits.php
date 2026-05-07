<?php
/**
 * Create or update the /image-credits page.
 *
 * Run with: drush php:script import/setup-image-credits.php
 *
 * All images on this site are sourced from Unsplash under the Unsplash License.
 * Attribution is appreciated but not required by the license. This page provides
 * links to the original photo pages so photographers can be credited.
 *
 * 5 of the original photo IDs were no longer available on Unsplash at the time
 * of local mirroring and were replaced with visually appropriate substitutes;
 * those are marked below with their substitute photo URL.
 */

// Photo data: [file_id, context, unsplash_page_url]
// For replaced photos, the unsplash_page_url is the replacement photo.
$photos = [
  // Homepage
  ['photo-1562774053-701939374585', 'Homepage hero banner', 'https://unsplash.com/photos/photo-1562774053-701939374585'],
  ['photo-1677442135703-1787eea5ce01', 'Homepage — LLM/NLP card', 'https://unsplash.com/photos/photo-1677442135703-1787eea5ce01'],
  ['photo-1516116216624-53e697fedbea', 'Homepage — Computer Vision card', 'https://unsplash.com/photos/photo-1516116216624-53e697fedbea'],
  ['photo-1573164574572-cb89e39749b4', 'Homepage — AI Ethics card', 'https://unsplash.com/photos/photo-1573164574572-cb89e39749b4'],
  ['photo-1485827404703-89b55fcc595e', 'Homepage — Reinforcement Learning card', 'https://unsplash.com/photos/photo-1485827404703-89b55fcc595e'],
  ['photo-1541339907198-e08756dedf3f', 'Homepage — Lectures photo strip', 'https://unsplash.com/photos/photo-1541339907198-e08756dedf3f'],
  ['photo-1522202176988-66273c2fd55f', 'Homepage — Collaborative Research photo strip', 'https://unsplash.com/photos/photo-1522202176988-66273c2fd55f'],
  ['photo-1507413245164-6160d8298b31', 'Homepage — Research Labs photo strip; also Research page hero', 'https://unsplash.com/photos/photo-1507413245164-6160d8298b31'],

  // Campus & pages
  ['photo-1498243691581-b145c3f54a5a', 'Campus Life page hero; About page hero', 'https://unsplash.com/photos/photo-1498243691581-b145c3f54a5a'],
  ['photo-1481627834876-b7833e8f5570', 'Campus Life — library reading room', 'https://unsplash.com/photos/photo-1481627834876-b7833e8f5570'],
  ['photo-1523050854058-8df90110c9f1', 'Campus Life — students on campus (substitute)', 'https://unsplash.com/photos/photo-1521587765099-8835e7201186'],
  ['photo-1531746790731-6c087fecd65a', 'Research page — team collaboration', 'https://unsplash.com/photos/photo-1531746790731-6c087fecd65a'],
  ['photo-1543269865-cbf427effbad', 'Admissions page hero', 'https://unsplash.com/photos/photo-1543269865-cbf427effbad'],

  // Courses
  ['photo-1555952517-2e8e729e0b44', 'Course: Building a Tokenizer', 'https://unsplash.com/photos/photo-1555952517-2e8e729e0b44'],
  ['photo-1516321318423-f06f85e504b3', 'Course: Prompt Engineering', 'https://unsplash.com/photos/photo-1516321318423-f06f85e504b3'],
  ['photo-1618005182384-a83a8bd57fbe', 'Course: Diffusion Models', 'https://unsplash.com/photos/photo-1618005182384-a83a8bd57fbe'],
  ['photo-1451187580459-43490279c0fa', 'Course: How Search Engines Use AI', 'https://unsplash.com/photos/photo-1451187580459-43490279c0fa'],

  // Programs
  ['photo-1563986768609-322da13575f3', 'Program: AI for Robotics', 'https://unsplash.com/photos/photo-1563986768609-322da13575f3'],
  ['photo-1635070041078-e363dbe005cb', 'Program: Mathematical Foundations of AI', 'https://unsplash.com/photos/photo-1635070041078-e363dbe005cb'],
  ['photo-1550751827-4bd374c3f58b', 'Program: AI Safety &amp; Alignment', 'https://unsplash.com/photos/photo-1550751827-4bd374c3f58b'],
  ['photo-1558494949-ef010cbdcc31', 'Program: AI Engineering &amp; MLOps', 'https://unsplash.com/photos/photo-1558494949-ef010cbdcc31'],
  ['photo-1551288049-bebda4e38f71', 'Program: AI-Powered Search; Research: Fairness Benchmark', 'https://unsplash.com/photos/photo-1551288049-bebda4e38f71'],

  // Research
  ['photo-1454165804606-c3d57bc86b40', 'Research: Algorithmic Impact Assessment', 'https://unsplash.com/photos/photo-1454165804606-c3d57bc86b40'],
  ['photo-1518770660439-4636190af475', 'Research: Neural Architecture Search', 'https://unsplash.com/photos/photo-1518770660439-4636190af475'],
  ['photo-1589998059171-988d887df646', 'Research: Causal Fairness', 'https://unsplash.com/photos/photo-1589998059171-988d887df646'],
  ['photo-1576671081837-49000212a0b9', 'Research: Surgical Robotics (substitute)', 'https://unsplash.com/photos/photo-1551190822-a9333d879b1f'],
  ['photo-1535378917042-10a22c95931a', 'Research: Continuous Learning', 'https://unsplash.com/photos/photo-1535378917042-10a22c95931a'],
  ['photo-1530026405186-ed1f139313f8', 'Research: Federated Learning', 'https://unsplash.com/photos/photo-1530026405186-ed1f139313f8'],
  ['photo-1589829545856-d10d557cf95f', 'Research: Constitutional AI', 'https://unsplash.com/photos/photo-1589829545856-d10d557cf95f'],
  ['photo-1607799279861-4dd421887fb3', 'Research: LLM APIs in Production', 'https://unsplash.com/photos/photo-1607799279861-4dd421887fb3'],
  ['photo-1549317661-bd32c8ce0db2', 'Research: Autonomous Driving', 'https://unsplash.com/photos/photo-1549317661-bd32c8ce0db2'],
  ['photo-1555421689-d68471e189f2', 'Research: AI Regulation', 'https://unsplash.com/photos/photo-1555421689-d68471e189f2'],

  // News
  ['photo-1475721027785-f74eccf877e2', 'News: NSF CAREER Award', 'https://unsplash.com/photos/photo-1475721027785-f74eccf877e2'],
  ['photo-1513258496099-48168024aec0', 'News: AI Literacy Program', 'https://unsplash.com/photos/photo-1513258496099-48168024aec0'],
  ['photo-1540575467063-178a50c2df87', 'News: ICML Conference', 'https://unsplash.com/photos/photo-1540575467063-178a50c2df87'],
  ['photo-1523580846011-d3a5bc25702b', 'News: PhD Student Fellowship', 'https://unsplash.com/photos/photo-1523580846011-d3a5bc25702b'],
  ['photo-1477959858617-67f85cf4f1df', 'News: AI Governance Partnership', 'https://unsplash.com/photos/photo-1477959858617-67f85cf4f1df'],
  ['photo-1491975474562-1f4e30bc9468', 'News: Research Symposium', 'https://unsplash.com/photos/photo-1491975474562-1f4e30bc9468'],

  // Faculty
  ['photo-1494790108377-be9c29b29330', 'Faculty portrait: Dr. Amara Osei-Mensah', 'https://unsplash.com/photos/photo-1494790108377-be9c29b29330'],
  ['photo-1544725176-7c40e5a71c5e', 'Faculty portrait: Dr. Yuki Tanaka', 'https://unsplash.com/photos/photo-1544725176-7c40e5a71c5e'],
  ['photo-1531746020798-e6953c6e8e04', 'Faculty portrait: Dr. Sofia Navarro', 'https://unsplash.com/photos/photo-1531746020798-e6953c6e8e04'],
  ['photo-1560250097-0b93528c311a', 'Faculty portrait: Dr. Marcus Webb', 'https://unsplash.com/photos/photo-1560250097-0b93528c311a'],
  ['photo-1472099645785-5658abf4ff4e', 'Faculty portrait: Dr. Elias Müller', 'https://unsplash.com/photos/photo-1472099645785-5658abf4ff4e'],
  ['photo-1544005313-94ddf0286df2', 'Faculty portrait: Dr. Ingrid Holmberg', 'https://unsplash.com/photos/photo-1544005313-94ddf0286df2'],
  ['photo-1568602471122-7832951cc4c5', 'Faculty portrait: Dr. Ravi Shankar', 'https://unsplash.com/photos/photo-1568602471122-7832951cc4c5'],
  ['photo-1573496359142-b8d87734a5a2', 'Faculty portrait: Dr. Chioma Adeyemi', 'https://unsplash.com/photos/photo-1573496359142-b8d87734a5a2'],
  ['photo-1531384441138-2736e62e0919', 'Faculty portrait: Dr. James Okafor', 'https://unsplash.com/photos/photo-1531384441138-2736e62e0919'],
  ['photo-1463453091185-61582044d556', 'Faculty portrait: Dr. Kwame Asante', 'https://unsplash.com/photos/photo-1463453091185-61582044d556'],
  ['photo-1534528741775-53994a69daeb', 'Faculty portrait: Dr. Nadia Petrov', 'https://unsplash.com/photos/photo-1534528741775-53994a69daeb'],
  ['photo-1507003211169-0a1dd7228f2d', 'Faculty portrait: Dr. Oluwaseun Adewale', 'https://unsplash.com/photos/photo-1507003211169-0a1dd7228f2d'],
  ['photo-1519085360753-af0119f7cbe7', 'Faculty portrait: Dr. Tariq Hassan', 'https://unsplash.com/photos/photo-1519085360753-af0119f7cbe7'],
  ['photo-1542909168-82c3e7fdca5c', 'Faculty portrait: Dr. Wei Chen', 'https://unsplash.com/photos/photo-1542909168-82c3e7fdca5c'],
  ['photo-1580489944761-15a19d654956', 'Faculty portrait: Dr. Amelia Torres', 'https://unsplash.com/photos/photo-1580489944761-15a19d654956'],
  ['photo-1438761681033-6461ffad8d80', 'Faculty portrait: Dr. Elena Marchetti', 'https://unsplash.com/photos/photo-1438761681033-6461ffad8d80'],
  ['photo-1546961342-ea5f71b193f3', 'Faculty portrait: Dr. Fatima Al-Rashid', 'https://unsplash.com/photos/photo-1546961342-ea5f71b193f3'],
  // Replaced photos (originals were removed from Unsplash)
  ['photo-1522529599102-193144843ded', 'Faculty portrait: Dr. Abraham Kiptoo (substitute)', 'https://unsplash.com/photos/photo-1633332755192-727a05c4013d'],
  ['photo-1502031882019-24c0bccffe8f', 'Faculty portrait: Dr. Mei Lin (substitute)', 'https://unsplash.com/photos/photo-1580618672591-eb180b1a973f'],
  ['photo-1523038874956-c5dc66469c46', 'Faculty portrait: Dr. Priya Chakraborty (substitute)', 'https://unsplash.com/photos/photo-1601979031925-424e53b6caaa'],
];

$rows = '';
foreach ($photos as [$file_id, $context, $url]) {
  $rows .= '<tr><td><code>' . htmlspecialchars($file_id) . '.jpg</code></td>'
         . '<td>' . htmlspecialchars($context) . '</td>'
         . '<td><a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener">View on Unsplash</a></td></tr>' . "\n";
}

$body = <<<HTML
<p>This demo site uses photographs from <a href="https://unsplash.com" target="_blank" rel="noopener">Unsplash</a>. Images are stored locally and are used under the <a href="https://unsplash.com/license" target="_blank" rel="noopener">Unsplash License</a>.</p>

<table>
<thead>
  <tr>
    <th>File</th>
    <th>Used for</th>
    <th>Original</th>
  </tr>
</thead>
<tbody>
{$rows}
</tbody>
</table>

<p><small>Photos marked "(substitute)" are visually appropriate replacements for photos that were no longer available on Unsplash at the time this site was built. All images remain subject to the Unsplash License.</small></p>
HTML;

$alias_manager = \Drupal::service('path_alias.manager');
$path = $alias_manager->getPathByAlias('/image-credits');

$nid = null;
if ($path !== '/image-credits') {
  // Existing page found — extract nid
  if (preg_match('|^/node/(\d+)$|', $path, $m)) {
    $nid = (int) $m[1];
  }
}

if ($nid) {
  $node = \Drupal\node\Entity\Node::load($nid);
  echo "Updating existing credits page (nid $nid)\n";
} else {
  $node = \Drupal\node\Entity\Node::create([
    'type'   => 'page',
    'title'  => 'Image Credits',
    'status' => 1,
    'uid'    => 1,
  ]);
  echo "Creating new credits page\n";
}

$node->set('body', [
  'value'  => $body,
  'format' => 'full_html',
  'summary' => 'Image credits for photos used on the Meridian AI demo site.',
]);
$node->save();

// Set URL alias /image-credits
$alias_storage = \Drupal::entityTypeManager()->getStorage('path_alias');
$existing = $alias_storage->loadByProperties([
  'path'     => '/node/' . $node->id(),
  'langcode' => 'en',
]);
foreach ($existing as $a) { $a->delete(); }

$alias_storage->create([
  'path'     => '/node/' . $node->id(),
  'alias'    => '/image-credits',
  'langcode' => 'en',
])->save();

echo "Credits page saved at /image-credits (nid " . $node->id() . ")\n";

// Add image-credits link to /about/demo (nid 161) Reuse and Attribution section
$about_demo = \Drupal\node\Entity\Node::load(161);
if ($about_demo) {
  $about_body = $about_demo->get('body')->value;
  $credits_link = '<p>The photographs used throughout this site are sourced from Unsplash. See the <a href="/image-credits">Image Credits</a> page for a full listing with links to each photographer\'s work.</p>';
  if (!str_contains($about_body, 'image-credits')) {
    $anchor = '<p>The technical content on this site was written to be educationally accurate.';
    $about_body = str_replace($anchor, $credits_link . "\n" . $anchor, $about_body);
    $about_demo->set('body', ['value' => $about_body, 'format' => 'full_html', 'summary' => '']);
    $about_demo->save();
    echo "Added image credits link to /about/demo\n";
  } else {
    echo "/about/demo already has image-credits link\n";
  }
}
