<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Tương đương generate_greeting.py — chạy 1 lần để tạo file media/greeting.mp3
 * bằng OpenAI TTS.
 *
 *   php artisan kiosk:generate-greeting
 */
class GenerateGreeting extends Command
{
    protected $signature = 'kiosk:generate-greeting';
    protected $description = 'Tạo file media/greeting.mp3 bằng OpenAI TTS';

    public function handle(): int
    {
        $apiKey = config('kiosk.openai.api_key');
        if (!$apiKey) {
            $this->error('Thiếu OPENAI_API_KEY trong .env');
            return self::FAILURE;
        }

        $response = Http::withToken($apiKey)
            ->post('https://api.openai.com/v1/audio/speech', [
                'model' => 'gpt-4o-mini-tts',
                'voice' => 'alloy',
                'input' => 'Xin chào bạn, bạn muốn xem dự án gì?',
            ]);

        if ($response->failed()) {
            $this->error('Lỗi gọi OpenAI TTS: ' . $response->body());
            return self::FAILURE;
        }

        $path = public_path('media/greeting.mp3');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }
        file_put_contents($path, $response->body());

        $this->info("Đã tạo {$path}");
        return self::SUCCESS;
    }
}
