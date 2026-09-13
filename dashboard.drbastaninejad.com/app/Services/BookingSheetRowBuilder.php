<?php
declare(strict_types=1);

namespace App\Services;

final class BookingSheetRowBuilder
{
    public const HEADERS = [
        'FirstName', 'LastName', 'FatherName', 'TavalodDay', 'TavalodMonth', 'TavalodYear',
        'HomeTel', 'Mobile', 'Mobile2', 'CodeAshnaei', 'CodeBimeh', 'CodeMeli', 'CodeJob',
        'HomeAd', 'Description', 'IsTransfer', 'drugs', 'difficult', 'morefmob',
    ];

    public static function build(array $data): array
    {
        $birth = str_replace('-', '/', (string)($data['birth_date_jalali'] ?? ''));
        $parts = preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $birth, $m)
            ? [str_pad($m[3], 2, '0', STR_PAD_LEFT), str_pad($m[2], 2, '0', STR_PAD_LEFT), $m[1]]
            : ['', '', ''];
        $description = [];
        if (trim((string)($data['email'] ?? '')) !== '') {
            $description[] = 'ایمیل: ' . trim((string)$data['email']);
        }
        $description[] = 'درخواست از دکتر: ' . trim((string)($data['doctor_request'] ?? ''));

        return [
            'FirstName' => trim((string)($data['first_name'] ?? '')),
            'LastName' => trim((string)($data['last_name'] ?? '')),
            'FatherName' => '', 'TavalodDay' => $parts[0], 'TavalodMonth' => $parts[1],
            'TavalodYear' => $parts[2], 'HomeTel' => '',
            'Mobile' => trim((string)($data['mobile'] ?? '')), 'Mobile2' => '',
            'CodeAshnaei' => '', 'CodeBimeh' => '',
            'CodeMeli' => trim((string)($data['national_id'] ?? '')), 'CodeJob' => '',
            'HomeAd' => '', 'Description' => implode(' | ', $description), 'IsTransfer' => '0',
            'drugs' => trim((string)($data['medications'] ?? '')),
            'difficult' => trim((string)($data['medical_history'] ?? '')), 'morefmob' => '',
        ];
    }
}
