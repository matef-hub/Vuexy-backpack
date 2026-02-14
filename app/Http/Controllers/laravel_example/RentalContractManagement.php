<?php

namespace App\Http\Controllers\laravel_example;

use App\Http\Controllers\Controller;
use App\Models\RentalContract;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class RentalContractManagement extends Controller
{
    public function page()
    {
        return view('content.pages.rental-contracts.index');
    }

    public function index(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $recordsTotal = RentalContract::query()->count();
        $searchValue = trim((string) $request->input('search.value', ''));

        $query = RentalContract::query();

        if ($searchValue !== '') {
            $query->where(function ($builder) use ($searchValue) {
                $builder
                    ->where('contract_number', 'like', "%{$searchValue}%")
                    ->orWhere('landlord_name', 'like', "%{$searchValue}%")
                    ->orWhere('tenant_name', 'like', "%{$searchValue}%")
                    ->orWhere('unit_number', 'like', "%{$searchValue}%")
                    ->orWhere('landlord_national_id', 'like', "%{$searchValue}%")
                    ->orWhere('tenant_national_id', 'like', "%{$searchValue}%")
                    ->orWhere('contract_original_name', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderableColumns = [
            'id',
            'contract_number',
            'landlord_name',
            'tenant_name',
            'unit_number',
            'lease_start_date',
            'lease_end_date',
            'monthly_rent',
            'status',
            'created_at',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderColumnName = (string) $request->input("columns.{$orderColumnIndex}.data", 'id');
        $orderColumnName = in_array($orderColumnName, $orderableColumns, true) ? $orderColumnName : 'id';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $contracts = $query
            ->orderBy($orderColumnName, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $contracts->map(function (RentalContract $contract) {
            return [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'landlord_name' => $contract->landlord_name,
                'landlord_entity_type' => $contract->landlord_entity_type,
                'tenant_name' => $contract->tenant_name,
                'tenant_entity_type' => $contract->tenant_entity_type,
                'unit_number' => $contract->unit_number,
                'unit_address' => $contract->unit_address,
                'unit_area_sqm' => $contract->unit_area_sqm,
                'lease_duration_months' => $contract->lease_duration_months,
                'lease_start_date' => optional($contract->lease_start_date)->format('Y-m-d'),
                'lease_end_date' => optional($contract->lease_end_date)->format('Y-m-d'),
                'monthly_rent' => $contract->monthly_rent,
                'security_deposit' => $contract->security_deposit,
                'contract_file' => $contract->contract_file,
                'contract_file_url' => $contract->contract_file ? Storage::url($contract->contract_file) : null,
                'contract_original_name' => $contract->contract_original_name,
                'contract_mime' => $contract->contract_mime,
                'contract_size' => $contract->contract_size,
                'status' => $contract->status,
                'status_label' => $contract->status_label,
                'notes' => $contract->notes,
                'action' => $contract->id,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data,
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $contract = RentalContract::query()->find($id);

        if (!$contract) {
            return response()->json([
                'message' => 'العقد غير موجود.',
            ], 404);
        }

        return response()->json([
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'landlord_name' => $contract->landlord_name,
            'landlord_entity_type' => $contract->landlord_entity_type,
            'landlord_national_id' => $contract->landlord_national_id,
            'landlord_address' => $contract->landlord_address,
            'tenant_name' => $contract->tenant_name,
            'tenant_entity_type' => $contract->tenant_entity_type,
            'tenant_national_id' => $contract->tenant_national_id,
            'tenant_address' => $contract->tenant_address,
            'unit_number' => $contract->unit_number,
            'unit_address' => $contract->unit_address,
            'unit_area_sqm' => $contract->unit_area_sqm,
            'lease_duration_months' => $contract->lease_duration_months,
            'lease_start_date' => optional($contract->lease_start_date)->format('Y-m-d'),
            'lease_end_date' => optional($contract->lease_end_date)->format('Y-m-d'),
            'monthly_rent' => $contract->monthly_rent,
            'security_deposit' => $contract->security_deposit,
            'contract_file' => $contract->contract_file,
            'contract_file_url' => $contract->contract_file ? Storage::url($contract->contract_file) : null,
            'contract_original_name' => $contract->contract_original_name,
            'contract_mime' => $contract->contract_mime,
            'contract_size' => $contract->contract_size,
            'status' => $contract->status,
            'notes' => $contract->notes,
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $contract = RentalContract::query()->find($id);

        if (!$contract) {
            return response()->json([
                'message' => 'العقد غير موجود.',
            ], 404);
        }

        return response()->json([
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,
            'landlord_name' => $contract->landlord_name,
            'landlord_entity_type' => $contract->landlord_entity_type,
            'landlord_national_id' => $contract->landlord_national_id,
            'landlord_address' => $contract->landlord_address,
            'tenant_name' => $contract->tenant_name,
            'tenant_entity_type' => $contract->tenant_entity_type,
            'tenant_national_id' => $contract->tenant_national_id,
            'tenant_address' => $contract->tenant_address,
            'unit_number' => $contract->unit_number,
            'unit_address' => $contract->unit_address,
            'unit_area_sqm' => $contract->unit_area_sqm,
            'lease_duration_months' => $contract->lease_duration_months,
            'lease_start_date' => optional($contract->lease_start_date)->format('Y-m-d'),
            'lease_end_date' => optional($contract->lease_end_date)->format('Y-m-d'),
            'monthly_rent' => $contract->monthly_rent,
            'security_deposit' => $contract->security_deposit,
            'contract_file' => $contract->contract_file,
            'contract_file_url' => $contract->contract_file ? Storage::url($contract->contract_file) : null,
            'contract_original_name' => $contract->contract_original_name,
            'contract_mime' => $contract->contract_mime,
            'contract_size' => $contract->contract_size,
            'status' => $contract->status,
            'status_label' => $contract->status_label,
            'notes' => $contract->notes,
            'created_at' => optional($contract->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($contract->updated_at)->format('Y-m-d H:i:s'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            array_merge($request->all(), [
                'status' => $request->input('status', 'active'),
            ]),
            [
                'id' => [
                    'nullable',
                    'integer',
                    Rule::exists('rental_contracts', 'id')->whereNull('deleted_at'),
                ],
                'landlord_name' => ['required', 'string', 'max:255'],
                'landlord_entity_type' => ['required', 'in:individual,company,sole_proprietorship'],
                'landlord_national_id' => ['nullable', 'string', 'max:20'],
                'landlord_address' => ['nullable', 'string'],

                'tenant_name' => ['required', 'string', 'max:255'],
                'tenant_entity_type' => ['required', 'in:individual,company,sole_proprietorship'],
                'tenant_national_id' => ['nullable', 'string', 'max:20'],
                'tenant_address' => ['nullable', 'string'],

                'unit_number' => ['required', 'string', 'max:100'],
                'unit_address' => ['required', 'string'],
                'unit_area_sqm' => ['nullable', 'numeric', 'min:0'],

                'lease_duration_months' => ['nullable', 'integer', 'min:1'],
                'lease_start_date' => ['required', 'date'],
                'lease_end_date' => ['required', 'date', 'after_or_equal:lease_start_date'],

                'monthly_rent' => ['required', 'numeric', 'min:0'],
                'security_deposit' => ['nullable', 'numeric', 'min:0'],
                'contract_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],

                'status' => ['required', 'in:draft,active,expired,terminated'],
                'notes' => ['nullable', 'string'],
            ],
            [
                'lease_end_date.after_or_equal' => 'تاريخ نهاية العقد يجب أن يكون بعد أو يساوي تاريخ بداية العقد.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $contractId = isset($validated['id']) ? (int) $validated['id'] : null;
        $uploadedFile = $request->file('contract_file');
        $userId = $request->user()?->id;

        unset($validated['id'], $validated['contract_file']);

        if ($contractId) {
            $existingContract = RentalContract::query()->find($contractId);
            if (!$existingContract) {
                return response()->json([
                    'message' => 'العقد غير موجود.',
                ], 404);
            }

            $validated['updated_by'] = $userId;

            if ($uploadedFile instanceof UploadedFile) {
                $this->deleteStoredFile($existingContract->contract_file);
                $this->setContractFilePayload($validated, $uploadedFile);
            }

            $contract = RentalContract::query()->updateOrCreate(['id' => $contractId], $validated);

            return response()->json([
                'message' => 'تم تحديث العقد بنجاح.',
                'data' => [
                    'id' => $contract->id,
                    'contract_number' => $contract->contract_number,
                ],
            ]);
        }

        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        if ($uploadedFile instanceof UploadedFile) {
            $this->setContractFilePayload($validated, $uploadedFile);
        }

        try {
            $contract = $this->createWithUniqueContractNumber($validated);
        } catch (QueryException) {
            return response()->json([
                'message' => 'تعذر إنشاء رقم عقد فريد. يرجى المحاولة مرة أخرى.',
            ], 500);
        }

        return response()->json([
            'message' => 'تم إنشاء العقد بنجاح.',
            'data' => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ],
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $contract = RentalContract::query()->find($id);

        if (!$contract) {
            return response()->json([
                'message' => 'العقد غير موجود.',
            ], 404);
        }

        $this->deleteStoredFile($contract->contract_file);
        $contract->delete();

        return response()->json([
            'message' => 'تم حذف العقد بنجاح.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function setContractFilePayload(array &$payload, UploadedFile $uploadedFile): void
    {
        $storedPath = $uploadedFile->store('rental-contracts', 'public');

        $payload['contract_file'] = $storedPath;
        $payload['contract_original_name'] = $uploadedFile->getClientOriginalName();
        $payload['contract_mime'] = $uploadedFile->getClientMimeType();
        $payload['contract_size'] = $uploadedFile->getSize();
    }

    private function deleteStoredFile(?string $path): void
    {
        if (!empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createWithUniqueContractNumber(array $payload): RentalContract
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($payload) {
                    $year = (int) now()->format('Y');
                    $payload['contract_number'] = $this->nextContractNumber($year);

                    return RentalContract::query()->create($payload);
                }, 5);
            } catch (QueryException $exception) {
                if (!$this->isDuplicateContractNumberException($exception) || $attempt === $maxAttempts) {
                    throw $exception;
                }
            }
        }

        throw new RuntimeException('Failed to generate a unique contract number.');
    }

    private function nextContractNumber(int $year): string
    {
        $prefix = sprintf('RC-%d-', $year);

        $latestContractNumber = RentalContract::withTrashed()
            ->where('contract_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('contract_number')
            ->value('contract_number');

        $nextSequence = 1;
        if (is_string($latestContractNumber) && preg_match('/^RC-\d{4}-(\d{6})$/', $latestContractNumber, $matches)) {
            $nextSequence = ((int) $matches[1]) + 1;
        }

        return sprintf('%s%06d', $prefix, $nextSequence);
    }

    private function isDuplicateContractNumberException(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;
        $message = strtolower($exception->getMessage());

        if ($sqlState === '23000' && str_contains($message, 'contract_number')) {
            return true;
        }

        return (int) $driverCode === 1062 && str_contains($message, 'contract_number');
    }
}
