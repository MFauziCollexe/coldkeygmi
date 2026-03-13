<?php

return [
    // Path to the .docx letterhead template (kop surat).
    // Recommended: copy the file to storage/app/templates/kop-surat.docx
    'letterhead_docx_path' => env('BA_LETTERHEAD_DOCX_PATH', storage_path('app/templates/kop-surat.docx')),
];

