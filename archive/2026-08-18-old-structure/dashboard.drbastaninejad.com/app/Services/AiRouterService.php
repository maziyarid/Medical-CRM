<?php
declare(strict_types=1);

namespace App\Services;

/**
 * AiRouterService
 * Thin wrapper around the OpenRouter-based AI layer (see OpenRouter Router Service Spec doc).
 * For the EMR Copilot, this method must ONLY return a draft string — it must never write
 * directly into emr_records. The controller enforces requires_review = true on every response.
 */
final class AiRouterService
{
    public function draftClinicalNote(string $chiefComplaint, array $context = []): string
    {
        // Placeholder until OpenRouterClient wiring (see OpenRouter Router Service Spec doc)
        // is connected with clinic-specific prompt templates and PII redaction.
        // Intentionally returns a clearly-labelled placeholder so the UI's
        // "AI draft — review required" card never gets mistaken for real output.
        return "[پیش‌نویس هوش مصنوعی - نیاز به تایید] بر اساس شکایت اصلی: {$chiefComplaint}";
    }
}
