<?php

namespace App\Commands;

use Illuminate\Console\Scheduling\Schedule;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Process\Process;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\select;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Termwind\render;

class InstallGitHubCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'install:github 
                            {--force : Force installation even if already installed}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure GitHub Zero integration for Conduit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->displayWelcome();

        if ($this->isAlreadyInstalled() && !$this->option('force')) {
            info('GitHub Zero is already installed!');
            
            $action = select(
                'What would you like to do?',
                [
                    'test' => '🧪 Test existing installation',
                    'reinstall' => '🔄 Reinstall GitHub Zero',
                    'exit' => '✅ Exit (everything looks good)',
                ],
                'test'
            );

            if ($action === 'exit') {
                return 0;
            } elseif ($action === 'test') {
                return $this->testExistingInstallation();
            }
            // If reinstall, continue with installation
        }

        // Show what will be installed
        note('This installer will:');
        $this->line('  📦 Install jordanpartridge/github-zero package');
        $this->line('  🔧 Configure service provider');
        $this->line('  🔑 Setup GitHub token configuration');
        $this->line('  🧪 Test the installation');
        $this->newLine();

        if (!confirm('Ready to install GitHub Zero?', true)) {
            warning('Installation cancelled');
            return 0;
        }

        // Install package with spinner
        $packageInstalled = spin(
            fn () => $this->installPackage(),
            '📦 Installing GitHub Zero package...'
        );

        if (!$packageInstalled) {
            warning('Failed to install GitHub Zero package');
            return 1;
        }

        info('Package installed successfully!');

        // Configure service provider with spinner
        spin(
            fn () => $this->configureServiceProvider(),
            '🔧 Configuring service provider...'
        );

        info('Service provider configured!');

        // Setup GitHub token
        $this->setupGitHubTokenInteractive();

        // Test installation
        $testPassed = spin(
            fn () => $this->testInstallation(),
            '🧪 Testing installation...'
        );

        if ($testPassed) {
            $this->displaySuccess();
        } else {
            $this->displayFailure();
            return 1;
        }

        return 0;
    }

    private function runFullInstallation(): int
    {
        // Show what will be installed
        note('This installer will:');
        $this->line('  📦 Install jordanpartridge/github-zero package');
        $this->line('  🔧 Configure service provider');
        $this->line('  🔑 Setup GitHub token configuration');
        $this->line('  🧪 Test the installation');
        $this->newLine();

        // Install package with spinner
        $packageInstalled = spin(
            fn () => $this->installPackage(),
            '📦 Installing GitHub Zero package...'
        );

        if (!$packageInstalled) {
            warning('Failed to install GitHub Zero package');
            return 1;
        }

        info('Package installed successfully!');

        // Configure service provider with spinner
        spin(
            fn () => $this->configureServiceProvider(),
            '🔧 Configuring service provider...'
        );

        info('Service provider configured!');

        // Setup GitHub token
        $this->setupGitHubTokenInteractive();

        // Test installation
        $testPassed = spin(
            fn () => $this->testInstallation(),
            '🧪 Testing installation...'
        );

        if ($testPassed) {
            $this->displaySuccess();
        } else {
            $this->displayFailure();
            return 1;
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
                <div class="px-2 py-1 bg-blue-600 text-white font-bold">
                    🐙 GitHub Zero Installer for Conduit
                </div>
                <div class="mt-1 text-gray-300">
                    This will install and configure GitHub Zero integration
                </div>
            </div>
        HTML);
    }

    private function isAlreadyInstalled(): bool
    {
        return file_exists(base_path('vendor/jordanpartridge/github-zero'));
    }

    private function installPackage(): bool
    {
        $process = new Process(['composer', 'require', 'jordanpartridge/github-zero'], base_path());
        $process->setTimeout(300); // 5 minutes
        
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->line($buffer);
            }
        });

        return $process->isSuccessful();
    }

    private function configureServiceProvider(): void
    {
        $configPath = config_path('app.php');
        $config = file_get_contents($configPath);
        
        $serviceProvider = 'JordanPartridge\\GitHubZero\\GitHubZeroServiceProvider::class';
        
        // Check if already added
        if (strpos($config, $serviceProvider) !== false) {
            $this->comment('Service provider already configured');
            return;
        }

        // Add service provider to the providers array
        $pattern = "/'providers'\s*=>\s*\[([^\]]*)\]/s";
        
        if (preg_match($pattern, $config, $matches)) {
            $providersContent = $matches[1];
            
            // Add the service provider with proper formatting
            $newProvidersContent = rtrim($providersContent) . 
                "\n        " . $serviceProvider . ",\n    ";
            
            $newConfig = preg_replace($pattern, "'providers' => [" . $newProvidersContent . "]", $config);
            
            if ($newConfig && $newConfig !== $config) {
                file_put_contents($configPath, $newConfig);
            }
        }
    }

    private function setupGitHubToken(): void
    {
        $envPath = base_path('.env');
        
        if (!file_exists($envPath)) {
            // Create .env file
            $envContent = "# GitHub Configuration\nGITHUB_TOKEN=your_github_personal_access_token_here\n\n";
            $envContent .= "# Optional: GitHub Enterprise\nGITHUB_BASE_URL=https://api.github.com\n\n";
            $envContent .= "# Application Settings\nAPP_NAME=\"Conduit\"\nAPP_ENV=local\nAPP_DEBUG=true\n";
            
            file_put_contents($envPath, $envContent);
            $this->comment('📝 Created .env file with GitHub token placeholder');
        } else {
            $envContent = file_get_contents($envPath);
            if (strpos($envContent, 'GITHUB_TOKEN') === false) {
                $envContent .= "\n# GitHub Configuration\nGITHUB_TOKEN=your_github_personal_access_token_here\n";
                file_put_contents($envPath, $envContent);
                $this->comment('📝 Added GitHub token to existing .env file');
            } else {
                $this->comment('📝 GitHub token already configured in .env');
            }
        }
    }

    private function setupGitHubTokenInteractive(): void
    {
        $this->setupGitHubToken(); // Create .env if needed

        $hasToken = !empty(env('GITHUB_TOKEN')) && env('GITHUB_TOKEN') !== 'your_github_personal_access_token_here';

        if ($hasToken) {
            info('GitHub token is already configured!');
            
            if (confirm('Would you like to update your GitHub token?', false)) {
                $this->promptForToken();
            }
        } else {
            warning('GitHub token is required for GitHub Zero to work');
            
            $action = select(
                'How would you like to set your GitHub token?',
                [
                    'now' => '🔑 Enter token now',
                    'later' => '⏳ Set it manually later in .env file',
                    'help' => '❓ Show me how to create a GitHub token',
                ],
                'now'
            );

            match($action) {
                'now' => $this->promptForToken(),
                'help' => $this->showTokenHelp(),
                'later' => note('You can set GITHUB_TOKEN in your .env file later'),
            };
        }
    }

    private function promptForToken(): void
    {
        $token = text(
            label: 'Enter your GitHub personal access token:',
            placeholder: 'ghp_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            hint: 'Your token needs "repo" permissions'
        );
        
        if ($token) {
            $envPath = base_path('.env');
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/GITHUB_TOKEN=.*/', 'GITHUB_TOKEN=' . $token, $envContent);
            file_put_contents($envPath, $envContent);
            info('GitHub token saved successfully!');
        } else {
            warning('No token provided - you can set it manually in .env later');
        }
    }

    private function showTokenHelp(): void
    {
        note('To create a GitHub Personal Access Token:');
        $this->line('  1. Go to https://github.com/settings/tokens');
        $this->line('  2. Click "Generate new token" → "Generate new token (classic)"');
        $this->line('  3. Give it a name like "Conduit CLI"');
        $this->line('  4. Select "repo" scope for repository access');
        $this->line('  5. Click "Generate token"');
        $this->line('  6. Copy the token and paste it here');
        $this->newLine();
        
        if (confirm('Ready to enter your token?', true)) {
            $this->promptForToken();
        }
    }

    private function testExistingInstallation(): int
    {
        info('Testing existing GitHub Zero installation...');
        
        $tests = [
            'Package exists' => fn() => $this->isAlreadyInstalled(),
            'Commands available' => fn() => $this->testInstallation(),
            'GitHub token configured' => fn() => !empty(env('GITHUB_TOKEN')) && env('GITHUB_TOKEN') !== 'your_github_personal_access_token_here',
        ];

        $allPassed = true;
        foreach ($tests as $testName => $test) {
            $passed = spin($test, "Testing: {$testName}...");
            
            if ($passed) {
                info("✅ {$testName}");
            } else {
                warning("❌ {$testName}");
                $allPassed = false;
            }
        }

        if ($allPassed) {
            info('🎉 All tests passed! GitHub Zero is working correctly.');
            return 0;
        } else {
            warning('Some tests failed. You may need to reinstall or reconfigure.');
            
            if (confirm('Would you like to run the full installation to fix issues?', true)) {
                // Force reinstallation by bypassing the already-installed check
                return $this->runFullInstallation();
            }
            return 1;
        }
    }

    private function testInstallation(): bool
    {
        try {
            // Test if commands are available
            $process = new Process(['php', 'conduit', 'list'], base_path());
            $process->run();
            
            $output = $process->getOutput();
            
            return strpos($output, 'repos') !== false && strpos($output, 'clone') !== false;
        } catch (\Exception $e) {
            return false;
        }
    }

    private function displaySuccess(): void
    {
        render(<<<'HTML'
            <div class="py-2 ml-2">
                <div class="px-3 py-2 bg-green-600 text-white font-bold">
                    ✅ GitHub Zero Installation Complete!
                </div>
                <div class="mt-2 text-gray-300">
                    <div class="mb-1">🎉 Available commands:</div>
                    <div class="ml-4 text-blue-400">• conduit repos --interactive</div>
                    <div class="ml-4 text-blue-400">• conduit clone --interactive</div>
                </div>
                <div class="mt-2 text-yellow-400">
                    💡 Make sure to set your GITHUB_TOKEN in .env file
                </div>
            </div>
        HTML);
    }

    private function displayFailure(): void
    {
        render(<<<'HTML'
            <div class="py-2 ml-2">
                <div class="px-3 py-2 bg-red-600 text-white font-bold">
                    ❌ Installation Failed
                </div>
                <div class="mt-2 text-gray-300">
                    Please check the error messages above and try again.
                </div>
                <div class="mt-2 text-yellow-400">
                    💡 You can use --force to retry the installation
                </div>
            </div>
        HTML);
    }
}
