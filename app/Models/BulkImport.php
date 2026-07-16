<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkImport extends Model
{
    protected $fillable = [
        'type', 'original_filename', 'stored_path', 'status',
        'total_rows', 'processed_rows', 'created_count', 'skipped_count',
        'errors', 'user_id', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'errors'      => 'array',
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function progressPercent(): int
    {
        if (! $this->total_rows) {
            return $this->status === 'completed' ? 100 : 0;
        }

        return (int) min(100, round($this->processed_rows / $this->total_rows * 100));
    }
}
