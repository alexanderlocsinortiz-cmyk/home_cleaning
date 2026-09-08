<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CleanerApplicationDocument extends Model
{
    public const TYPE_VALID_ID = 'valid_id';

    public const TYPE_VALID_ID_FRONT = 'valid_id_front';

    public const TYPE_VALID_ID_BACK = 'valid_id_back';

    public const TYPE_BUSINESS_PERMIT = 'business_permit';

    public const TYPE_PAYOUT_ACCOUNT_PROOF = 'payout_account_proof';

    public const TYPE_LABELS = [
        self::TYPE_VALID_ID => 'Valid ID',
        self::TYPE_VALID_ID_FRONT => 'Valid ID front',
        self::TYPE_VALID_ID_BACK => 'Valid ID back',
        self::TYPE_BUSINESS_PERMIT => 'Business permit',
        self::TYPE_PAYOUT_ACCOUNT_PROOF => 'Proof of payout account',
    ];

    protected $fillable = [
        'cleaner_application_id',
        'document_type',
        'original_filename',
        'file_path',
        'mime_type',
        'file_size',
        'uploaded_by',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function cleanerApplication()
    {
        return $this->belongsTo(CleanerApplication::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function documentTypes(): array
    {
        return array_keys(self::TYPE_LABELS);
    }

    public function typeLabel(): string
    {
        return self::TYPE_LABELS[$this->document_type] ?? str($this->document_type)->replace('_', ' ')->title()->toString();
    }

    public function fileSizeLabel(): string
    {
        if ($this->file_size >= 1048576) {
            return number_format($this->file_size / 1048576, 1).' MB';
        }

        return number_format(max(1, $this->file_size) / 1024, 1).' KB';
    }
}
