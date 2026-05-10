<?php

use App\Domain\Imports\Mappers\VehicleExcelMapper;

it('maps a complete row including dates and owner', function () {
    $row = [
        'A' => 1,
        'B' => 'CLQ18B',
        'C' => 'EDINSON YESID BECERRA',
        'D' => '1098641249',
        'E' => 'LXSYCHLY351106734',
        'F' => '51134532',
        'G' => 'SIGMA',
        'H' => 'SG 110-3',
        'I' => 2006,
        'J' => 'AZUL BLANCO',
        'K' => 'MOTOCICLETA',
        'L' => 109,
        'M' => 'DIR TTOyTTE FLORIDABLANCA',
        'N' => '110KG',
        'O' => '75KG',
        'P' => 'DIR. TRANSITO BUCARAMANGA',
        'Q' => 'PARTICULAR',
        'R' => 4826,
        'S' => 'Comparendo',
        'T' => 41309, // Excel serial = 2013-02-04
    ];

    $result = (new VehicleExcelMapper)->map($row);

    expect($result['placa'])->toBe('CLQ18B');
    expect($result['vehicle']['marca'])->toBe('SIGMA');
    expect($result['vehicle']['linea'])->toBe('SG 110-3');
    expect($result['vehicle']['year'])->toBe(2006);
    expect($result['vehicle']['tipo'])->toBe('MOTOCICLETA');
    expect($result['vehicle']['servicio'])->toBe('PARTICULAR');
    expect($result['vehicle']['cilindraje'])->toBe(109);
    expect($result['vehicle']['fecha_ingreso'])->toBe('2013-02-04');
    expect($result['owner']['document_number'])->toBe('1098641249');
    expect($result['owner']['full_name'])->toBe('EDINSON YESID BECERRA');
});

it('detects empty rows', function () {
    expect((new VehicleExcelMapper)->isEmptyRow(['A' => 1]))->toBeTrue();
    expect((new VehicleExcelMapper)->isEmptyRow(['B' => 'ABC123']))->toBeFalse();
});

it('normalizes plate by trimming and uppercasing', function () {
    $result = (new VehicleExcelMapper)->map(['B' => '  abc-123  ']);

    expect($result['placa'])->toBe('ABC-123');
});

it('rejects implausible years', function () {
    $result = (new VehicleExcelMapper)->map(['B' => 'XXX111', 'I' => 1800]);

    expect($result['vehicle'])->not->toHaveKey('year');
});
