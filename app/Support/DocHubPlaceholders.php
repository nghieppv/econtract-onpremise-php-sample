<?php

namespace App\Support;

final class DocHubPlaceholders
{
    public const FILE_NAME = '{{D.FileName}}';
    public const NO = '{{D.No}}';
    public const SUBJECT = '{{D.Subject}}';
    public const EXPIRY_DATE = '{{D.ExpiryDate}}';
    public const DESCRIPTION = '{{D.Description}}';
    public const IS_ORDER = '{{D.IsOrder}}';

    public const PROCESS_USER_CODE = '{{P.Code}}';
    public const PROCESS_ACCESS_PERMISSION = '{{P.AccessPermission}}';

    public static function requiredDocumentColumns(): array
    {
        return [
            self::FILE_NAME,
            self::NO,
            self::SUBJECT,
            self::EXPIRY_DATE,
            self::DESCRIPTION,
            self::IS_ORDER,
        ];
    }

    public static function processColumns(int $numberOfProcessUsers): array
    {
        $columns = [];

        for ($i = 0; $i < $numberOfProcessUsers; $i++) {
            $columns[] = self::PROCESS_USER_CODE;
            $columns[] = self::PROCESS_ACCESS_PERMISSION;
        }

        return $columns;
    }
}
