<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Limits MySQL enforces and SQLite does not.
 *
 * The suite runs on SQLite, which accepts almost any schema you hand it.
 * Production is MySQL, which does not — so a migration can be green in every
 * test and still fail on the first deploy. That happened: an auto-generated
 * unique index name came to 67 characters and MySQL refused it mid-migration,
 * against a live database.
 *
 * These read the migrations as text rather than executing them, so they hold
 * on SQLite too. `phpunit-mysql.xml` runs the real thing against MySQL.
 */
class SchemaPortabilityTest extends TestCase
{
    /**
     * MySQL's hard limit on any identifier.
     */
    private const MAX_IDENTIFIER = 64;

    #[Test]
    public function no_generated_index_name_exceeds_the_mysql_limit(): void
    {
        $offenders = [];

        foreach ($this->schemaBlocks() as [$file, $table, $block]) {
            preg_match_all(
                '/->(unique|index|foreign)\(\s*(\[[^\]]*\]|[\'"][a-z_0-9]+[\'"])\s*(,\s*[\'"][a-z_0-9]+[\'"])?\s*\)/',
                $block,
                $hits,
                PREG_SET_ORDER
            );

            foreach ($hits as $hit) {
                // An explicitly named index is the fix, so it is exempt.
                if (! empty($hit[3])) {
                    continue;
                }

                preg_match_all('/[\'"]([a-z_0-9]+)[\'"]/', $hit[2], $columns);

                $name = strtolower(
                    $table.'_'.implode('_', $columns[1] ?: [trim($hit[2], '\'"[]')]).'_'.$hit[1]
                );

                if (strlen($name) > self::MAX_IDENTIFIER) {
                    $offenders[] = sprintf('%s (%d chars) in %s', $name, strlen($name), $file);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'MySQL refuses identifiers over '.self::MAX_IDENTIFIER.' characters. '
                ."Pass an explicit short name as the second argument:\n  ".implode("\n  ", $offenders)
        );
    }

    /**
     * The check has to be able to fail, or it is decoration.
     */
    #[Test]
    public function the_limit_would_catch_a_long_name(): void
    {
        $generated = 'business_company_preferences_business_id_delivery_company_id_unique';

        $this->assertGreaterThan(self::MAX_IDENTIFIER, strlen($generated));
        $this->assertLessThanOrEqual(self::MAX_IDENTIFIER, strlen('bcp_business_company_unique'));
    }

    /**
     * Every Schema::create/table block, paired with the table it builds.
     *
     * Split per block because one migration routinely creates several tables,
     * and an index name is derived from whichever table it sits in — reading
     * only the first table in a file is how an earlier version of this check
     * reported no problems while one existed.
     *
     * @return iterable<array{string, string, string}>
     */
    private function schemaBlocks(): iterable
    {
        foreach (glob(database_path('migrations/*.php')) as $file) {
            foreach (explode('Schema::', (string) file_get_contents($file)) as $block) {
                if (preg_match('/^(?:create|table)\(\s*[\'"]([a-z_0-9]+)[\'"]/', $block, $match)) {
                    yield [basename($file), $match[1], $block];
                }
            }
        }
    }
}
