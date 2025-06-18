<?php

namespace App\Commands;

use App\Services\ComponentManager;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

/**
 * Command for managing Conduit components
 * 
 * This command provides a unified interface for discovering, installing, and managing
 * components in the Conduit microkernel architecture. Components are external packages
 * that extend Conduit's functionality through Laravel Zero service providers.
 */
class ComponentsCommand extends Command
{
    protected $signature = 'components 
                            {action? : Action to perform (list, discover, install, uninstall)}
                            {component? : Component name for install/uninstall operations}
                            {--non-interactive : Run in non-interactive mode}';
    protected $description = 'Manage Conduit components';

    public function handle(ComponentManager $manager): int
    {
        try {
            $action = $this->argument('action');
            
            // If no action provided and not in non-interactive mode, show interactive menu
            if (!$action && !$this->option('non-interactive')) {
                $choice = select(
                    label: 'What would you like to do?',
                    options: [
                        'list' => 'List installed components',
                        'discover' => 'Discover available components',
                        'install' => 'Install a component',
                        'uninstall' => 'Uninstall a component',
                    ]
                );
                $action = $choice;
            }
            
            // Default to list if no action specified in non-interactive mode
            if (!$action) {
                $action = 'list';
            }
            
            // Validate action
            if (!in_array($action, ['list', 'discover', 'install', 'uninstall'])) {
                $this->error("Invalid action: {$action}. Valid actions are: list, discover, install, uninstall");
                return Command::FAILURE;
            }

            return match ($action) {
                'list' => $this->listInstalled($manager),
                'discover' => $this->discoverComponents($manager),
                'install' => $this->installComponent($manager),
                'uninstall' => $this->uninstallComponent($manager),
            };
        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function listInstalled(ComponentManager $manager): int
    {
        $installed = $manager->getInstalled();

        if (empty($installed)) {
            $this->info('No components are currently installed.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->line('<fg=green>Installed Components</>');
        $this->newLine();

        $rows = collect($installed)->map(function ($component, $name) {
            return [
                'Name' => $name,
                'Package' => $component['package'] ?? 'N/A',
                'Version' => $component['version'] ?? 'N/A',
                'Status' => $component['status'] ?? 'unknown',
                'Installed' => isset($component['installed_at']) 
                    ? \Carbon\Carbon::parse($component['installed_at'])->diffForHumans()
                    : 'Unknown',
            ];
        })->values()->toArray();

        table(['Name', 'Package', 'Version', 'Status', 'Installed'], $rows);

        return Command::SUCCESS;
    }

    protected function discoverComponents(ComponentManager $manager): int
    {
        $this->info('Discovering available components...');
        
        $discovered = $manager->discoverComponents();
        
        if (empty($discovered)) {
            $this->warn('No components found.');
            return Command::SUCCESS;
        }

        // Filter out already installed components
        $installed = $manager->getInstalled();
        $availableComponents = collect($discovered)
            ->filter(function ($component) use ($installed) {
                return !isset($installed[$component['name']]);
            })
            ->toArray();

        if (empty($availableComponents)) {
            $this->info('All discovered components are already installed.');
            $this->info('Run `conduit components list` to see installed components.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->line('<fg=green>Available Components (Not Installed)</>');
        $this->newLine();

        $rows = collect($availableComponents)->map(function ($repo) {
            return [
                'Name' => $repo['name'],
                'Description' => \Illuminate\Support\Str::limit($repo['description'] ?? 'No description', 50),
                'Stars' => $repo['stars'],
                'Updated' => \Carbon\Carbon::parse($repo['updated_at'])->diffForHumans(),
            ];
        })->toArray();

        table(['Name', 'Description', 'Stars', 'Updated'], $rows);

        return Command::SUCCESS;
    }

    protected function installComponent(ComponentManager $manager): int
    {
        try {
            $componentName = $this->argument('component');
            
            // Discover available components
            $this->info('Discovering available components...');
            $discovered = $manager->discoverComponents();
            
            if (empty($discovered)) {
                $this->warn('No components found for installation.');
                return Command::SUCCESS;
            }
            
            // Filter out already installed components
            $installed = $manager->getInstalled();
            $availableComponents = collect($discovered)
                ->filter(function ($component) use ($installed) {
                    return !isset($installed[$component['name']]);
                })
                ->keyBy('name')
                ->toArray();
            
            if (empty($availableComponents)) {
                $this->info('All discovered components are already installed.');
                return Command::SUCCESS;
            }
            
            // If no component name provided, show selection menu
            if (!$componentName && !$this->option('non-interactive')) {
                $choices = [];
                foreach ($availableComponents as $component) {
                    $description = \Illuminate\Support\Str::limit($component['description'], 60);
                    $choices[$component['name']] = "{$component['name']} - {$description}";
                }
                
                $componentName = select(
                    label: 'Select a component to install:',
                    options: $choices
                );
            }
            
            if (!$componentName) {
                $this->error('Component name is required for installation.');
                return Command::FAILURE;
            }
            
            // Check if component exists in discovered components
            if (!isset($availableComponents[$componentName])) {
                $this->error("Component '{$componentName}' not found or already installed.");
                $this->info('Available components:');
                foreach (array_keys($availableComponents) as $name) {
                    $this->line("  - {$name}");
                }
                return Command::FAILURE;
            }
            
            // Confirm installation
            if (!$this->option('non-interactive')) {
                $component = $availableComponents[$componentName];
                $confirmed = confirm(
                    label: "Install '{$componentName}' from {$component['full_name']}?",
                    default: true
                );
                
                if (!$confirmed) {
                    $this->info('Installation cancelled.');
                    return Command::SUCCESS;
                }
            }
            
            // Install the actual Composer package
            $component = $availableComponents[$componentName];
            $this->info("Installing component '{$componentName}'...");
            $this->line("Package: {$component['full_name']}");
            $this->line("Description: {$component['description']}");
            
            // Step 1: Install via Composer
            $this->info("📦 Installing Composer package...");
            $composerResult = \Symfony\Component\Process\Process::fromShellCommandline(
                "composer require {$component['full_name']} --no-interaction --no-progress"
            );
            $composerResult->setTimeout(300); // 5 minute timeout
            $composerResult->run();
            
            if (!$composerResult->isSuccessful()) {
                $this->error("Failed to install Composer package:");
                $this->line($composerResult->getErrorOutput());
                
                // Provide helpful guidance based on common errors
                $errorOutput = $composerResult->getErrorOutput();
                if (str_contains($errorOutput, 'could not be found')) {
                    $this->warn("💡 The package may not exist or be misspelled.");
                } elseif (str_contains($errorOutput, 'version constraints')) {
                    $this->warn("💡 There may be version compatibility issues.");
                } elseif (str_contains($errorOutput, 'timeout')) {
                    $this->warn("💡 Network timeout - try again with a better connection.");
                }
                
                return Command::FAILURE;
            }
            
            $this->info("✅ Composer package installed successfully");
            
            // Step 2: Detect service providers from installed package
            $this->info("🔧 Detecting service providers...");
            $serviceProviders = $this->detectServiceProviders($component['full_name']);
            
            // Step 3: Get commands from service provider (if possible)
            $commands = $this->detectCommands($serviceProviders);
            
            // Step 4: Convert to component info format
            $componentInfo = [
                'package' => $component['full_name'],
                'description' => $component['description'],
                'commands' => $commands,
                'env_vars' => [], // TODO: Detect from package
                'service_providers' => $serviceProviders,
                'topics' => $component['topics'],
                'url' => $component['url'],
                'stars' => $component['stars'],
            ];
            
            // Step 5: Register the component with service providers
            $manager->register($componentName, $componentInfo);
            
            $this->info("✅ Successfully installed and registered component '{$componentName}'.");
            
            if (!empty($commands)) {
                $this->info("📋 New commands available:");
                foreach ($commands as $command) {
                    $this->line("  - conduit {$command}");
                }
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Installation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    protected function uninstallComponent(ComponentManager $manager): int
    {
        try {
            $componentName = $this->argument('component');
            
            if (!$componentName && !$this->option('non-interactive')) {
                $installed = $manager->getInstalled();
                if (empty($installed)) {
                    $this->info('No components are currently installed.');
                    return Command::SUCCESS;
                }
                
                $componentName = select(
                    label: 'Select a component to uninstall',
                    options: array_combine(array_keys($installed), array_keys($installed))
                );
            }
            
            if (!$componentName) {
                $this->error('Component name is required for uninstallation.');
                return Command::FAILURE;
            }
            
            // Check if component is installed
            if (!$manager->isInstalled($componentName)) {
                $this->error("Component '{$componentName}' is not installed.");
                return Command::FAILURE;
            }
            
            // Confirm uninstallation
            if (!$this->option('non-interactive')) {
                $confirmed = confirm(
                    label: "Are you sure you want to uninstall '{$componentName}'?",
                    default: false
                );
                
                if (!$confirmed) {
                    $this->info('Uninstallation cancelled.');
                    return Command::SUCCESS;
                }
            }
            
            // Uninstall the component
            $manager->unregister($componentName);
            
            $this->info("Successfully uninstalled component '{$componentName}'.");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Uninstallation failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Detect service providers from an installed Composer package
     */
    private function detectServiceProviders(string $packageName): array
    {
        try {
            // Read the package's composer.json
            $vendorPath = base_path("vendor/{$packageName}/composer.json");
            
            if (!file_exists($vendorPath)) {
                return [];
            }
            
            $composerJson = json_decode(file_get_contents($vendorPath), true);
            
            // Check Laravel extra.laravel.providers
            $providers = $composerJson['extra']['laravel']['providers'] ?? [];
            
            return is_array($providers) ? $providers : [];
        } catch (\Exception $e) {
            error_log("Error detecting service providers: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Detect commands provided by service providers
     * 
     * Note: Command detection is complex because Laravel Zero service providers
     * register commands during boot phase. We use a discovery approach that
     * checks for command files in the package structure.
     */
    private function detectCommands(array $serviceProviders): array
    {
        $commands = [];
        
        foreach ($serviceProviders as $provider) {
            try {
                // Extract package name from provider namespace
                $packageCommands = $this->discoverCommandsFromProvider($provider);
                $commands = array_merge($commands, $packageCommands);
                
            } catch (\Exception $e) {
                error_log("Error detecting commands from provider {$provider}: " . $e->getMessage());
            }
        }
        
        return array_unique($commands);
    }

    /**
     * Discover commands from a service provider by examining package structure
     */
    private function discoverCommandsFromProvider(string $provider): array
    {
        // Extract package namespace to find vendor directory
        $parts = explode('\\', $provider);
        if (count($parts) < 2) {
            return [];
        }
        
        $vendor = strtolower($parts[0]);
        $package = strtolower($parts[1]);
        
        // Look for command files in common locations
        $possiblePaths = [
            base_path("vendor/{$vendor}/{$package}/src/Commands"),
            base_path("vendor/{$vendor}/{$package}/app/Commands"),
        ];
        
        $commands = [];
        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $commands = array_merge($commands, $this->extractCommandsFromDirectory($path));
            }
        }
        
        return $commands;
    }

    /**
     * Extract command signatures from PHP command files
     */
    private function extractCommandsFromDirectory(string $directory): array
    {
        $commands = [];
        
        try {
            $files = glob($directory . '/*.php');
            foreach ($files as $file) {
                $content = file_get_contents($file);
                
                // Look for command signatures using regex
                if (preg_match('/protected\s+\$signature\s*=\s*[\'"]([^\'"\s]+)/', $content, $matches)) {
                    $signature = $matches[1];
                    // Extract just the command name (before any arguments/options)
                    $commandName = explode(' ', $signature)[0];
                    if (!empty($commandName)) {
                        $commands[] = $commandName;
                    }
                }
            }
        } catch (\Exception $e) {
            error_log("Error reading command directory {$directory}: " . $e->getMessage());
        }
        
        return $commands;
    }
}