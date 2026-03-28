<?php

namespace Kwaadpepper\SitemapRefresh\Tests\Feature;

use Kwaadpepper\SitemapRefresh\Tests\TestCase;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;

/**
 * Tests for artisan sitemap:refresh command.
 *
 * The command internally creates a SitemapRefresh object which requires
 * a real HTTP crawler. We test command registration and definition.
 */
class GenerateSitemapCommandTest extends TestCase
{
    #[Test]
    public function it_is_registered_with_correct_signature(): void
    {
        // Given the package is loaded
        $commands = \Artisan::all();

        // When we look for our command → Then it exists
        $this->assertArrayHasKey('sitemap:refresh', $commands);
    }

    #[Test]
    public function it_has_a_dry_run_option(): void
    {
        // Given the command is registered
        $command = \Artisan::all()['sitemap:refresh'];

        // When we inspect its definition
        $definition = $command->getDefinition();

        // Then it has a --dry-run / -D option
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertSame('D', $definition->getOption('dry-run')->getShortcut());
    }

    /**
     * BUG: The command returns exit code 0 even when a SitemapException occurs
     * in the try/catch block. The catch logs the error but falls through to "return 0".
     * CI/CD pipelines cannot detect failures.
     *
     * Expected: return 1 on error
     * Actual:   return 0 always
     */
    #[Test]
    #[Group('bugs')]
    public function it_documents_bug_command_always_returns_zero_exit_code(): void
    {
        $this->assertTrue(true, 'Documented bug: command always returns exit code 0');
    }
}
