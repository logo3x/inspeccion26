<?php

/**
 * Configuración específica del sistema de inspección vehicular.
 * Centraliza valores por defecto, datos institucionales y configuraciones
 * que pueden cambiar entre clientes (Bucaramanga, Valledupar, etc.).
 */
return [

    // Defaults editables que precargan el formulario y el importador Excel.
    // El operador puede cambiarlos en cada registro.
    'defaults' => [
        'tipo' => 'MOTOCICLETA',
        'servicio' => 'PARTICULAR',
        'peso_bruto' => '110KG',
        'peso_neto' => '75KG',
        'ubicacion_fisica' => 'DIRECCION DE TRANSITO BUCARAMANGA',
        'organismo_transito' => 'DIRECCION DE TRANSITO BUCARAMANGA',
    ],

    // Información institucional que aparece en la ficha técnica generada.
    'institucion' => [
        'nombre' => 'SECRETARIA DE TRANSITO Y TRANSPORTE DE BUCARAMANGA',
        'nit' => '800.098.911-8',
        'ciudad' => 'Bucaramanga',
    ],

    // Técnico avaluador fijo. Puede cambiarse aquí sin recompilar nada.
    'tecnico_avaluador' => [
        'nombre' => 'Luis A. Fuentes',
        'cargo' => 'Técnico Automotores',
    ],

    // Generador de número de ficha (secuencia anual independiente).
    // Formato: YYYY-NNNNNN  → "2026-000001"
    'ficha' => [
        'prefix_year' => true,
        'padding' => 6,
    ],

];
