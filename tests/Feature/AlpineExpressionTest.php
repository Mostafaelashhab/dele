<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use SplFileInfo;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * Guards a class of bug that only shows up in a real browser.
 *
 * Alpine evaluates an expression with the component's scope in front of the
 * global one, and inside a Livewire component that scope is the `$wire` proxy
 * — which answers `null` for any name that is not a component property rather
 * than falling through to `window`. So a handler calling `confirm(...)` looks
 * completely ordinary, renders fine, passes every render test, and then does
 * nothing at all when a rider taps the button:
 *
 *     Alpine Expression Error: confirm is not a function
 *
 * Nothing on the server can catch that, so it is checked here as text.
 */
class AlpineExpressionTest extends TestCase
{
    /**
     * Browser globals that Alpine's scope chain can shadow. Each of these is
     * either a real `window` member people reach for in a handler, or a name
     * common enough to collide with a component property.
     */
    private const SHADOWED = [
        'confirm', 'alert', 'prompt', 'print',
        'open', 'close', 'focus', 'blur', 'scroll',
    ];

    #[Test]
    public function alpine_handlers_call_browser_globals_through_window(): void
    {
        $offenders = [];

        foreach ($this->bladeFiles() as $file) {
            $contents = file_get_contents($file->getRealPath());

            // Alpine event bindings only: x-on:click="…" and @click="…".
            preg_match_all(
                '/(?:x-on:[a-z.\-]+|@[a-z][a-z.\-]*)="([^"]*)"/i',
                $contents,
                $handlers
            );

            foreach ($handlers[1] as $expression) {
                foreach (self::SHADOWED as $global) {
                    // A call to the bare name, not preceded by a dot or word
                    // character — so `window.confirm(` and `$wire.open(` pass.
                    if (preg_match('/(?<![\w.$])'.$global.'\s*\(/', $expression)) {
                        $offenders[] = sprintf(
                            '%s: %s(…) should be window.%s(…)',
                            str_replace(base_path().'/', '', $file->getRealPath()),
                            $global,
                            $global
                        );
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Alpine resolves a bare name against the component scope before the window:\n  "
                .implode("\n  ", $offenders)
        );
    }

    /**
     * The check has to be able to fail, or it is decoration.
     */
    #[Test]
    public function the_check_recognises_a_shadowed_call(): void
    {
        $bad = 'confirm(\'are you sure?\') && $wire.destroy()';
        $good = 'window.confirm(\'are you sure?\') && $wire.destroy()';

        $pattern = '/(?<![\w.$])confirm\s*\(/';

        $this->assertMatchesRegularExpression($pattern, $bad);
        $this->assertDoesNotMatchRegularExpression($pattern, $good);
    }

    /**
     * @return iterable<SplFileInfo>
     */
    private function bladeFiles(): iterable
    {
        return Finder::create()
            ->files()
            ->in(resource_path('views'))
            ->name('*.blade.php');
    }
}
