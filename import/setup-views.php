<?php
/**
 * Creates listing views for each content type.
 * Run with: ddev drush php:script import/setup-views.php
 */

use Drupal\views\Entity\View;

$sections = [
  [
    'id'     => 'meridian_programs',
    'title'  => 'Programs',
    'path'   => 'programs',
    'type'   => 'program',
    'header' => 'Degree Programs',
    'desc'   => 'Graduate and undergraduate programs in artificial intelligence.',
    'pager'  => 12,
    'sort'   => 'title',
  ],
  [
    'id'     => 'meridian_courses',
    'title'  => 'Courses',
    'path'   => 'courses',
    'type'   => 'course',
    'header' => 'Course Catalog',
    'desc'   => 'Individual courses across all AI disciplines.',
    'pager'  => 12,
    'sort'   => 'title',
  ],
  [
    'id'     => 'meridian_faculty',
    'title'  => 'Faculty',
    'path'   => 'faculty',
    'type'   => 'faculty',
    'header' => 'Faculty',
    'desc'   => 'Meridian AI faculty and researchers.',
    'pager'  => 20,
    'sort'   => 'title',
  ],
  [
    'id'     => 'meridian_research',
    'title'  => 'Research',
    'path'   => 'research',
    'type'   => 'research_project',
    'header' => 'Research Projects',
    'desc'   => 'Active research projects and initiatives.',
    'pager'  => 12,
    'sort'   => 'title',
  ],
  [
    'id'     => 'meridian_resources',
    'title'  => 'Resources',
    'path'   => 'resources',
    'type'   => 'resource_article',
    'header' => 'Resources',
    'desc'   => 'Articles, guides, and reference materials.',
    'pager'  => 12,
    'sort'   => 'title',
  ],
  [
    'id'     => 'meridian_learn',
    'title'  => 'Learning Center',
    'path'   => 'learn',
    'type'   => 'lecture_page',
    'header' => 'Learning Center',
    'desc'   => 'Lectures, tutorials, and hands-on learning materials.',
    'pager'  => 12,
    'sort'   => 'title',
  ],
  [
    'id'     => 'meridian_news',
    'title'  => 'News',
    'path'   => 'news',
    'type'   => 'news',
    'header' => 'News',
    'desc'   => 'Latest news and announcements from Meridian AI.',
    'pager'  => 12,
    'sort'   => 'created_desc',
  ],
];

$storage = \Drupal::entityTypeManager()->getStorage('view');

foreach ($sections as $s) {
  // Delete if exists.
  if ($existing = $storage->load($s['id'])) {
    $existing->delete();
    echo "Deleted existing: {$s['id']}\n";
  }

  $sort_field = $s['sort'] === 'created_desc' ? 'created' : 'title';
  $sort_order = $s['sort'] === 'created_desc' ? 'DESC' : 'ASC';

  $view_config = [
    'id'          => $s['id'],
    'label'       => $s['title'],
    'module'      => 'views',
    'description' => $s['desc'],
    'tag'         => 'meridian',
    'base_table'  => 'node_field_data',
    'base_field'  => 'nid',
    'display'     => [
      'default' => [
        'display_plugin' => 'default',
        'id'             => 'default',
        'display_title'  => 'Default',
        'position'       => 0,
        'display_options' => [
          'title'  => $s['header'],
          'fields' => [],
          'sorts'  => [
            $sort_field => [
              'id'         => $sort_field,
              'table'      => 'node_field_data',
              'field'      => $sort_field,
              'order'      => $sort_order,
              'plugin_id'  => $sort_field === 'title' ? 'standard' : 'date',
            ],
          ],
          'filters' => [
            'status' => [
              'id'         => 'status',
              'table'      => 'node_field_data',
              'field'      => 'status',
              'value'      => '1',
              'group'      => 1,
              'expose'     => ['operator' => ''],
              'plugin_id'  => 'boolean',
            ],
            'type' => [
              'id'         => 'type',
              'table'      => 'node_field_data',
              'field'      => 'type',
              'value'      => [$s['type'] => $s['type']],
              'group'      => 1,
              'plugin_id'  => 'bundle',
            ],
          ],
          'pager'  => [
            'type'    => 'full',
            'options' => [
              'items_per_page'   => $s['pager'],
              'offset'           => 0,
              'id'               => 0,
              'total_pages'      => null,
              'tags'             => [
                'previous'    => '‹ Previous',
                'next'        => 'Next ›',
                'first'       => '« First',
                'last'        => 'Last »',
              ],
              'expose'           => [
                'items_per_page'       => FALSE,
                'items_per_page_label' => 'Items per page',
                'items_per_page_options' => '5, 10, 25, 50',
                'items_per_page_options_all' => FALSE,
                'items_per_page_options_all_label' => '- All -',
                'offset'              => FALSE,
                'offset_label'        => 'Offset',
              ],
              'quantity'         => 9,
            ],
          ],
          'style'  => [
            'type'    => 'default',
            'options' => ['grouping' => [], 'row_class' => '', 'default_row_class' => TRUE],
          ],
          'row'    => [
            'type'    => 'entity:node',
            'options' => [
              'relationship'       => 'none',
              'view_mode'          => 'teaser',
            ],
          ],
          'query'  => [
            'type'    => 'views_query',
            'options' => [
              'query_comment'   => '',
              'disable_sql_rewrite' => FALSE,
              'distinct'        => FALSE,
              'replica'         => FALSE,
              'query_tags'      => [],
            ],
          ],
          'relationships' => [],
          'arguments' => [],
          'header' => [],
          'footer' => [],
          'empty' => [
            'area_text_custom' => [
              'id'         => 'area_text_custom',
              'table'      => 'views',
              'field'      => 'area_text_custom',
              'content'    => '<p>No content found.</p>',
              'plugin_id'  => 'text_custom',
            ],
          ],
          'access' => ['type' => 'perm', 'options' => ['perm' => 'access content']],
          'cache'  => ['type' => 'tag', 'options' => []],
          'exposed_form' => ['type' => 'basic', 'options' => ['submit_button' => 'Apply', 'reset_button' => FALSE, 'reset_button_label' => 'Reset', 'exposed_sorts_label' => 'Sort by', 'expose_sort_order' => TRUE, 'sort_asc_label' => 'Asc', 'sort_desc_label' => 'Desc']],
          'use_ajax' => FALSE,
          'use_more' => FALSE,
          'use_more_always' => TRUE,
          'use_more_text' => 'more',
          'link_display' => '',
          'group_by' => FALSE,
          'rendering_language' => '***LANGUAGE_entity_translation***',
        ],
      ],
      'page_1' => [
        'display_plugin' => 'page',
        'id'             => 'page_1',
        'display_title'  => 'Page',
        'position'       => 1,
        'display_options' => [
          'path'           => $s['path'],
          'display_description' => '',
          'menu' => [
            'type'       => 'none',
            'title'      => '',
            'description' => '',
            'weight'     => 0,
            'name'       => 'main',
          ],
          'tab_options' => [
            'type'  => 'none',
            'title' => '',
            'description' => '',
            'weight' => 0,
            'name'  => 'operations',
          ],
          'defaults' => [
            'title'       => TRUE,
            'style'       => TRUE,
            'row'         => TRUE,
            'pager'       => TRUE,
            'header'      => TRUE,
            'footer'      => TRUE,
            'empty'       => TRUE,
            'filters'     => TRUE,
            'sorts'       => TRUE,
            'access'      => TRUE,
            'cache'       => TRUE,
            'use_more'    => TRUE,
            'use_more_always' => TRUE,
            'use_more_text' => TRUE,
            'exposed_form' => TRUE,
            'rendering_language' => TRUE,
          ],
        ],
      ],
    ],
  ];

  $view = View::create($view_config);
  $view->save();
  echo "Created view: {$s['id']} at /{$s['path']}\n";
}

echo "\nDone! Clear caches and test the views.\n";
