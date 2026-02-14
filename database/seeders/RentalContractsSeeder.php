<?php

namespace Database\Seeders;

use App\Models\RentalContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class RentalContractsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = fake();
        $userIds = User::query()->pluck('id')->all();
        $entityTypes = ['individual', 'company', 'sole_proprietorship'];
        $statuses = ['draft', 'active', 'expired', 'terminated'];

        for ($i = 1; $i <= 20; $i++) {
            $startDate = Carbon::instance($faker->dateTimeBetween('-24 months', '+3 months'));
            $durationMonths = $faker->numberBetween(6, 36);
            $endDate = (clone $startDate)->addMonths($durationMonths);
            $year = $startDate->year;

            $existingCountForYear = RentalContract::withTrashed()
                ->where('contract_number', 'like', sprintf('RC-%d-%%', $year))
                ->count();
            $contractNumber = sprintf('RC-%d-%06d', $year, $existingCountForYear + 1);

            $status = $faker->randomElement($statuses);
            if ($endDate->isPast()) {
                $status = $faker->randomElement(['expired', 'terminated']);
            }

            $createdBy = !empty($userIds) ? $faker->randomElement($userIds) : null;
            $updatedBy = !empty($userIds) ? $faker->randomElement($userIds) : null;

            RentalContract::query()->create([
                'contract_number' => $contractNumber,
                'landlord_name' => $faker->name(),
                'landlord_entity_type' => $faker->randomElement($entityTypes),
                'landlord_national_id' => $faker->boolean(70) ? $faker->numerify('##########') : null,
                'landlord_address' => $faker->boolean(80) ? $faker->address() : null,
                'tenant_name' => $faker->company(),
                'tenant_entity_type' => $faker->randomElement($entityTypes),
                'tenant_national_id' => $faker->boolean(70) ? $faker->numerify('##########') : null,
                'tenant_address' => $faker->boolean(80) ? $faker->address() : null,
                'unit_number' => $faker->bothify('BLD-##/UNIT-###'),
                'unit_address' => $faker->address(),
                'unit_area_sqm' => $faker->boolean(75) ? $faker->randomFloat(2, 20, 450) : null,
                'lease_duration_months' => $faker->boolean(90) ? $durationMonths : null,
                'lease_start_date' => $startDate->toDateString(),
                'lease_end_date' => $endDate->toDateString(),
                'monthly_rent' => $faker->randomFloat(2, 1000, 60000),
                'security_deposit' => $faker->boolean(85) ? $faker->randomFloat(2, 0, 120000) : null,
                'contract_file' => null,
                'contract_original_name' => null,
                'contract_mime' => null,
                'contract_size' => null,
                'status' => $status,
                'notes' => $faker->boolean(60) ? $faker->sentence(12) : null,
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
            ]);
        }
    }
}
