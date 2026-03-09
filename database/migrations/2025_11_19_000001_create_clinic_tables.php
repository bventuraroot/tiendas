<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de Pacientes
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_paciente')->unique()->comment('Código único del paciente');
            $table->string('numero_expediente')->unique()->comment('Número de expediente clínico');
            $table->string('primer_nombre', 100);
            $table->string('segundo_nombre', 100)->nullable();
            $table->string('primer_apellido', 100);
            $table->string('segundo_apellido', 100)->nullable();
            $table->string('documento_identidad', 50)->unique();
            $table->enum('tipo_documento', ['DUI', 'NIT', 'Pasaporte', 'Carnet_residente'])->default('DUI');
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('telefono', 20);
            $table->string('telefono_emergencia', 20)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('direccion');
            $table->string('tipo_sangre', 10)->nullable();
            $table->text('alergias')->nullable();
            $table->text('enfermedades_cronicas')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Médicos
        Schema::create('doctors', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_medico')->unique();
            $table->string('numero_jvpm', 50)->unique()->comment('Número de Junta de Vigilancia de la Profesión Médica');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('nombres', 200);
            $table->string('apellidos', 200);
            $table->string('especialidad', 150);
            $table->text('especialidades_secundarias')->nullable();
            $table->string('telefono', 20);
            $table->string('email', 150);
            $table->text('direccion_consultorio')->nullable();
            $table->string('horario_atencion')->nullable();
            $table->enum('estado', ['activo', 'inactivo', 'suspendido'])->default('activo');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Citas Médicas
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_cita')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->dateTime('fecha_hora');
            $table->integer('duracion_minutos')->default(30);
            $table->enum('tipo_cita', ['primera_vez', 'seguimiento', 'emergencia', 'control'])->default('primera_vez');
            $table->text('motivo_consulta')->nullable();
            $table->text('notas')->nullable();
            $table->enum('estado', ['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'])->default('programada');
            $table->timestamp('fecha_confirmacion')->nullable();
            $table->timestamp('fecha_cancelacion')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Consultas Médicas
        Schema::create('medical_consultations', function (Blueprint $table) {
            $table->id();
            $table->string('numero_consulta')->unique();
            $table->foreignId('appointment_id')->nullable()->constrained('appointments')->onDelete('set null');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->dateTime('fecha_hora');
            $table->text('motivo_consulta');
            $table->text('sintomas')->nullable();
            
            // Signos Vitales
            $table->decimal('temperatura', 5, 2)->nullable();
            $table->string('presion_arterial', 20)->nullable();
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->integer('frecuencia_respiratoria')->nullable();
            $table->decimal('peso', 6, 2)->nullable();
            $table->decimal('altura', 5, 2)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->integer('saturacion_oxigeno')->nullable();
            
            // Diagnóstico
            $table->text('exploracion_fisica')->nullable();
            $table->string('diagnostico_cie10', 20)->nullable();
            $table->text('diagnostico_descripcion');
            $table->text('diagnosticos_secundarios')->nullable();
            $table->text('plan_tratamiento')->nullable();
            $table->text('indicaciones')->nullable();
            $table->text('observaciones')->nullable();
            
            // Receta
            $table->boolean('genera_receta')->default(false);
            $table->text('receta_digital')->nullable();
            
            // Seguimiento
            $table->boolean('requiere_seguimiento')->default(false);
            $table->date('fecha_proximo_control')->nullable();
            
            $table->enum('estado', ['en_curso', 'finalizada', 'cancelada'])->default('en_curso');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Recetas Médicas
        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('numero_receta')->unique();
            $table->foreignId('consultation_id')->constrained('medical_consultations')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento')->nullable();
            $table->text('indicaciones_generales')->nullable();
            $table->enum('estado', ['activa', 'dispensada', 'vencida', 'cancelada'])->default('activa');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Detalle de Recetas
        Schema::create('prescription_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('nombre_medicamento');
            $table->string('concentracion')->nullable();
            $table->string('forma_farmaceutica')->nullable();
            $table->decimal('cantidad', 10, 2);
            $table->string('unidad_medida', 50);
            $table->text('posologia')->comment('Indicaciones de cómo tomar el medicamento');
            $table->string('via_administracion')->nullable();
            $table->integer('duracion_tratamiento_dias')->nullable();
            $table->text('notas')->nullable();
            $table->boolean('dispensado')->default(false);
            $table->timestamp('fecha_dispensacion')->nullable();
            $table->foreignId('dispensado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
        });

        // Tabla de Historial Clínico (para almacenar archivos adjuntos)
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('consultation_id')->nullable()->constrained('medical_consultations')->onDelete('set null');
            $table->string('tipo_documento')->comment('Ej: radiografía, análisis, estudio');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('ruta_archivo');
            $table->date('fecha_documento');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
        Schema::dropIfExists('prescription_details');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('medical_consultations');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('patients');
    }
};

