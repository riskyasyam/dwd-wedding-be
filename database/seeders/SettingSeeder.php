<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['key' => 'company_name', 'value' => 'DWD Wedding Organizer'],
            ['key' => 'company_tagline', 'value' => 'Your Dream Wedding Decorator'],
            ['key' => 'address', 'value' => 'Jl. Wedding Paradise No. 123, Jakarta Selatan'],
            ['key' => 'phone', 'value' => '+62 812-3456-7890'],
            ['key' => 'email', 'value' => 'info@dwdecor.co.id'],
            ['key' => 'whatsapp_link', 'value' => 'https://wa.me/6281234567890'],
            ['key' => 'social_facebook', 'value' => 'https://facebook.com/dwdecor'],
            ['key' => 'social_instagram', 'value' => 'https://instagram.com/dwdecor'],
            ['key' => 'social_tiktok', 'value' => 'https://tiktok.com/@dwdecor'],
            ['key' => 'social_youtube', 'value' => 'https://youtube.com/@dwdecor'],
            ['key' => 'about_us', 'value' => 'DWD Wedding Organizer adalah layanan wedding organizer profesional yang menyediakan dekorasi pernikahan dan informasi lengkap untuk kebutuhan pernikahan Anda.'],
            ['key' => 'business_hours', 'value' => 'Senin - Jumat: 09:00 - 18:00 WIB, Sabtu: 09:00 - 15:00 WIB'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}
