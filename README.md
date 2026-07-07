# eContract On-Premise Laravel Sample

Laravel sample ported from the .NET repository for development teams integrating with eContract On-Premise.

## Covered Business Flows

1. Authenticate and select a company to retrieve an access token.
2. Create a document from a PDF file using multipart upload.
3. Create documents in bulk from a document template using batch import.
4. Update the document processing workflow.
5. Send the document processing workflow.
6. Retrieve the document list and the current waiting process.
7. Process documents through the workflow: Approve, SignDraw, and ESign.
8. Confirm OTP when the API returns a `receiveOtpMethod` other than `-1`.
9. Send a notification when document processing is completed.
10. Retrieve the document list again to verify the final status.

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Update these variables in `.env`:

```dotenv
DOCHUB_BASE_URL=https://your-base-api-url
DOCHUB_USERNAME=your_username
DOCHUB_PASSWORD=your_password
DOCHUB_COMPANY_ID=164
DOCHUB_DOCUMENT_TYPE_ID=54
DOCHUB_DEPARTMENT_ID=33
DOCHUB_PROCESS_USER_CODE=BAOTH
```

The sample PDF file is located at `storage/app/samples/sample.pdf`. If you use another file, update `DOCHUB_DOCUMENT_FILE`.

## Run The Full PDF Flow

```bash
php artisan dochub:sample
```

If the environment requires OTP, the command prompts for it in the console. You can also pass the OTP directly:

```bash
php artisan dochub:sample --otp=123456
```

Create the document, update the workflow, and send the workflow without approve/sign processing:

```bash
php artisan dochub:sample --skip-process
```

## Run Batch Import From Template

```bash
php artisan dochub:sample --batch
```

Create the batch and send the batch workflow immediately:

```bash
php artisan dochub:sample --batch --send-batch
```

Additional required configuration:

```dotenv
DOCHUB_TEMPLATE_ID=1141
DOCHUB_BATCH_DOCUMENT_TYPE_ID=1089
DOCHUB_BATCH_DEPARTMENT_ID=33
DOCHUB_BATCH_USER_CODE=baoth
DOCHUB_BATCH_ROWS=2
```

## Main Files

- `app/Services/DocHubService.php`: wrapper endpoint DocHub/eContract On-Premise.
- `app/Console/Commands/RunDocHubSample.php`: end-to-end business flow ported from the .NET sample.
- `app/Support/DocHubPlaceholders.php`: required placeholders for batch import.
- `config/dochub.php`: `.env` mapping for integration settings.
- `tests/Feature/DocHubServiceTest.php`: service endpoint and payload tests using `Http::fake`.
- `tests/Feature/RunDocHubSampleCommandTest.php`: full-flow command test without a real server.

## Run Tests

```bash
php artisan test
```

The tests fake all HTTP requests, so they are useful for verifying endpoints, bearer token headers, and payloads before connecting to a real On-Premise environment.
