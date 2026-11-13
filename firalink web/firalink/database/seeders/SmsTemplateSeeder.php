<?php

namespace Database\Seeders;

use App\Models\SmsTemplate;
use Illuminate\Database\Seeder;

class SmsTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SmsTemplate::defaultTemplates() as $template) {
            SmsTemplate::updateOrCreate(
                ['key' => $template['key']],
                $template
            );
        }
    }
}
