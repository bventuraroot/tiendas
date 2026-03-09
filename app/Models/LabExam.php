<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabExam extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'codigo_examen',
        'nombre',
        'descripcion',
        'preparacion_requerida',
        'tipo_muestra',
        'tiempo_procesamiento_horas',
        'precio',
        'valores_referencia',
        'template_id',
        'valores_referencia_especificos',
        'unidad_medida',
        'requiere_ayuno',
        'prioridad',
        'activo',
        'company_id',
    ];

    protected $casts = [
        'requiere_ayuno' => 'boolean',
        'activo' => 'boolean',
        'valores_referencia_especificos' => 'array',
    ];

    /**
     * Relación con la categoría
     */
    public function category()
    {
        return $this->belongsTo(LabExamCategory::class, 'category_id');
    }

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con perfiles
     */
    public function profiles()
    {
        return $this->belongsToMany(LabExamProfile::class, 'lab_profile_exams', 'exam_id', 'profile_id');
    }

    /**
     * Relación con órdenes de laboratorio
     */
    public function orderExams()
    {
        return $this->hasMany(LabOrderExam::class, 'exam_id');
    }
}

