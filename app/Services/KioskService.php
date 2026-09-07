<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Port từ backend Python (FastAPI) sang Laravel.
 * Giữ nguyên logic: fuzzy match trước, LLM sau; chuẩn hóa tiếng Việt; lọc ảo giác STT.
 *
 * LƯU Ý QUAN TRỌNG:
 * Provider "local" (faster-whisper chạy offline trên máy) KHÔNG có thư viện
 * tương đương trong PHP. Nếu .env gốc không đặt PROVIDER=local (mặc định là
 * "openai") thì không ảnh hưởng gì. Nếu có dùng "local", cần giữ lại 1 service
 * Python riêng chỉ cho STT, Laravel gọi sang qua HTTP nội bộ.
 * Provider "fpt" (FPT.AI) đã port đầy đủ vì chỉ là 1 API call.
 */
class KioskService
{
    protected array $providers;
    protected string $defaultProvider;
    protected string $matchProvider;

    /** Các câu Whisper hay "ảo giác" khi audio im lặng/toàn tiếng ồn (đã bỏ dấu) */
    protected array $hallucinationPatterns = [
        'subscribe',
        'dang ky kenh',
        'ghien mi go',
        'cam on cac ban da theo doi',
        'cam on cac ban da xem',
        'hen gap lai cac ban',
        'chuc cac ban xem video vui ve',
        'nho like va dang ky',
        'cam on va hen gap lai',
        'xin chao va hen gap lai',
    ];

    public function __construct()
    {
        $this->defaultProvider = strtolower(config('kiosk.default_provider', 'openai'));
        $this->matchProvider = strtolower(config('kiosk.match_provider', $this->defaultProvider));

        $this->providers = [
            'openai' => [
                'api_key' => config('kiosk.openai.api_key'),
                'base_url' => 'https://api.openai.com/v1',
                'stt_model' => config('kiosk.openai.stt_model', 'whisper-1'),
                'match_model' => config('kiosk.openai.match_model', 'gpt-4o-mini'),
            ],
            'groq' => [
                'api_key' => config('kiosk.groq.api_key'),
                'base_url' => 'https://api.groq.com/openai/v1',
                'stt_model' => config('kiosk.groq.stt_model', 'whisper-large-v3-turbo'),
                'match_model' => config('kiosk.groq.match_model', 'llama-3.3-70b-versatile'),
            ],
        ];
    }

    public function loadProjects(): array
    {
        $path = storage_path('app/projects.json');
        if (!file_exists($path)) {
            return [];
        }
        return json_decode(file_get_contents($path), true) ?? [];
    }

    protected function sttPrompt(): string
    {
        $names = collect($this->loadProjects())->pluck('name')->implode(', ');
        return "Khách tham quan triển lãm nói tên chủ đề muốn xem. Các chủ đề: {$names}.";
    }

    // ============ Chuẩn hóa + fuzzy matching tiếng Việt ============

    /** Bỏ dấu, thường hóa, bỏ ký tự lạ: 'Trường học Số!' -> 'truong hoc so' */
    public function normalizeVi(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace('đ', 'd', $s);
        $s = Str::ascii($s); // bỏ dấu tiếng Việt (tương đương unicodedata NFD + strip combining marks)
        $s = preg_replace('/[^a-z0-9 ]+/', ' ', $s);
        return trim(preg_replace('/\s+/', ' ', $s));
    }

    /** Lọc kết quả STT rác/ảo giác. Trả về '' nếu là ảo giác. */
    public function cleanSttText(string $text): string
    {
        $norm = $this->normalizeVi($text);
        if ($norm === '') {
            return '';
        }
        if (mb_strlen($norm) <= 100) {
            foreach ($this->hallucinationPatterns as $p) {
                if (str_contains($norm, $p)) {
                    return '';
                }
            }
        }
        return trim($text);
    }

    /**
     * So khớp mờ câu nói với tên + aliases của từng chủ đề (không cần LLM).
     * Trả về [project|null, score 0..1].
     */
    public function fuzzyMatch(string $text, array $projects): array
    {
        $q = $this->normalizeVi($text);
        if ($q === '') {
            return [null, 0.0];
        }
        $qTokens = explode(' ', $q);
        $qTokenSet = array_flip($qTokens);

        $best = null;
        $bestScore = 0.0;

        foreach ($projects as $p) {
            $candidates = array_merge([$p['name']], $p['aliases'] ?? []);
            foreach ($candidates as $c) {
                $n = $this->normalizeVi($c);
                if ($n === '') {
                    continue;
                }
                $nTokens = explode(' ', $n);
                $score = $this->similarity($q, $n);

                // Khớp theo TỪ TRỌN VẸN (token), không phải substring ký tự thô — tránh
                // alias ngắn như "mỏ" tình cờ khớp bên trong từ dài hơn như "môi trường".
                if (mb_strlen($n) >= 4 && count($nTokens) > 0 && $this->allTokensIn($nTokens, $qTokenSet)) {
                    $score = max($score, 0.93);
                }

                // phần lớn từ của tên chủ đề xuất hiện trong câu nói
                if (mb_strlen($n) >= 4 && count($nTokens) > 0) {
                    $matchCount = 0;
                    foreach ($nTokens as $t) {
                        if (isset($qTokenSet[$t])) {
                            $matchCount++;
                        }
                    }
                    if ($matchCount / count($nTokens) >= 0.8) {
                        $score = max($score, 0.86);
                    }
                }

                if ($score > $bestScore) {
                    $best = $p;
                    $bestScore = $score;
                }
            }
        }

        return [$best, $bestScore];
    }

    protected function allTokensIn(array $tokens, array $tokenSet): bool
    {
        foreach ($tokens as $t) {
            if (!isset($tokenSet[$t])) {
                return false;
            }
        }
        return true;
    }

    /** Tương đương SequenceMatcher.ratio() của Python (dùng similar_text của PHP) */
    protected function similarity(string $a, string $b): float
    {
        similar_text($a, $b, $percent);
        return $percent / 100;
    }

    // ============ STT ============

    /**
     * @throws \RuntimeException
     */
    public function transcribe(string $binaryData, string $filename, ?string $provider): array
    {
        $name = strtolower($provider ?: $this->defaultProvider);

        if ($name === 'fpt') {
            $text = $this->fptTranscribe($binaryData);
            $text = $this->cleanSttText($text);
            return ['text' => $text, 'provider' => $name];
        }

        if ($name === 'local') {
            throw new \RuntimeException(
                "Provider 'local' (faster-whisper offline) chưa được hỗ trợ ở bản Laravel — " .
                "cần giữ 1 microservice Python riêng cho STT offline. Dùng 'openai', 'groq' hoặc 'fpt'."
            );
        }

        [$cfg] = $this->getApiProvider($name);

        $response = Http::withToken($cfg['api_key'])
            ->attach('file', $binaryData, $filename ?: 'speech.webm')
            ->asMultipart()
            ->post("{$cfg['base_url']}/audio/transcriptions", [
                ['name' => 'model', 'contents' => $cfg['stt_model']],
                ['name' => 'language', 'contents' => 'vi'],
                ['name' => 'prompt', 'contents' => $this->sttPrompt()],
            ]);

        if ($response->failed()) {
            throw new \RuntimeException("Lỗi STT ({$name}): " . $response->body());
        }

        $text = $this->cleanSttText(trim($response->json('text', '')));
        return ['text' => $text, 'provider' => $name];
    }

    protected function fptTranscribe(string $data): string
    {
        $apiKey = config('kiosk.fpt.api_key');
        if (!$apiKey) {
            throw new \RuntimeException("Thiếu FPT_API_KEY trong .env");
        }

        // FPT.AI chỉ nhận wav 16kHz mono — nếu audio gốc không phải wav,
        // cần convert trước (ví dụ qua ffmpeg binary trên server, xem ghi chú README).
        $response = Http::withHeaders(['api_key' => $apiKey])
            ->withBody($data, 'application/octet-stream')
            ->post('https://api.fpt.ai/hmi/asr/general');

        if ($response->failed()) {
            throw new \RuntimeException("Lỗi gọi FPT.AI: " . $response->body());
        }

        $hyps = $response->json('hypotheses', []);
        return trim($hyps[0]['utterance'] ?? '');
    }

    // ============ Match (LLM) ============

    public function matchProject(string $text, ?string $provider): array
    {
        $text = trim($text);
        if ($text === '') {
            return ['project' => null, 'heard' => $text];
        }

        $projects = $this->loadProjects();

        // Bước 1: fuzzy match (nhanh, offline, 0 đồng)
        [$fzProject, $fzScore] = $this->fuzzyMatch($text, $projects);
        if ($fzProject && $fzScore >= 0.82) {
            return [
                'project' => $fzProject,
                'heard' => $text,
                'provider' => 'fuzzy',
                'score' => round($fzScore, 2),
            ];
        }

        // Bước 2: LLM
        $name = strtolower($provider ?: $this->defaultProvider);
        if (in_array($name, ['local', 'fpt'], true)) {
            $name = $this->matchProvider;
        }

        [$cfg] = $this->getApiProvider($name);

        $catalog = collect($projects)->map(fn ($p) => [
            'id' => $p['id'],
            'name' => $p['name'],
            'description' => $p['description'] ?? '',
            'aliases' => $p['aliases'] ?? [],
        ])->values()->all();

        $systemPrompt = "Bạn là bộ định tuyến cho một kiosk triển lãm khoa học công nghệ. " .
            "Người dùng nói tên (hoặc mô tả) chủ đề họ muốn xem; câu nói được chuyển từ giọng nói " .
            "nên có thể sai chính tả hoặc sai dấu tiếng Việt. " .
            "Dựa vào danh sách chủ đề dưới đây, hãy chọn chủ đề khớp nhất.\n\n" .
            "DANH SÁCH CHỦ ĐỀ (JSON):\n" . json_encode($catalog, JSON_UNESCAPED_UNICODE) . "\n\n" .
            'Trả về DUY NHẤT một JSON object dạng {"project_id": "<id>"} ' .
            'hoặc {"project_id": null} nếu không có chủ đề nào phù hợp.';

        try {
            $response = Http::withToken($cfg['api_key'])
                ->post("{$cfg['base_url']}/chat/completions", [
                    'model' => $cfg['match_model'],
                    'temperature' => 0,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $text],
                    ],
                ]);

            if ($response->failed()) {
                throw new \RuntimeException($response->body());
            }

            $content = $response->json('choices.0.message.content');
            $result = json_decode($content, true);
            $projectId = $result['project_id'] ?? null;
        } catch (\Throwable $e) {
            // LLM lỗi/mất mạng: nếu fuzzy đã có kết quả tạm ổn thì dùng luôn.
            if ($fzProject && $fzScore >= 0.72) {
                return [
                    'project' => $fzProject,
                    'heard' => $text,
                    'provider' => 'fuzzy-fallback',
                    'score' => round($fzScore, 2),
                ];
            }
            throw new \RuntimeException("Lỗi match ({$name}): " . $e->getMessage());
        }

        $project = collect($projects)->firstWhere('id', $projectId);

        return ['project' => $project, 'heard' => $text, 'provider' => $name];
    }

    protected function getApiProvider(string $name): array
    {
        if (!isset($this->providers[$name])) {
            throw new \InvalidArgumentException("Provider không hỗ trợ: {$name}");
        }
        $cfg = $this->providers[$name];
        if (!$cfg['api_key']) {
            throw new \RuntimeException("Thiếu API key trong .env cho provider '{$name}'");
        }
        return [$cfg];
    }
}
