<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ComponentManager
{
    protected string $componentsConfigPath;
    protected string $appConfigPath;

    public function __construct()
    {
        $this->componentsConfigPath = config_path('components.php');
        $this->appConfigPath = config_path('app.php');
    }

    public function isInstalled(string $name): bool
    {
        $installed = config('components.installed', []);
        return isset($installed[$name]) && $installed[$name]['status'] === 'active';
    }

    public function getInstalled(): array
    {
        return config('components.installed', []);
    }

    public function getRegistry(): array
    {
        return config('components.registry', []);
    }

    public function register(string $name, array $componentInfo, string $version = null): void
    {
        $componentInfo['status'] = 'active';
        $componentInfo['installed_at'] = Carbon::now()->toISOString();
        
        if ($version) {
            $componentInfo['version'] = $version;
        }

        $this->updateComponentsConfig($name, $componentInfo);
        $this->registerServiceProviders($componentInfo['service_providers'] ?? []);
    }

    public function unregister(string $name): void
    {
        $installed = config('components.installed', []);
        
        if (!isset($installed[$name])) {
            return;
        }

        $component = $installed[$name];
        $this->unregisterServiceProviders($component['service_providers'] ?? []);
        
        unset($installed[$name]);
        $this->writeComponentsConfig(['installed' => $installed]);
    }

    public function discoverFromGitHub(): array
    {
        $topic = config('components.discovery.github_topic', 'conduit-component');
        
        try {
            $response = Http::get("https://api.github.com/search/repositories", [
                'q' => "topic:{$topic}",
                'sort' => 'updated',
                'order' => 'desc'
            ]);

            if (!$response->successful()) {
                return [];
            }

            return collect($response->json()['items'] ?? [])
                ->map(function ($repo) {
                    return [
                        'name' => $repo['name'],
                        'full_name' => $repo['full_name'],
                        'description' => $repo['description'],
                        'url' => $repo['html_url'],
                        'topics' => $repo['topics'] ?? [],
                        'updated_at' => $repo['updated_at'],
                        'stars' => $repo['stargazers_count'],
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function updateComponentsConfig(string $name, array $componentInfo): void
    {
        $config = config('components', []);
        $config['installed'][$name] = $componentInfo;
        $this->writeComponentsConfig($config);
    }

    protected function writeComponentsConfig(array $config): void
    {
        $content = "<?php\n\nreturn " . $this->arrayToString($config) . ";\n";
        File::put($this->componentsConfigPath, $content);
        
        // Reload the config
        Config::set('components', $config);
    }

    protected function registerServiceProviders(array $providers): void
    {
        $appConfig = include $this->appConfigPath;
        
        foreach ($providers as $provider) {
            if (!in_array($provider, $appConfig['providers'])) {
                $appConfig['providers'][] = $provider;
            }
        }

        $this->writeAppConfig($appConfig);
    }

    protected function unregisterServiceProviders(array $providers): void
    {
        $appConfig = include $this->appConfigPath;
        
        $appConfig['providers'] = array_values(
            array_filter($appConfig['providers'], function ($provider) use ($providers) {
                return !in_array($provider, $providers);
            })
        );

        $this->writeAppConfig($appConfig);
    }

    protected function writeAppConfig(array $config): void
    {
        $content = "<?php\n\nreturn " . $this->arrayToString($config) . ";\n";
        File::put($this->appConfigPath, $content);
    }

    protected function arrayToString(array $array, int $depth = 0): string
    {
        $indent = str_repeat('  ', $depth);
        $result = "array (\n";

        foreach ($array as $key => $value) {
            $result .= $indent . '  ';
            
            if (is_string($key)) {
                $result .= "'{$key}' => ";
            } else {
                $result .= "{$key} => ";
            }

            if (is_array($value)) {
                $result .= $this->arrayToString($value, $depth + 1);
            } elseif (is_string($value)) {
                $escaped = addslashes($value);
                $result .= "'{$escaped}'";
            } elseif (is_bool($value)) {
                $result .= $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $result .= 'null';
            } else {
                $result .= $value;
            }

            $result .= ",\n";
        }

        $result .= $indent . ')';
        return $result;
    }
}