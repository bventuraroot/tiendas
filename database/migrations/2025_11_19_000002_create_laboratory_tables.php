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
        // Tabla de Categorías de Exámenes
        Schema::create('lab_exam_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('codigo', 50)->unique();
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de Exámenes de Laboratorio
        Schema::create('lab_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('lab_exam_categories')->onDelete('cascade');
            $table->string('codigo_examen', 50)->unique();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->text('preparacion_requerida')->nullable()->comment('Indicaciones de preparación para el paciente');
            $table->string('tipo_muestra', 100)->comment('Ej: Sangre, Orina, Heces, etc');
            $table->integer('tiempo_procesamiento_horas')->default(24);
            $table->decimal('precio', 10, 2);
            $table->text('valores_referencia')->nullable();
            $table->boolean('requiere_ayuno')->default(false);
            $table->enum('prioridad', ['normal', 'urgente', 'stat'])->default('normal');
            $table->boolean('activo')->default(true);
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Perfiles de Exámenes (conjunto de exámenes que se piden juntos)
        Schema::create('lab_exam_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_perfil', 50)->unique();
            $table->string('nombre', 200);
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 10, 2);
            $table->boolean('activo')->default(true);
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de Exámenes incluidos en cada Perfil
        Schema::create('lab_profile_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained('lab_exam_profiles')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('lab_exams')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de Órdenes de Laboratorio
        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('numero_orden')->unique();
            $table->foreignId('patient_id')->constrained('patients')->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->onDelete('set null');
            $table->foreignId('consultation_id')->nullable()->constrained('medical_consultations')->onDelete('set null');
            $table->dateTime('fecha_orden');
            $table->dateTime('fecha_toma_muestra')->nullable();
            $table->dateTime('fecha_entrega_estimada')->nullable();
            $table->dateTime('fecha_entrega_real')->nullable();
            $table->text('indicaciones_especiales')->nullable();
            $table->boolean('requiere_ayuno')->default(false);
            $table->text('preparacion_requerida')->nullable();
            $table->enum('prioridad', ['normal', 'urgente', 'stat'])->default('normal');
            $table->enum('estado', ['pendiente', 'muestra_tomada', 'en_proceso', 'completada', 'entregada', 'cancelada'])->default('pendiente');
            $table->decimal('total', 10, 2);
            $table->foreignId('recibido_por')->nullable()->constrained('users')->onDelete('set null')->comment('Usuario que recibió al paciente');
            $table->foreignId('procesado_por')->nullable()->constrained('users')->onDelete('set null')->comment('Usuario técnico de laboratorio');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });

        // Tabla de Exámenes incluidos en cada Orden
        Schema::create('lab_order_exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('lab_orders')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained('lab_exams')->onDelete('cascade');
            $table->decimal('precio', 10, 2);
            $table->enum('estado', ['pendiente', 'en_proceso', 'completado', 'cancelado'])->default('pendiente');
            $table->timestamps();
        });

        // Tabla de Muestras de Laboratorio
        Schema::create('lab_samples', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_muestra')->unique();
            $table->foreignId('order_id')->constrained('lab_orders')->onDelete('cascade');
            $table->string('tipo_muestra', 100);
            $table->dateTime('fecha_toma');
            $table->string('condiciones_muestra')->nullable()->comment('Ej: hemolizada, coagulada, normal');
            $table->text('observaciones')->nullable();
            $table->foreignId('tomada_por')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['recibida', 'en_proceso', 'procesada', 'descartada'])->default('recibida');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de Resultados de Laboratorio
        Schema::create('lab_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_exam_id')->constrained('lab_order_exams')->onDelete('cascade');
            $table->foreignId('sample_id')->nullable()->constrained('lab_samples')->onDelete('set null');
            $table->string('parametro', 200);
            $table->text('resultado');
            $table->string('unidad_medida', 50)->nullable();
            $table->string('valor_referencia', 200)->nullable();
            $table->enum('estado_resultado', ['normal', 'alto', 'bajo', 'critico'])->default('normal');
            $table->text('observaciones')->nullable();
            $table->dateTime('fecha_procesamiento');
            $table->foreignId('procesado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('validado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->dateTime('fecha_validacion')->nullable();
            $table->boolean('resultado_critico')->default(false)->comment('Indica si el resultado requiere atención inmediata');
            $table->timestamps();
        });

        // Tabla de Control de Calidad del Laboratorio
        Schema::create('lab_quality_controls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('lab_exams')->onDelete('cascade');
            $table->date('fecha_control');
            $table->string('lote_reactivo', 100)->nullable();
            $table->date('fecha_vencimiento_reactivo')->nullable();
            $table->string('equipo_utilizado')->nullable();
            $table->text('resultado_control');
            $table->enum('resultado', ['aprobado', 'rechazado', 'pendiente'])->default('pendiente');
            $table->text('observaciones')->nullable();
            $table->foreignId('realizado_por')->constrained('users')->onDelete('cascade');
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->timestamps();
        });

        // Tabla de Equipos de Laboratorio
        Schema::create('lab_equipment', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_equipo', 50)->unique();
            $table->string('nombre', 200);
            $table->string('marca', 100)->nullable();
            $table->string('modelo', 100)->nullable();
            $table->string('numero_serie', 100)->nullable();
            $table->text('descripcion')->nullable();
            $table->date('fecha_adquisicion')->nullable();
            $table->date('ultima_calibracion')->nullable();
            $table->date('proxima_calibracion')->nullable();
            $table->date('ultimo_mantenimiento')->nullable();
            $table->date('proximo_mantenimiento')->nullable();
            $table->enum('estado', ['operativo', 'mantenimiento', 'fuera_servicio', 'calibracion'])->default('operativo');
            $table->text('observaciones')->nullable();
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
        Schema::dropIfExists('lab_equipment');
        Schema::dropIfExists('lab_quality_controls');
        Schema::dropIfExists('lab_results');
        Schema::dropIfExists('lab_samples');
        Schema::dropIfExists('lab_order_exams');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_profile_exams');
        Schema::dropIfExists('lab_exam_profiles');
        Schema::dropIfExists('lab_exams');
        Schema::dropIfExists('lab_exam_categories');
    }
};

