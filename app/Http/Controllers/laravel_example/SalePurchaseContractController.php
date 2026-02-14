<?php

namespace App\Http\Controllers\laravel_example;

use App\Http\Controllers\Controller;
use App\Models\SalePurchaseContract;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class SalePurchaseContractController extends Controller
{
    public function indexPage()
    {
        return view('content.pages.sale-purchase-contracts.index');
    }

    public function list(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $recordsTotal = SalePurchaseContract::query()->count();
        $searchValue = trim((string) $request->input('search.value', ''));

        $query = SalePurchaseContract::query();

        if ($searchValue !== '') {
            $query->where(function ($builder) use ($searchValue) {
                $builder
                    ->where('contract_number', 'like', "%{$searchValue}%")
                    ->orWhere('seller_name', 'like', "%{$searchValue}%")
                    ->orWhere('buyer_name', 'like', "%{$searchValue}%")
                    ->orWhere('unit_number', 'like', "%{$searchValue}%")
                    ->orWhere('unit_address', 'like', "%{$searchValue}%")
                    ->orWhere('seller_national_id', 'like', "%{$searchValue}%")
                    ->orWhere('buyer_national_id', 'like', "%{$searchValue}%")
                    ->orWhere('contract_word_original_name', 'like', "%{$searchValue}%")
                    ->orWhere('signed_pdf_original_name', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderableColumns = [
            'id',
            'contract_number',
            'seller_name',
            'unit_number',
            'contract_date',
            'total_price',
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

        $data = $contracts->map(function (SalePurchaseContract $contract) {
            return [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'seller_name' => $contract->seller_name,
                'seller_entity_type' => $contract->seller_entity_type,
                'buyer_name' => $contract->buyer_name,
                'buyer_entity_type' => $contract->buyer_entity_type,
                'unit_number' => $contract->unit_number,
                'unit_address' => $contract->unit_address,
                'contract_date' => optional($contract->contract_date)->format('Y-m-d'),
                'total_price' => $contract->total_price,
                'down_payment' => $contract->down_payment,
                'remaining_amount' => $this->calculateRemainingAmount($contract),
                'status' => $contract->status,
                'status_label' => $contract->status_label,
                'has_files' => !empty($contract->contract_word_file) || !empty($contract->signed_pdf_file),
                'contract_word_file_url' => $contract->contract_word_file ? Storage::url($contract->contract_word_file) : null,
                'signed_pdf_file_url' => $contract->signed_pdf_file ? Storage::url($contract->signed_pdf_file) : null,
                'action' => $contract->id,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'code' => 200,
            'data' => $data,
        ]);
    }

    public function edit(int $id): JsonResponse
    {
        $contract = SalePurchaseContract::query()->find($id);

        if (!$contract) {
            return response()->json([
                'message' => 'العقد غير موجود.',
            ], 404);
        }

        return response()->json($this->serializeContract($contract, false));
    }

    public function show(int $id): JsonResponse
    {
        $contract = SalePurchaseContract::query()->find($id);

        if (!$contract) {
            return response()->json([
                'message' => 'العقد غير موجود.',
            ], 404);
        }

        return response()->json($this->serializeContract($contract, true));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make(
            array_merge($request->all(), [
                'status' => $request->input('status', 'active'),
                'currency' => $request->input('currency', 'EGP'),
                'down_payment' => $request->input('down_payment', 0),
            ]),
            [
                'id' => [
                    'nullable',
                    'integer',
                    Rule::exists('sale_purchase_contracts', 'id')->whereNull('deleted_at'),
                ],

                'seller_name' => ['required', 'string', 'max:255'],
                'seller_entity_type' => ['required', 'in:individual,company,sole_proprietorship'],
                'seller_national_id' => ['nullable', 'string', 'max:20'],
                'seller_address' => ['nullable', 'string'],

                'buyer_name' => ['required', 'string', 'max:255'],
                'buyer_entity_type' => ['required', 'in:individual,company,sole_proprietorship'],
                'buyer_national_id' => ['nullable', 'string', 'max:20'],
                'buyer_address' => ['nullable', 'string'],

                'unit_number' => ['required', 'string', 'max:100'],
                'unit_address' => ['required', 'string'],
                'unit_area_sqm' => ['nullable', 'numeric', 'min:0'],
                'unit_description' => ['nullable', 'string'],

                'contract_date' => ['required', 'date'],
                'delivery_date' => ['nullable', 'date'],

                'currency' => ['nullable', 'string', 'max:10'],
                'total_price' => ['required', 'numeric', 'min:0'],
                'down_payment' => ['required', 'numeric', 'min:0', 'lte:total_price'],

                'payment_method' => ['required', 'in:cash,bank_transfer,installments'],
                'installments_count' => ['nullable', 'required_if:payment_method,installments', 'integer', 'min:1'],
                'installment_amount' => ['nullable', 'required_if:payment_method,installments', 'numeric', 'min:0'],
                'first_installment_date' => ['nullable', 'required_if:payment_method,installments', 'date'],

                'status' => ['required', 'in:draft,active,completed,cancelled'],
                'notes' => ['nullable', 'string'],

                'contract_word' => ['nullable', 'file', 'mimes:doc,docx', 'max:10240'],
                'signed_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
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
        $uploadedWord = $request->file('contract_word');
        $uploadedSignedPdf = $request->file('signed_pdf');
        $userId = $request->user()?->id;

        unset($validated['id'], $validated['contract_word'], $validated['signed_pdf'], $validated['remaining_amount']);

        $validated['currency'] = strtoupper((string) ($validated['currency'] ?? 'EGP'));
        $validated['down_payment'] = (float) ($validated['down_payment'] ?? 0);
        $validated['total_price'] = (float) ($validated['total_price'] ?? 0);

        if (($validated['payment_method'] ?? 'cash') !== 'installments') {
            $validated['installments_count'] = null;
            $validated['installment_amount'] = null;
            $validated['first_installment_date'] = null;
        }

        if ($contractId) {
            $existingContract = SalePurchaseContract::query()->find($contractId);
            if (!$existingContract) {
                return response()->json([
                    'message' => 'العقد غير موجود.',
                ], 404);
            }

            $validated['updated_by'] = $userId;

            if ($uploadedWord instanceof UploadedFile) {
                $this->deleteStoredFile($existingContract->contract_word_file);
                $this->setWordFilePayload($validated, $uploadedWord);
            }

            if ($uploadedSignedPdf instanceof UploadedFile) {
                $this->deleteStoredFile($existingContract->signed_pdf_file);
                $this->setSignedPdfFilePayload($validated, $uploadedSignedPdf);
            }

            $existingContract->fill($validated);
            $existingContract->save();

            return response()->json([
                'message' => 'تم تحديث العقد بنجاح.',
                'data' => [
                    'id' => $existingContract->id,
                    'contract_number' => $existingContract->contract_number,
                ],
            ]);
        }

        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        if ($uploadedWord instanceof UploadedFile) {
            $this->setWordFilePayload($validated, $uploadedWord);
        }

        if ($uploadedSignedPdf instanceof UploadedFile) {
            $this->setSignedPdfFilePayload($validated, $uploadedSignedPdf);
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
        $contract = SalePurchaseContract::query()->find($id);

        if (!$contract) {
            return response()->json([
                'message' => 'العقد غير موجود.',
            ], 404);
        }

        $this->deleteStoredFile($contract->contract_word_file);
        $this->deleteStoredFile($contract->signed_pdf_file);
        $contract->delete();

        return response()->json([
            'message' => 'تم حذف العقد بنجاح.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function setWordFilePayload(array &$payload, UploadedFile $uploadedFile): void
    {
        $storedPath = $uploadedFile->store('sale-purchase-contracts/word', 'public');

        $payload['contract_word_file'] = $storedPath;
        $payload['contract_word_original_name'] = $uploadedFile->getClientOriginalName();
        $payload['contract_word_mime'] = $uploadedFile->getClientMimeType();
        $payload['contract_word_size'] = $uploadedFile->getSize();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function setSignedPdfFilePayload(array &$payload, UploadedFile $uploadedFile): void
    {
        $storedPath = $uploadedFile->store('sale-purchase-contracts/signed', 'public');

        $payload['signed_pdf_file'] = $storedPath;
        $payload['signed_pdf_original_name'] = $uploadedFile->getClientOriginalName();
        $payload['signed_pdf_mime'] = $uploadedFile->getClientMimeType();
        $payload['signed_pdf_size'] = $uploadedFile->getSize();
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
    private function createWithUniqueContractNumber(array $payload): SalePurchaseContract
    {
        $maxAttempts = 5;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                return DB::transaction(function () use ($payload) {
                    $year = (int) now()->format('Y');
                    $payload['contract_number'] = $this->nextContractNumber($year);

                    return SalePurchaseContract::query()->create($payload);
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
        $prefix = sprintf('SP-%d-', $year);

        $latestContractNumber = SalePurchaseContract::withTrashed()
            ->where('contract_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('contract_number')
            ->value('contract_number');

        $nextSequence = 1;
        if (is_string($latestContractNumber) && preg_match('/^SP-\d{4}-(\d{6})$/', $latestContractNumber, $matches)) {
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

    private function calculateRemainingAmount(SalePurchaseContract $contract): float
    {
        return max(0, (float) $contract->total_price - (float) $contract->down_payment);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeContract(SalePurchaseContract $contract, bool $includeTimestamps = false): array
    {
        $payload = [
            'id' => $contract->id,
            'contract_number' => $contract->contract_number,

            'seller_name' => $contract->seller_name,
            'seller_entity_type' => $contract->seller_entity_type,
            'seller_national_id' => $contract->seller_national_id,
            'seller_address' => $contract->seller_address,

            'buyer_name' => $contract->buyer_name,
            'buyer_entity_type' => $contract->buyer_entity_type,
            'buyer_national_id' => $contract->buyer_national_id,
            'buyer_address' => $contract->buyer_address,

            'unit_number' => $contract->unit_number,
            'unit_address' => $contract->unit_address,
            'unit_area_sqm' => $contract->unit_area_sqm,
            'unit_description' => $contract->unit_description,

            'contract_date' => optional($contract->contract_date)->format('Y-m-d'),
            'delivery_date' => optional($contract->delivery_date)->format('Y-m-d'),

            'currency' => $contract->currency,
            'total_price' => $contract->total_price,
            'down_payment' => $contract->down_payment,
            'remaining_amount' => $this->calculateRemainingAmount($contract),

            'payment_method' => $contract->payment_method,
            'installments_count' => $contract->installments_count,
            'installment_amount' => $contract->installment_amount,
            'first_installment_date' => optional($contract->first_installment_date)->format('Y-m-d'),

            'status' => $contract->status,
            'status_label' => $contract->status_label,
            'notes' => $contract->notes,

            'contract_word_file' => $contract->contract_word_file,
            'contract_word_file_url' => $contract->contract_word_file ? Storage::url($contract->contract_word_file) : null,
            'contract_word_original_name' => $contract->contract_word_original_name,
            'contract_word_mime' => $contract->contract_word_mime,
            'contract_word_size' => $contract->contract_word_size,

            'signed_pdf_file' => $contract->signed_pdf_file,
            'signed_pdf_file_url' => $contract->signed_pdf_file ? Storage::url($contract->signed_pdf_file) : null,
            'signed_pdf_original_name' => $contract->signed_pdf_original_name,
            'signed_pdf_mime' => $contract->signed_pdf_mime,
            'signed_pdf_size' => $contract->signed_pdf_size,
        ];

        if ($includeTimestamps) {
            $payload['created_at'] = optional($contract->created_at)->format('Y-m-d H:i:s');
            $payload['updated_at'] = optional($contract->updated_at)->format('Y-m-d H:i:s');
        }

        return $payload;
    }
}

