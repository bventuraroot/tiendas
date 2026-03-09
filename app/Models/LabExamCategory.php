<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabExamCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'codigo',
        'descripcion',
        'orden',
        'activo',
        'company_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Relación con los exámenes
     */
    public function exams()
    {
        return $this->hasMany(LabExam::class, 'category_id');
    }
}

