<?php

return [
    'strict_keys' => [
        'socket_type',
        'ram_type',
        'form_factor',
    ],

    'compatibility_aliases' => [
        'socket_type' => ['socket'],
        'ram_type_slot' => ['ram_type'],
        'ram_type' => ['ram_type'],
    ],

    'categories' => [
        'processor' => [
            'labels' => ['Processor'],
            'fields' => [
                [
                    'key' => 'socket_type',
                    'label' => 'Socket type',
                    'placeholder' => 'AM5 / LGA1700',
                    'hint' => 'Wajib untuk pencocokan processor dan motherboard.',
                    'lookup_keys' => ['socket', 'Socket'],
                ],
                [
                    'key' => 'chipset_support',
                    'label' => 'Chipset support',
                    'placeholder' => 'B650 / Z790',
                    'hint' => 'Opsional, tapi membantu mengelompokkan processor dengan platform atau chipset yang relevan.',
                    'lookup_keys' => ['chipset_support', 'chipset', 'Chipset'],
                ],
                [
                    'key' => 'ram_type_support',
                    'label' => 'RAM type support',
                    'placeholder' => 'DDR4 / DDR5',
                    'hint' => 'Opsional, tapi membantu rekomendasi RAM yang lebih akurat.',
                    'lookup_keys' => ['ram_type_support', 'RAM Type Support', 'ram_type', 'RAM Type'],
                ],
                [
                    'key' => 'tdp_watt',
                    'label' => 'TDP watt',
                    'placeholder' => '65',
                    'hint' => 'Opsional, tapi berguna untuk estimasi kebutuhan daya.',
                    'lookup_keys' => ['tdp_watt', 'tdp', 'TDP'],
                ],
            ],
        ],
        'motherboard' => [
            'labels' => ['Motherboard'],
            'fields' => [
                [
                    'key' => 'socket_type',
                    'label' => 'Socket type',
                    'placeholder' => 'AM5 / LGA1700',
                    'hint' => 'Wajib agar simulasi bisa mencocokkan processor.',
                    'lookup_keys' => ['socket', 'Socket'],
                ],
                [
                    'key' => 'ram_type_slot',
                    'label' => 'RAM type slot',
                    'placeholder' => 'DDR4 / DDR5',
                    'hint' => 'Opsional, tapi penting untuk validasi RAM yang cocok.',
                    'lookup_keys' => ['ram_type_slot', 'ram_type', 'RAM Type'],
                ],
                [
                    'key' => 'form_factor',
                    'label' => 'Form factor',
                    'placeholder' => 'ATX / M-ATX / ITX',
                    'hint' => 'Wajib untuk mencocokkan motherboard dengan casing dan PSU.',
                    'lookup_keys' => ['form_factor', 'Form Factor'],
                ],
                [
                    'key' => 'chipset',
                    'label' => 'Chipset',
                    'placeholder' => 'B650 / Z790',
                    'hint' => 'Opsional, tapi membantu identifikasi platform motherboard.',
                    'lookup_keys' => ['chipset', 'Chipset'],
                ],
            ],
        ],
        'ram' => [
            'labels' => ['RAM'],
            'fields' => [
                [
                    'key' => 'ram_type',
                    'label' => 'RAM type',
                    'placeholder' => 'DDR4 / DDR5',
                    'hint' => 'Wajib agar RAM bisa dicocokkan dengan motherboard.',
                    'lookup_keys' => ['ram_type', 'RAM Type', 'Type'],
                ],
                [
                    'key' => 'speed_mhz',
                    'label' => 'Speed MHz',
                    'placeholder' => '3200',
                    'hint' => 'Opsional, tapi membantu simulasi memilih RAM yang seimbang.',
                    'lookup_keys' => ['speed_mhz', 'speed', 'Speed'],
                ],
                [
                    'key' => 'capacity_gb',
                    'label' => 'Capacity GB',
                    'placeholder' => '16',
                    'hint' => 'Opsional, tapi berguna untuk perhitungan total kapasitas RAM.',
                    'lookup_keys' => ['capacity_gb', 'capacity', 'Capacity'],
                ],
            ],
        ],
        'gpu' => [
            'labels' => ['GPU', 'VGA Card', 'VGA'],
            'fields' => [
                [
                    'key' => 'pcie_version',
                    'label' => 'PCIe version',
                    'placeholder' => 'PCIe 4.0',
                    'hint' => 'Opsional, tapi membantu validasi jalur ekspansi motherboard.',
                    'lookup_keys' => ['pcie_version', 'pcie', 'PCIe'],
                ],
                [
                    'key' => 'min_psu_watt',
                    'label' => 'Minimum PSU watt',
                    'placeholder' => '650',
                    'hint' => 'Opsional, tapi sangat membantu rekomendasi PSU.',
                    'lookup_keys' => ['min_psu_watt', 'recommended_psu', 'Recommended PSU'],
                ],
                [
                    'key' => 'length_mm',
                    'label' => 'Length mm',
                    'placeholder' => '300',
                    'hint' => 'Opsional, tapi penting untuk kecocokan dengan casing.',
                    'lookup_keys' => ['length_mm', 'length', 'Length'],
                ],
            ],
        ],
        'power_supply' => [
            'labels' => ['Power Supply', 'Power_Supply'],
            'fields' => [
                [
                    'key' => 'total_wattage',
                    'label' => 'Total wattage',
                    'placeholder' => '750',
                    'hint' => 'Opsional, tapi penting untuk estimasi daya sistem.',
                    'lookup_keys' => ['total_wattage', 'wattage', 'Wattage'],
                ],
                [
                    'key' => 'efficiency_rating',
                    'label' => 'Efficiency rating',
                    'placeholder' => '80+ GOLD',
                    'hint' => 'Opsional, tapi membantu standar kualitas PSU.',
                    'lookup_keys' => ['efficiency_rating', 'efficiency', 'Efficiency'],
                ],
                [
                    'key' => 'form_factor',
                    'label' => 'Form factor',
                    'placeholder' => 'ATX / SFX',
                    'hint' => 'Wajib untuk kecocokan fisik dengan casing.',
                    'lookup_keys' => ['form_factor', 'Form Factor'],
                ],
            ],
        ],
        'casing' => [
            'labels' => ['Casing'],
            'fields' => [
                [
                    'key' => 'supported_motherboard_sizes',
                    'label' => 'Supported motherboard sizes',
                    'placeholder' => 'ATX, M-ATX, ITX',
                    'hint' => 'Opsional, tapi krusial untuk memilih motherboard yang muat.',
                    'lookup_keys' => ['supported_motherboard_sizes', 'supported_motherboard_sizes', 'form_factor'],
                ],
                [
                    'key' => 'max_gpu_length_mm',
                    'label' => 'Max GPU length mm',
                    'placeholder' => '340',
                    'hint' => 'Opsional, tapi membantu validasi panjang GPU terhadap casing.',
                    'lookup_keys' => ['max_gpu_length_mm', 'max_gpu_length', 'Length'],
                ],
                [
                    'key' => 'max_cpu_cooler_height_mm',
                    'label' => 'Max CPU cooler height mm',
                    'placeholder' => '165',
                    'hint' => 'Opsional, tapi membantu validasi cooler terhadap casing.',
                    'lookup_keys' => ['max_cpu_cooler_height_mm', 'max_cpu_cooler_height'],
                ],
            ],
        ],
        'storage' => [
            'labels' => ['Storage', 'Storage (SSD/HDD)'],
            'fields' => [
                [
                    'key' => 'form_factor',
                    'label' => 'Form factor',
                    'placeholder' => 'M.2 / 2.5 / 3.5',
                    'hint' => 'Wajib untuk pencocokan storage dengan slot atau bay yang tersedia.',
                    'lookup_keys' => ['form_factor', 'Form Factor'],
                ],
                [
                    'key' => 'interface_type',
                    'label' => 'Interface type',
                    'placeholder' => 'NVME / SATA',
                    'hint' => 'Opsional, tapi membantu memilih storage yang kompatibel.',
                    'lookup_keys' => ['interface_type', 'interface', 'Interface'],
                ],
                [
                    'key' => 'capacity_gb',
                    'label' => 'Capacity GB',
                    'placeholder' => '1000',
                    'hint' => 'Opsional, tapi berguna untuk perhitungan kapasitas total storage.',
                    'lookup_keys' => ['capacity_gb', 'capacity', 'Capacity'],
                ],
            ],
        ],
    ],
];
