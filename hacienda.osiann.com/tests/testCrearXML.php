<?php
require __DIR__ . '/db.php'; // Carga $db, $ajustes y $facturador

$comprobante = [
    'CodigoActividad' => '123456',  // 6 dígitos obligatorio
    'NumeroConsecutivo' => '00100001010000000011',
    'FechaEmision' => date('c'), // formato ISO8601
    'Emisor' => [
        'Nombre' => 'Emisor',
        'Identificacion' => [
            'Tipo' => '02',
            'Numero' => '3102934285'
        ],
        'Ubicacion' => [
            'Provincia' => '6',
            'Canton' => '01',
            'Distrito' => '01',
            'OtrasSenas' => 'direccion'
        ],
        'CorreoElectronico' => 'admin@osiann.com'
    ],

    'Receptor' => [
        'Nombre' => 'Receptor',
        'Identificacion' => [
            'Tipo' => '01',
            'Numero' => '110370650'
        ],
        'Ubicacion' => [
            'Provincia' => '6',
            'Canton' => '01',
            'Distrito' => '01',
            'OtrasSenas' => 'direccion'
        ],
        'CorreoElectronico' => 'pablo@osiann.com'
    ],

    'CondicionVenta' => '01',

    // MedioPago debe ser string simple, no array (puedes enviar el medio principal)
    'MedioPago' => '01',

    'DetalleServicio' => [
        'LineaDetalle' => [
            [
                'NumeroLinea' => '1',
                'Codigo' => '1234567890123', // string simple, 13 dígitos
                'CodigoComercial' => [
                    'Tipo' => '01',
                    'Codigo' => '00001'
                ],
                'Cantidad' => '1',
                'UnidadMedida' => 'Unid',
                'Detalle' => 'Producto sin IVA',
                'PrecioUnitario' => '15000.00',
                'MontoTotal' => '15000.00',
                'Descuento' => [
                    'MontoDescuento' => '1000.00',
                    'NaturalezaDescuento' => '...'
                ],
                'SubTotal' => '14000.00',
                'MontoTotalLinea' => '14000.00'
            ],
            [
                'NumeroLinea' => '2',
                'Codigo' => '1234567890123', // string simple, 13 dígitos
                'CodigoComercial' => [
                    'Tipo' => '04',
                    'Codigo' => '00002'
                ],
                'Cantidad' => '2',
                'UnidadMedida' => 'Unid', // cambia a unidad válida según catálogo
                'Detalle' => 'Servicio con IVA',
                'PrecioUnitario' => '3000.00',
                'MontoTotal' => '6000.00',
                'SubTotal' => '6000.00',
                'Impuesto' => [   // Impuesto después de SubTotal
                    'Codigo' => '01',
                    'CodigoTarifa' => '08',
                    'Tarifa' => '13.00',
                    'Monto' => '780.00'
                ],
                'MontoTotalLinea' => '6780.00'
            ]
        ]
    ],

    'ResumenFactura' => [
        'TotalServGravados' => '6000.00',
        'TotalMercanciasExentas' => '15000.00',
        'TotalGravado' => '6000.00',
        'TotalExento' => '15000.00',
        'TotalVenta' => '21000.00',
        'TotalDescuentos' => '1000.00',
        'TotalVentaNeta' => '20000.00',
        'TotalImpuesto' => '780.00',
        'TotalComprobante' => '20780.00'
    ]
];

/**
 * Esta función devuelve la clave del comprobante
 * Necesario para futuras consultas
 */
$id_empresa = '5';

$clave = $facturador->enviarComprobante($comprobante, $id_empresa);
print_r($clave);
