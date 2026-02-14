<?php

namespace Database\Seeders;

use App\Models\SalePurchaseContract;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SalePurchaseContractsSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faker = fake();
        $userIds = User::query()->pluck('id')->all();
        $entityTypes = ['individual', 'company', 'sole_proprietorship'];
        $statuses = ['draft', 'active', 'completed', 'cancelled'];
        $paymentMethods = ['cash', 'bank_transfer', 'installments'];

        for ($i = 1; $i <= 30; $i++) {
            $contractDate = Carbon::instance($faker->dateTimeBetween('-24 months', '+2 months'));
            $deliveryDate = $faker->boolean(65)
                ? Carbon::instance($faker->dateTimeBetween($contractDate, $contractDate->copy()->addMonths(8)))
                : null;
            $year = $contractDate->year;

            $existingCountForYear = SalePurchaseContract::withTrashed()
                ->where('contract_number', 'like', sprintf('SP-%d-%%', $year))
                ->count();
            $contractNumber = sprintf('SP-%d-%06d', $year, $existingCountForYear + 1);

            $totalPrice = $faker->randomFloat(2, 50000, 2500000);
            $downPayment = $faker->randomFloat(2, 0, (float) $totalPrice);
            $paymentMethod = $faker->randomElement($paymentMethods);

            $installmentsCount = null;
            $installmentAmount = null;
            $firstInstallmentDate = null;

            if ($paymentMethod === 'installments') {
                $installmentsCount = $faker->numberBetween(3, 48);
                $remainingAmount = max(0, (float) $totalPrice - (float) $downPayment);
                $installmentAmount = $installmentsCount > 0 ? round($remainingAmount / $installmentsCount, 2) : 0;
                $firstInstallmentDate = Carbon::instance(
                    $faker->dateTimeBetween($contractDate->copy()->addDays(7), $contractDate->copy()->addMonths(3))
                )->toDateString();
            }

            $createdBy = !empty($userIds) ? $faker->randomElement($userIds) : null;
            $updatedBy = !empty($userIds) ? $faker->randomElement($userIds) : null;

            SalePurchaseContract::query()->create([
                'contract_number' => $contractNumber,
                'seller_name' => $faker->boolean(55) ? $faker->name() : $faker->company(),
                'seller_entity_type' => $faker->randomElement($entityTypes),
                'seller_national_id' => $faker->boolean(70) ? $faker->numerify('##########') : null,
                'seller_address' => $faker->boolean(85) ? $faker->address() : null,
                'buyer_name' => $faker->boolean(55) ? $faker->name() : $faker->company(),
                'buyer_entity_type' => $faker->randomElement($entityTypes),
                'buyer_national_id' => $faker->boolean(70) ? $faker->numerify('##########') : null,
                'buyer_address' => $faker->boolean(85) ? $faker->address() : null,
                'unit_number' => $faker->bothify('BLD-##/UNIT-###'),
                'unit_address' => $faker->address(),
                'unit_area_sqm' => $faker->boolean(80) ? $faker->randomFloat(2, 30, 600) : null,
                'unit_description' => $faker->boolean(60) ? $faker->sentence(14) : null,
                'contract_date' => $contractDate->toDateString(),
                'delivery_date' => $deliveryDate?->toDateString(),
                'currency' => 'EGP',
                'total_price' => $totalPrice,
                'down_payment' => $downPayment,
                'payment_method' => $paymentMethod,
                'installments_count' => $installmentsCount,
                'installment_amount' => $installmentAmount,
                'first_installment_date' => $firstInstallmentDate,
                'status' => $faker->randomElement($statuses),
                'notes' => $faker->boolean(65) ? $faker->sentence(15) : null,
                'contract_word_file' => null,
                'contract_word_original_name' => null,
                'contract_word_mime' => null,
                'contract_word_size' => null,
                'signed_pdf_file' => null,
                'signed_pdf_original_name' => null,
                'signed_pdf_mime' => null,
                'signed_pdf_size' => null,
                'created_by' => $createdBy,
                'updated_by' => $updatedBy,
            ]);
        }
    }
}

