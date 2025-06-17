<?php

namespace App\Commands;

use App\Services\ComponentManager;
use LaravelZero\Framework\Commands\Command;
use function Laravel\Prompts\select;
use function Laravel\Prompts\table;

class ComponentsCommand extends Command
{
    protected $signature = 'components';
    protected $description = 'Manage Conduit components';

    public function handle(ComponentManager $manager): int
    {
        $choice = select(
            label: 'What would you like to do?',
            options: [
                'list' => 'List installed components',
                'discover' => 'Discover available components',
                'install' => 'Install a component',
                'uninstall' => 'Uninstall a component',
            ]
        );

        return match ($choice) {
            'list' => $this->listInstalled($manager),
            'discover' => $this->discoverComponents($manager),
            'install' => $this->installComponent(),
            'uninstall' => $this->uninstallComponent(),
        };
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
        $this->info('Discovering components from GitHub...');
        
        $discovered = $manager->discoverFromGitHub();
        
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

    protected function installComponent(): int
    {
        $this->call('install:github');
        return Command::SUCCESS;
    }

    protected function uninstallComponent(): int
    {
        $this->call('uninstall:github');
        return Command::SUCCESS;
    }
}