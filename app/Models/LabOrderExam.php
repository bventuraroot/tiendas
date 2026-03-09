<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabOrderExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'exam_id',
        'precio',
        'estado',
    ];

    /**
     * Relación con la orden
     */
    public function order()
    {
        return $this->belongsTo(LabOrder::class, 'order_id');
    }

    /**
     * Relación con el examen
     */
    public function exam()
    {
        return $this->belongsTo(LabExam::class, 'exam_id');
    }

    /**
     * Relación con los resultados
     */
    public function results()
    {
        return $this->hasMany(LabResult::class, 'order_exam_id');
    }
}

