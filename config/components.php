<?php

return array (
  'installed' => array (
    'github-zero' => array (
      'package' => 'jordanpartridge/github-zero',
      'description' => 'Lightweight GitHub CLI that works standalone, in Laravel, Laravel Zero, or as a Conduit extension',
      'commands' => array (
        0 => 'repos',
        1 => 'clone',
      ),
      'env_vars' => array (
      ),
      'service_providers' => array (
        0 => 'JordanPartridge\\GitHubZero\\GitHubZeroServiceProvider',
      ),
      'topics' => array (
        0 => 'cli',
        1 => 'conduit-component',
        2 => 'conduit-extension',
        3 => 'github',
        4 => 'laravel',
        5 => 'laravel-zero',
      ),
      'url' => 'https://github.com/jordanpartridge/github-zero',
      'stars' => 0,
      'status' => 'active',
      'installed_at' => '2025-06-18T03:05:00.000000Z',
    ),
  ),
  'registry' => array (
  ),
  'discovery' => array (
    'github_topic' => 'conduit-component',
    'fallback_to_local' => true,
    'auto_discover' => false,
  ),
);
