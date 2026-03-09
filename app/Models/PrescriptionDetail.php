<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'product_id',
        'nombre_medicamento',
        'concentracion',
        'forma_farmaceutica',
        'cantidad',
        'unidad_medida',
        'posologia',
        'via_administracion',
        'duracion_tratamiento_dias',
        'notas',
        'dispensado',
        'fecha_dispensacion',
        'dispensado_por',
    ];

    protected $casts = [
        'dispensado' => 'boolean',
        'fecha_dispensacion' => 'datetime',
    ];

    /**
     * Relación con la receta
     */
    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    /**
     * Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con el usuario que dispensó
     */
    public function dispensedBy()
    {
        return $this->belongsTo(User::class, 'dispensado_por');
    }
}

