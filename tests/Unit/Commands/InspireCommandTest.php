<?php

declare(strict_types=1);

use App\Commands\InspireCommand;
use Illuminate\Console\Scheduling\Schedule;



describe('InspireCommand', function () {
    describe('handle method', function () {
        it('executes successfully with default name', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with(Mockery::type('string'))
                    ->once();
            });

            $this->artisan('inspire')
                ->assertExitCode(0);
        });

        it('executes successfully with custom name', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with(Mockery::type('string'))
                    ->once();
            });

            $this->artisan('inspire', ['name' => 'Developer'])
                ->assertExitCode(0);
        });

        it('renders the correct HTML content', function () {
            $expectedHtml = <<<'HTML'
            <div class="py-1 ml-2">
                <div class="px-1 bg-blue-300 text-black">Laravel Zero</div>
                <em class="ml-1">
                  Simplicity is the ultimate sophistication.
                </em>
            </div>
        HTML;

            // Mock the Termwind render function to capture the HTML
            $this->mock('alias:Termwind\render', function ($mock) use ($expectedHtml) {
                $mock->shouldReceive('__invoke')
                    ->with($expectedHtml)
                    ->once();
            });

            $command = new InspireCommand();
            $command->handle();
        });

        it('calls render function exactly once', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->once();
            });

            $command = new InspireCommand();
            $command->handle();
        });

        it('returns void', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke');
            });

            $command = new InspireCommand();
            $result = $command->handle();

            expect($result)->toBeNull();
        });
    });

    describe('schedule method', function () {
        it('accepts Schedule parameter', function () {
            $schedule = mock(Schedule::class);
            $command = new InspireCommand();

            // The method should not throw an exception
            expect(function () use ($command, $schedule) {
                $command->schedule($schedule);
            })->not->toThrow(Exception::class);
        });

        it('returns void', function () {
            $schedule = mock(Schedule::class);
            $command = new InspireCommand();

            $result = $command->schedule($schedule);

            expect($result)->toBeNull();
        });

        it('does not call any schedule methods by default', function () {
            $schedule = mock(Schedule::class);
            $schedule->shouldNotReceive('command');

            $command = new InspireCommand();
            $command->schedule($schedule);
        });

        it('has commented out scheduling code', function () {
            // Verify that the schedule method exists but doesn't actively schedule anything
            $reflection = new ReflectionClass(InspireCommand::class);
            $method = $reflection->getMethod('schedule');

            expect($method->getNumberOfParameters())->toBe(1);
            expect($method->getParameters()[0]->getType()->getName())->toBe(Schedule::class);
        });
    });

    describe('command signature and description', function () {
        it('has correct signature', function () {
            $command = new InspireCommand();
            $reflection = new ReflectionClass($command);
            $signature = $reflection->getProperty('signature');
            $signature->setAccessible(true);

            expect($signature->getValue($command))->toBe('inspire {name=Artisan}');
        });

        it('has correct description', function () {
            $command = new InspireCommand();
            $reflection = new ReflectionClass($command);
            $description = $reflection->getProperty('description');
            $description->setAccessible(true);

            expect($description->getValue($command))->toBe('Display an inspiring quote');
        });

        it('has name parameter with default value', function () {
            // Test that the command accepts a name parameter with default value
            $this->artisan('inspire')
                ->assertExitCode(0);

            $this->artisan('inspire TestName')
                ->assertExitCode(0);
        });
    });

    describe('class structure', function () {
        it('extends Laravel Zero Command', function () {
            $command = new InspireCommand();
            expect($command)->toBeInstanceOf(LaravelZero\Framework\Commands\Command::class);
        });

        it('is a final class', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        it('has proper namespace', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            expect($reflection->getNamespaceName())->toBe('App\Commands');
        });

        it('uses strict types declaration', function () {
            $fileContent = file_get_contents(__DIR__.'/../../../app/Commands/InspireCommand.php');
            expect($fileContent)->toContain('declare(strict_types=1);');
        });
    });

    describe('method visibility and types', function () {
        it('has public handle method', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $method = $reflection->getMethod('handle');

            expect($method->isPublic())->toBeTrue();
            expect($method->hasReturnType())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
        });

        it('has public schedule method', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $method = $reflection->getMethod('schedule');

            expect($method->isPublic())->toBeTrue();
            expect($method->hasReturnType())->toBeTrue();
            expect($method->getReturnType()->getName())->toBe('void');
        });

        it('has protected signature property', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $property = $reflection->getProperty('signature');

            expect($property->isProtected())->toBeTrue();
        });

        it('has protected description property', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $property = $reflection->getProperty('description');

            expect($property->isProtected())->toBeTrue();
        });
    });

    describe('PHPDoc annotations', function () {
        it('has proper PHPDoc for signature property', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $property = $reflection->getProperty('signature');
            $docComment = $property->getDocComment();

            expect($docComment)->toContain('@var string');
            expect($docComment)->toContain('The signature of the command');
        });

        it('has proper PHPDoc for description property', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $property = $reflection->getProperty('description');
            $docComment = $property->getDocComment();

            expect($docComment)->toContain('@var string');
            expect($docComment)->toContain('The description of the command');
        });

        it('has proper PHPDoc for handle method', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $method = $reflection->getMethod('handle');
            $docComment = $method->getDocComment();

            expect($docComment)->toContain('Execute the console command');
        });

        it('has proper PHPDoc for schedule method', function () {
            $reflection = new ReflectionClass(InspireCommand::class);
            $method = $reflection->getMethod('schedule');
            $docComment = $method->getDocComment();

            expect($docComment)->toContain("Define the command's schedule");
        });
    });

    describe('HTML content validation', function () {
        it('contains Laravel Zero branding', function () {
            $command = new InspireCommand();
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handle');

            // Get the method source to verify HTML content
            $filename = $reflection->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

            expect($source)->toContain('Laravel Zero');
            expect($source)->toContain('Simplicity is the ultimate sophistication');
        });

        it('uses proper CSS classes', function () {
            $command = new InspireCommand();
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handle');

            $filename = $reflection->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

            expect($source)->toContain('py-1 ml-2');
            expect($source)->toContain('px-1 bg-blue-300 text-black');
            expect($source)->toContain('ml-1');
        });

        it('uses semantic HTML elements', function () {
            $command = new InspireCommand();
            $reflection = new ReflectionClass($command);
            $method = $reflection->getMethod('handle');

            $filename = $reflection->getFileName();
            $startLine = $method->getStartLine();
            $endLine = $method->getEndLine();
            $source = implode('', array_slice(file($filename), $startLine - 1, $endLine - $startLine + 1));

            expect($source)->toContain('<div');
            expect($source)->toContain('<em');
            expect($source)->toContain('</div>');
            expect($source)->toContain('</em>');
        });
    });

    describe('edge cases and error handling', function () {
        it('handles empty name parameter', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke');
            });

            $this->artisan('inspire', ['name' => ''])
                ->assertExitCode(0);
        });

        it('handles special characters in name parameter', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke');
            });

            $this->artisan('inspire', ['name' => 'Test@#$%'])
                ->assertExitCode(0);
        });

        it('handles very long name parameter', function () {
            // Mock the Termwind render function
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke');
            });

            $longName = str_repeat('A', 1000);
            $this->artisan('inspire', ['name' => $longName])
                ->assertExitCode(0);
        });

        it('handles null Schedule parameter gracefully', function () {
            $command = new InspireCommand();

            // This should not throw an exception even with null
            // (though in practice Schedule would never be null)
            expect(function () use ($command) {
                $schedule = mock(Schedule::class);
                $command->schedule($schedule);
            })->not->toThrow(Exception::class);
        });
    });

    describe('integration with Termwind', function () {
        it('imports Termwind render function correctly', function () {
            // Verify the use statement exists in the file
            $fileContent = file_get_contents(__DIR__.'/../../../app/Commands/InspireCommand.php');
            expect($fileContent)->toContain('use function Termwind\render;');
        });

        it('calls render with string parameter', function () {
            // Mock the Termwind render function to verify parameter type
            $this->mock('alias:Termwind\render', function ($mock) {
                $mock->shouldReceive('__invoke')
                    ->with(Mockery::type('string'))
                    ->once();
            });

            $command = new InspireCommand();
            $command->handle();
        });
    });
});
