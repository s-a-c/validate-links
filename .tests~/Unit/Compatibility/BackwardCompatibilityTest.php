<?php

declare(strict_types=1);

use Tests\Support\CompatibilityTestHelper;

describe('Backward Compatibility', function () {
    describe('version compatibility validation', function () {
        it('validates current PHP version compatibility', function () {
            $currentPhpVersion = PHP_VERSION;
            $majorMinor = implode('.', array_slice(explode('.', $currentPhpVersion), 0, 2));
            
            $results = CompatibilityTestHelper::testVersionCompatibility('php', $majorMinor);
            
            expect($results['compatible'])->toBeTrue("PHP {$majorMinor} should be compatible")
                ->and($results['supported'])->toBeTrue("PHP {$majorMinor} should be supported");
        });

        it('validates supported PHP versions', function () {
            $supportedVersions = ['8.4', '8.3', '8.2'];
            
            foreach ($supportedVersions as $version) {
                $results = CompatibilityTestHelper::testVersionCompatibility('php', $version);
                
                expect($results['compatible'])->toBeTrue("PHP {$version} should be compatible")
                    ->and($results['supported'])->toBeTrue("PHP {$version} should be supported");
            }
        });

        it('validates unsupported PHP versions are properly flagged', function () {
            $unsupportedVersions = ['8.1', '8.0', '7.4'];
            
            foreach ($unsupportedVersions as $version) {
                $results = CompatibilityTestHelper::testVersionCompatibility('php', $version);
                
                expect($results['supported'])->toBeFalse("PHP {$version} should not be supported");
            }
        });

        it('validates Laravel framework compatibility', function () {
            $supportedVersions = ['12.0', '11.0'];
            
            foreach ($supportedVersions as $version) {
                $results = CompatibilityTestHelper::testVersionCompatibility('laravel', $version);
                
                expect($results['compatible'])->toBeTrue("Laravel {$version} should be compatible");
            }
        });

        it('validates Pest testing framework compatibility', function () {
            $supportedVersions = ['4.0', '3.0'];
            
            foreach ($supportedVersions as $version) {
                $results = CompatibilityTestHelper::testVersionCompatibility('pest', $version);
                
                expect($results['compatible'])->toBeTrue("Pest {$version} should be compatible");
            }
        });

        it('handles invalid version formats gracefully', function () {
            $invalidVersions = ['invalid', '1.2.3.4.5', '', 'v1.0', '1.x'];
            
            foreach ($invalidVersions as $version) {
                $results = CompatibilityTestHelper::testVersionCompatibility('php', $version);
                
                expect($results['errors'])->not->toBeEmpty("Invalid version {$version} should produce errors");
            }
        });
    });

    describe('API compatibility testing', function () {
        it('validates exception class API compatibility', function () {
            $exceptionClasses = [
                'App\Exceptions\ValidateLinksException',
                'App\Exceptions\ValidationException',
                'App\Exceptions\ConfigurationException',
                'App\Exceptions\SecurityException',
            ];

            foreach ($exceptionClasses as $className) {
                $results = CompatibilityTestHelper::testApiCompatibility($className, '1.0.0', '2.0.0');
                
                expect($results)->toHaveKey('compatible')
                    ->and($results)->toHaveKey('method_changes')
                    ->and($results)->toHaveKey('property_changes')
                    ->and($results)->toHaveKey('class_changes');
            }
        });

        it('validates service class API compatibility', function () {
            $serviceClasses = [
                'App\Services\LinkValidationService',
                'App\Services\ReportingService',
                'App\Services\SecurityValidationService',
            ];

            foreach ($serviceClasses as $className) {
                if (class_exists($className)) {
                    $results = CompatibilityTestHelper::testApiCompatibility($className, '1.0.0', '2.0.0');
                    
                    expect($results)->toHaveKey('compatible')
                        ->and($results)->toHaveKey('method_changes')
                        ->and($results)->toHaveKey('property_changes');
                }
            }
        });

        it('validates command class API compatibility', function () {
            $commandClasses = [
                'App\Commands\ValidateCommand',
                'App\Commands\ConfigCommand',
                'App\Commands\ReportCommand',
                'App\Commands\FixCommand',
            ];

            foreach ($commandClasses as $className) {
                if (class_exists($className)) {
                    $results = CompatibilityTestHelper::testApiCompatibility($className, '1.0.0', '2.0.0');
                    
                    expect($results)->toHaveKey('compatible')
                        ->and($results)->toHaveKey('method_changes');
                }
            }
        });

        it('detects breaking changes in method signatures', function () {
            // This would be a more comprehensive test in a real scenario
            // where we compare actual method signatures between versions
            $className = 'App\Exceptions\ValidationException';
            
            if (class_exists($className)) {
                $results = CompatibilityTestHelper::testApiCompatibility($className, '1.0.0', '2.0.0');
                
                // Validate that the analysis includes method information
                expect($results['method_changes'])->toBeArray();
                
                foreach ($results['method_changes'] as $change) {
                    expect($change)->toHaveKey('type', 'method')
                        ->and($change)->toHaveKey('name')
                        ->and($change)->toHaveKey('compatibility')
                        ->and($change)->toHaveKey('description');
                }
            }
        });
    });

    describe('legacy test pattern preservation', function () {
        it('validates legacy test patterns in existing test files', function () {
            $testFiles = [
                'tests/Unit/Exceptions/ValidationExceptionTest.php',
                'tests/Unit/Commands/ValidateCommandTest.php',
                'tests/Feature/ValidationWorkflowTest.php',
            ];

            foreach ($testFiles as $testFile) {
                if (file_exists($testFile)) {
                    $results = CompatibilityTestHelper::validateLegacyTestPatterns($testFile);
                    
                    expect($results)->toHaveKey('valid')
                        ->and($results)->toHaveKey('patterns_found')
                        ->and($results)->toHaveKey('deprecated_patterns')
                        ->and($results)->toHaveKey('migration_suggestions');
                }
            }
        });

        it('identifies deprecated test patterns', function () {
            // Create a temporary test file with deprecated patterns
            $tempFile = createTestFile('deprecated_test.php', '<?php
                use PHPUnit\Framework\TestCase;
                
                class DeprecatedTest extends TestCase {
                    public function testSomething() {
                        $this->expectException(Exception::class);
                        $mock = $this->createMock(SomeClass::class);
                        $this->assertSame($expected, $actual);
                    }
                }
            ');

            $results = CompatibilityTestHelper::validateLegacyTestPatterns($tempFile);
            
            expect($results['patterns_found'])->not->toBeEmpty()
                ->and($results['deprecated_patterns'])->toBeArray();
            
            // Check for specific deprecated patterns
            $foundPatterns = $results['patterns_found'];
            expect($foundPatterns)->toContain('exception_testing'); // expectException
            expect($foundPatterns)->toContain('mock_creation'); // createMock
            expect($foundPatterns)->toContain('assertion_methods'); // assertSame
        });

        it('provides migration suggestions for deprecated patterns', function () {
            $tempFile = createTestFile('migration_test.php', '<?php
                $this->expectException(ValidationException::class);
                $mock = $this->createMock(LinkValidationService::class);
            ');

            $results = CompatibilityTestHelper::validateLegacyTestPatterns($tempFile);
            
            expect($results['migration_suggestions'])->not->toBeEmpty();
            
            foreach ($results['migration_suggestions'] as $suggestion) {
                expect($suggestion)->toBeString()
                    ->and(strlen($suggestion))->toBeGreaterThan(10);
            }
        });

        it('handles files with no deprecated patterns', function () {
            $tempFile = createTestFile('modern_test.php', '<?php
                describe("Modern Test", function () {
                    it("uses modern syntax", function () {
                        expect(true)->toBeTrue();
                    });
                });
            ');

            $results = CompatibilityTestHelper::validateLegacyTestPatterns($tempFile);
            
            expect($results['valid'])->toBeTrue()
                ->and($results['deprecated_patterns'])->toBeEmpty()
                ->and($results['migration_suggestions'])->toBeEmpty();
        });
    });

    describe('version compatibility matrix validation', function () {
        it('validates compatible version combinations', function () {
            $compatibleCombinations = [
                ['php' => '8.4', 'laravel' => '12.0', 'pest' => '4.0'],
                ['php' => '8.3', 'laravel' => '12.0', 'pest' => '4.0'],
                ['php' => '8.3', 'laravel' => '11.0', 'pest' => '3.0'],
            ];

            foreach ($compatibleCombinations as $combination) {
                $results = CompatibilityTestHelper::validateCompatibilityMatrix($combination);
                
                expect($results['valid'])->toBeTrue("Combination should be compatible: " . json_encode($combination))
                    ->and($results['compatible_combinations'])->not->toBeEmpty()
                    ->and($results['incompatible_combinations'])->toBeEmpty();
            }
        });

        it('detects incompatible version combinations', function () {
            $incompatibleCombinations = [
                ['php' => '8.1', 'laravel' => '12.0'], // PHP too old for Laravel 12
                ['php' => '8.0', 'pest' => '4.0'], // PHP too old for Pest 4
                ['php' => '7.4', 'laravel' => '11.0'], // PHP way too old
            ];

            foreach ($incompatibleCombinations as $combination) {
                $results = CompatibilityTestHelper::validateCompatibilityMatrix($combination);
                
                expect($results['valid'])->toBeFalse("Combination should be incompatible: " . json_encode($combination))
                    ->and($results['incompatible_combinations'])->not->toBeEmpty();
            }
        });

        it('provides recommendations for version upgrades', function () {
            $suboptimalCombinations = [
                ['php' => '8.2', 'laravel' => '11.0', 'pest' => '3.0'], // Not latest versions
            ];

            foreach ($suboptimalCombinations as $combination) {
                $results = CompatibilityTestHelper::validateCompatibilityMatrix($combination);
                
                expect($results['recommendations'])->not->toBeEmpty();
                
                foreach ($results['recommendations'] as $recommendation) {
                    expect($recommendation)->toBeString()
                        ->and($recommendation)->toContain('upgrade');
                }
            }
        });
    });

    describe('deprecation handling validation', function () {
        it('validates deprecation information for known features', function () {
            $deprecatedFeatures = [
                'expectException',
                'createMock',
                'assertSame',
            ];

            foreach ($deprecatedFeatures as $feature) {
                $results = CompatibilityTestHelper::testDeprecationHandling($feature, '3.0.0');
                
                expect($results)->toHaveKey('deprecated')
                    ->and($results)->toHaveKey('deprecation_version')
                    ->and($results)->toHaveKey('removal_version')
                    ->and($results)->toHaveKey('replacement')
                    ->and($results)->toHaveKey('migration_guide');
            }
        });

        it('handles unknown features gracefully', function () {
            $unknownFeature = 'nonexistent_feature';
            $results = CompatibilityTestHelper::testDeprecationHandling($unknownFeature, '1.0.0');
            
            expect($results['deprecated'])->toBeFalse()
                ->and($results['warnings'])->not->toBeEmpty();
        });

        it('validates deprecation timeline consistency', function () {
            $deprecatedFeatures = CompatibilityTestHelper::getLegacyTestPatterns();
            
            foreach ($deprecatedFeatures as $featureName => $featureData) {
                if (isset($featureData['deprecated_since']) && isset($featureData['removed_in'])) {
                    $deprecatedVersion = $featureData['deprecated_since'];
                    $removedVersion = $featureData['removed_in'];
                    
                    // Validate that removal version is greater than deprecation version
                    expect(version_compare($removedVersion, $deprecatedVersion, '>'))
                        ->toBeTrue("Removal version should be greater than deprecation version for {$featureName}");
                }
            }
        });
    });

    describe('cross-component compatibility', function () {
        it('validates PHP and Laravel compatibility', function () {
            $testCases = [
                ['php' => '8.4', 'laravel' => '12.0', 'should_be_compatible' => true],
                ['php' => '8.3', 'laravel' => '12.0', 'should_be_compatible' => true],
                ['php' => '8.2', 'laravel' => '11.0', 'should_be_compatible' => true],
                ['php' => '8.1', 'laravel' => '12.0', 'should_be_compatible' => false],
            ];

            foreach ($testCases as $testCase) {
                $versions = ['php' => $testCase['php'], 'laravel' => $testCase['laravel']];
                $results = CompatibilityTestHelper::validateCompatibilityMatrix($versions);
                
                if ($testCase['should_be_compatible']) {
                    expect($results['valid'])->toBeTrue("PHP {$testCase['php']} should be compatible with Laravel {$testCase['laravel']}");
                } else {
                    expect($results['valid'])->toBeFalse("PHP {$testCase['php']} should not be compatible with Laravel {$testCase['laravel']}");
                }
            }
        });

        it('validates PHP and Pest compatibility', function () {
            $testCases = [
                ['php' => '8.4', 'pest' => '4.0', 'should_be_compatible' => true],
                ['php' => '8.3', 'pest' => '4.0', 'should_be_compatible' => true],
                ['php' => '8.2', 'pest' => '3.0', 'should_be_compatible' => true],
                ['php' => '8.0', 'pest' => '4.0', 'should_be_compatible' => false],
            ];

            foreach ($testCases as $testCase) {
                $versions = ['php' => $testCase['php'], 'pest' => $testCase['pest']];
                $results = CompatibilityTestHelper::validateCompatibilityMatrix($versions);
                
                if ($testCase['should_be_compatible']) {
                    expect($results['valid'])->toBeTrue("PHP {$testCase['php']} should be compatible with Pest {$testCase['pest']}");
                } else {
                    expect($results['valid'])->toBeFalse("PHP {$testCase['php']} should not be compatible with Pest {$testCase['pest']}");
                }
            }
        });
    });

    describe('comprehensive compatibility reporting', function () {
        it('generates comprehensive compatibility report for current versions', function () {
            $currentVersions = [
                'php' => '8.4',
                'laravel' => '12.0',
                'pest' => '4.0',
            ];
            
            $targetVersions = [
                'php' => '8.4',
                'laravel' => '12.0',
                'pest' => '4.0',
            ];

            $report = CompatibilityTestHelper::generateCompatibilityReport($currentVersions, $targetVersions);
            
            expect($report)->toHaveKey('timestamp')
                ->and($report)->toHaveKey('current_versions')
                ->and($report)->toHaveKey('target_versions')
                ->and($report)->toHaveKey('compatibility_status')
                ->and($report)->toHaveKey('migration_required')
                ->and($report)->toHaveKey('recommendations')
                ->and($report['compatibility_status'])->toBe('compatible')
                ->and($report['migration_required'])->toBeFalse();
        });

        it('generates migration report for version upgrades', function () {
            $currentVersions = [
                'php' => '8.3',
                'laravel' => '11.0',
                'pest' => '3.0',
            ];
            
            $targetVersions = [
                'php' => '8.4',
                'laravel' => '12.0',
                'pest' => '4.0',
            ];

            $report = CompatibilityTestHelper::generateCompatibilityReport($currentVersions, $targetVersions);
            
            expect($report['migration_required'])->toBeTrue()
                ->and($report['migration_paths'])->not->toBeEmpty()
                ->and($report['compatibility_status'])->toBe('migration_required')
                ->and($report['recommendations'])->not->toBeEmpty();
        });

        it('identifies breaking changes in compatibility report', function () {
            $currentVersions = [
                'php' => '8.1',
                'laravel' => '10.0',
                'pest' => '2.0',
            ];
            
            $targetVersions = [
                'php' => '8.4',
                'laravel' => '12.0',
                'pest' => '4.0',
            ];

            $report = CompatibilityTestHelper::generateCompatibilityReport($currentVersions, $targetVersions);
            
            expect($report['migration_required'])->toBeTrue()
                ->and($report['errors'])->not->toBeEmpty()
                ->and($report['compatibility_status'])->toBeIn(['migration_required', 'incompatible']);
        });
    });

    describe('supported components validation', function () {
        it('validates all supported components are available', function () {
            $supportedComponents = CompatibilityTestHelper::getSupportedComponents();
            
            expect($supportedComponents)->toContain('php')
                ->and($supportedComponents)->toContain('laravel')
                ->and($supportedComponents)->toContain('pest')
                ->and($supportedComponents)->toBeArray()
                ->and(count($supportedComponents))->toBeGreaterThan(0);
        });

        it('validates compatibility matrix exists for all components', function () {
            $supportedComponents = CompatibilityTestHelper::getSupportedComponents();
            
            foreach ($supportedComponents as $component) {
                $matrix = CompatibilityTestHelper::getCompatibilityMatrix($component);
                
                expect($matrix)->toBeArray()
                    ->and($matrix)->not->toBeEmpty("Compatibility matrix should exist for {$component}");
            }
        });

        it('validates legacy test patterns are documented', function () {
            $legacyPatterns = CompatibilityTestHelper::getLegacyTestPatterns();
            
            expect($legacyPatterns)->toBeArray()
                ->and($legacyPatterns)->not->toBeEmpty();
            
            foreach ($legacyPatterns as $patternName => $patternData) {
                expect($patternData)->toHaveKey('pattern')
                    ->and($patternData)->toHaveKey('replacement')
                    ->and($patternData)->toHaveKey('migration_guide');
            }
        });

        it('validates migration paths are available', function () {
            $migrationPaths = CompatibilityTestHelper::getMigrationPaths();
            
            expect($migrationPaths)->toBeArray()
                ->and($migrationPaths)->not->toBeEmpty();
            
            foreach ($migrationPaths as $pathName => $pathData) {
                expect($pathData)->toHaveKey('steps')
                    ->and($pathData)->toHaveKey('automated_tools')
                    ->and($pathData)->toHaveKey('manual_changes')
                    ->and($pathData)->toHaveKey('rollback_steps');
            }
        });
    });
});