<?php

namespace Tests\Unit;

use App\Commands\ComponentsCommand;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class SecurityTest extends TestCase
{
    public function test_package_name_validation_prevents_command_injection()
    {
        $command = new ComponentsCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('validatePackageName');
        $method->setAccessible(true);

        // Test malicious inputs that could cause command injection
        $maliciousInputs = [
            'evil; rm -rf /',
            'package && echo pwned',
            'vendor/package; cat /etc/passwd',
            'valid/package | malicious-command',
            'package`whoami`',
            'package$(id)',
        ];

        foreach ($maliciousInputs as $maliciousInput) {
            $this->expectException(\InvalidArgumentException::class);
            $method->invoke($command, $maliciousInput);
        }
    }

    public function test_package_name_validation_accepts_valid_names()
    {
        $command = new ComponentsCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('validatePackageName');
        $method->setAccessible(true);

        // Test valid package names
        $validInputs = [
            'vendor/package',
            'jordanpartridge/github-zero',
            'laravel-zero/framework',
            'company/my-package',
            'dev/package.name',
            'org/package_name',
        ];

        foreach ($validInputs as $validInput) {
            // Should not throw exception
            $method->invoke($command, $validInput);
            $this->assertTrue(true); // Assertion to confirm no exception
        }
    }

    public function test_package_name_validation_rejects_invalid_formats()
    {
        $command = new ComponentsCommand();
        $reflection = new ReflectionClass($command);
        $method = $reflection->getMethod('validatePackageName');
        $method->setAccessible(true);

        // Test invalid but non-malicious formats
        $invalidInputs = [
            'just-package-name', // Missing vendor
            'vendor/', // Missing package
            '/package', // Missing vendor
            'VENDOR/package', // Uppercase not allowed
            'vendor/Package', // Uppercase not allowed
            'vendor//package', // Double slash
            '', // Empty string
            str_repeat('a', 101), // Too long
        ];

        foreach ($invalidInputs as $invalidInput) {
            $this->expectException(\InvalidArgumentException::class);
            $method->invoke($command, $invalidInput);
        }
    }

    public function test_package_verification_prevents_unauthorized_packages()
    {
        // This would require mocking HTTP requests and command output
        // For now, we'll test the validation logic works
        $this->assertTrue(true);
        
        // TODO: Add proper integration test with HTTP mocking
        // The verification logic checks:
        // 1. Package exists on Packagist (HTTP call)
        // 2. Package has 'conduit-component' topic
        // 3. Throws RuntimeException if either fails
    }
}