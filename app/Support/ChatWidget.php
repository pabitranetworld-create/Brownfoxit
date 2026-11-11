<?php

namespace App\Support;

/**
 * Floating multi-channel chat button (WhatsApp, Messenger, phone …), configured in Admin → চ্যাট বাটন.
 */
class ChatWidget
{
    public const TYPES = [
        'whatsapp' => 'WhatsApp',
        'messenger' => 'Facebook Messenger',
        'phone' => 'Phone call',
        'sms' => 'SMS',
        'email' => 'Email',
        'telegram' => 'Telegram',
        'viber' => 'Viber',
        'instagram' => 'Instagram',
        'link' => 'Other link',
    ];

    protected const LOOK = [
        'whatsapp' => ['fa-brands fa-whatsapp', '#25D366'],
        'messenger' => ['fa-brands fa-facebook-messenger', '#0084FF'],
        'phone' => ['fa-solid fa-phone', '#03E78B'],
        'sms' => ['fa-solid fa-comment-sms', '#FF549C'],
        'email' => ['fa-solid fa-envelope', '#FF485F'],
        'telegram' => ['fa-brands fa-telegram', '#229ED9'],
        'viber' => ['fa-brands fa-viber', '#7360F2'],
        'instagram' => ['fa-brands fa-instagram', '#E1306C'],
        'link' => ['fa-solid fa-link', '#6D5DFC'],
    ];

    /** @return array<int, array{type:string,label:string,url:string,icon:string,color:string,external:bool}> */
    public static function channels(): array
    {
        $out = [];
        foreach ((array) setting('chat.channels', []) as $c) {
            $type = (string) ($c['type'] ?? '');
            $value = trim((string) ($c['value'] ?? ''));
            if (! isset(self::TYPES[$type]) || $value === '' || ! ($url = self::url($type, $value))) {
                continue;
            }
            [$icon, $color] = self::LOOK[$type];
            $out[] = [
                'type' => $type,
                'label' => trim((string) ($c['label'] ?? '')) ?: self::TYPES[$type],
                'url' => $url,
                'icon' => $icon,
                'color' => $color,
                'external' => str_starts_with($url, 'http'),
            ];
        }

        return $out;
    }

    public static function url(string $type, string $v): ?string
    {
        $isUrl = (bool) preg_match('#^https?://#i', $v);
        $digits = preg_replace('/\D+/', '', $v);
        $user = ltrim(preg_replace('#^(https?://)?(www\.)?[^/]+/#i', '', $v), '@/');

        $url = match ($type) {
            'whatsapp' => $isUrl ? $v : ($digits ? 'https://wa.me/'.$digits : null),
            'messenger' => $isUrl ? $v : 'https://m.me/'.rawurlencode($user),
            'phone' => $digits ? 'tel:+'.$digits : null,
            'sms' => $digits ? 'sms:+'.$digits : null,
            'email' => filter_var($v, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$v : null,
            'telegram' => $isUrl ? $v : 'https://t.me/'.rawurlencode($user),
            'viber' => $digits ? 'viber://chat?number=%2B'.$digits : null,
            'instagram' => $isUrl ? $v : 'https://ig.me/m/'.rawurlencode($user),
            'link' => $isUrl ? $v : null,
            default => null,
        };

        return $url;
    }
}
