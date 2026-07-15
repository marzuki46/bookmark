<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ResendMailService
{
    private string $apiKey;

    private string $fromEmail;

    private string $fromName;

    public function __construct()
    {
        $this->apiKey = config('services.resend.key', '');
        $this->fromEmail = config('mail.from.address', 'ohmjuki@gmail.com');
        $this->fromName = config('mail.from.name', 'Knowledge Hub');
    }

    public function send(string $to, string $subject, string $htmlBody): bool
    {
        if (empty($this->apiKey)) {
            Log::warning('Resend API key not configured.');

            return false;
        }

        $fromEmail = config('services.resend.from_email', 'onboarding@resend.dev');
        $fromName = config('services.resend.from_name', $this->fromName);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from' => $fromName.' <'.$fromEmail.'>',
                'to' => [$to],
                'subject' => $subject,
                'html' => $htmlBody,
            ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('Resend API error: '.$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('Resend mail failed: '.$e->getMessage());

            return false;
        }
    }

    public function sendFailedLoginAlert(string $email, string $ip, string $userAgent, int $attemptCount): bool
    {
        $subject = "[Knowledge Hub] Login Gagal #{$attemptCount} terdeteksi";

        $html = $this->buildFailedLoginAlertHtml($email, $ip, $userAgent, $attemptCount);

        return $this->send($email, $subject, $html);
    }

    public function sendIpBlockedAlert(string $email, string $ip, string $reason): bool
    {
        $subject = "[Knowledge Hub] IP {$ip} telah diblokir";

        $html = $this->buildIpBlockedAlertHtml($email, $ip, $reason);

        return $this->send($email, $subject, $html);
    }

    public function sendPasswordChangeRequest($user, string $type, string $approveUrl, string $rejectUrl, ?string $toEmail = null): bool
    {
        $typeLabel = match ($type) {
            'password' => 'Password',
            'pin' => 'PIN',
            'email' => 'Email',
            default => ucfirst($type),
        };

        $subject = "[Knowledge Hub] Konfirmasi Perubahan {$typeLabel}";
        $html = $this->buildPasswordChangeRequestHtml($user, $type, $typeLabel, $approveUrl, $rejectUrl);

        return $this->send($toEmail ?? $user->email, $subject, $html);
    }

    private function buildPasswordChangeRequestHtml($user, string $type, string $typeLabel, string $approveUrl, string $rejectUrl): string
    {
        $typeBadgeColor = match ($type) {
            'password' => '#2563eb',
            'pin' => '#d97706',
            'email' => '#7c3aed',
            default => '#64748b',
        };

        return '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f1f5f9; margin: 0; padding: 40px;">
  <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
    <div style="background: linear-gradient(135deg, #2563eb, #1d4ed8); padding: 32px; text-align: center;">
      <div style="font-size: 48px; margin-bottom: 8px;">&#x1F512;</div>
      <h1 style="color: #fff; margin: 0; font-size: 22px;">Konfirmasi Perubahan '.$typeLabel.'</h1>
    </div>
    <div style="padding: 32px;">
      <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">Hai <strong>'.e($user->name).'</strong>,</p>
      <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">Kamu meminta perubahan <span style="display: inline-block; background: '.$typeBadgeColor.'; color: #fff; font-size: 12px; font-weight: 600; padding: 2px 10px; border-radius: 12px;">'.$typeLabel.'</span> pada akun Knowledge Hub kamu.</p>

      <div style="background: #f8fafc; border-radius: 8px; padding: 16px; margin: 20px 0;">
        <p style="color: #64748b; font-size: 13px; margin: 0;">Email: <strong style="color: #1e293b;">'.e($user->email).'</strong></p>
        <p style="color: #64748b; font-size: 13px; margin: 4px 0 0;">Tipe: <strong style="color: #1e293b;">Perubahan '.$typeLabel.'</strong></p>
      </div>

      <div style="text-align: center; margin: 28px 0;">
        <a href="'.$approveUrl.'" style="display: inline-block; background: #16a34a; color: #fff; font-size: 15px; font-weight: 600; padding: 14px 40px; border-radius: 8px; text-decoration: none; margin-right: 8px;">Ya, Ubah '.$typeLabel.'</a>
        <a href="'.$rejectUrl.'" style="display: inline-block; background: #dc2626; color: #fff; font-size: 15px; font-weight: 600; padding: 14px 40px; border-radius: 8px; text-decoration: none;">Batal</a>
      </div>

      <div style="background: #fffbeb; border-left: 4px solid #f59e0b; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
        <p style="color: #92400e; font-size: 13px; margin: 0;">Link ini akan kedaluwarsa dalam 24 jam. Jika kamu tidak melakukan permintaan ini, abaikan email ini atau klik "Batal" dan segera ubah password kamu.</p>
      </div>
    </div>
    <div style="background: #f8fafc; padding: 16px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
      <p style="color: #94a3b8; font-size: 12px; margin: 0;">Knowledge Hub &mdash; Security Confirmation</p>
    </div>
  </div>
</body>
</html>';
    }

    private function buildFailedLoginAlertHtml(string $email, string $ip, string $userAgent, int $attemptCount): string
    {
        return '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f1f5f9; margin: 0; padding: 40px;">
  <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
    <div style="background: linear-gradient(135deg, #dc2626, #b91c1c); padding: 32px; text-align: center;">
      <div style="font-size: 48px; margin-bottom: 8px;">&#x1F6A8;</div>
      <h1 style="color: #fff; margin: 0; font-size: 22px;">Login Gagal Terdeteksi</h1>
    </div>
    <div style="padding: 32px;">
      <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">Akun kamu (<strong>'.e($email).'</strong>) mengalami <strong>'.e($attemptCount).'x percobaan login gagal</strong>.</p>
      <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr><td style="padding: 10px 12px; color: #64748b; font-size: 13px; border-bottom: 1px solid #f1f5f9;">IP Address</td><td style="padding: 10px 12px; color: #1e293b; font-size: 13px; font-weight: 600; border-bottom: 1px solid #f1f5f9;">'.e($ip).'</td></tr>
        <tr><td style="padding: 10px 12px; color: #64748b; font-size: 13px; border-bottom: 1px solid #f1f5f9;">User Agent</td><td style="padding: 10px 12px; color: #1e293b; font-size: 13px; word-break: break-all; border-bottom: 1px solid #f1f5f9;">'.e($userAgent).'</td></tr>
        <tr><td style="padding: 10px 12px; color: #64748b; font-size: 13px;">Waktu</td><td style="padding: 10px 12px; color: #1e293b; font-size: 13px; font-weight: 600;">'.now()->format('d M Y H:i:s').'</td></tr>
      </table>
      <div style="background: #fef2f2; border-left: 4px solid #dc2626; padding: 12px 16px; margin: 20px 0; border-radius: 0 8px 8px 0;">
        <p style="color: #991b1b; font-size: 13px; margin: 0;">Jika ini bukan kamu, segera ubah password di Settings atau blokir IP ini melalui dashboard.</p>
      </div>
    </div>
    <div style="background: #f8fafc; padding: 16px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
      <p style="color: #94a3b8; font-size: 12px; margin: 0;">Knowledge Hub &mdash; Auto-generated security alert</p>
    </div>
  </div>
</body>
</html>';
    }

    private function buildIpBlockedAlertHtml(string $email, string $ip, string $reason): string
    {
        return '
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif; background: #f1f5f9; margin: 0; padding: 40px;">
  <div style="max-width: 500px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.08);">
    <div style="background: linear-gradient(135deg, #f59e0b, #d97706); padding: 32px; text-align: center;">
      <div style="font-size: 48px; margin-bottom: 8px;">&#x1F6E1;</div>
      <h1 style="color: #fff; margin: 0; font-size: 22px;">IP Telah Diblokir</h1>
    </div>
    <div style="padding: 32px;">
      <p style="color: #334155; font-size: 15px; margin: 0 0 16px;">IP berikut telah diblokir dari akses login:</p>
      <table style="width: 100%; border-collapse: collapse; margin: 16px 0;">
        <tr><td style="padding: 10px 12px; color: #64748b; font-size: 13px; border-bottom: 1px solid #f1f5f9;">IP Address</td><td style="padding: 10px 12px; color: #1e293b; font-size: 13px; font-weight: 600; border-bottom: 1px solid #f1f5f9;">'.e($ip).'</td></tr>
        <tr><td style="padding: 10px 12px; color: #64748b; font-size: 13px; border-bottom: 1px solid #f1f5f9;">Alasan</td><td style="padding: 10px 12px; color: #1e293b; font-size: 13px; border-bottom: 1px solid #f1f5f9;">'.e($reason).'</td></tr>
        <tr><td style="padding: 10px 12px; color: #64748b; font-size: 13px;">Waktu</td><td style="padding: 10px 12px; color: #1e293b; font-size: 13px; font-weight: 600;">'.now()->format('d M Y H:i:s').'</td></tr>
      </table>
    </div>
    <div style="background: #f8fafc; padding: 16px 32px; text-align: center; border-top: 1px solid #e2e8f0;">
      <p style="color: #94a3b8; font-size: 12px; margin: 0;">Knowledge Hub &mdash; Auto-generated security alert</p>
    </div>
  </div>
</body>
</html>';
    }
}
