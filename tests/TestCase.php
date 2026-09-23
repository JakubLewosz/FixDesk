<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUpTraits(): array
    {
        // Ta kontrola musi poprzedzać RefreshDatabase i migrate:fresh.
        $connection = DB::connection();
        if (app()->configurationIsCached() || ! app()->environment('testing')
            || $connection->getDriverName() !== 'mysql'
            || $connection->getDatabaseName() !== 'fixdesk_test'
            || $connection->selectOne('SELECT DATABASE() AS name')->name !== 'fixdesk_test') {
            throw new RuntimeException('Testy wymagają osobnej bazy MySQL fixdesk_test i niebuforowanej konfiguracji.');
        }

        return parent::setUpTraits();
    }
}
