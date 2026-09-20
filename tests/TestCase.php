<?php

namespace Tests;

use Database\Seeders\UnidadesMedidaSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Las unidades de medida son un catálogo cerrado que la app da por
     * existente: cada test con RefreshDatabase arranca con ellas cargadas.
     */
    protected $seed = true;

    protected $seeder = UnidadesMedidaSeeder::class;
}
