<?php

namespace App\Services;

/**
 * Hard-coded URL mappings.
 * In the future, this will be replaced with database lookups.
 */
class UrlLookupService
{
    private array $urlMappings = [
        'psb' => 'https://t.me/+riKLSWe79YMyMWYy',
        'kothvoice' => 'https://discord.gg/UWagZcG8pj',
        'ntrlkoth' => 'https://discord.gg/TTphyud4PE',
        'jelly' => 'https://t.me/joinchat/UhDoWth1dzlVhv6j',
        '5aa95' => 'http://bit.ly/berlinsocialchat',
        '94d39' => 'https://clockworkbanana.com/berlin-social-chat-groups-community/',
        'd2a94' => 'https://discord.gg/amT2vCbJ',
        'jellydiscord' => 'https://discord.gg/9bfpV2z',
        'american-pro-mazda-cup' => 'https://discord.gg/6s87v5',
        'undiecar' => 'https://discordapp.com/invite/csjKs6z',
        'seacrest' => 'https://discord.gg/QZZB6GZ',
    ];

    public function getUrl(string $slug): ?string
    {
        return $this->urlMappings[$slug] ?? null;
    }

    public function slugExists(string $slug): bool
    {
        return isset($this->urlMappings[$slug]);
    }
}
