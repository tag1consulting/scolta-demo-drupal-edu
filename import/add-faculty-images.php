<?php
/**
 * Add professional portrait images to each faculty node.
 * Uses Unsplash crop=faces to auto-center on faces in the crop.
 */

// Keyed by nid => [photo_id, alt_text]
// Photos are chosen for diversity matching faculty names/backgrounds.
$images = [
  65 => ['photo-1522529599102-193144843ded', 'Dr. Abraham Kiptoo, Meridian AI faculty'],
  49 => ['photo-1494790108377-be9c29b29330', 'Dr. Amara Osei-Mensah, School of Language and Reasoning'],
  68 => ['photo-1580489944761-15a19d654956', 'Dr. Amelia Torres, Labor and Society Program'],
  58 => ['photo-1573496359142-b8d87734a5a2', 'Dr. Chioma Adeyemi, School of Societal and Governance AI'],
  60 => ['photo-1438761681033-6461ffad8d80', 'Dr. Elena Marchetti, School of Applied AI and Infrastructure'],
  55 => ['photo-1472099645785-5658abf4ff4e', 'Dr. Elias Müller, Meridian AI faculty'],
  66 => ['photo-1546961342-ea5f71b193f3', 'Dr. Fatima Al-Rashid, Clinical AI Research Group'],
  56 => ['photo-1544005313-94ddf0286df2', 'Dr. Ingrid Holmberg, School of Foundations and Mathematics'],
  59 => ['photo-1531384441138-2736e62e0919', 'Dr. James Okafor, AlignBench project'],
  50 => ['photo-1463453091185-61582044d556', 'Dr. Kwame Asante, AlignBench project'],
  53 => ['photo-1560250097-0b93528c311a', 'Dr. Marcus Webb, Meridian AI faculty'],
  62 => ['photo-1502031882019-24c0bccffe8f', 'Dr. Mei Lin, Meridian AI faculty'],
  64 => ['photo-1534528741775-53994a69daeb', 'Dr. Nadia Petrov, Meridian AI faculty'],
  63 => ['photo-1507003211169-0a1dd7228f2d', 'Dr. Oluwaseun Adewale, Meridian AI faculty'],
  54 => ['photo-1523038874956-c5dc66469c46', 'Dr. Priya Chakraborty, School of Decision and Control'],
  57 => ['photo-1568602471122-7832951cc4c5', 'Dr. Ravi Shankar, optimization research'],
  52 => ['photo-1531746020798-e6953c6e8e04', 'Dr. Sofia Navarro, School of Perception and Synthesis'],
  61 => ['photo-1519085360753-af0119f7cbe7', 'Dr. Tariq Hassan, Meridian AI faculty'],
  67 => ['photo-1542909168-82c3e7fdca5c', 'Dr. Wei Chen, Meridian AI faculty'],
  51 => ['photo-1544725176-7c40e5a71c5e', 'Dr. Yuki Tanaka, Meridian AI faculty'],
];

$count = 0;
foreach ($images as $nid => $info) {
  [$photo_id, $alt] = $info;
  $node = \Drupal\node\Entity\Node::load($nid);
  if (!$node) { echo "Not found: $nid\n"; continue; }

  $body = $node->get('body')->value;
  if (str_contains($body, 'node-featured-image')) {
    echo "Skipping (already has image): $nid\n"; continue;
  }

  // Portrait crop with face detection for professional headshots
  $url = 'https://images.unsplash.com/' . $photo_id . '?w=480&h=600&fit=crop&crop=faces&q=80';
  $img = '<div class="node-featured-image node-featured-image--portrait"><img src="' . $url . '" alt="' . htmlspecialchars($alt) . '" loading="lazy"></div>' . "\n\n";

  $node->set('body', ['value' => $img . $body, 'format' => 'full_html', 'summary' => '']);
  $node->save();
  echo "Updated: $nid — " . $node->getTitle() . "\n";
  $count++;
}

echo "\nAdded portrait images to $count faculty nodes.\n";
