<?php

return [
    // Allocation defaults for the Kang Cuan financial advisor ("Pendamping Keuangan").
    // These are starting points, not rules. Real mandatory debt installments
    // replace the "debt" share when they are recorded in the debts module.
    'defaults' => [
        'essentials' => 0.50, // kebutuhan pokok + hunian
        'health' => 0.05,     // asuransi/proteksi kesehatan
        'debt' => 0.15,       // cicilan wajib (override dengan cicilan nyata)
        'emergency' => 0.10,  // dana darurat
        'goals' => 0.10,      // pendidikan/tujuan/tabungan
        'fun' => 0.05,        // jajan & hiburan
        'buffer' => 0.05,     // cadangan kebutuhan tidak rutin (diterima sisa)
    ],

    // Emergency fund coverage: how many months of essential expense the family
    // should hold. Raised automatically for irregular income.
    'emergency_months' => 3,
    'emergency_months_irregular' => 6,
];