<?php

namespace App\Commands;

use App\Services\ComponentManager;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

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

        $this->newLine();
        $this->line('<fg=green>Available Components</>');
        $this->newLine();

        $rows = collect($discovered)->map(function ($repo) {
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
            
            if (!$componentName && !$this->option('non-interactive')) {
                $componentName = text(
                    label: 'Enter the component name to install',
                    placeholder: 'e.g., github'
                );
            }
            
            if (!$componentName) {
                $this->error('Component name is required for installation.');
                return Command::FAILURE;
            }
            
            // Check if component exists in registry
            $registry = $manager->getRegistry();
            if (!isset($registry[$componentName])) {
                $this->error("Component '{$componentName}' not found in registry.");
                $this->info('Available components:');
                foreach (array_keys($registry) as $name) {
                    $this->line("  - {$name}");
                }
                return Command::FAILURE;
            }
            
            // Check if already installed
            if ($manager->isInstalled($componentName)) {
                $this->warn("Component '{$componentName}' is already installed.");
                return Command::SUCCESS;
            }
            
            // Install the component
            $componentInfo = $registry[$componentName];
            $manager->register($componentName, $componentInfo);
            
            $this->info("Successfully installed component '{$componentName}'.");
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
}