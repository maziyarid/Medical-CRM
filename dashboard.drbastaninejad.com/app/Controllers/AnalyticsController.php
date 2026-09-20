<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;
use App\Services\PaymentLedgerService;
use DateTimeImmutable;
use DateTimeZone;
use PDO;

/**
 * AnalyticsController
 *
 * Operational analytics are calculated from the clinic CRM ledger. External
 * web-behaviour sources (GA4/GTM/Clarity) are surfaced with explicit connection
 * status and are never fabricated when their data APIs are not connected.
 */
final class AnalyticsController extends Controller
{
    public function summary(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        [$dateFrom, $dateToExclusive, $localFrom, $localTo] = $this->resolveDateRange(
            (string)($req->query['range'] ?? '30d'),
            isset($req->query['date_from']) ? (string)$req->query['date_from'] : null,
            isset($req->query['date_to']) ? (string)$req->query['date_to'] : null
        );

        [$prevFrom, $prevTo] = $this->previousPeriod($dateFrom, $dateToExclusive);

        $newPatients = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM patients WHERE clinic_id=? AND created_at>=? AND created_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );
        $prevNew = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM patients WHERE clinic_id=? AND created_at>=? AND created_at<?',
            [$clinicId,$prevFrom,$prevTo]
        );
        $newDelta = $this->pctDelta($newPatients,$prevNew);

        $bookingIntakes = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM intakes
             WHERE clinic_id=? AND source_type="booking" AND deleted_at IS NULL
               AND created_at>=? AND created_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );
        $bookingSelections = $this->scalar(
            $db,
            'SELECT COUNT(DISTINCT intake_id) FROM appointment_booking_requests
             WHERE clinic_id=? AND source="online" AND intake_id IS NOT NULL
               AND created_at>=? AND created_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );
        $conversionRate = $bookingIntakes > 0
            ? round(min(1,$bookingSelections/$bookingIntakes)*100,1)
            : 0.0;

        $prevBookingIntakes = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM intakes
             WHERE clinic_id=? AND source_type="booking" AND deleted_at IS NULL
               AND created_at>=? AND created_at<?',
            [$clinicId,$prevFrom,$prevTo]
        );
        $prevSelections = $this->scalar(
            $db,
            'SELECT COUNT(DISTINCT intake_id) FROM appointment_booking_requests
             WHERE clinic_id=? AND source="online" AND intake_id IS NOT NULL
               AND created_at>=? AND created_at<?',
            [$clinicId,$prevFrom,$prevTo]
        );
        $prevConversion = $prevBookingIntakes > 0
            ? round(min(1,$prevSelections/$prevBookingIntakes)*100,1)
            : 0.0;
        $conversionDelta = round($conversionRate-$prevConversion,1);

        $ledger = new PaymentLedgerService($db);
        // Ledger method accepts inclusive-ish date strings, so end is reduced by 1s.
        $ledgerEnd = (new DateTimeImmutable($dateToExclusive,new DateTimeZone('UTC')))
            ->modify('-1 second')->format('Y-m-d H:i:s');
        $prevLedgerEnd = (new DateTimeImmutable($prevTo,new DateTimeZone('UTC')))
            ->modify('-1 second')->format('Y-m-d H:i:s');
        $revenue = $ledger->revenueBetween($clinicId,$dateFrom,$ledgerEnd);
        $prevRevenue = $ledger->revenueBetween($clinicId,$prevFrom,$prevLedgerEnd);
        $revenueDelta = $this->pctDelta($revenue,$prevRevenue);

        $bookedPatients = $this->scalar(
            $db,
            'SELECT COUNT(DISTINCT patient_id) FROM appointments
             WHERE clinic_id=? AND deleted_at IS NULL AND status<>"cancelled"
               AND scheduled_at>=? AND scheduled_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );
        $returningCount = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM (
                SELECT patient_id FROM appointments
                WHERE clinic_id=? AND deleted_at IS NULL AND status="completed"
                  AND scheduled_at>=? AND scheduled_at<?
                GROUP BY patient_id HAVING COUNT(*)>=2
             ) x',
            [$clinicId,$dateFrom,$dateToExclusive]
        );
        $returnRate = $bookedPatients > 0
            ? round($returningCount/$bookedPatients*100,1)
            : 0.0;

        $contactThreads = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM communication_threads
             WHERE clinic_id=? AND source IN ("contact_form","email","gmail_archive")
               AND created_at>=? AND created_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );

        $responseStats = $this->responseStats($db,$clinicId,$dateFrom,$dateToExclusive);

        $verifiedPayments = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM appointment_payment_attempts p
             JOIN appointment_booking_requests b ON b.id=p.booking_request_id
             WHERE b.clinic_id=? AND p.status="verified"
               AND p.verified_at>=? AND p.verified_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );
        $confirmedBookings = $this->scalar(
            $db,
            'SELECT COUNT(*) FROM appointment_booking_requests
             WHERE clinic_id=? AND confirmation_status="confirmed"
               AND confirmed_at>=? AND confirmed_at<?',
            [$clinicId,$dateFrom,$dateToExclusive]
        );

        $funnel = [
            ['key'=>'contacts','label'=>'پیام و تماس','value'=>$contactThreads],
            ['key'=>'booking_intakes','label'=>'درخواست نوبت','value'=>$bookingIntakes],
            ['key'=>'slot_selected','label'=>'انتخاب زمان','value'=>$bookingSelections],
            ['key'=>'verified_payments','label'=>'پرداخت موفق','value'=>$verifiedPayments],
            ['key'=>'confirmed_bookings','label'=>'نوبت تأییدشده','value'=>$confirmedBookings],
        ];

        $paymentStatuses = $this->paymentStatuses($db,$clinicId,$dateFrom,$dateToExclusive);
        $chart = $this->weeklyPatients($db,$clinicId,$dateFrom,$dateToExclusive);
        $referrals = $this->referrals($db,$clinicId,$dateFrom,$dateToExclusive);

        $gaId = trim((string)($_ENV['GA4_MEASUREMENT_ID'] ?? 'G-7MMJ2J4TY7'));
        $gtmId = trim((string)($_ENV['GTM_CONTAINER_ID'] ?? ''));

        return $this->success([
            'range'=>[
                'from_utc'=>$dateFrom,
                'to_utc_exclusive'=>$dateToExclusive,
                'from_local'=>$localFrom,
                'to_local'=>$localTo,
                'timezone'=>'Asia/Tehran',
            ],
            'kpis'=>[
                ['key'=>'new_patients','label'=>'مراجعین جدید','value'=>$newPatients,'delta_pct'=>$newDelta,'delta_dir'=>$newDelta>=0?'up':'down'],
                ['key'=>'conversion_rate','label'=>'تبدیل درخواست → انتخاب زمان','value'=>$conversionRate,'delta_pct'=>$conversionDelta,'delta_dir'=>$conversionDelta>=0?'up':'down'],
                ['key'=>'revenue','label'=>'درآمد تأییدشده (ریال)','value'=>$revenue,'delta_pct'=>$revenueDelta,'delta_dir'=>$revenueDelta>=0?'up':'down'],
                ['key'=>'return_rate','label'=>'نرخ بازگشت بیمار','value'=>$returnRate,'delta_pct'=>0,'delta_dir'=>''],
                ['key'=>'contacts','label'=>'پیام‌های جدید','value'=>$contactThreads,'delta_pct'=>0,'delta_dir'=>''],
                ['key'=>'response_time','label'=>'میانگین اولین پاسخ','value'=>$responseStats['avg_minutes'],'delta_pct'=>0,'delta_dir'=>''],
            ],
            'communications'=>$responseStats+[
                'new_threads'=>$contactThreads,
            ],
            'funnel'=>$funnel,
            'payment_statuses'=>$paymentStatuses,
            'chart_new_patients'=>$chart,
            'referral_sources'=>$referrals,
            'web_analytics'=>[
                'ga4'=>[
                    'configured'=>(bool)preg_match('/^G-[A-Z0-9]+$/',$gaId),
                    'measurement_id'=>preg_match('/^G-[A-Z0-9]+$/',$gaId)?$gaId:null,
                    'data_api_connected'=>false,
                    'note'=>'جمع‌آوری GA4 فعال است؛ خواندن داده تاریخی نیازمند اتصال حساب Analytics است.',
                ],
                'gtm'=>[
                    'configured'=>(bool)preg_match('/^GTM-[A-Z0-9]+$/',$gtmId),
                    'container_id'=>preg_match('/^GTM-[A-Z0-9]+$/',$gtmId)?$gtmId:null,
                    'note'=>$gtmId!==''?'کانتینر GTM پیکربندی شده است.':'شناسه GTM هنوز پیکربندی نشده است.',
                ],
                'clarity'=>[
                    'configured'=>true,
                    'sensitive_pages_excluded'=>true,
                    'note'=>'Clarity روی صفحات حساس رزرو و تماس اجرا نمی‌شود.',
                ],
                'privacy'=>[
                    'pii_in_events'=>false,
                    'note'=>'ایمیل، تلفن، کد ملی، متن پزشکی و مقادیر فرم به ابزارهای تحلیل ارسال نمی‌شوند.',
                ],
            ],
            'generated_at_utc'=>(new DateTimeImmutable('now',new DateTimeZone('UTC')))->format(DATE_ATOM),
        ]);
    }

    /** @return array{avg_minutes:?float,answered_threads:int,unanswered_threads:int} */
    private function responseStats(PDO $db,int $clinicId,string $from,string $to): array
    {
        $stmt=$db->prepare(
            'SELECT AVG(TIMESTAMPDIFF(MINUTE,x.first_inbound,x.first_outbound)) avg_minutes,
                    SUM(CASE WHEN x.first_outbound IS NOT NULL THEN 1 ELSE 0 END) answered_threads,
                    SUM(CASE WHEN x.first_outbound IS NULL THEN 1 ELSE 0 END) unanswered_threads
             FROM (
                SELECT t.id,
                    MIN(CASE WHEN m.direction="inbound" THEN m.created_at END) first_inbound,
                    MIN(CASE WHEN m.direction="outbound" THEN m.created_at END) first_outbound
                FROM communication_threads t
                JOIN communication_messages m ON m.thread_id=t.id
                WHERE t.clinic_id=? AND t.created_at>=? AND t.created_at<?
                  AND t.status<>"spam"
                GROUP BY t.id
             ) x'
        );
        $stmt->execute([$clinicId,$from,$to]);
        $r=$stmt->fetch()?:[];
        return [
            'avg_minutes'=>$r['avg_minutes']===null?null:round((float)$r['avg_minutes'],1),
            'answered_threads'=>(int)($r['answered_threads']??0),
            'unanswered_threads'=>(int)($r['unanswered_threads']??0),
        ];
    }

    /** @return array<int,array<string,mixed>> */
    private function paymentStatuses(PDO $db,int $clinicId,string $from,string $to): array
    {
        $stmt=$db->prepare(
            'SELECT p.status,COUNT(*) cnt,COALESCE(SUM(p.amount_rials),0) amount_rials
             FROM appointment_payment_attempts p
             JOIN appointment_booking_requests b ON b.id=p.booking_request_id
             WHERE b.clinic_id=? AND p.created_at>=? AND p.created_at<?
             GROUP BY p.status ORDER BY cnt DESC'
        );
        $stmt->execute([$clinicId,$from,$to]);
        return array_map(static fn(array $r):array=>[
            'status'=>(string)$r['status'],
            'count'=>(int)$r['cnt'],
            'amount_rials'=>(int)$r['amount_rials'],
        ],$stmt->fetchAll());
    }

    /** @return array<int,array{week:string,count:int}> */
    private function weeklyPatients(PDO $db,int $clinicId,string $from,string $to): array
    {
        $stmt=$db->prepare(
            'SELECT YEARWEEK(DATE_ADD(created_at,INTERVAL 210 MINUTE),1) yw,COUNT(*) cnt
             FROM patients
             WHERE clinic_id=? AND created_at>=? AND created_at<?
             GROUP BY yw ORDER BY yw ASC LIMIT 52'
        );
        $stmt->execute([$clinicId,$from,$to]);
        return array_map(static fn(array $r):array=>[
            'week'=>(string)$r['yw'],
            'count'=>(int)$r['cnt'],
        ],$stmt->fetchAll());
    }

    /** @return array<int,array<string,mixed>> */
    private function referrals(PDO $db,int $clinicId,string $from,string $to): array
    {
        $stmt=$db->prepare(
            'SELECT CASE source_type WHEN "booking" THEN "رزرو وب‌سایت" ELSE "فرم پذیرش" END source,
                    COUNT(*) cnt
             FROM intakes
             WHERE clinic_id=? AND deleted_at IS NULL AND created_at>=? AND created_at<?
             GROUP BY source ORDER BY cnt DESC LIMIT 10'
        );
        $stmt->execute([$clinicId,$from,$to]);
        $rows=$stmt->fetchAll();
        $total=array_sum(array_map(static fn(array $r):int=>(int)$r['cnt'],$rows));
        return array_map(static fn(array $r):array=>[
            'source'=>(string)$r['source'],
            'count'=>(int)$r['cnt'],
            'pct'=>$total>0?round((int)$r['cnt']/$total*100,1):0,
        ],$rows);
    }

    private function scalar(PDO $db,string $sql,array $params): int
    {
        $stmt=$db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    private function pctDelta(int|float $current,int|float $previous): float
    {
        if((float)$previous===0.0)return (float)$current>0?100.0:0.0;
        return round(((float)$current-(float)$previous)/(float)$previous*100,1);
    }

    /** @return array{0:string,1:string,2:string,3:string} */
    private function resolveDateRange(string $range,?string $from,?string $to): array
    {
        $tehran=new DateTimeZone('Asia/Tehran');
        $utc=new DateTimeZone('UTC');
        try{
            if($from&&$to){
                $localStart=(new DateTimeImmutable($from.' 00:00:00',$tehran));
                $localEnd=(new DateTimeImmutable($to.' 00:00:00',$tehran))->modify('+1 day');
            }else{
                $days=match($range){'90d'=>90,'1y'=>365,default=>30};
                $today=new DateTimeImmutable('today',$tehran);
                $localStart=$today->modify('-'.max(0,$days-1).' days');
                $localEnd=$today->modify('+1 day');
            }
        }catch(\Throwable){
            $today=new DateTimeImmutable('today',$tehran);
            $localStart=$today->modify('-29 days');
            $localEnd=$today->modify('+1 day');
        }
        return [
            $localStart->setTimezone($utc)->format('Y-m-d H:i:s'),
            $localEnd->setTimezone($utc)->format('Y-m-d H:i:s'),
            $localStart->format('Y-m-d'),
            $localEnd->modify('-1 day')->format('Y-m-d'),
        ];
    }

    /** @return array{0:string,1:string} */
    private function previousPeriod(string $from,string $to): array
    {
        $f=new DateTimeImmutable($from,new DateTimeZone('UTC'));
        $t=new DateTimeImmutable($to,new DateTimeZone('UTC'));
        $seconds=max(1,$t->getTimestamp()-$f->getTimestamp());
        return [
            $f->modify('-'.$seconds.' seconds')->format('Y-m-d H:i:s'),
            $f->format('Y-m-d H:i:s'),
        ];
    }
}