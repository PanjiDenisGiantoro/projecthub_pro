<?php

// Hanya override pesan aturan password; pesan validasi lain tetap bawaan Laravel.
return [
    'password' => [
        'letters'       => ':Attribute harus mengandung huruf.',
        'mixed'         => ':Attribute harus mengandung huruf besar dan huruf kecil.',
        'numbers'       => ':Attribute harus mengandung angka.',
        'symbols'       => ':Attribute harus mengandung simbol (mis. ! @ # $).',
        'uncompromised' => ':Attribute ini pernah bocor di data breach. Gunakan password lain.',
    ],
];
