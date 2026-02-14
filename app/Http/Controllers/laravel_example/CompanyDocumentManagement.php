<?php

namespace App\Http\Controllers\laravel_example;

use App\Http\Controllers\Controller;
use App\Models\CompanyDocument;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CompanyDocumentManagement extends Controller
{
    public function page()
    {
        return view('content.pages.company-documents.index');
    }

    public function index(Request $request): JsonResponse
    {
        $draw = (int) $request->input('draw', 1);
        $start = max((int) $request->input('start', 0), 0);
        $length = (int) $request->input('length', 10);
        $length = $length > 0 ? min($length, 100) : 10;

        $recordsTotal = CompanyDocument::query()->count();
        $searchValue = trim((string) $request->input('search.value', ''));

        $query = CompanyDocument::query();

        if ($searchValue !== '') {
            $query->where(function ($builder) use ($searchValue) {
                $builder
                    ->where('docname', 'like', "%{$searchValue}%")
                    ->orWhere('doc_number', 'like', "%{$searchValue}%")
                    ->orWhere('doc_type', 'like', "%{$searchValue}%")
                    ->orWhere('status', 'like', "%{$searchValue}%")
                    ->orWhere('notes', 'like', "%{$searchValue}%")
                    ->orWhere('doc_original_name', 'like', "%{$searchValue}%");
            });
        }

        $recordsFiltered = (clone $query)->count();

        $orderableColumns = [
            'id',
            'docname',
            'doc_number',
            'doc_type',
            'doc_issue_date',
            'doc_end_date',
            'status',
            'created_at',
        ];

        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderColumnName = (string) $request->input("columns.{$orderColumnIndex}.data", 'id');
        $orderColumnName = in_array($orderColumnName, $orderableColumns, true) ? $orderColumnName : 'id';
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $documents = $query
            ->orderBy($orderColumnName, $orderDirection)
            ->skip($start)
            ->take($length)
            ->get();

        $data = $documents->map(function (CompanyDocument $document) {
            return [
                'id' => $document->id,
                'docname' => $document->docname,
                'doc_number' => $document->doc_number,
                'doc_type' => $document->doc_type,
                'doc_issue_date' => optional($document->doc_issue_date)->format('Y-m-d'),
                'doc_end_date' => optional($document->doc_end_date)->format('Y-m-d'),
                'doc_file' => $document->doc_file,
                'doc_file_url' => $document->doc_file ? Storage::disk('public')->url($document->doc_file) : null,
                'doc_original_name' => $document->doc_original_name,
                'doc_mime' => $document->doc_mime,
                'doc_size' => $document->doc_size,
                'status' => $document->status,
                'notes' => $document->notes,
                'action' => $document->id,
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
        $document = CompanyDocument::query()->find($id);

        if (!$document) {
            return response()->json([
                'message' => 'المستند غير موجود.',
            ], 404);
        }

        return response()->json([
            'id' => $document->id,
            'docname' => $document->docname,
            'doc_number' => $document->doc_number,
            'doc_type' => $document->doc_type,
            'doc_issue_date' => optional($document->doc_issue_date)->format('Y-m-d'),
            'doc_end_date' => optional($document->doc_end_date)->format('Y-m-d'),
            'doc_file' => $document->doc_file,
            'doc_file_url' => $document->doc_file ? Storage::disk('public')->url($document->doc_file) : null,
            'doc_original_name' => $document->doc_original_name,
            'doc_mime' => $document->doc_mime,
            'doc_size' => $document->doc_size,
            'status' => $document->status,
            'notes' => $document->notes,
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
                    Rule::exists('company_documents', 'id')->whereNull('deleted_at'),
                ],
                'docname' => ['required', 'string', 'max:255'],
                'doc_number' => ['nullable', 'string', 'max:100'],
                'doc_type' => ['nullable', 'string', 'max:100'],
                'doc_issue_date' => ['required', 'date'],
                'doc_end_date' => ['nullable', 'date', 'after_or_equal:doc_issue_date'],
                'doc_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp'],
                'status' => ['required', 'in:active,expired,archived'],
                'notes' => ['nullable', 'string'],
            ],
            [
                'doc_end_date.after_or_equal' => 'تاريخ الانتهاء يجب أن يكون بعد أو يساوي تاريخ الإصدار.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'message' => 'فشل التحقق من صحة البيانات.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $documentId = isset($validated['id']) ? (int) $validated['id'] : null;
        $uploadedFile = $request->file('doc_file');
        $userId = $request->user()?->id;

        unset($validated['id'], $validated['doc_file']);

        if ($documentId) {
            $existingDocument = CompanyDocument::query()->find($documentId);
            if (!$existingDocument) {
                return response()->json([
                    'message' => 'المستند غير موجود.',
                ], 404);
            }

            $validated['updated_by'] = $userId;

            if ($uploadedFile instanceof UploadedFile) {
                $this->deleteStoredFile($existingDocument->doc_file);
                $this->setFilePayload($validated, $uploadedFile);
            }

            $document = CompanyDocument::updateOrCreate(['id' => $documentId], $validated);

            return response()->json([
                'message' => 'تم تحديث المستند بنجاح.',
                'data' => [
                    'id' => $document->id,
                ],
            ]);
        }

        $validated['created_by'] = $userId;
        $validated['updated_by'] = $userId;

        if ($uploadedFile instanceof UploadedFile) {
            $this->setFilePayload($validated, $uploadedFile);
        }

        $document = CompanyDocument::query()->create($validated);

        return response()->json([
            'message' => 'تم إنشاء المستند بنجاح.',
            'data' => [
                'id' => $document->id,
            ],
        ], 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $document = CompanyDocument::query()->find($id);

        if (!$document) {
            return response()->json([
                'message' => 'المستند غير موجود.',
            ], 404);
        }

        $this->deleteStoredFile($document->doc_file);
        $document->delete();

        return response()->json([
            'message' => 'تم حذف المستند بنجاح.',
        ]);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function setFilePayload(array &$payload, UploadedFile $uploadedFile): void
    {
        $storedPath = $uploadedFile->store('company-documents', 'public');

        $payload['doc_file'] = $storedPath;
        $payload['doc_original_name'] = $uploadedFile->getClientOriginalName();
        $payload['doc_mime'] = $uploadedFile->getClientMimeType();
        $payload['doc_size'] = $uploadedFile->getSize();
    }

    private function deleteStoredFile(?string $path): void
    {
        if (!empty($path) && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

