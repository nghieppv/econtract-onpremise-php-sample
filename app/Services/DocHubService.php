<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DocHubService
{
    private ?string $accessToken = null;
    private ?string $baseUrl = null;

    public function authenticate(?string $username = null, ?string $password = null, int|string|null $companyId = null, ?string $baseUrl = null): array
    {
        $this->baseUrl = rtrim((string) ($baseUrl ?? config('dochub.base_url')), '/');

        $payload = [
            'username' => $username ?? config('dochub.username'),
            'password' => $password ?? config('dochub.password'),
            'companyId' => $companyId ?? config('dochub.company_id'),
        ];

        if ($payload['username'] === null || $payload['password'] === null || $this->baseUrl === '') {
            throw new RuntimeException('Thieu thong tin dang nhap DocHub. Vui long cau hinh DOCHUB_BASE_URL, DOCHUB_USERNAME, DOCHUB_PASSWORD.');
        }

        $result = $this->post('/api/auth/password-login', $payload, authenticated: false);

        if (($result['success'] ?? false) === true) {
            $this->accessToken = (string) Arr::get($result, 'data');
        }

        return $result;
    }

    public function createDocument(array $document, ?string $filePath = null): array
    {
        $filePath ??= (string) Arr::get($document, 'file_path', config('dochub.document.file_path'));
        $fileName = (string) Arr::get($document, 'file_name', basename($filePath));

        if (! is_file($filePath)) {
            throw new RuntimeException("Khong tim thay file chung tu: {$filePath}");
        }

        return $this->request()
            ->attach('File', fopen($filePath, 'r'), $fileName)
            ->post($this->url('/api/documents/create'), [
                'No' => $document['no'],
                'Subject' => $document['subject'],
                'Description' => $document['description'] ?? '',
                'TypeId' => $document['type_id'],
                'DepartmentId' => $document['department_id'],
            ])
            ->throw()
            ->json();
    }

    public function createDocumentFromBase64(array $document, string $base64File, string $fileName = 'document.pdf'): array
    {
        $binary = base64_decode($base64File, strict: true);

        if ($binary === false) {
            throw new RuntimeException('File base64 khong hop le.');
        }

        return $this->request()
            ->attach('File', $binary, $fileName)
            ->post($this->url('/api/documents/create'), [
                'No' => $document['no'],
                'Subject' => $document['subject'],
                'Description' => $document['description'] ?? '',
                'TypeId' => $document['type_id'],
                'DepartmentId' => $document['department_id'],
            ])
            ->throw()
            ->json();
    }

    public function updateProcessDocument(string $documentId, bool $processInOrder, array $processes): array
    {
        return $this->post('/api/documents/update-process', [
            'id' => $documentId,
            'processInOrder' => $processInOrder,
            'processes' => $processes,
        ]);
    }

    public function sendProcessDocument(string $documentId): array
    {
        return $this->post("/api/documents/send-process/{$documentId}");
    }

    public function getDocuments(string $search, int $pageSize = 10, int $page = 1, bool $waitToESign = false): array
    {
        return $this->request()
            ->get($this->url('/api/documents'), [
                'search' => $search,
                'pageSize' => $pageSize,
                'page' => $page,
                'WaitToESign' => $waitToESign ? 'true' : 'false',
            ])
            ->throw()
            ->json();
    }

    public function processDocument(array $process): array
    {
        return $this->post('/api/documents/process', array_filter(
            $process,
            static fn ($value): bool => $value !== null
        ));
    }

    public function sendNotify(string $documentId): array
    {
        return $this->post("/api/documents/send-notify/{$documentId}");
    }

    public function getDocumentTemplate(int $templateId): array
    {
        return $this->request()
            ->get($this->url("/api/document-templates/{$templateId}"))
            ->throw()
            ->json();
    }

    public function createBatchImport(array $batchData): array
    {
        return $this->post('/api/v2/batch-imports/create-advanced', $batchData);
    }

    public function sendBatchDocument(int $batchId): array
    {
        return $this->post("/api/batch-imports/send/{$batchId}");
    }

    private function post(string $path, ?array $payload = null, bool $authenticated = true): array
    {
        try {
            return $this->request($authenticated)
                ->post($this->url($path), $payload ?? [])
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            $message = $exception->response?->body() ?: $exception->getMessage();
            throw new RuntimeException("DocHub API loi tai {$path}: {$message}", previous: $exception);
        }
    }

    private function request(bool $authenticated = true): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout(60)
            ->retry(2, 300);

        if ($authenticated) {
            if ($this->accessToken === null) {
                throw new RuntimeException('Chua authenticate DocHub. Hay goi authenticate() truoc.');
            }

            $request = $request->withToken($this->accessToken);
        }

        return $request;
    }

    private function url(string $path): string
    {
        $this->baseUrl ??= rtrim((string) config('dochub.base_url'), '/');

        return $this->baseUrl.'/'.ltrim($path, '/');
    }
}
