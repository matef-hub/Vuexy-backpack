<?php

namespace Database\Seeders;

use App\Models\CompanyDocument;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CompanyDocumentsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = fake();
        $userIds = User::query()->pluck('id')->all();
        $docTypes = ['رخصة تجارية', 'سجل ضريبي', 'شهادة تأمين', 'عقد', 'تصريح بلدي'];

        for ($i = 0; $i < 20; $i++) {
            $issueDate = Carbon::instance($faker->dateTimeBetween('-3 years', 'now'))->toDateString();
            $endDate = $faker->boolean(70)
                ? Carbon::instance($faker->dateTimeBetween($issueDate, '+2 years'))->toDateString()
                : null;

            $status = 'active';
            if ($endDate !== null && Carbon::parse($endDate)->isPast()) {
                $status = 'expired';
            } elseif ($faker->boolean(20)) {
                $status = 'archived';
            }

            $createdBy = !empty($userIds) ? $faker->randomElement($userIds) : null;
            $updatedBy = !empty($userIds) ? $faker->randomElement($userIds) : null;

            CompanyDocument::create([
                'docname' => $faker->sentence(3),
                'doc_number' => $faker->bothify('DOC-####-??'),
                'doc_type' => $faker->randomElement($docTypes),
                'doc_issue_date' => $issueDate,
                'doc_end_date' => $endDate,
                'doc_file' => null,
                'doc_original_name' => null,
                'doc_mime' => null,
                'doc_size' => null,
                'status' => $status,
                'notes' => $faker->boolean(60) ? $faker->sentence(10) : null,
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
            ]);
        }
    }
}

