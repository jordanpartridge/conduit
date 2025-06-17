<?php

return [
    'installed' => [
        // Components will be automatically registered here by ComponentManager
    ],

    'registry' => [
        'github' => [
            'package' => 'jordanpartridge/github-zero',
            'description' => 'GitHub CLI integration with interactive commands',
            'commands' => ['repos', 'clone'],
            'env_vars' => ['GITHUB_TOKEN'],
            'service_providers' => ['JordanPartridge\GitHubZero\GitHubZeroServiceProvider'],
            'topics' => ['conduit-component', 'github', 'cli'],
        ],
    ],

    'discovery' => [
        'github_topic' => 'conduit-component',
        'fallback_to_local' => true,
        'auto_discover' => false,
    ],
];