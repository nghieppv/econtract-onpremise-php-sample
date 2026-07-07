<?php

namespace App\Console\Commands;

use App\Enums\ReceiveOtpMethod;
use App\Services\DocHubService;
use App\Support\DocHubPlaceholders;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class RunDocHubSample extends Command
{
    protected $signature = 'dochub:sample
        {--batch : Tao lo chung tu tu document template truoc khi chay flow PDF}
        {--send-batch : Gui quy trinh cho lo chung tu sau khi tao batch import}
        {--otp= : OTP dung chung khi API yeu cau xac nhan OTP}
        {--skip-process : Chi tao/cap nhat/gui quy trinh, khong xu ly approve/sign}';

    protected $description = 'Run eContract On-Premise sample business flow';

    public function handle(DocHubService $docHub): int
    {
        $this->info('[1] Authenticate + select company');
        $auth = $docHub->authenticate();
        $this->line($this->messageOf($auth));

        if ($this->option('batch')) {
            $this->runBatchImport($docHub);
        }

        $random = strtoupper(Str::random(4));

        $this->info('[2.1] Create document from PDF');
        $create = $docHub->createDocument([
            'no' => $random,
            'subject' => "Chung tu thu nghiem {$random}",
            'description' => 'Chung tu thu nghiem',
            'type_id' => (int) config('dochub.document.type_id'),
            'department_id' => (int) config('dochub.document.department_id'),
            'file_path' => (string) config('dochub.document.file_path'),
        ]);
        $this->line($this->messageOf($create));

        $documentId = (string) Arr::get($create, 'data.id');
        $documentNo = (string) Arr::get($create, 'data.no', $random);

        $this->info('[3] Update document process');
        $updateProcess = $docHub->updateProcessDocument($documentId, true, $this->defaultProcesses());
        $this->line($this->messageOf($updateProcess));

        $this->info('[4] Send document process');
        $sendProcess = $docHub->sendProcessDocument($documentId);
        $this->line($this->messageOf($sendProcess));

        $this->info('[5] Get created document and waiting process');
        $documents = $docHub->getDocuments($documentNo);
        $this->line(json_encode(Arr::get($documents, 'data.items.0.waitingProcess'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (! $this->option('skip-process')) {
            $processes = Arr::get($updateProcess, 'data.processes', []);
            $this->processStep($docHub, Arr::get($processes, '0.id'), '[6.1] Approve', 'Nguyen Van A');
            $this->processStep($docHub, Arr::get($processes, '1.id'), '[6.2] SignDraw', 'Nguyen Van A1');
            $this->processStep($docHub, Arr::get($processes, '2.id'), '[6.3] ESign', 'Nguyen Van A2');
        }

        $this->info('[7] Send completed document notification');
        $notify = $docHub->sendNotify($documentId);
        $this->line($this->messageOf($notify));

        $this->info('[8] Get document status');
        $status = $docHub->getDocuments($documentNo);
        $this->line(json_encode(Arr::get($status, 'data.items.0.status'), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    private function runBatchImport(DocHubService $docHub): void
    {
        $this->info('[2.2] Create documents from template by batch import');

        $batch = $docHub->createBatchImport($this->batchImportPayload());
        $this->line($this->messageOf($batch));

        if ($this->option('send-batch')) {
            $batchId = (int) Arr::get($batch, 'data.id');
            $send = $docHub->sendBatchDocument($batchId);
            $this->line($this->messageOf($send));
        }
    }

    private function processStep(DocHubService $docHub, ?string $processId, string $title, string $signatureText): void
    {
        if ($processId === null || $processId === '') {
            $this->warn("{$title}: skip vi khong co process id.");
            return;
        }

        $this->info($title);
        $payload = $this->processPayload($processId, $signatureText);
        $result = $docHub->processDocument($payload);
        $this->line($this->messageOf($result));

        $otpMethod = Arr::get($result, 'data.receiveOtpMethod', ReceiveOtpMethod::None->value);
        if ((int) $otpMethod === ReceiveOtpMethod::None->value) {
            return;
        }

        $otp = $this->option('otp') ?: $this->ask('Nhap OTP de xac nhan '.ReceiveOtpMethod::labelFrom((int) $otpMethod));
        $payload['otp'] = $otp;

        $confirmed = $docHub->processDocument($payload);
        $this->line($this->messageOf($confirmed));
    }

    private function defaultProcesses(): array
    {
        $userCode = (string) config('dochub.process.user_code');
        $position = (string) config('dochub.process.position');
        $pageSign = (int) config('dochub.process.page_sign');

        return [
            $this->processUser(1, $userCode, 'A', $position, $pageSign),
            $this->processUser(2, $userCode, 'DR', $position, $pageSign),
            $this->processUser(3, $userCode, 'E', $position, $pageSign),
        ];
    }

    private function processUser(int $orderNo, string $userCode, string $permission, string $position, int $pageSign): array
    {
        return [
            'orderNo' => $orderNo,
            'processedByUserCode' => $userCode,
            'accessPermissionCode' => $permission,
            'position' => $position,
            'pageSign' => $pageSign,
        ];
    }

    private function processPayload(string $processId, string $signatureText): array
    {
        return [
            'processId' => $processId,
            'otp' => null,
            'reason' => 'Dong y',
            'reject' => false,
            'signatureDisplayMode' => 3,
            'signatureImage' => $this->signatureImage(),
            'signingPage' => 1,
            'signingPosition' => '10,110,202,200',
            'signatureText' => $signatureText,
            'fontSize' => 12,
            'showReason' => false,
            'confirmTermsConditions' => true,
        ];
    }

    private function batchImportPayload(): array
    {
        $parameters = array_merge(
            DocHubPlaceholders::requiredDocumentColumns(),
            DocHubPlaceholders::processColumns(2),
            ['{{day}}', '{{month}}', '{{year}}', '{{ben_a}}', '{{ben_b}}', '{{dien_tich_dat}}', '{{dien_tich_nha}}']
        );

        $prefix = strtoupper(Str::random(4));
        $rows = [];

        for ($i = 1; $i <= (int) config('dochub.batch_import.rows'); $i++) {
            $rows[] = [
                "{$prefix}{$i}",
                "{$prefix}{$i}",
                "Hop dong {$prefix}{$i}",
                '',
                'Chung tu thu nghiem',
                'Y',
                (string) config('dochub.batch_import.user_code'),
                'DR',
                (string) config('dochub.batch_import.user_code'),
                'D',
                now()->day,
                now()->month,
                now()->year,
                "Nguyen Van A_{$i}",
                "Nguyen Van B_{$i}",
                '1000',
                '700',
            ];
        }

        return [
            'documentTemplateId' => (int) config('dochub.batch_import.document_template_id'),
            'documentTypeId' => (int) config('dochub.batch_import.document_type_id'),
            'departmentId' => (int) config('dochub.batch_import.department_id'),
            'parameters' => $parameters,
            'rows' => $rows,
        ];
    }

    private function signatureImage(): string
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==';
    }

    private function messageOf(array $result): string
    {
        return (string) Arr::first($result['messages'] ?? [], default: json_encode($result, JSON_UNESCAPED_UNICODE));
    }
}
