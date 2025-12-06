<?php

namespace Database\Seeders;

use App\Models\UrlMapping;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $jsonPath = storage_path('app/url-mappings.json');
        
        // Try to load from JSON file first, fallback to hard-coded array
        if (File::exists($jsonPath)) {
            $jsonContent = File::get($jsonPath);
            $mappings = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($mappings)) {
                $this->command->warn('Failed to parse JSON file, using fallback data');
                $mappings = $this->getFallbackMappings();
            }
        } else {
            $this->command->info('JSON file not found, using fallback data');
            $mappings = $this->getFallbackMappings();
        }

        foreach ($mappings as $slug => $url) {
            UrlMapping::updateOrCreate(
                ['slug' => $slug],
                ['url' => $url]
            );
        }
    }

    /**
     * Get fallback mappings if JSON file is not available.
     */
    private function getFallbackMappings(): array
    {
        return [
            'wandern' => 'https://t.me/+VRNLJOrG7OwxZWIy',
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
    }
}
