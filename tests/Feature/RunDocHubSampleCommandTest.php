<?php

namespace Tests\Feature;

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RunDocHubSampleCommandTest extends TestCase
{
    public function test_sample_command_runs_full_pdf_flow_with_http_fake(): void
    {
        config()->set('dochub.base_url', 'https://dochub.test');
        config()->set('dochub.document.file_path', base_path('storage/app/samples/sample.pdf'));

        Http::fake([
            'https://dochub.test/api/auth/password-login' => Http::response(['success' => true, 'messages' => ['Login ok'], 'data' => 'token']),
            'https://dochub.test/api/documents/create' => Http::response(['success' => true, 'messages' => ['Created'], 'data' => ['id' => 'doc-1', 'no' => 'ABCD']]),
            'https://dochub.test/api/documents/update-process' => Http::response([
                'success' => true,
                'messages' => ['Updated'],
                'data' => [
                    'id' => 'doc-1',
                    'processes' => [
                        ['id' => 'process-approve'],
                        ['id' => 'process-signdraw'],
                        ['id' => 'process-esign'],
                    ],
                ],
            ]),
            'https://dochub.test/api/documents/send-process/doc-1' => Http::response(['success' => true, 'messages' => ['Sent'], 'data' => ['id' => 'doc-1']]),
            'https://dochub.test/api/documents?*' => Http::response(['success' => true, 'messages' => ['Found'], 'data' => ['items' => [[
                'waitingProcess' => ['id' => 'process-approve'],
                'status' => ['value' => 3, 'description' => 'Completed'],
            ]]]]),
            'https://dochub.test/api/documents/process' => Http::response(['success' => true, 'messages' => ['Processed'], 'data' => ['receiveOtpMethod' => -1]]),
            'https://dochub.test/api/documents/send-notify/doc-1' => Http::response(['success' => true, 'messages' => ['Notified'], 'data' => ['id' => 'doc-1']]),
        ]);

        $this->artisan('dochub:sample')
            ->expectsOutputToContain('[1] Authenticate + select company')
            ->assertSuccessful();

        Http::assertSentCount(10);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://dochub.test/api/documents/process' && $request['processId'] === 'process-esign');
    }
}
