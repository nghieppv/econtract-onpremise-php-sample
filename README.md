# eContract On-Premise Laravel Sample

Sample Laravel port tu repo .NET, dung cho Dev team tham khao khi tich hop eContract On-Premise.

## Nghiep Vu Bao Phu

1. Xac thuc tai khoan va chon cong ty de lay access token.
2. Tao chung tu tu file PDF bang multipart upload.
3. Tao chung tu hang loat tu document template bang batch import.
4. Cap nhat quy trinh xu ly chung tu.
5. Gui quy trinh xu ly chung tu.
6. Lay danh sach chung tu va waiting process.
7. Xu ly chung tu theo quy trinh: Approve, SignDraw, ESign.
8. Xac nhan OTP khi API tra ve `receiveOtpMethod` khac `-1`.
9. Gui thong bao chung tu hoan tat.
10. Lay lai danh sach chung tu de kiem tra trang thai.

## Cai Dat

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Cap nhat cac bien trong `.env`:

```dotenv
DOCHUB_BASE_URL=https://your-base-api-url
DOCHUB_USERNAME=your_username
DOCHUB_PASSWORD=your_password
DOCHUB_COMPANY_ID=164
DOCHUB_DOCUMENT_TYPE_ID=54
DOCHUB_DEPARTMENT_ID=33
DOCHUB_PROCESS_USER_CODE=BAOTH
```

File PDF mau nam tai `storage/app/samples/sample.pdf`. Neu dung file khac, cap nhat `DOCHUB_DOCUMENT_FILE`.

## Chay Full Flow PDF

```bash
php artisan dochub:sample
```

Neu moi truong yeu cau OTP, command se hoi OTP tren console. Co the truyen san OTP:

```bash
php artisan dochub:sample --otp=123456
```

Chi tao chung tu, cap nhat quy trinh va gui quy trinh, khong xu ly approve/sign:

```bash
php artisan dochub:sample --skip-process
```

## Chay Batch Import Tu Template

```bash
php artisan dochub:sample --batch
```

Tao batch va gui luon quy trinh cho batch:

```bash
php artisan dochub:sample --batch --send-batch
```

Can cau hinh them:

```dotenv
DOCHUB_TEMPLATE_ID=1141
DOCHUB_BATCH_DOCUMENT_TYPE_ID=1089
DOCHUB_BATCH_DEPARTMENT_ID=33
DOCHUB_BATCH_USER_CODE=baoth
DOCHUB_BATCH_ROWS=2
```

## File Chinh

- `app/Services/DocHubService.php`: wrapper endpoint DocHub/eContract On-Premise.
- `app/Console/Commands/RunDocHubSample.php`: flow nghiep vu end-to-end tu .NET sample.
- `app/Support/DocHubPlaceholders.php`: placeholder bat buoc khi import theo lo.
- `config/dochub.php`: mapping `.env` cho thong tin tich hop.
- `tests/Feature/DocHubServiceTest.php`: test payload va endpoint service bang `Http::fake`.
- `tests/Feature/RunDocHubSampleCommandTest.php`: test command full flow khong can server that.

## Chay Test

```bash
php artisan test
```

Test hien tai fake tat ca HTTP request, nen dung de kiem tra code tao dung endpoint, header bearer token va payload truoc khi ket noi moi truong On-Premise that.
