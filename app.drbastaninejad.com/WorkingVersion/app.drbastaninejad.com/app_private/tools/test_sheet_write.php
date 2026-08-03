<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$stamp = gmdate('c');
$row = [
    'FirstName'    => 'تست',
    'LastName'     => 'PHP Web App',
    'FatherName'   => '—',
    'TavalodDay'   => '1',
    'TavalodMonth' => '1',
    'TavalodYear'  => '1400',
    'HomeTel'      => '',
    'Mobile'       => '09120000000',
    'Mobile2'      => '09120000001',
    'CodeAshnaei'  => '41',
    'CodeBimeh'    => '1',
    'CodeMeli'     => '0000000000',
    'CodeJob'      => '1',
    'HomeAd'       => 'آزمایش نوشتن PHP به Google Sheet',
    'Description'  => 'PHP Web App write test — ' . $stamp,
    'IsTransfer'   => '1',
    'drugs'        => '',
    'difficult'    => '',
    'morefmob'     => '',
];

try {
    $result = (new App\SheetClient())->append($row);
    echo 'Sheet write succeeded. Row: ' . (string) ($result['row'] ?? '?') . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Sheet write failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
