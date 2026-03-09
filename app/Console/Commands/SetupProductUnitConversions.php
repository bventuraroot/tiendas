<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupProductUnitConversions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:setup-unit-conversions {--product-id= : ID específico del producto} {--force : Forzar recreación de conversiones existentes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Configurar conversiones de unidades para productos automáticamente';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        return Command::SUCCESS;
    }
}
