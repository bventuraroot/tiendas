<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contingencia;

class UpdateContingenciasEstados extends Command
{
    protected $signature = 'contingencias:update-estados';
    protected $description = 'Actualiza los estados de las contingencias para que los botones de autorización funcionen correctamente';

    public function handle()
    {
        $this->info('Actualizando estados de contingencias...');

        // Actualizar contingencias con estado 'pendiente' a 'En Cola'
        $pendientes = Contingencia::where('estado', 'pendiente')->get();
        $this->info("Encontradas {$pendientes->count()} contingencias con estado 'pendiente'");

        foreach ($pendientes as $contingencia) {
            $contingencia->update([
                'estado' => 'En Cola',
                'selloRecibido' => null
            ]);
            $this->line(" - Actualizada contingencia ID: {$contingencia->id}");
        }

        // Actualizar contingencias con estado 'aprobada' a 'En Cola' si no tienen selloRecibido
        $aprobadas = Contingencia::where('estado', 'aprobada')
            ->whereNull('selloRecibido')
            ->get();
        $this->info("Encontradas {$aprobadas->count()} contingencias con estado 'aprobada' sin sello");

        foreach ($aprobadas as $contingencia) {
            $contingencia->update([
                'estado' => 'En Cola'
            ]);
            $this->line(" - Actualizada contingencia ID: {$contingencia->id}");
        }

        // Mostrar resumen final
        $enCola = Contingencia::where('estado', 'En Cola')->count();
        $enviadas = Contingencia::where('estado', 'Enviado')->count();
        $rechazadas = Contingencia::where('estado', 'Rechazado')->count();

        $this->info('Resumen de estados:');
        $this->line(" - En Cola: {$enCola}");
        $this->line(" - Enviadas: {$enviadas}");
        $this->line(" - Rechazadas: {$rechazadas}");

        $this->info('Actualización completada exitosamente.');
    }
}
