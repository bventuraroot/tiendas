<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabExamProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo_perfil',
        'nombre',
        'descripcion',
        'precio',
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
     * Relación con los exámenes incluidos en el perfil
     */
    public function exams()
    {
        return $this->belongsToMany(LabExam::class, 'lab_profile_exams', 'profile_id', 'exam_id');
    }
}

