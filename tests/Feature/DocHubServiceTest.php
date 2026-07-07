<?php

namespace Tests\Feature;

use App\Services\DocHubService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DocHubServiceTest extends TestCase
{
    public function test_authenticate_stores_token_and_sends_company_id(): void
    {
        Http::fake([
            'https://dochub.test/api/auth/password-login' => Http::response([
                'success' => true,
                'messages' => ['Login successful'],
                'data' => 'access-token',
            ]),
            'https://dochub.test/api/documents/update-process' => Http::response([
                'success' => true,
                'messages' => ['Workflow updated successfully'],
                'data' => ['id' => 'doc-1'],
            ]),
        ]);

        $service = new DocHubService();
        $service->authenticate('user-a', 'secret', 164, 'https://dochub.test');
        $service->updateProcessDocument('doc-1', true, [[
            'orderNo' => 1,
            'processedByUserCode' => 'BAOTH',
            'accessPermissionCode' => 'A',
            'position' => '14,478,206,568',
            'pageSign' => 1,
        ]]);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://dochub.test/api/auth/password-login'
                && $request['username'] === 'user-a'
                && $request['password'] === 'secret'
                && $request['companyId'] === 164;
        });

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://dochub.test/api/documents/update-process'
                && $request->hasHeader('Authorization', 'Bearer access-token')
                && $request['id'] === 'doc-1'
                && $request['processInOrder'] === true
                && $request['processes'][0]['accessPermissionCode'] === 'A';
        });
    }

    public function test_service_calls_document_business_endpoints(): void
    {
        Http::fake([
            'https://dochub.test/api/auth/password-login' => Http::response(['success' => true, 'messages' => ['OK'], 'data' => 'token']),
            'https://dochub.test/api/documents/create' => Http::response(['success' => true, 'messages' => ['Created'], 'data' => ['id' => 'doc-1', 'no' => 'ABCD']]),
            'https://dochub.test/api/documents/send-process/doc-1' => Http::response(['success' => true, 'messages' => ['Sent'], 'data' => ['id' => 'doc-1']]),
            'https://dochub.test/api/documents?*' => Http::response(['success' => true, 'messages' => ['Found'], 'data' => ['items' => []]]),
            'https://dochub.test/api/documents/process' => Http::response(['success' => true, 'messages' => ['Processed'], 'data' => ['receiveOtpMethod' => -1]]),
            'https://dochub.test/api/documents/send-notify/doc-1' => Http::response(['success' => true, 'messages' => ['Notified'], 'data' => ['id' => 'doc-1']]),
        ]);

        $service = new DocHubService();
        $service->authenticate('user', 'password', 164, 'https://dochub.test');

        $created = $service->createDocument([
            'no' => 'ABCD',
            'subject' => 'Sample document ABCD',
            'description' => 'Sample document',
            'type_id' => 54,
            'department_id' => 33,
        ], base_path('storage/app/samples/sample.pdf'));

        $service->sendProcessDocument('doc-1');
        $service->getDocuments('ABCD');
        $service->processDocument(['processId' => 'process-1', 'otp' => null, 'reject' => false]);
        $service->sendNotify('doc-1');

        $this->assertSame('doc-1', $created['data']['id']);

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://dochub.test/api/documents/create');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://dochub.test/api/documents/send-process/doc-1');
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://dochub.test/api/documents?'));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://dochub.test/api/documents/process' && ! array_key_exists('otp', $request->data()));
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://dochub.test/api/documents/send-notify/doc-1');
    }

    public function test_batch_import_payload_endpoint(): void
    {
        Http::fake([
            'https://dochub.test/api/auth/password-login' => Http::response(['success' => true, 'messages' => ['OK'], 'data' => 'token']),
            'https://dochub.test/api/v2/batch-imports/create-advanced' => Http::response(['success' => true, 'messages' => ['Batch created'], 'data' => ['id' => 10]]),
            'https://dochub.test/api/batch-imports/send/10' => Http::response(['success' => true, 'messages' => ['Batch sent'], 'data' => ['id' => 10]]),
        ]);

        $service = new DocHubService();
        $service->authenticate('user', 'password', 164, 'https://dochub.test');
        $service->createBatchImport([
            'documentTemplateId' => 1141,
            'documentTypeId' => 1089,
            'departmentId' => 33,
            'parameters' => ['{{D.FileName}}', '{{P.Code}}'],
            'rows' => [['FILE1', 'BAOTH']],
        ]);
        $service->sendBatchDocument(10);

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://dochub.test/api/v2/batch-imports/create-advanced'
                && $request['documentTemplateId'] === 1141
                && $request['parameters'][0] === '{{D.FileName}}'
                && $request['rows'][0][1] === 'BAOTH';
        });

        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://dochub.test/api/batch-imports/send/10');
    }
}
