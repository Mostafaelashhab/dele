<?php

namespace Tests\Feature;

use Illuminate\Support\Arr;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Guards the copy.
 *
 * A missing key renders as its own dotted path — "app.nav.orders" printed on
 * screen — and Laravel does not complain, so nothing else in the suite would
 * catch it. This walks every `__()` call in every view and every PHP file and
 * checks the key resolves in both languages.
 *
 * It also enforces that the two locales stay in step: a string added to
 * Arabic and forgotten in English is the usual way a bilingual interface
 * quietly rots.
 */
class TranslationCoverageTest extends TestCase
{
    /**
     * Keys built at runtime from a variable, which cannot be resolved by
     * static inspection and are covered by the page-render tests instead.
     *
     * @var array<int, string>
     */
    private const DYNAMIC_PREFIXES = [
        'validation.',
        'auth.',
        'pagination.',
        'passwords.',
    ];

    #[Test]
    public function every_translation_key_used_in_the_app_exists_in_arabic(): void
    {
        $this->assertKeysResolve('ar');
    }

    #[Test]
    public function every_translation_key_used_in_the_app_exists_in_english(): void
    {
        $this->assertKeysResolve('en');
    }

    /**
     * Enum labels build their key at runtime, so they are resolved case by
     * case here — this is where a status added to an enum without a matching
     * string gets caught.
     */
    #[Test]
    public function every_enum_label_resolves_in_both_languages(): void
    {
        $unresolved = [];

        foreach ($this->labelledEnums() as $enum) {
            foreach ($enum::cases() as $case) {
                foreach (['ar', 'en'] as $locale) {
                    app()->setLocale($locale);

                    $label = $case->label();

                    if (str_contains($label, '.') && ! str_contains($label, ' ')) {
                        $unresolved[] = "[{$locale}] ".class_basename($enum)."::{$case->name} → {$label}";
                    }
                }
            }
        }

        $this->assertSame(
            [],
            $unresolved,
            "Enum labels with no translation:\n  ".implode("\n  ", $unresolved),
        );
    }

    /**
     * @return array<int, class-string>
     */
    private function labelledEnums(): array
    {
        $enums = [];

        foreach (glob(app_path('Enums/*.php')) as $path) {
            $class = 'App\\Enums\\'.basename($path, '.php');

            if (! enum_exists($class) || ! method_exists($class, 'label')) {
                continue;
            }

            $enums[] = $class;
        }

        return $enums;
    }

    #[Test]
    public function the_two_languages_define_exactly_the_same_keys(): void
    {
        $arabic = $this->flattenLocale('ar');
        $english = $this->flattenLocale('en');

        $missingInEnglish = array_diff(array_keys($arabic), array_keys($english));
        $missingInArabic = array_diff(array_keys($english), array_keys($arabic));

        $this->assertSame(
            [],
            array_values($missingInEnglish),
            'These keys exist in Arabic but not in English: '.implode(', ', $missingInEnglish),
        );

        $this->assertSame(
            [],
            array_values($missingInArabic),
            'These keys exist in English but not in Arabic: '.implode(', ', $missingInArabic),
        );
    }

    #[Test]
    public function no_translation_string_is_left_empty(): void
    {
        foreach (['ar', 'en'] as $locale) {
            foreach ($this->flattenLocale($locale) as $key => $value) {
                if (! is_string($value)) {
                    continue;
                }

                $this->assertNotSame(
                    '',
                    trim($value),
                    "[{$locale}] {$key} is an empty string.",
                );
            }
        }
    }

    private function assertKeysResolve(string $locale): void
    {
        app()->setLocale($locale);

        $missing = [];

        foreach ($this->usedKeys() as $key => $files) {
            // A resolved key returns its string; an unresolved one returns the
            // key itself, which is exactly the bug being hunted.
            if (__($key) !== $key) {
                continue;
            }

            $missing[] = $key.'  ('.implode(', ', array_unique($files)).')';
        }

        $this->assertSame(
            [],
            $missing,
            "Unresolved translation keys in [{$locale}]:\n  ".implode("\n  ", $missing),
        );
    }

    /**
     * Every literal key passed to __() or @lang across the application.
     *
     * @return array<string, array<int, string>>
     */
    private function usedKeys(): array
    {
        $roots = [resource_path('views'), app_path()];
        $keys = [];

        foreach ($roots as $root) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root));

            foreach ($files as $file) {
                if (! $file->isFile() || ! in_array($file->getExtension(), ['php'], true)) {
                    continue;
                }

                $contents = file_get_contents($file->getPathname());

                // Only single-quoted literals: anything interpolated is by
                // definition not statically checkable.
                preg_match_all("/__\(\s*'([a-z0-9_]+\.[a-z0-9_.]+)'/i", $contents, $matches);

                foreach ($matches[1] as $key) {
                    // A literal ending in a dot is the fixed half of a key
                    // built at runtime — `__('delivery.status.'.$value)`. Those
                    // are covered by the enum test below, which resolves every
                    // case for real.
                    if (str_ends_with($key, '.') || $this->isDynamic($key)) {
                        continue;
                    }

                    $keys[$key][] = str_replace(base_path().'/', '', $file->getPathname());
                }
            }
        }

        ksort($keys);

        return $keys;
    }

    private function isDynamic(string $key): bool
    {
        foreach (self::DYNAMIC_PREFIXES as $prefix) {
            if (str_starts_with($key, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every string a locale defines, flattened to dotted keys.
     *
     * @return array<string, mixed>
     */
    private function flattenLocale(string $locale): array
    {
        $flattened = [];

        foreach (glob(lang_path($locale.'/*.php')) as $path) {
            $group = basename($path, '.php');

            foreach (Arr::dot(require $path) as $key => $value) {
                $flattened[$group.'.'.$key] = $value;
            }
        }

        return $flattened;
    }
}
