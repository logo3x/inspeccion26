<?php

namespace App\Domain\Imports\Support;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Lee únicamente las filas dentro de [startRow, endRow] inclusive.
 * Permite a PhpSpreadsheet cargar solo un chunk en memoria por iteración.
 */
class ChunkReadFilter implements IReadFilter
{
    public function __construct(
        public int $startRow,
        public int $endRow,
    ) {}

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        return $row >= $this->startRow && $row <= $this->endRow;
    }
}
