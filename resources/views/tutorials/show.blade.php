@php
    $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Tutorial')

@section('vendor-style')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/default.min.css">
@endsection

@section('vendor-script')
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/marked/9.1.6/marked.min.js"></script>
@endsection

@section('page-style')
<style>
    .tutorial-content {
        max-width: 900px;
        margin: 0 auto;
        line-height: 1.6;
    }
    .tutorial-content h1 {
        font-size: 2.5rem;
        margin-bottom: 1.5rem;
        margin-top: 1rem;
        color: #696cff;
        border-bottom: 2px solid #e7eaf3;
        padding-bottom: 0.5rem;
    }
    .tutorial-content h2 {
        font-size: 2rem;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        color: #566a7f;
        border-bottom: 1px solid #e7eaf3;
        padding-bottom: 0.5rem;
    }
    .tutorial-content h3 {
        font-size: 1.5rem;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
        color: #566a7f;
    }
    .tutorial-content h4 {
        font-size: 1.25rem;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
        color: #566a7f;
    }
    .tutorial-content p {
        margin-bottom: 1rem;
    }
    .tutorial-content code {
        background-color: #f5f5f9;
        padding: 0.2rem 0.4rem;
        border-radius: 0.25rem;
        font-size: 0.875em;
        color: #e83e8c;
        font-family: 'Courier New', monospace;
    }
    .tutorial-content pre {
        background-color: #f5f5f9;
        padding: 1rem;
        border-radius: 0.5rem;
        overflow-x: auto;
        border: 1px solid #e7eaf3;
        margin: 1rem 0;
    }
    .tutorial-content pre code {
        background-color: transparent;
        padding: 0;
        color: #333;
    }
    .tutorial-content ul, .tutorial-content ol {
        margin-left: 1.5rem;
        margin-bottom: 1.5rem;
        padding-left: 1rem;
    }
    .tutorial-content li {
        margin-bottom: 0.5rem;
    }
    .tutorial-content ul li {
        list-style-type: disc;
    }
    .tutorial-content ol li {
        list-style-type: decimal;
    }
    .tutorial-content blockquote {
        border-left: 4px solid #696cff;
        padding-left: 1rem;
        margin-left: 0;
        margin: 1rem 0;
        color: #566a7f;
        font-style: italic;
    }
    .tutorial-content hr {
        margin: 2rem 0;
        border: none;
        border-top: 2px solid #e7eaf3;
    }
    .tutorial-content strong {
        font-weight: 600;
        color: #566a7f;
    }
    .tutorial-content em {
        font-style: italic;
    }
    .tutorial-content a {
        color: #696cff;
        text-decoration: none;
    }
    .tutorial-content a:hover {
        text-decoration: underline;
    }
</style>
@endsection

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold py-3 mb-0">
            <span class="text-muted fw-light">Utilidades / Tutoriales /</span> {{ basename($file, '.md') }}
        </h4>
        <a href="{{ route('tutorials.index') }}" class="btn btn-label-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Volver a Tutoriales
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="tutorial-content">
                {!! $content !!}
            </div>
        </div>
    </div>
</div>

<script>
    // Mejorar el renderizado de markdown si es necesario
    document.addEventListener('DOMContentLoaded', function() {
        // Resaltar código si hay bloques de código
        if (typeof hljs !== 'undefined') {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        }
    });
</script>
@endsection
