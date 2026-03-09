<?php

namespace App\Http\Controllers;

use App\Models\LabOrder;
use App\Models\LabOrderExam;
use App\Models\LabResult;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class LabResultController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:lab-results.print')->only(['printPdf', 'printDoc', 'downloadPdf', 'downloadDoc']);
        $this->middleware('permission:lab-results.create')->only(['create', 'store']);
        $this->middleware('permission:lab-results.edit')->only(['edit', 'update']);
    }

    /**
     * Generar PDF del resultado de un examen
     */
    public function printPdf($orderExamId)
    {
        $orderExam = LabOrderExam::with([
            'order.patient',
            'order.doctor',
            'order.company',
            'exam.category',
            'results.validatedBy',
            'results.processedBy'
        ])->findOrFail($orderExamId);

        $company = $orderExam->order->company ?? Company::first();

        // Obtener información del laboratorio desde configuración o company
        $labInfo = [
            'nombre' => config('laboratorio.informacion.nombre', $company->name ?? 'LABORATORIO CLINICO PRO-MEDIC'),
            'direccion' => $company->addres ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)',
            'telefonos' => $company->number_phone ?? '2420-4997 y 6303-3392',
            'horario' => 'Lunes a Sábado de: 7:00 a.m. - 3:00 p.m.',
        ];

        $exam = $orderExam->exam;
        $template = $this->templatePdfForExam($exam);

        try {
            $pdf = Pdf::loadView($template, [
                'orderExam' => $orderExam,
                'order' => $orderExam->order,
                'patient' => $orderExam->order->patient,
                'doctor' => $orderExam->order->doctor,
                'exam' => $exam,
                'results' => $orderExam->results,
                'company' => $company,
                'labInfo' => $labInfo,
                'valoresReferencia' => $exam->valores_referencia_especificos ?? null,
            ]);

            $pdf->setPaper('Letter', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultFont', 'Arial');
            $pdf->setOption('enableCss', true);
            $pdf->setOption('chroot', base_path());
            $pdf->setOption('debugKeepTemp', false);
            $pdf->setOption('enableFontSubsetting', false);
            
            // Forzar regeneración sin caché
            $dompdf = $pdf->getDomPDF();
            if ($dompdf) {
                $dompdf->set_option('enableCss', true);
            }

            $fileName = 'resultado_' . str_replace(' ', '_', $orderExam->exam->nombre) . '_' . date('Y-m-d') . '_' . time() . '.pdf';

            return $pdf->stream($fileName);

        } catch (\Exception $e) {
            \Log::error('Error generando PDF de resultado: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    /**
     * Generar DOC del resultado de un examen
     */
    public function printDoc($orderExamId)
    {
        $orderExam = LabOrderExam::with([
            'order.patient',
            'order.doctor',
            'order.company',
            'exam.category',
            'results.validatedBy',
            'results.processedBy'
        ])->findOrFail($orderExamId);

        $company = $orderExam->order->company ?? Company::first();

        // Obtener información del laboratorio desde configuración o company
        $labInfo = [
            'nombre' => config('laboratorio.informacion.nombre', $company->name ?? 'LABORATORIO CLINICO PRO-MEDIC'),
            'direccion' => $company->addres ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)',
            'telefonos' => $company->number_phone ?? '2420-4997 y 6303-3392',
            'horario' => 'Lunes a Sábado de: 7:00 a.m. - 3:00 p.m.',
        ];

        $exam = $orderExam->exam;
        $template = $this->templatePdfForExam($exam);

        try {
            // Renderizar la vista como HTML
            $html = view($template, [
                'orderExam' => $orderExam,
                'order' => $orderExam->order,
                'patient' => $orderExam->order->patient,
                'doctor' => $orderExam->order->doctor,
                'exam' => $exam,
                'results' => $orderExam->results,
                'company' => $company,
                'labInfo' => $labInfo,
                'valoresReferencia' => $exam->valores_referencia_especificos ?? null,
            ])->render();

            // Convertir HTML a formato Word compatible
            // Agregar encabezado MHTML para compatibilidad con Word
            $mhtml = "MIME-Version: 1.0\n";
            $mhtml .= "Content-Type: multipart/related; boundary=\"----=_NextPart_01\"\n\n";
            $mhtml .= "------=_NextPart_01\n";
            $mhtml .= "Content-Type: text/html; charset=\"utf-8\"\n";
            $mhtml .= "Content-Transfer-Encoding: quoted-printable\n\n";
            $mhtml .= $html . "\n";
            $mhtml .= "------=_NextPart_01--";

            $fileName = 'resultado_' . str_replace(' ', '_', $orderExam->exam->nombre) . '_' . date('Y-m-d') . '_' . time() . '.doc';

            return response($html)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"')
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public');

        } catch (\Exception $e) {
            \Log::error('Error generando DOC de resultado: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el DOC: ' . $e->getMessage());
        }
    }

    /**
     * Descargar DOC del resultado de un examen
     */
    public function downloadDoc($orderExamId)
    {
        $orderExam = LabOrderExam::with([
            'order.patient',
            'order.doctor',
            'order.company',
            'exam.category',
            'results.validatedBy',
            'results.processedBy'
        ])->findOrFail($orderExamId);

        $company = $orderExam->order->company ?? Company::first();

        // Obtener información del laboratorio desde configuración o company
        $labInfo = [
            'nombre' => config('laboratorio.informacion.nombre', $company->name ?? 'LABORATORIO CLINICO PRO-MEDIC'),
            'direccion' => $company->addres ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)',
            'telefonos' => $company->number_phone ?? '2420-4997 y 6303-3392',
            'horario' => 'Lunes a Sábado de: 7:00 a.m. - 3:00 p.m.',
        ];

        $exam = $orderExam->exam;
        $template = $this->templatePdfForExam($exam);

        try {
            // Renderizar la vista como HTML
            $html = view($template, [
                'orderExam' => $orderExam,
                'order' => $orderExam->order,
                'patient' => $orderExam->order->patient,
                'doctor' => $orderExam->order->doctor,
                'exam' => $exam,
                'results' => $orderExam->results,
                'company' => $company,
                'labInfo' => $labInfo,
                'valoresReferencia' => $exam->valores_referencia_especificos ?? null,
            ])->render();

            $fileName = 'resultado_' . str_replace(' ', '_', $orderExam->exam->nombre) . '_' . date('Y-m-d') . '_' . time() . '.doc';

            return response($html)
                ->header('Content-Type', 'application/msword')
                ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"')
                ->header('Content-Transfer-Encoding', 'binary')
                ->header('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
                ->header('Pragma', 'public');

        } catch (\Exception $e) {
            \Log::error('Error descargando DOC de resultado: ' . $e->getMessage());
            return back()->with('error', 'Error al descargar el DOC: ' . $e->getMessage());
        }
    }

    /**
     * Descargar PDF del resultado de un examen
     */
    public function downloadPdf($orderExamId)
    {
        $orderExam = LabOrderExam::with([
            'order.patient',
            'order.doctor',
            'order.company',
            'exam.category',
            'results.validatedBy',
            'results.processedBy'
        ])->findOrFail($orderExamId);

        $company = $orderExam->order->company ?? Company::first();

        $labInfo = [
            'nombre' => config('laboratorio.informacion.nombre', $company->name ?? 'LABORATORIO CLINICO PRO-MEDIC'),
            'direccion' => $company->addres ?? 'Final Avenida El Calvario, Calle principal, Nahuizalco (Frente a parada de buses del chorro Público)',
            'telefonos' => $company->number_phone ?? '2420-4997 y 6303-3392',
            'horario' => 'Lunes a Sábado de: 7:00 a.m. - 3:00 p.m.',
        ];

        $exam = $orderExam->exam;
        $template = $this->templatePdfForExam($exam);

        try {
            $pdf = Pdf::loadView($template, [
                'orderExam' => $orderExam,
                'order' => $orderExam->order,
                'patient' => $orderExam->order->patient,
                'doctor' => $orderExam->order->doctor,
                'exam' => $exam,
                'results' => $orderExam->results,
                'company' => $company,
                'labInfo' => $labInfo,
                'valoresReferencia' => $exam->valores_referencia_especificos ?? null,
            ]);

            $pdf->setPaper('Letter', 'portrait');
            $pdf->setOption('isHtml5ParserEnabled', true);
            $pdf->setOption('isRemoteEnabled', true);
            $pdf->setOption('defaultFont', 'Arial');
            $pdf->setOption('enableCss', true);
            $pdf->setOption('chroot', base_path());

            $fileName = 'resultado_' . str_replace(' ', '_', $orderExam->exam->nombre) . '_' . date('Y-m-d') . '_' . time() . '.pdf';

            return $pdf->download($fileName);

        } catch (\Exception $e) {
            \Log::error('Error descargando PDF de resultado: ' . $e->getMessage());
            return back()->with('error', 'Error al descargar el PDF: ' . $e->getMessage());
        }
    }

    private function templatePdfForExam($exam)
    {
        $tid = $exam->template_id ?? null;
        $name = strtolower(trim($exam->nombre ?? ''));

        // Normalizar espacios múltiples a espacios simples
        $name = preg_replace('/\s+/', ' ', $name);
        if ($tid === 'general_orina' || str_contains($name, 'orina completa') || str_contains($name, 'general de orina')) {
            return 'laboratory.results.general-orina-pdf';
        }
        // Depuración de Creatinina 24 Horas
        if ($tid === 'depuracion_creatinina' || 
            (str_contains($name, 'depuracion') && str_contains($name, 'creatinina'))) {
            return 'laboratory.results.depuracion-creatinina-pdf';
        }
        if ($tid === 'urocultivo' || str_contains($name, 'urocultivo') || str_contains($name, 'cultivo de orina')) {
            return 'laboratory.results.urocultivo-pdf';
        }
        if ($tid === 'coagulacion' || str_contains($name, 'coagulacion')) {
            return 'laboratory.results.coagulacion-pdf';
        }
        // Perfil Bioquímica Clínica / Perfil Químico
        if ($tid === 'perfil_bioquimica_clinica'
            || str_contains($name, 'perfil bioquimica clinica')
            || str_contains($name, 'perfil bioquímica clínica')
            || str_contains($name, 'perfil quimico')
            || str_contains($name, 'perfil químico')) {
            return 'laboratory.results.perfil-bioquimica-clinica-pdf';
        }
        // Pruebas Tiroideas Completo (TSH, T4 Libre, T3 Libre): detectar primero si contiene "libre"
        // También detectar variantes como "Hormona Estimulante de Tiroides (TSH) T3 Y4 Libres"
        if ($tid === 'pruebas_tiroideas_completo' ||
            (str_contains($name, 'tiroideas') && (str_contains($name, 'libre') || str_contains($name, 'ft4') || str_contains($name, 'ft3'))) ||
            ((str_contains($name, 'hormona estimulante de tiroides') || str_contains($name, 'tsh')) &&
             (str_contains($name, 't3') || str_contains($name, 't4') || str_contains($name, 'y4')) &&
             (str_contains($name, 'libre') || str_contains($name, 'libres')))) {
            return 'laboratory.results.pruebas-tiroideas-completo-pdf';
        }
        // Pruebas Tiroideas para Niños: detectar primero si contiene "niños"
        if ($tid === 'tiroideas_ninos' || (str_contains($name, 'tiroideas') && (str_contains($name, 'niños') || str_contains($name, 'ninos')))) {
            return 'laboratory.results.tiroideas-ninos-pdf';
        }
        // Pruebas Tiroideas para Adultos (T3, T4, TSH)
        if ($tid === 'tiroideas_t3_t4_tsh' || str_contains($name, 'tiroideas') ||
            (str_contains($name, 't3') && str_contains($name, 't4') && str_contains($name, 'tsh'))) {
            return 'laboratory.results.tiroideas-t3-t4-tsh-pdf';
        }
        // TPT y TP
        if ($tid === 'tpt_tp' || str_contains($name, 'tpt y tp') || str_contains($name, 'tpt tp')) {
            return 'laboratory.results.tpt-tp-pdf';
        }
        // Glucosa Post-Pandrial 2 Horas (detectar ANTES de otras detecciones de glucosa)
        // Detectar por template_id primero (aceptar ambas variantes)
        if ($tid === 'glucosa_post_pandrial_2h' || $tid === 'glucosa_post_prandial_2h') {
            return 'laboratory.results.glucosa-post-prandial-2h-pdf';
        }
        // Detectar por nombre - normalizar y buscar variantes
        // El nombre puede ser "Glucosa Post Pandrial 2 Horas" (sin guión, con mayúsculas)
        $nameNormalized = preg_replace('/\s+/', ' ', trim($name));
        
        // Detección con regex flexible que busca: glucosa + (post + pandrial) + 2 + horas
        // Acepta: "post pandrial", "post-pandrial", "postpandrial", etc.
        if (preg_match('/glucosa.*post.*pandrial.*2.*hora/i', $nameNormalized)) {
            return 'laboratory.results.glucosa-post-prandial-2h-pdf';
        }
        
        // Detección adicional por partes separadas (más robusta)
        $hasGlucosa = str_contains($nameNormalized, 'glucosa');
        $hasPostPandrial = str_contains($nameNormalized, 'post pandrial') || 
                           str_contains($nameNormalized, 'post-pandrial') ||
                           str_contains($nameNormalized, 'postpandrial') ||
                           (str_contains($nameNormalized, 'post') && str_contains($nameNormalized, 'pandrial'));
        $has2Horas = str_contains($nameNormalized, '2 horas') || 
                     str_contains($nameNormalized, '2h') ||
                     str_contains($nameNormalized, '2 hora');
        
        if ($hasGlucosa && $hasPostPandrial && $has2Horas) {
            return 'laboratory.results.glucosa-post-prandial-2h-pdf';
        }
        // Curva de Tolerancia a la Glucosa
        if ($tid === 'curva_tolerancia_glucosa' || (str_contains($name, 'curva') && str_contains($name, 'tolerancia') && str_contains($name, 'glucosa'))) {
            return 'laboratory.results.curva-tolerancia-glucosa-pdf';
        }
        // TGO y TGP
        if ($tid === 'tgo_tgp' || (str_contains($name, 'tgo') && str_contains($name, 'tgp')) ||
            (str_contains($name, 'transaminasa') && (str_contains($name, 'oxalacetica') || str_contains($name, 'oxalacética')))) {
            return 'laboratory.results.tgo-tgp-pdf';
        }
        // Test de O'Sullivan
        if ($tid === 'test_osullivan' || str_contains($name, 'osullivan') || str_contains($name, 'o\'sullivan')) {
            return 'laboratory.results.test-osullivan-pdf';
        }
        // Electrolitos / Minerales
        if ($tid === 'electrolitos_minerales' || str_contains($name, 'electrolitos') || str_contains($name, 'minerales')) {
            return 'laboratory.results.electrolitos-minerales-pdf';
        }
        // General de Heces / Heces Completo - detectar ANTES de otras detecciones de heces
        if ($tid === 'heces_completo' || 
            (str_contains($name, 'heces') && (str_contains($name, 'completo') || str_contains($name, 'general'))) ||
            str_contains($name, 'heces completo')) {
            return 'laboratory.results.heces-completo-pdf';
        }
        // Colesterol HDL Y LDL
        if ($tid === 'hdl_ldl' || 
            (str_contains($name, 'hdl') && str_contains($name, 'ldl')) || 
            str_contains($name, 'colesterol hdl') || 
            str_contains($name, 'colesterol ldl')) {
            return 'laboratory.results.hdl-ldl-pdf';
        }
        // Toxoplasma gondii IgM IgG
        if ($tid === 'toxoplasma_gondii' || 
            str_contains($name, 'toxoplasma gondii') || 
            (str_contains($name, 'toxoplasma') && (str_contains($name, 'igg') || str_contains($name, 'igm')))) {
            return 'laboratory.results.toxoplasma-gondii-pdf';
        }
        // Tipo Sanguíneo / Tipificación Sanguínea / Tipeo Sanguíneo
        if ($tid === 'tipo_sanguineo' || 
            str_contains($name, 'tipeo sanguineo') || 
            str_contains($name, 'tipeo sanguíneo') ||
            str_contains($name, 'tipo sanguineo') || 
            str_contains($name, 'tipo sanguíneo') ||
            str_contains($name, 'tipificacion sanguinea') ||
            str_contains($name, 'tipificación sanguínea') ||
            str_contains($name, 'tipo de sangre')) {
            return 'laboratory.results.tipo-sanguineo-pdf';
        }
        // Coombs Directo y Indirecto
        if ($tid === 'coombs_directo_indirecto' || 
            ((str_contains($name, 'coombs directo') && str_contains($name, 'indirecto')) ||
             (str_contains($name, 'coombs') && (str_contains($name, 'directo') || str_contains($name, 'indirecto'))))) {
            return 'laboratory.results.coombs-directo-indirecto-pdf';
        }
        // Antígenos Febriles
        if ($tid === 'antigenos_febriles' || 
            str_contains($name, 'antigenos febriles') || 
            str_contains($name, 'antígenos febriles') ||
            str_contains($name, 'antigeno febril') ||
            str_contains($name, 'antígeno febril')) {
            return 'laboratory.results.antigenos-febriles-pdf';
        }
        // Hemograma / Hematología Completa
        if ($tid === 'hemograma' || 
            str_contains($name, 'hemograma') || 
            str_contains($name, 'hematologia completa') ||
            str_contains($name, 'hematología completa')) {
            return 'laboratory.results.hemograma-pdf';
        }
        // Frotis de Sangre Periférica - detectar por "frotis" y cualquier variante
        if ($tid === 'frotis_sangre_periferica' || str_contains($name, 'frotis')) {
            return 'laboratory.results.frotis-sangre-periferica-pdf';
        }
        // Hematocrito y Hemoglobina
        if ($tid === 'hematocrito_hemoglobina' || 
            (str_contains($name, 'hematocrito') && str_contains($name, 'hemoglobina'))) {
            return 'laboratory.results.hematocrito-hemoglobina-pdf';
        }
        // Concentrado de strout
        if ($tid === 'concentrado_strout' || 
            (str_contains($name, 'concentrado') && str_contains($name, 'strout'))) {
            return 'laboratory.results.concentrado-strout-pdf';
        }
        return 'laboratory.results.generic-pdf';
    }

    /**
     * NOTA: getTemplateById y getTemplateForExam ya no se usan. Se mantienen comentados como referencia.
     */
    /*
    private function getTemplateById($templateId)
    {
        $templates = [
            'acido_valproico' => 'laboratory.results.acido-valproico-pdf',
            'albumina' => 'laboratory.results.albumina-pdf',
            'amilasa_lipasa' => 'laboratory.results.amilasa-lipasa-pdf',
            'ana_tamizaje' => 'laboratory.results.ana-tamizaje-pdf',
            'antigenos_febriles' => 'laboratory.results.antigenos-febriles-pdf',
            'antimicrosomales' => 'laboratory.results.antimicrosomales-pdf',
            'antitiroglobulinicos' => 'laboratory.results.antitiroglobulinicos-pdf',
            'aso' => 'laboratory.results.aso-pdf',
            'factor_reumatoide' => 'laboratory.results.factor-reumatoide-pdf',
            'baciloscopia' => 'laboratory.results.baciloscopia-pdf',
            'bilirrubina' => 'laboratory.results.bilirrubina-pdf',
            'ca15_3' => 'laboratory.results.ca15-3-pdf',
            'ca19_9' => 'laboratory.results.ca19-9-pdf',
            'cea' => 'laboratory.results.cea-pdf',
            'ca125' => 'laboratory.results.ca125-pdf',
            'ferritina' => 'laboratory.results.ferritina-pdf',
            'fibrinogeno' => 'laboratory.results.fibrinogeno-pdf',
            'celulas_le' => 'laboratory.results.celulas-le-pdf',
            'citomegalovirus' => 'laboratory.results.citomegalovirus-pdf',
            'hdl_ldl' => 'laboratory.results.hdl-ldl-pdf',
            'deshidrogenasa_ldh' => 'laboratory.results.deshidrogenasa-ldh-pdf',
            'colinesterasa' => 'laboratory.results.colinesterasa-pdf',
            'colesterol_total' => 'laboratory.results.colesterol-total-pdf',
            'coombs' => 'laboratory.results.coombs-pdf',
            'cortisol_am' => 'laboratory.results.cortisol-am-pdf',
            'cortisol_pm' => 'laboratory.results.cortisol-pm-pdf',
            'coprocultivo' => 'laboratory.results.coprocultivo-pdf',
            'curva_tolerancia_glucosa' => 'laboratory.results.curva-tolerancia-glucosa-pdf',
            'depuracion_creatinina' => 'laboratory.results.depuracion-creatinina-pdf',
            'tpt_tp' => 'laboratory.results.tpt-tp-pdf',
            'ggt' => 'laboratory.results.ggt-pdf',
            'trypanosoma_cruzi' => 'laboratory.results.trypanosoma-cruzi-pdf',
            'urocultivo' => 'laboratory.results.urocultivo-pdf',
            'vih' => 'laboratory.results.vih-pdf',
            'vdrl_rpr' => 'laboratory.results.vdrl-rpr-pdf',
            'vdrl_cardiolipina' => 'laboratory.results.vdrl-cardiolipina-pdf',
            'velocidad_eritrosedimentacion' => 'laboratory.results.velocidad-eritrosedimentacion-pdf',
            'deshidrogenasa_ldh' => 'laboratory.results.deshidrogenasa-ldh-pdf',
            'dimero_d' => 'laboratory.results.dimero-d-pdf',
            'electrolitos_minerales' => 'laboratory.results.electrolitos-minerales-pdf',
            'general_orina' => 'laboratory.results.general-orina-pdf',
            'frotis_sangre_periferica' => 'laboratory.results.frotis-sangre-periferica-pdf',
            'glucosa' => 'laboratory.results.glucosa-pdf',
            'helicobacter_pylori' => 'laboratory.results.helicobacter-pylori-pdf',
            'antigenos_helicobacter_pylori' => 'laboratory.results.antigenos-helicobacter-pylori-pdf',
            'anticuerpos_totales_helicobacter_pylori' => 'laboratory.results.anticuerpos-totales-helicobacter-pylori-pdf',
            'hematocrito_hemoglobina' => 'laboratory.results.hematocrito-hemoglobina-pdf',
            'hemoglobina_glicosilada' => 'laboratory.results.hemoglobina-glicosilada-pdf',
            'hemograma' => 'laboratory.results.hemograma-pdf',
            'hepatitis_a_igm' => 'laboratory.results.hepatitis-a-igm-pdf',
            'hormona_crecimiento' => 'laboratory.results.hormona-crecimiento-pdf',
            'hormona_folículo_estimulante' => 'laboratory.results.hormona-folículo-estimulante-pdf',
            'hormona_luteinizante' => 'laboratory.results.hormona-luteinizante-pdf',
            'insulina' => 'laboratory.results.insulina-pdf',
            'prueba_azul_metileno' => 'laboratory.results.prueba-azul-metileno-pdf',
            'proteina_c_reactiva' => 'laboratory.results.proteina-c-reactiva-pdf',
            'perfil_bioquimica_clinica' => 'laboratory.results.perfil-bioquimica-clinica-pdf',
            'bhcg_cualitativa' => 'laboratory.results.bhcg-cualitativa-pdf',
            'prolactina' => 'laboratory.results.prolactina-pdf',
            'proteinas_orina_azar' => 'laboratory.results.proteinas-orina-azar-pdf',
            'psa_total' => 'laboratory.results.psa-total-pdf',
            'sangre_oculta_heces' => 'laboratory.results.sangre-oculta-heces-pdf',
            'pruebas_tiroideas' => 'laboratory.results.pruebas-tiroideas-pdf',
            'tiroideas_t3_t4_tsh' => 'laboratory.results.tiroideas-t3-t4-tsh-pdf',
            'tiroideas_ninos' => 'laboratory.results.tiroideas-ninos-pdf',
            'pruebas_tiroideas_completo' => 'laboratory.results.pruebas-tiroideas-completo-pdf',
            'test_osullivan' => 'laboratory.results.test-osullivan-pdf',
            'testosterona' => 'laboratory.results.testosterona-pdf',
            'tgo_tgp' => 'laboratory.results.tgo-tgp-pdf',
            'tiempo_sangrado' => 'laboratory.results.tiempo-sangrado-pdf',
            'tiroglobulina' => 'laboratory.results.tiroglobulina-pdf',
            'toxoplasma_gondii' => 'laboratory.results.toxoplasma-gondii-pdf',
        ];

        return $templates[$templateId] ?? 'laboratory.results.generic-pdf';
    }

    // Determinar qué plantilla usar según el nombre del examen
    private function getTemplateForExam($examName)
    {
        // Mapeo de exámenes a plantillas
        if (str_contains($examName, 'ácido valpróico') || str_contains($examName, 'acido valproico') || str_contains($examName, 'valpróico') || str_contains($examName, 'valproico')) {
            return 'laboratory.results.acido-valproico-pdf';
        }

        if (str_contains($examName, 'albúmina') || str_contains($examName, 'albumina')) {
            return 'laboratory.results.albumina-pdf';
        }

        if (str_contains($examName, 'amilasa') && str_contains($examName, 'lipasa')) {
            return 'laboratory.results.amilasa-lipasa-pdf';
        }

        if (str_contains($examName, 'antinucleares') || str_contains($examName, 'ana tamizaje') || str_contains($examName, 'ana')) {
            return 'laboratory.results.ana-tamizaje-pdf';
        }

        if (str_contains($examName, 'antigenos febriles') || str_contains($examName, 'antígenos febriles') || str_contains($examName, 'antigeno febril')) {
            return 'laboratory.results.antigenos-febriles-pdf';
        }

        if (str_contains($examName, 'antimicrosomales') || str_contains($examName, 'anti tpo') || str_contains($examName, 'atm')) {
            return 'laboratory.results.antimicrosomales-pdf';
        }

        if (str_contains($examName, 'antitiroglobulinicos') || str_contains($examName, 'antitiroglobulin') || str_contains($examName, 'att')) {
            return 'laboratory.results.antitiroglobulinicos-pdf';
        }

        if (str_contains($examName, 'anti-estreptolisina') || str_contains($examName, 'estreptolisina') || str_contains($examName, 'aso')) {
            return 'laboratory.results.aso-pdf';
        }

        if (str_contains($examName, 'baciloscopía') || str_contains($examName, 'baciloscopia') || str_contains($examName, 'bk esputo') || str_contains($examName, 'baar')) {
            return 'laboratory.results.baciloscopia-pdf';
        }

        if (str_contains($examName, 'bilirrubina')) {
            return 'laboratory.results.bilirrubina-pdf';
        }

        if (str_contains($examName, 'ca 15-3') || str_contains($examName, 'ca15-3') || str_contains($examName, 'carcinoma mamario')) {
            return 'laboratory.results.ca15-3-pdf';
        }

        if (str_contains($examName, 'ca 19-9') || str_contains($examName, 'ca19-9') || str_contains($examName, 'páncreas gastrointestinales') || str_contains($examName, 'pancreas gastrointestinales')) {
            return 'laboratory.results.ca19-9-pdf';
        }

        if (str_contains($examName, 'cea') || str_contains($examName, 'carcinoembriogénico') || str_contains($examName, 'carcinoembriogenico')) {
            return 'laboratory.results.cea-pdf';
        }

        if (str_contains($examName, 'ca 125') || str_contains($examName, 'ca125') || str_contains($examName, 'cáncer de ovario') || str_contains($examName, 'cancer de ovario')) {
            return 'laboratory.results.ca125-pdf';
        }

        if (str_contains($examName, 'ferritina')) {
            return 'laboratory.results.ferritina-pdf';
        }

        if (str_contains($examName, 'fibrinogeno') || str_contains($examName, 'fibrinógeno')) {
            return 'laboratory.results.fibrinogeno-pdf';
        }

        if (str_contains($examName, 'células l.e') || str_contains($examName, 'celulas l.e') || str_contains($examName, 'celulas le') || str_contains($examName, 'células le')) {
            return 'laboratory.results.celulas-le-pdf';
        }

        if (str_contains($examName, 'citomegalovirus') || str_contains($examName, 'cmv') || (str_contains($examName, 'cmv') && str_contains($examName, 'igm'))) {
            return 'laboratory.results.citomegalovirus-pdf';
        }

        if (str_contains($examName, 'hdl y ldl') || str_contains($examName, 'hdl y ldl') || (str_contains($examName, 'hdl') && str_contains($examName, 'ldl')) || str_contains($examName, 'colesterol hdl') || str_contains($examName, 'colesterol ldl')) {
            return 'laboratory.results.hdl-ldl-pdf';
        }

        if (str_contains($examName, 'colinesterasa')) {
            return 'laboratory.results.colinesterasa-pdf';
        }

        if (str_contains($examName, 'deshidrogenasa lactica') || str_contains($examName, 'deshidrogenasa láctica') || str_contains($examName, 'dhl') || str_contains($examName, 'ldh')) {
            return 'laboratory.results.deshidrogenasa-ldh-pdf';
        }

        if (str_contains($examName, 'dimero d') || str_contains($examName, 'dímero d') || str_contains($examName, 'd-dimero') || str_contains($examName, 'd dimer')) {
            return 'laboratory.results.dimero-d-pdf';
        }

        if (str_contains($examName, 'electrolitos') || str_contains($examName, 'minerales')) {
            return 'laboratory.results.electrolitos-minerales-pdf';
        }

        if (str_contains($examName, 'factor reumatoide') || str_contains($examName, 'factor reumatoideo') || str_contains($examName, 'fr ')) {
            return 'laboratory.results.factor-reumatoide-pdf';
        }

        if (str_contains($examName, 'colesterol total') || str_contains($examName, 'colesterol_total')) {
            return 'laboratory.results.colesterol-total-pdf';
        }

        if (str_contains($examName, 'coombs')) {
            return 'laboratory.results.coombs-pdf';
        }

        if (str_contains($examName, 'urocultivo') || (str_contains($examName, 'cultivo') && str_contains($examName, 'orina'))) {
            return 'laboratory.results.urocultivo-pdf';
        }

        if (str_contains($examName, 'coprocultivo')) {
            return 'laboratory.results.coprocultivo-pdf';
        }

        if (str_contains($examName, 'general de orina') || str_contains($examName, 'orina completa')) {
            return 'laboratory.results.general-orina-pdf';
        }

        if (str_contains($examName, 'frotis de sangre periferica') || str_contains($examName, 'frotis sangre periférica') || str_contains($examName, 'frotis periferica')) {
            return 'laboratory.results.frotis-sangre-periferica-pdf';
        }

        if ((str_contains($examName, 'glucosa') && str_contains($examName, 'ayunas')) || (str_contains($examName, 'glucosa') && str_contains($examName, 'post-prandial')) || (str_contains($examName, 'glucosa') && str_contains($examName, 'post prandial'))) {
            return 'laboratory.results.glucosa-pdf';
        }

        if (str_contains($examName, 'toxoplasma gondii') || str_contains($examName, 'toxoplasma') || (str_contains($examName, 'toxoplasma') && (str_contains($examName, 'igg') || str_contains($examName, 'igm')))) {
            return 'laboratory.results.toxoplasma-gondii-pdf';
        }

        if (str_contains($examName, 'tiroglobulina') || str_contains($examName, 'thyroglobulin') || (str_contains($examName, 'tg') && str_contains($examName, 'tiroglobulina'))) {
            return 'laboratory.results.tiroglobulina-pdf';
        }

        if (str_contains($examName, 'tiempo de sangrado') || str_contains($examName, 'tiempo sangrado') || str_contains($examName, 'sangramiento') || str_contains($examName, 'bleeding time')) {
            return 'laboratory.results.tiempo-sangrado-pdf';
        }

        if ((str_contains($examName, 'tgo') && str_contains($examName, 'tgp')) || (str_contains($examName, 'transaminasa glutamico oxalacetica') && str_contains($examName, 'transaminasa glutamico piruvia')) || (str_contains($examName, 'tgo y tgp'))) {
            return 'laboratory.results.tgo-tgp-pdf';
        }

        if (str_contains($examName, 'testosterona') || str_contains($examName, 'testosterone') || (str_contains($examName, 'te') && str_contains($examName, 'hormona'))) {
            return 'laboratory.results.testosterona-pdf';
        }

        if (str_contains($examName, 'test de o\'sullivan') || str_contains($examName, 'test osullivan') || str_contains($examName, 'osullivan') || (str_contains($examName, 'test') && str_contains($examName, 'osullivan'))) {
            return 'laboratory.results.test-osullivan-pdf';
        }

        if ((str_contains($examName, 'tiroideas') && str_contains($examName, 't3') && str_contains($examName, 't4') && str_contains($examName, 'tsh')) || (str_contains($examName, 'pruebas tiroideas') && !str_contains($examName, 'libre'))) {
            return 'laboratory.results.tiroideas-t3-t4-tsh-pdf';
        }

        if (str_contains($examName, 'pruebas tiroideas') || str_contains($examName, 'prueba tiroidea') || ((str_contains($examName, 'tsh') || str_contains($examName, 't4 libre') || str_contains($examName, 't3 libre') || str_contains($examName, 'ft4') || str_contains($examName, 'ft3')) && (str_contains($examName, 'tiroidea') || str_contains($examName, 'tiroides')))) {
            return 'laboratory.results.pruebas-tiroideas-pdf';
        }

        if (str_contains($examName, 'sangre oculta en heces') || str_contains($examName, 'sangre oculta heces') || (str_contains($examName, 'sangre oculta') && str_contains($examName, 'heces')) || str_contains($examName, 'fecal occult blood')) {
            return 'laboratory.results.sangre-oculta-heces-pdf';
        }

        if (str_contains($examName, 'antigeno prostatico especifico total') || str_contains($examName, 'antígeno prostático específico total') || str_contains($examName, 'psa total') || (str_contains($examName, 'psa') && str_contains($examName, 'total')) || str_contains($examName, 'prostate specific antigen')) {
            return 'laboratory.results.psa-total-pdf';
        }

        if (str_contains($examName, 'proteinas en orina al azar') || str_contains($examName, 'proteínas en orina al azar') || (str_contains($examName, 'proteinas') && str_contains($examName, 'orina') && str_contains($examName, 'azar'))) {
            return 'laboratory.results.proteinas-orina-azar-pdf';
        }

        if (str_contains($examName, 'prolactina') || str_contains($examName, 'prl') || str_contains($examName, 'prolactin')) {
            return 'laboratory.results.prolactina-pdf';
        }

        if (str_contains($examName, 'bhcg cualitativa') || str_contains($examName, 'b-hcg cualitativa') || str_contains($examName, 'beta hcg cualitativa') || (str_contains($examName, 'hcg') && str_contains($examName, 'cualitativa'))) {
            return 'laboratory.results.bhcg-cualitativa-pdf';
        }

        if (str_contains($examName, 'perfil bioquimica clinica') || str_contains($examName, 'perfil bioquímica clínica') || (str_contains($examName, 'bioquimica') && str_contains($examName, 'clinica'))) {
            return 'laboratory.results.perfil-bioquimica-clinica-pdf';
        }

        if (str_contains($examName, 'proteína c reactiva') || str_contains($examName, 'proteina c reactiva') || str_contains($examName, 'pcr') || (str_contains($examName, 'c reactive') && str_contains($examName, 'protein'))) {
            return 'laboratory.results.proteina-c-reactiva-pdf';
        }

        // General de Heces / Heces Completo - ya detectado arriba, esta línea es redundante pero se mantiene por compatibilidad
        if ((str_contains($examName, 'general de heces') || str_contains($examName, 'heces completo')) && !str_contains($examName, 'sangre oculta')) {
            return 'laboratory.results.heces-completo-pdf';
        }

        if (str_contains($examName, 'prueba de azul de metileno') || str_contains($examName, 'azul de metileno') || str_contains($examName, 'p.a.m.') || str_contains($examName, 'pam')) {
            return 'laboratory.results.pam-azul-metileno-pdf';
        }

        if (str_contains($examName, 'insulina') || str_contains($examName, 'insulin')) {
            return 'laboratory.results.insulina-pdf';
        }

        // Detectar Cortisol A.M primero (antes de P.M para evitar conflictos)
        if ((str_contains($examName, 'cortisol') && (str_contains($examName, 'am') || str_contains($examName, 'a.m') || str_contains($examName, 'a.m.'))) || (str_contains($examName, 'cortisol am') || str_contains($examName, 'cortisol a.m'))) {
            return 'laboratory.results.cortisol-am-pdf';
        }

        if ((str_contains($examName, 'cortisol') && (str_contains($examName, 'pm') || str_contains($examName, 'p.m') || str_contains($examName, 'p.m.'))) || (str_contains($examName, 'cortisol pm') || str_contains($examName, 'cortisol p.m'))) {
            return 'laboratory.results.cortisol-pm-pdf';
        }

        if (str_contains($examName, 'hormona luteinizante') || str_contains($examName, 'hormona luteinizante') || str_contains($examName, 'lh') || (str_contains($examName, 'luteinizing') && str_contains($examName, 'hormone'))) {
            return 'laboratory.results.hormona-luteinizante-pdf';
        }

        if (str_contains($examName, 'hormona folículo estimulante') || str_contains($examName, 'hormona folículo') || str_contains($examName, 'fsh') || (str_contains($examName, 'follicle stimulating') && str_contains($examName, 'hormone'))) {
            return 'laboratory.results.hormona-folículo-estimulante-pdf';
        }

        if (str_contains($examName, 'hormona del crecimiento') || str_contains($examName, 'hormona crecimiento') || str_contains($examName, 'hgh') || (str_contains($examName, 'growth hormone') && str_contains($examName, 'pre-ejercicio'))) {
            return 'laboratory.results.hormona-crecimiento-pdf';
        }

        if (str_contains($examName, 'hepatitis a') && (str_contains($examName, 'igm') || str_contains($examName, 'ig m'))) {
            return 'laboratory.results.hepatitis-a-igm-pdf';
        }

        if (str_contains($examName, 'hemograma') || str_contains($examName, 'hematologia completa') || str_contains($examName, 'hematología completa')) {
            return 'laboratory.results.hemograma-pdf';
        }

        if ((str_contains($examName, 'hematocrito') && str_contains($examName, 'hemoglobina')) || (str_contains($examName, 'hematocrito y hemoglobina'))) {
            return 'laboratory.results.hematocrito-hemoglobina-pdf';
        }

        if (str_contains($examName, 'hemoglobina glicosilada') || str_contains($examName, 'hemoglobina glicosilada a1c') || str_contains($examName, 'hba1c') || str_contains($examName, 'hb a1c')) {
            return 'laboratory.results.hemoglobina-glicosilada-pdf';
        }

        if ((str_contains($examName, 'anticuerpos totales helicobacter') || str_contains($examName, 'anticuerpos totales helicobacter pylori')) && !str_contains($examName, 'igg') && !str_contains($examName, 'igm')) {
            return 'laboratory.results.anticuerpos-totales-helicobacter-pylori-pdf';
        }

        if ((str_contains($examName, 'antigenos helicobacter') || str_contains($examName, 'antígenos helicobacter')) && (str_contains($examName, 'heces') || str_contains($examName, 'coprologia') || str_contains($examName, 'coprología'))) {
            return 'laboratory.results.antigenos-helicobacter-pylori-pdf';
        }

        if (str_contains($examName, 'helicobacter pylori') || str_contains($examName, 'helicobacter') || (str_contains($examName, 'anti-helicobacter') && str_contains($examName, 'igg')) || (str_contains($examName, 'anti-helicobacter') && str_contains($examName, 'igm'))) {
            return 'laboratory.results.helicobacter-pylori-pdf';
        }

        if (str_contains($examName, 'curva de la tolerancia a la glucosa') || str_contains($examName, 'curva tolerancia glucosa')) {
            return 'laboratory.results.curva-tolerancia-glucosa-pdf';
        }

        if (str_contains($examName, 'tpt y tp') || str_contains($examName, 'tpt tp') || str_contains($examName, 'tromboplastina parcial') || str_contains($examName, 'tiempo de protrombina')) {
            return 'laboratory.results.tpt-tp-pdf';
        }

        if (str_contains($examName, 'gamma glutamil transpeptidasa') || str_contains($examName, 'ggt') || str_contains($examName, 'gamma glutamil') || str_contains($examName, 'transpeptidasa ggt')) {
            return 'laboratory.results.ggt-pdf';
        }

        if (str_contains($examName, 'velocidad de eritrosedimentacion') || str_contains($examName, 'velocidad eritrosedimentacion') || str_contains($examName, 'eritrosedimentacion') || str_contains($examName, 'vsg') || str_contains($examName, 'esr')) {
            return 'laboratory.results.velocidad-eritrosedimentacion-pdf';
        }

        if ((str_contains($examName, 'vdrl') && str_contains($examName, 'cardiolipina')) || (str_contains($examName, 'v.d.r.l') && str_contains($examName, 'cardiolipina'))) {
            return 'laboratory.results.vdrl-cardiolipina-pdf';
        }

        if (str_contains($examName, 'vdrl') || str_contains($examName, 'rpr') || (str_contains($examName, 'vdrl') && str_contains($examName, 'rpr'))) {
            return 'laboratory.results.vdrl-rpr-pdf';
        }

        if (str_contains($examName, 'vih') || str_contains($examName, 'virus inmunodeficiencia humana') || str_contains($examName, 'hiv') || (str_contains($examName, 'virus') && str_contains($examName, 'inmunodeficiencia'))) {
            return 'laboratory.results.vih-pdf';
        }

        if (str_contains($examName, 'trypanosoma cruzi') || str_contains($examName, 'chagas') || str_contains($examName, 'anti-trypanosoma')) {
            return 'laboratory.results.trypanosoma-cruzi-pdf';
        }

        // Por defecto, usar plantilla genérica
        return 'laboratory.results.generic-pdf';
    }
    */

    /**
     * Mostrar formulario para agregar resultados.
     * Solo hay formato genérico (un resultado) o multi-parámetro (varios resultados según catálogo).
     */
    public function create($orderExamId)
    {
        $orderExam = LabOrderExam::with([
            'order.patient',
            'order.doctor',
            'exam',
            'results'
        ])->findOrFail($orderExamId);

        $exam = $orderExam->exam;
        $isMultiParam = lab_exam_es_multi_param($exam);
        $parametros = $isMultiParam ? lab_exam_parametros($exam) : [];

        $result = null;
        $results = $isMultiParam ? $orderExam->results->keyBy('parametro') : collect();
        $editResultId = null;

        return view('laboratory.results.create', compact('orderExam', 'isMultiParam', 'parametros', 'result', 'results', 'editResultId'));
    }

    /**
     * Guardar resultados de un examen (genérico o multi-parámetro).
     */
    public function store(Request $request, $orderExamId)
    {
        $orderExam = LabOrderExam::with('exam')->findOrFail($orderExamId);
        $exam = $orderExam->exam;
        $isMultiParam = lab_exam_es_multi_param($exam);

        if ($isMultiParam) {
            return $this->storeMultiParam($request, $orderExam, $exam);
        }

        $validated = $request->validate([
            'resultado' => 'required',
            'valor_referencia' => 'required|string',
            'unidad_medida' => 'nullable|string|max:50',
            'estado_resultado' => 'required|in:normal,alto,bajo,critico',
            'observaciones' => 'nullable|string',
        ]);

        $unidadMedida = $validated['unidad_medida']
            ?? $exam->unidad_medida
            ?? $exam->valores_referencia_especificos['unidad_medida'] ?? null;

        $valorReferencia = !empty($validated['valor_referencia'])
            ? $validated['valor_referencia']
            : ($exam->valores_referencia
                ?? $exam->valores_referencia_especificos['valores_referencia']['rango']
                ?? $exam->valores_referencia_especificos['rango']
                ?? '');

        $labResult = LabResult::create([
            'order_exam_id' => $orderExamId,
            'parametro' => $exam->nombre,
            'resultado' => is_numeric($validated['resultado']) ? (float)$validated['resultado'] : $validated['resultado'],
            'unidad_medida' => $unidadMedida,
            'valor_referencia' => $valorReferencia,
            'estado_resultado' => $validated['estado_resultado'],
            'resultado_critico' => $validated['estado_resultado'] === 'critico',
            'observaciones' => !empty($validated['observaciones']) ? $validated['observaciones'] : '**DATOS CONTROLADOS**',
            'fecha_procesamiento' => now(),
            'procesado_por' => Auth::id(),
        ]);

        $orderExam->update(['estado' => 'completado']);

        return response()->json([
            'success' => true,
            'message' => 'Resultado registrado exitosamente',
            'result' => $labResult->load('processedBy')
        ]);
    }

    /**
     * Guardar resultados multi-parámetro (varios resultados por examen).
     */
    private function storeMultiParam(Request $request, LabOrderExam $orderExam, $exam)
    {
        $parametros = lab_exam_parametros($exam);
        $rules = ['observaciones' => 'nullable|string'];
        foreach ($parametros as $p) {
            $pk = $p['param_key'];
            $rules["resultado_{$pk}"] = $p['required'] ? 'required' : 'nullable';
            $rules["valor_referencia_{$pk}"] = 'nullable|string';
            $rules["estado_resultado_{$pk}"] = ($p['required'] ? 'required' : 'nullable') . '|in:normal,alto,bajo,critico';
            $rules["unidad_medida_{$pk}"] = 'nullable|string|max:80';
        }
        $validated = $request->validate($rules);

        $observaciones = $validated['observaciones'] ?? '**DATOS CONTROLADOS**';

        foreach ($parametros as $p) {
            $pk = $p['param_key'];
            $res = $validated["resultado_{$pk}"] ?? null;
            if ($res === null && !$p['required']) {
                continue;
            }
            $vr = $validated["valor_referencia_{$pk}"] ?? $p['valor_referencia'] ?? '';
            $est = $validated["estado_resultado_{$pk}"] ?? 'normal';
            $unidad = $validated["unidad_medida_{$pk}"] ?? $p['unidad'] ?? $exam->unidad_medida ?? null;
            if ($unidad === '') {
                $unidad = null;
            }

            LabResult::create([
                'order_exam_id' => $orderExam->id,
                'parametro' => $p['label'],
                'resultado' => is_numeric($res) ? (float)$res : (string)$res,
                'unidad_medida' => $unidad,
                'valor_referencia' => $vr,
                'estado_resultado' => $est,
                'resultado_critico' => $est === 'critico',
                'observaciones' => $observaciones,
                'fecha_procesamiento' => now(),
                'procesado_por' => Auth::id(),
            ]);
        }

        $orderExam->update(['estado' => 'completado']);

        return response()->json([
            'success' => true,
            'message' => 'Resultados registrados exitosamente',
            'result' => null
        ]);
    }

    /**
     * Mostrar formulario para editar un resultado
     */
    public function edit($resultId)
    {
        $result = LabResult::with([
            'orderExam.order.patient',
            'orderExam.order.doctor',
            'orderExam.exam'
        ])->findOrFail($resultId);

        $orderExam = $result->orderExam;
        $exam = $orderExam->exam;

        // Verificar que la orden no esté autorizada
        if (in_array($orderExam->order->estado, ['completada', 'entregada'])) {
            return redirect()->route('lab-orders.show', $orderExam->order_id)
                ->with('error', 'No se puede editar el resultado porque la orden ya está autorizada.');
        }

        $isMultiParam = lab_exam_es_multi_param($exam);
        $parametros = $isMultiParam ? lab_exam_parametros($exam) : [];

        if ($isMultiParam) {
            $results = LabResult::where('order_exam_id', $orderExam->id)->get()->keyBy('parametro');
            $result = null;
            $editResultId = $results->isNotEmpty() ? $results->first()->id : null;
        } else {
            $result = LabResult::where('order_exam_id', $orderExam->id)->first();
            if (!$result) {
                $result = new LabResult();
            }
            $results = collect();
            $editResultId = $result->id ?? null;
        }

        return view('laboratory.results.create', compact('orderExam', 'result', 'isMultiParam', 'parametros', 'results', 'editResultId'));
    }

    /**
     * Actualizar un resultado existente (genérico o multi-parámetro).
     */
    public function update(Request $request, $resultId)
    {
        $result = LabResult::with('orderExam.exam', 'orderExam.order')->findOrFail($resultId);
        $orderExam = $result->orderExam;
        $exam = $orderExam->exam;

        if (in_array($orderExam->order->estado, ['completada', 'entregada'])) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede editar el resultado porque la orden ya está autorizada.'
            ], 403);
        }

        $isMultiParam = lab_exam_es_multi_param($exam);
        if ($isMultiParam) {
            return $this->updateMultiParam($request, $orderExam, $exam);
        }

        $validated = $request->validate([
            'resultado' => 'required',
            'valor_referencia' => 'required|string',
            'unidad_medida' => 'nullable|string|max:50',
            'estado_resultado' => 'required|in:normal,alto,bajo,critico',
            'observaciones' => 'nullable|string',
        ]);

        $unidadMedida = $validated['unidad_medida']
            ?? $exam->unidad_medida
            ?? $exam->valores_referencia_especificos['unidad_medida']
            ?? $result->unidad_medida;

        $valorReferencia = !empty($validated['valor_referencia'])
            ? $validated['valor_referencia']
            : ($exam->valores_referencia
                ?? $exam->valores_referencia_especificos['valores_referencia']['rango']
                ?? $exam->valores_referencia_especificos['rango']
                ?? $result->valor_referencia);

        $result->update([
            'parametro' => $exam->nombre,
            'resultado' => is_numeric($validated['resultado']) ? (float)$validated['resultado'] : $validated['resultado'],
            'unidad_medida' => $unidadMedida,
            'valor_referencia' => $valorReferencia,
            'estado_resultado' => $validated['estado_resultado'],
            'resultado_critico' => $validated['estado_resultado'] === 'critico',
            'observaciones' => !empty($validated['observaciones']) ? $validated['observaciones'] : '**DATOS CONTROLADOS**',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resultado actualizado exitosamente',
            'result' => $result->load('processedBy')
        ]);
    }

    /**
     * Actualizar resultados multi-parámetro.
     */
    private function updateMultiParam(Request $request, LabOrderExam $orderExam, $exam)
    {
        $parametros = lab_exam_parametros($exam);
        $rules = ['observaciones' => 'nullable|string'];
        foreach ($parametros as $p) {
            $pk = $p['param_key'];
            $rules["resultado_{$pk}"] = $p['required'] ? 'required' : 'nullable';
            $rules["valor_referencia_{$pk}"] = 'nullable|string';
            $rules["estado_resultado_{$pk}"] = ($p['required'] ? 'required' : 'nullable') . '|in:normal,alto,bajo,critico';
            $rules["unidad_medida_{$pk}"] = 'nullable|string|max:80';
        }
        $validated = $request->validate($rules);

        $observaciones = $validated['observaciones'] ?? '**DATOS CONTROLADOS**';
        $existing = LabResult::where('order_exam_id', $orderExam->id)->get()->keyBy('parametro');

        foreach ($parametros as $p) {
            $pk = $p['param_key'];
            $res = $validated["resultado_{$pk}"] ?? null;
            if ($res === null && !$p['required']) {
                continue;
            }
            $vr = $validated["valor_referencia_{$pk}"] ?? $p['valor_referencia'] ?? '';
            $est = $validated["estado_resultado_{$pk}"] ?? 'normal';
            $unidad = $validated["unidad_medida_{$pk}"] ?? $p['unidad'] ?? $exam->unidad_medida ?? null;
            if ($unidad === '') {
                $unidad = null;
            }

            $payload = [
                'parametro' => $p['label'],
                'resultado' => is_numeric($res) ? (float)$res : (string)$res,
                'unidad_medida' => $unidad,
                'valor_referencia' => $vr,
                'estado_resultado' => $est,
                'resultado_critico' => $est === 'critico',
                'observaciones' => $observaciones,
            ];

            $r = $existing->get($p['label']) ?? $existing->get($p['label_alt'] ?? '');
            if ($r) {
                $payload['parametro'] = $p['label'];
                $r->update($payload);
            } else {
                LabResult::create(array_merge($payload, [
                    'order_exam_id' => $orderExam->id,
                    'fecha_procesamiento' => now(),
                    'procesado_por' => Auth::id(),
                ]));
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Resultados actualizados exitosamente',
            'result' => null
        ]);
    }

    /**
     * Validar un resultado
     */
    public function validateResult(Request $request, $resultId)
    {
        $result = LabResult::findOrFail($resultId);

        $result->update([
            'validado_por' => Auth::id(),
            'fecha_validacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Resultado validado exitosamente'
        ]);
    }

    /**
     * Agregar resultados a la tabla según el tipo de examen
     */
    private function addExamResultsToTable($table, $examName, $results)
    {
        foreach ($results as $result) {
            $table->addRow();
            $table->addCell(5000)->addText($result->parametro);
            $resultText = $result->resultado;
            if ($result->unidad_medida) {
                $resultText .= ' ' . $result->unidad_medida;
            }
            $table->addCell(3000)->addText($resultText);
            $table->addCell(4000)->addText($result->valor_referencia ?? 'N/A');
        }
    }
}

