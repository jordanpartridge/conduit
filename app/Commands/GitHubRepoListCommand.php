<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use JordanPartridge\GithubClient\Enums\Sort;
use JordanPartridge\GithubClient\Enums\RepoType;
use JordanPartridge\GithubClient\Github;
use LaravelZero\Framework\Commands\Command;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\select;
use function Laravel\Prompts\spin;
use function Termwind\render;

class GitHubRepoListCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'github:repos 
                            {--type= : Repository type (all, owner, public, private, member)}
                            {--sort= : Sort repositories by (created, updated, pushed, full_name)}
                            {--limit= : Number of repositories to display}
                            {--interactive : Use interactive prompts}';

    /**\
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List and interact with your GitHub repositories';

    /**
     * Create a new command instance.
     */
    public function __construct(
        protected Github $github
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->hasGitHubToken()) {
            $this->error('🚫 No GitHub token found!');
            $this->comment('💡 Run: conduit github:setup');
            return 1;
        }

        $this->displayWelcome();

        $options = $this->getFilterOptions();

        try {
            $repos = spin(
                fn () => $this->github->repos()->all(
                    type: $this->mapTypeToEnum($options['type']),
                    sort: $this->mapSortToEnum($options['sort']),
                    per_page: $options['limit']
                )->json(),
                '🔍 Fetching your repositories...'
            );

            if (empty($repos)) {
                $this->comment('🤷 No repositories found. Time to start coding!');
                return 0;
            }

            $this->displayRepositories($repos);

            if ($this->option('interactive') || (!$this->option('type') && !$this->option('sort'))) {
                $this->handleInteractiveActions($repos);
            }

        } catch (\Exception $e) {
            $this->error("💥 Failed to fetch repositories: {$e->getMessage()}");
            $this->comment('🔧 Try running: conduit github:setup --check');
            return 1;
        }

        return 0;
    }

    private function displayWelcome(): void
    {
        render(<<<'HTML'
            <div class="py-1 ml-2">
                <div class="px-1 bg-purple-600 text-white font-bold">📚 Your GitHub Repositories</div>
                <div class="mt-1 text-gray-300">
                    Here's your coding empire! 👑
                </div>
            </div>
        HTML);

        $this->newLine();
    }

    private function displayRepositories(array $repos): void
    {
        foreach ($repos as $index => $repo) {
            $this->displayRepositoryCard($repo, $index + 1);
        }
        
        $this->newLine();
        $this->comment('💡 Pro tips:');
        $this->comment('  --interactive      Use interactive prompts');
        $this->comment('  --type=private     Show only private repos');
        $this->comment('  --sort=created     Sort by creation date');
        $this->comment('  --limit=20         Show more repos');
    }

    private function displayRepositoryCard(array $repo, int $index): void
    {
        $name = $repo['full_name'];
        $language = $this->addLanguageEmoji($repo['language'] ?? 'Unknown');
        $stars = number_format($repo['stargazers_count']);
        $forks = number_format($repo['forks_count']);
        $updated = $this->formatDate($repo['updated_at']);
        $private = $repo['private'] ? '🔒 Private' : '🌍 Public';
        $description = $repo['description'] ? substr($repo['description'], 0, 60) . (strlen($repo['description']) > 60 ? '...' : '') : '💭 No description';

        render(<<<HTML
            <div class="py-1 ml-2 mb-1">
                <div class="px-2 py-1 bg-gray-800 text-white font-bold">
                    #{$index} {$name}
                </div>
                <div class="text-right text-gray-400 mt-1">
                    {$private}
                </div>
                <div class="mt-1 px-2 text-gray-300">
                    {$description}
                </div>
                <div class="mt-1 px-2">
                    <span class="text-blue-400">{$language}</span>
                    <span class="text-yellow-400 ml-4">⭐ {$stars}</span>
                    <span class="text-green-400 ml-4">🍴 {$forks}</span>
                    <span class="text-gray-400 ml-4">🔄 {$updated}</span>
                </div>
            </div>
        HTML);
    }

    private function formatDate(string $date): string
    {
        return \Carbon\Carbon::parse($date)->diffForHumans();
    }

    private function addLanguageEmoji(string $language): string
    {
        $emojis = [
            'PHP' => '🐘',
            'JavaScript' => '🟨',
            'TypeScript' => '🔷',
            'Python' => '🐍',
            'Java' => '☕',
            'Go' => '🐹',
            'Rust' => '🦀',
            'C++' => '⚡',
            'C#' => '💎',
            'Ruby' => '💎',
            'Swift' => '🍎',
            'Kotlin' => '🟣',
            'Dart' => '🎯',
            'HTML' => '🌐',
            'CSS' => '🎨',
            'Shell' => '🐚',
            'Dockerfile' => '🐳',
        ];

        return ($emojis[$language] ?? '📝') . ' ' . $language;
    }

    private function hasGitHubToken(): bool
    {
        return !empty(env('GITHUB_TOKEN'));
    }

    private function mapSortToEnum(string $sort): Sort
    {
        return match($sort) {
            'created' => Sort::CREATED,
            'updated' => Sort::UPDATED,
            'pushed' => Sort::PUSHED,
            'full_name' => Sort::FULL_NAME,
            default => Sort::UPDATED,
        };
    }

    private function mapTypeToEnum(string $type): RepoType
    {
        return match($type) {
            'all' => RepoType::All,
            'public' => RepoType::Public,
            'private' => RepoType::Private,
            'member' => RepoType::Member,
            'owner' => RepoType::Owner,
            default => RepoType::All,
        };
    }


    private function getFilterOptions(): array
    {
        $type = $this->option('type') ?: ($this->option('interactive') ? $this->selectType() : 'all');
        $sort = $this->option('sort') ?: ($this->option('interactive') ? $this->selectSort() : 'updated');
        $limit = $this->option('limit') ?: ($this->option('interactive') ? $this->selectLimit() : 10);

        return [
            'type' => $type,
            'sort' => $sort,
            'limit' => (int) $limit,
        ];
    }

    private function selectType(): string
    {
        return select(
            '📂 Which repositories would you like to see?',
            [
                'all' => '🌍 All repositories',
                'owner' => '👤 Repositories I own',
                'public' => '🌐 Public repositories',
                'private' => '🔒 Private repositories',
                'member' => '👥 Repositories I\'m a member of',
            ],
            default: 'all'
        );
    }

    private function selectSort(): string
    {
        return select(
            '📊 How would you like to sort them?',
            [
                'updated' => '🔄 Recently updated',
                'created' => '✨ Recently created',
                'pushed' => '🚀 Recently pushed',
                'full_name' => '📝 Alphabetically',
            ],
            default: 'updated'
        );
    }

    private function selectLimit(): int
    {
        return (int) select(
            '🔢 How many repositories?',
            [
                '5' => '5 repositories',
                '10' => '10 repositories',
                '20' => '20 repositories',
                '50' => '50 repositories',
            ],
            default: '10'
        );
    }

    private function handleInteractiveActions(array $repos): void
    {
        $action = select(
            '🚀 What would you like to do?',
            [
                'open' => '🌐 Open a repository in browser',
                'clone' => '📥 Get clone command for a repository',
                'details' => '📋 View repository details',
                'done' => '✅ Done',
            ],
            default: 'done'
        );

        match($action) {
            'open' => $this->openRepository($repos),
            'clone' => $this->showCloneCommand($repos),
            'details' => $this->showRepositoryDetails($repos),
            'done' => null,
        };
    }

    private function openRepository(array $repos): void
    {
        $repoOptions = [];
        foreach ($repos as $repo) {
            $repoOptions[$repo['full_name']] = "🗂️ {$repo['full_name']} - {$this->addLanguageEmoji($repo['language'] ?? 'Unknown')}";
        }

        $selectedRepo = select('🌐 Which repository to open?', $repoOptions);
        
        $repo = collect($repos)->firstWhere('full_name', $selectedRepo);
        
        if ($repo && confirm("🚀 Open {$repo['full_name']} in your browser?", true)) {
            exec("open {$repo['html_url']}");
            $this->info("✅ Opened {$repo['full_name']}!");
        }
    }

    private function showCloneCommand(array $repos): void
    {
        $repoOptions = [];
        foreach ($repos as $repo) {
            $repoOptions[$repo['full_name']] = "🗂️ {$repo['full_name']} - {$this->addLanguageEmoji($repo['language'] ?? 'Unknown')}";
        }

        $selectedRepo = select('📥 Which repository to clone?', $repoOptions);
        
        $repo = collect($repos)->firstWhere('full_name', $selectedRepo);
        
        if ($repo) {
            $this->newLine();
            $this->comment('📋 Clone command:');
            $this->line("git clone {$repo['clone_url']}");
            $this->newLine();
        }
    }

    private function showRepositoryDetails(array $repos): void
    {
        $repoOptions = [];
        foreach ($repos as $repo) {
            $repoOptions[$repo['full_name']] = "🗂️ {$repo['full_name']} - {$this->addLanguageEmoji($repo['language'] ?? 'Unknown')}";
        }

        $selectedRepo = select('📋 Which repository details?', $repoOptions);
        
        $repo = collect($repos)->firstWhere('full_name', $selectedRepo);
        
        if ($repo) {
            $this->displayRepositoryDetails($repo);
        }
    }

    private function displayRepositoryDetails(array $repo): void
    {
        $this->newLine();
        
        render(<<<HTML
            <div class="py-2 ml-2">
                <div class="px-3 py-2 bg-blue-600 text-white font-bold">
                    📋 {$repo['full_name']}
                </div>
            </div>
        HTML);

        $this->newLine();

        $description = $repo['description'] ?? 'No description available';
        $language = $this->addLanguageEmoji($repo['language'] ?? 'Unknown');
        $stars = number_format($repo['stargazers_count']);
        $forks = number_format($repo['forks_count']);
        $watchers = number_format($repo['watchers_count']);
        $size = number_format($repo['size']);
        $private = $repo['private'] ? '🔒 Private' : '🌍 Public';
        $branch = $repo['default_branch'];
        $created = $this->formatDate($repo['created_at']);
        $updated = $this->formatDate($repo['updated_at']);

        render(<<<HTML
            <div class="ml-2">
                <div class="p-2 bg-gray-900 mb-2">
                    <div class="text-white font-bold mb-1">📝 Description</div>
                    <div class="text-gray-300">{$description}</div>
                </div>
                
                <div class="p-2 bg-gray-900 mb-2">
                    <div class="text-white font-bold mb-1">📊 Statistics</div>
                    <div class="text-yellow-400">⭐ Stars: <span class="text-white">{$stars}</span></div>
                    <div class="text-green-400">🍴 Forks: <span class="text-white">{$forks}</span></div>
                    <div class="text-blue-400">👁️ Watchers: <span class="text-white">{$watchers}</span></div>
                    <div class="text-purple-400">📂 Size: <span class="text-white">{$size} KB</span></div>
                </div>
                
                <div class="p-2 bg-gray-900 mb-2">
                    <div class="text-white font-bold mb-1">🔧 Details</div>
                    <div class="text-blue-400">🗣️ Language: <span class="text-white">{$language}</span></div>
                    <div class="text-gray-400">🔒 Visibility: <span class="text-white">{$private}</span></div>
                    <div class="text-green-400">🚀 Default Branch: <span class="text-white">{$branch}</span></div>
                    <div class="text-yellow-400">📅 Created: <span class="text-white">{$created}</span></div>
                    <div class="text-cyan-400">🔄 Updated: <span class="text-white">{$updated}</span></div>
                </div>
                
                <div class="p-2 bg-gray-900 mb-2">
                    <div class="text-white font-bold mb-1">🔗 Links</div>
                    <div class="text-blue-400 underline">{$repo['html_url']}</div>
                </div>
            </div>
        HTML);

        $this->newLine();
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // Not scheduled
    }
}