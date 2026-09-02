<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safety net against wiping real data.
     *
     * The feature suite uses RefreshDatabase, which DROPS every table. If a
     * run is ever pointed at a real database — a missing override in
     * phpunit.xml, a stray DB_DATABASE in the environment, a copied .env on
     * the server — it destroys development or production data with no warning
     * and no undo. That has already happened once.
     *
     * Configuration alone cannot prevent this, because the mistake IS a
     * configuration mistake. So refuse to run at all unless the target
     * database is obviously disposable. This runs inside refreshApplication(),
     * which the framework calls before setUpTraits() boots RefreshDatabase —
     * that is, before anything can be dropped.
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();

        $this->guardAgainstNonTestDatabase();
    }

    protected function guardAgainstNonTestDatabase(): void
    {
        $connection = (string) config('database.default');
        $database = (string) config("database.connections.{$connection}.database");

        // In-memory SQLite has nothing to lose.
        if ($connection === 'sqlite' && ($database === ':memory:' || $database === '')) {
            return;
        }

        // Anything else must announce itself as disposable: propfirm_test,
        // something_testing, a path under tests/, and so on. The surrounding
        // non-alphanumerics keep innocent names like "latest" from passing.
        if (preg_match('/(^|[^a-z0-9])test(ing|s)?([^a-z0-9]|$)/i', $database)) {
            return;
        }

        throw new RuntimeException(sprintf(
            'Refusing to run tests against database [%s] on connection [%s]. '
            .'The suite uses RefreshDatabase, which DROPS every table, so it must '
            .'only ever point at a disposable database whose name says so (e.g. '
            .'"propfirm_test"). Set <env name="DB_DATABASE" value="propfirm_test"/> '
            .'in phpunit.xml, and check that no DB_DATABASE in your shell '
            .'environment is overriding it.',
            $database === '' ? '(empty)' : $database,
            $connection
        ));
    }
}
