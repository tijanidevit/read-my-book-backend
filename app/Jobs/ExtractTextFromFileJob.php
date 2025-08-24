<?php

namespace App\Jobs;

use App\Models\UserBook;
use App\Traits\FileTrait;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExtractTextFromFileJob implements ShouldQueue
{
    use Queueable, FileTrait;

    /**
     * Create a new job instance.
     */
    public function __construct(protected UserBook $userBook)
    {}

    /**
     * Execute the job.
     */
    public function handle()
    {
        try {
            $mime = $this->userBook->mime_type;
            $path = $this->userBook->path;

            $extractedText = '';

            if (str_contains($mime, 'pdf')) {
                $extractedText = $this->extractTextFromPdf($path);
            } elseif (str_contains($mime, 'word') || str_contains($mime, 'officedocument')) {
                $extractedText = $this->extractTextFromWord($path);
            } elseif (str_contains($mime, 'image')) {
                $extractedText = $this->extractTextFromImage($path);
            }

            $this->userBook->update([
                'text' => $extractedText,
            ]);
        } catch (Exception $e) {
            Log::error("ExtractTextFromFileJob failed: " . $e->getMessage());
        }
    }
}
