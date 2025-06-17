<?php

namespace App\Commands;

use App\Services\ComponentManager;
use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\select;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Termwind\render;

class UninstallGitHubCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uninstall:github 
                            {--keep-token : Keep GitHub token in .env file}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove GitHub Zero integration from Conduit';

    /**
     * Execute the console command.
     */
    public function handle(ComponentManager $manager)
    {
        $this->displayWelcome();

        if (!$manager->isInstalled('github')) {
            info('GitHub Zero is not currently installed');
            return 0;
        }

        // Show what will be removed
        note('This will remove:');
        $this->line('  📦 jordanpartridge/github-zero package');
        $this->line('  🔧 GitHub Zero service provider');
        if (!$this->option('keep-token')) {
            $this->line('  🔑 GitHub token from .env (optional)');
        }
        $this->line('  📋 All GitHub Zero commands (repos, clone)');
        $this->newLine();

        if (!$this->option('force')) {
            $choice = select(
                'What would you like to do?',
                [
                    'uninstall' => '🗑️ Proceed with uninstallation',
                    'cancel' => '❌ Cancel (keep GitHub Zero)',
                ],
                'cancel'
            );

            if ($choice === 'cancel') {
                info('GitHub Zero uninstallation cancelled');
                return 0;
            }
        }

        // Unregister component first
        spin(
            fn () => $manager->unregister('github'),
            '🔧 Unregistering component...'
        );

        info('Component unregistered');

        // Remove package
        $packageRemoved = spin(
            fn () => $this->removePackage(),
            '📦 Removing GitHub Zero package...'
        );

        if (!$packageRemoved) {
            warning('Failed to remove GitHub Zero package');
            return 1;
        }

        info('Package removed successfully');

        // Handle GitHub token
        if (!$this->option('keep-token')) {
            $this->handleTokenRemoval();
        }

        // Test removal
        $removalComplete = spin(
            fn () => $this->testRemoval(),
            '🧪 Verifying removal...'
        );

        if ($removalComplete) {
            $this->displaySuccess();
        } else {
            $this->displayPartialSuccess();
        }

        return 0;
    }

    /**
     * Define the command's schedule.
     */
    public function schedule(Schedule $schedule): void
    {
        // $schedule->command(static::class)->everyMinute();
    }

    private function displayWelcome(): void
    {
        render(<<<'HTML'
            <div class="py-1 ml-2">
                <div class="px-2 py-1 bg-red-600 text-white font-bold">
                    🗑️ GitHub Zero Uninstaller
                </div>
                <div class="mt-1 text-gray-300">
                    Remove GitHub Zero integration from Conduit
                </div>
            </div>
        HTML);
    }



    private function removePackage(): bool
    {
        $process = new Process(['composer', 'remove', 'jordanpartridge/github-zero'], base_path());
        $process->setTimeout(300);
        
        $process->run();
        
        return $process->isSuccessful();
    }

    private function handleTokenRemoval(): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            return;
        }

        $envContent = file_get_contents($envPath);
        
        if (strpos($envContent, 'GITHUB_TOKEN') === false) {
            return;
        }

        $action = select(
            'What should we do with your GitHub token?',
            [
                'keep' => '💾 Keep it in .env file',
                'remove' => '🗑️ Remove it completely',
                'comment' => '💬 Comment it out (keep but disable)',
            ],
            'keep'
        );

        match($action) {
            'remove' => $this->removeTokenFromEnv(),
            'comment' => $this->commentTokenInEnv(),
            'keep' => info('GitHub token kept in .env file'),
        };
    }

    private function removeTokenFromEnv(): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        // Remove GITHUB_TOKEN lines
        $envContent = preg_replace('/^GITHUB_TOKEN=.*$/m', '', $envContent);
        $envContent = preg_replace('/^# GitHub Configuration.*$/m', '', $envContent);
        $envContent = preg_replace('/^GITHUB_BASE_URL=.*$/m', '', $envContent);
        
        // Clean up multiple empty lines
        $envContent = preg_replace('/(\n\s*){3,}/', "\n\n", $envContent);
        
        file_put_contents($envPath, $envContent);
        info('GitHub token removed from .env file');
    }

    private function commentTokenInEnv(): void
    {
        $envPath = base_path('.env');
        $envContent = file_get_contents($envPath);
        
        // Comment out GITHUB_TOKEN lines
        $envContent = preg_replace('/^(GITHUB_TOKEN=.*)$/m', '# $1', $envContent);
        $envContent = preg_replace('/^(GITHUB_BASE_URL=.*)$/m', '# $1', $envContent);
        
        file_put_contents($envPath, $envContent);
        info('GitHub token commented out in .env file');
    }

    private function testRemoval(): bool
    {
        try {
            // Test that package is gone
            if (file_exists(base_path('vendor/jordanpartridge/github-zero'))) {
                return false;
            }

            // Test that commands are no longer available
            $process = new Process(['php', 'conduit', 'list'], base_path());
            $process->run();
            
            $output = $process->getOutput();
            
            // Should not contain github-zero commands anymore
            return strpos($output, 'repos') === false && strpos($output, 'clone') === false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displaySuccess(): void
    {
        render(<<<'HTML'
            <div class="py-2 ml-2">
                <div class="px-3 py-2 bg-green-600 text-white font-bold">
                    ✅ GitHub Zero Uninstalled Successfully
                </div>
                <div class="mt-2 text-gray-300">
                    <div class="mb-1">🎉 GitHub Zero has been completely removed from Conduit</div>
                    <div class="text-blue-400">• All packages removed</div>
                    <div class="text-blue-400">• Service providers cleaned up</div>
                    <div class="text-blue-400">• Commands no longer available</div>
                </div>
                <div class="mt-2 text-yellow-400">
                    💡 You can reinstall anytime with: conduit install:github
                </div>
            </div>
        HTML);
    }

    private function displayPartialSuccess(): void
    {
        render(<<<'HTML'
            <div class="py-2 ml-2">
                <div class="px-3 py-2 bg-yellow-600 text-white font-bold">
                    ⚠️ Partial Uninstallation
                </div>
                <div class="mt-2 text-gray-300">
                    Some components may still be present. You may need to:
                </div>
                <div class="mt-2 text-yellow-400">
                    <div>• Manually check config/app.php for service providers</div>
                    <div>• Run: composer remove jordanpartridge/github-zero</div>
                    <div>• Clear any caches: php conduit config:clear</div>
                </div>
            </div>
        HTML);
    }
}
