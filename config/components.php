<?php

return array (
  'installed' => array (
    'github' => array (
      'package' => 'jordanpartridge/github-zero',
      'description' => 'GitHub CLI integration with interactive commands',
      'commands' => array (
        0 => 'repos',
        1 => 'clone',
      ),
      'env_vars' => array (
        0 => 'GITHUB_TOKEN',
      ),
      'service_providers' => array (
        0 => 'JordanPartridge\\GitHubZero\\GitHubZeroServiceProvider',
      ),
      'topics' => array (
        0 => 'conduit-component',
        1 => 'github',
        2 => 'cli',
      ),
      'status' => 'active',
      'installed_at' => '2025-06-17T06:07:13.567879Z',
      'version' => '^1.0',
    ),
  ),
  'registry' => array (
    'github' => array (
      'package' => 'jordanpartridge/github-zero',
      'description' => 'GitHub CLI integration with interactive commands',
      'commands' => array (
        0 => 'repos',
        1 => 'clone',
      ),
      'env_vars' => array (
        0 => 'GITHUB_TOKEN',
      ),
      'service_providers' => array (
        0 => 'JordanPartridge\\GitHubZero\\GitHubZeroServiceProvider',
      ),
      'topics' => array (
        0 => 'conduit-component',
        1 => 'github',
        2 => 'cli',
      ),
    ),
  ),
  'discovery' => array (
    'github_topic' => 'conduit-component',
    'fallback_to_local' => true,
    'auto_discover' => false,
  ),
);
