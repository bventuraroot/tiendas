<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TutorialController extends Controller
{
    /**
     * Display a listing of tutorials.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $tutorials = [
            [
                'title' => 'Registro de Productos',
                'description' => 'Aprende cómo registrar productos en el sistema, incluyendo marcas, proveedores y laboratorios.',
                'file' => 'TUTORIAL_REGISTRO_PRODUCTOS.md',
                'icon' => 'fa-solid fa-pills',
                'category' => 'Farmacia'
            ],
            // Aquí puedes agregar más tutoriales en el futuro
        ];

        return view('tutorials.index', compact('tutorials'));
    }

    /**
     * Display a specific tutorial.
     *
     * @param  string  $file
     * @return \Illuminate\Http\Response
     */
    public function show($file)
    {
        $filePath = base_path($file);
        
        if (!file_exists($filePath)) {
            abort(404, 'Tutorial no encontrado');
        }

        $content = file_get_contents($filePath);
        
        // Convertir markdown a HTML básico (puedes usar una librería como Parsedown si prefieres)
        $content = $this->markdownToHtml($content);

        return view('tutorials.show', compact('content', 'file'));
    }

    /**
     * Convert markdown to HTML (versión mejorada)
     *
     * @param  string  $markdown
     * @return string
     */
    private function markdownToHtml($markdown)
    {
        $html = $markdown;
        
        // Headers (con múltiples niveles)
        $html = preg_replace('/^# (.*)$/m', '<h1>$1</h1>', $html);
        $html = preg_replace('/^## (.*)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^### (.*)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^#### (.*)$/m', '<h4>$1</h4>', $html);
        
        // Bold
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        
        // Italic
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        
        // Code blocks (multilínea)
        $html = preg_replace('/```([\s\S]*?)```/', '<pre><code>$1</code></pre>', $html);
        
        // Inline code
        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        
        // Ordered lists
        $lines = explode("\n", $html);
        $inList = false;
        $newLines = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^\d+\.\s+(.*)$/', $line, $matches)) {
                if (!$inList) {
                    $newLines[] = '<ol>';
                    $inList = true;
                }
                $newLines[] = '<li>' . $matches[1] . '</li>';
            } else {
                if ($inList) {
                    $newLines[] = '</ol>';
                    $inList = false;
                }
                $newLines[] = $line;
            }
        }
        if ($inList) {
            $newLines[] = '</ol>';
        }
        $html = implode("\n", $newLines);
        
        // Unordered lists
        $lines = explode("\n", $html);
        $inList = false;
        $newLines = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^[-*]\s+(.*)$/', $line, $matches)) {
                if (!$inList) {
                    $newLines[] = '<ul>';
                    $inList = true;
                }
                $newLines[] = '<li>' . $matches[1] . '</li>';
            } else {
                if ($inList) {
                    $newLines[] = '</ul>';
                    $inList = false;
                }
                $newLines[] = $line;
            }
        }
        if ($inList) {
            $newLines[] = '</ul>';
        }
        $html = implode("\n", $newLines);
        
        // Horizontal rules
        $html = preg_replace('/^---$/m', '<hr>', $html);
        $html = preg_replace('/^___$/m', '<hr>', $html);
        
        // Links
        $html = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="$2">$1</a>', $html);
        
        // Line breaks (solo si no está dentro de un tag HTML)
        $html = preg_replace('/(?<!>)\n(?!<)/', '<br>', $html);
        
        // Limpiar múltiples <br> seguidos
        $html = preg_replace('/(<br>){3,}/', '<br><br>', $html);
        
        return $html;
    }
}
