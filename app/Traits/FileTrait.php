<?php

namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use App\Models\UserBook;
use App\Jobs\ExtractTextFromFileJob;
use PhpOffice\PhpWord\Writer\HTML;

trait FileTrait
{

    public function uploadBookFile($file)
    {
        $folder = 'books';
        $mimeType = $file->getMimeType();
        $extension = $file->getClientOriginalExtension();
        $randomName = time() . "_" . Str::random(10) . "." . $extension;

        $path = $file->storeAs("{$folder}", $randomName, 'public');

        $path = Storage::url($path);

        $userBook = UserBook::create([
            'user_id' => auth()->id(),
            'title' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
            'mime_type' => $mimeType,
            'path' => public_path($path),
            'full_link' => url($path),
            'last_read_at' => now(),
        ]);

        ExtractTextFromFileJob::dispatch($userBook);

        return $userBook;
    }


    public function uploadMultipleFile($folder, $files)
    {
        $filePaths = [];
        foreach ($files as $file) {
            $filePaths[] = $this->uploadFile($folder, $file);
        }
        return $filePaths;
    }

    public function extractTextFromPdf(string $filePath): ?string
    {
        try {
            if (!file_exists($filePath)) {
                throw new Exception("File does not exist at path: {$filePath}");
            }

            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
        } catch (Exception $e) {
            Log::error("PDF Extraction Failed: " . $e->getMessage());
            return null;
        }
    }

    // public function extractTextFromWord(string $filePath): ?string
    // {
    //     try {
    //         if (!file_exists($filePath)) {
    //             throw new Exception("File does not exist at path: {$filePath}");
    //         }

    //         $phpWord = IOFactory::load($filePath, 'Word2007');

    //         // Use HTML writer
    //         $writer = new HTML($phpWord);
    //         ob_start();
    //         $writer->save('php://output');
    //         $html = ob_get_clean();

    //         // Remove ALL HTML tags including style and script
    //         $text = strip_tags($html);

    //         // Remove CSS content that might have slipped through
    //         $text = preg_replace('/\{.*?\}/', '', $text); // Remove CSS blocks like {color: #000;}
    //         $text = preg_replace('/[a-zA-Z\-]+:\s*[^;]+;?/', '', $text); // Remove CSS properties

    //         // Decode HTML entities
    //         $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

    //         // Clean up
    //         $text = preg_replace('/\s+/', ' ', $text);
    //         $text = preg_replace('/(\n\s*){2,}/', "\n\n", $text);
    //         $text = trim($text);

    //         return $text;
    //     } catch (Exception $e) {
    //         Log::error("Word Extraction Failed: " . $e->getMessage());
    //         return null;
    //     }
    // }


    public function extractTextFromWord(string $filePath): ?string
{
    try {
        if (!file_exists($filePath)) {
            throw new Exception("File does not exist at path: {$filePath}");
        }

        $phpWord = IOFactory::load($filePath, 'Word2007');

        // Use HTML writer to convert document to HTML
        $writer = new HTML($phpWord);

        // Capture the output
        ob_start();
        $writer->save('php://output');
        $html = ob_get_clean();

        // Remove CSS styles (everything between <style> tags)
        $html = preg_replace('/<style.*?>.*?<\/style>/si', '', $html);

        // Remove all HTML tags but preserve line breaks by converting them to newlines
        $text = strip_tags($html);

        // Decode HTML entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        // Clean up the text
        $text = $this->cleanExtractedText($text);

        return $text;
    } catch (Exception $e) {
        Log::error("Word Extraction Failed: " . $e->getMessage());
        return null;
    }
}

    private function cleanExtractedText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/(\n\s*){2,}/', "\n\n", $text);

        $lines = explode("\n", $text);
        $lines = array_map('trim', $lines);

        $lines = array_filter($lines, function($line) {
            return !empty(trim($line));
        });

        $text = implode("\n", $lines);

        $text = str_replace('PHPWord', '', $text);

        return trim($text);
    }

    public function extractTextFromImage($filePath)
    {
        // Replace with your free key from https://ocr.space/ocrapi
        $apiKey = env('OCR_SPACE_API_KEY', 'helloworld');

        $response = Http::attach(
            'file', fopen($filePath, 'r')
        )->post('https://api.ocr.space/parse/image', [
            'apikey' => $apiKey,
            'language' => 'eng',
        ]);

        if ($response->successful() && isset($response['ParsedResults'][0]['ParsedText'])) {
            return $response['ParsedResults'][0]['ParsedText'];
        }

        return null; // or throw exception/log
    }
}
