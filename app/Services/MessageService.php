<?php

namespace App\Services;

use App\Enums\MessageLogStatus;
use App\Models\MessageLog;
use App\Models\MessageTemplate;
use App\Models\Patient;
use Illuminate\Support\Facades\Log;

class MessageService
{
    /**
     * Send a message to a patient using a template.
     * Returns the created MessageLog record.
     * Actual delivery is attempted immediately; for production, dispatch a queued job instead.
     */
    public function send(Patient $patient, MessageTemplate $template, array $vars = []): MessageLog
    {
        $phone = $patient->phone ?? '';

        $vars = array_merge([
            'patient_name' => $patient->full_name,
            'clinic_name'  => config('app.name', 'Nha khoa'),
            'date'         => now()->format('d/m/Y'),
        ], $vars);

        $content = $template->render($vars);

        $log = MessageLog::create([
            'template_id'  => $template->id,
            'patient_id'   => $patient->id,
            'channel'      => $template->channel->value,
            'phone'        => $phone,
            'content_sent' => $content,
            'status'       => MessageLogStatus::Pending,
        ]);

        try {
            $this->deliver($log, $template->channel->value, $phone, $content);
            $log->update(['status' => MessageLogStatus::Sent, 'sent_at' => now()]);
        } catch (\Throwable $e) {
            Log::error('MessageService delivery failed', ['log_id' => $log->id, 'error' => $e->getMessage()]);
            $log->update(['status' => MessageLogStatus::Failed, 'error_message' => $e->getMessage()]);
        }

        return $log;
    }

    private function deliver(MessageLog $log, string $channel, string $phone, string $content): void
    {
        // Read credentials from settings table (set via admin settings UI)
        $setting = fn(string $key) => \App\Models\Setting::where('key', $key)->value('value');

        if ($channel === 'sms') {
            $apiKey    = $setting('esms_api_key');
            $secretKey = $setting('esms_secret_key');
            $brandName = $setting('esms_brand_name') ?? 'Baotrixemay';

            if (!$apiKey || !$secretKey) {
                throw new \RuntimeException('ESMS credentials chưa được cấu hình trong Settings.');
            }

            $response = \Illuminate\Support\Facades\Http::get('https://rest.esms.vn/MainService.svc/json/SendMultipleMessage_V4_get', [
                'ApiKey'    => $apiKey,
                'SecretKey' => $secretKey,
                'Phone'     => $phone,
                'Content'   => $content,
                'SmsType'   => 2,
                'Brandname' => $brandName,
            ]);

            $body = $response->json();
            if (($body['CodeResult'] ?? '') !== '100') {
                throw new \RuntimeException('ESMS error: ' . ($body['ErrorMessage'] ?? 'unknown'));
            }
        } elseif ($channel === 'zalo') {
            $token = $setting('zalo_oa_token');
            if (!$token) {
                throw new \RuntimeException('Zalo OA token chưa được cấu hình trong Settings.');
            }

            $response = \Illuminate\Support\Facades\Http::withToken($token)
                ->post('https://openapi.zalo.me/v2.0/oa/message', [
                    'recipient' => ['user_id' => $phone],
                    'message'   => ['text' => $content],
                ]);

            $body = $response->json();
            if (($body['error'] ?? 0) !== 0) {
                throw new \RuntimeException('Zalo error: ' . ($body['message'] ?? 'unknown'));
            }
        }
    }
}
