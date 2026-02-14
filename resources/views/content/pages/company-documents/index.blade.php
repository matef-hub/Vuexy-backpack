@extends('layouts.layoutMaster')

@section('title', 'مستندات الشركة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    <style>
        .company-doc-preview {
            min-height: 84px;
            border: 1px dashed var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-paper-bg);
        }

        .company-doc-preview img {
            width: 100%;
            max-width: 220px;
            max-height: 170px;
            object-fit: contain;
            border-radius: 0.5rem;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.baseUrl = window.baseUrl || '{{ url('/') }}/';
        window.companyDocumentsBaseUrl = '{{ url('/') }}/';
        window.companyDocumentsRoutes = {
            list: '{{ url('/company-documents/list') }}',
            store: '{{ url('/company-documents') }}',
            editBase: '{{ url('/company-documents') }}',
            deleteBase: '{{ url('/company-documents') }}'
        };
    </script>
    @vite(['resources/js/laravel-company-documents.js'])
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">إدارة مستندات الشركة</h5>
                <p class="mb-0 text-body-secondary">عرض وإضافة وتعديل وحذف المستندات عبر Ajax بأسلوب Vuexy.</p>
            </div>
            <button type="button" id="addCompanyDocumentBtn" class="btn btn-primary">
                <i class="icon-base ti tabler-plus me-2"></i>
                إضافة مستند
            </button>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table border-top datatables-company-documents">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>اسم المستند</th>
                        <th>رقم المستند</th>
                        <th>نوع المستند</th>
                        <th>تاريخ الإصدار</th>
                        <th>تاريخ الانتهاء</th>
                        <th>الملف</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="companyDocumentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="companyDocumentModalLabel">إضافة مستند جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="companyDocumentForm" enctype="multipart/form-data" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="company_document_id" name="id">

                        <div id="companyDocumentsStepper" class="bs-stepper wizard-modern">
                            <div class="bs-stepper-header">
                                <div class="step" data-target="#company-doc-step-1">
                                    <button type="button" class="step-trigger" aria-selected="true">
                                        <span class="bs-stepper-circle"><i class="icon-base ti tabler-file-text"></i></span>
                                        <span class="bs-stepper-label">
                                            <span class="bs-stepper-title">البيانات الأساسية</span>
                                            <span class="bs-stepper-subtitle">المعلومات التعريفية</span>
                                        </span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#company-doc-step-2">
                                    <button type="button" class="step-trigger" aria-selected="false">
                                        <span class="bs-stepper-circle"><i class="icon-base ti tabler-upload"></i></span>
                                        <span class="bs-stepper-label">
                                            <span class="bs-stepper-title">الملف</span>
                                            <span class="bs-stepper-subtitle">رفع ومعاينة</span>
                                        </span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#company-doc-step-3">
                                    <button type="button" class="step-trigger" aria-selected="false">
                                        <span class="bs-stepper-circle"><i class="icon-base ti tabler-check"></i></span>
                                        <span class="bs-stepper-label">
                                            <span class="bs-stepper-title">المراجعة</span>
                                            <span class="bs-stepper-subtitle">تأكيد الحفظ</span>
                                        </span>
                                    </button>
                                </div>
                            </div>

                            <div class="bs-stepper-content pt-4">
                                <div id="company-doc-step-1" class="content">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label" for="docname">اسم المستند <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" id="docname" name="docname" class="form-control"
                                                placeholder="مثال: رخصة تجارية" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="doc_number">رقم المستند</label>
                                            <input type="text" id="doc_number" name="doc_number" class="form-control"
                                                placeholder="مثال: DOC-2026-001">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="doc_type">نوع المستند</label>
                                            <input type="text" id="doc_type" name="doc_type" class="form-control"
                                                placeholder="مثال: عقد / شهادة / تصريح">
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label" for="doc_issue_date">تاريخ الإصدار <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" id="doc_issue_date" name="doc_issue_date"
                                                class="form-control" required>
                                        </div>

                                        <div class="col-md-3">
                                            <label class="form-label" for="doc_end_date">تاريخ الانتهاء</label>
                                            <input type="date" id="doc_end_date" name="doc_end_date"
                                                class="form-control">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="status">الحالة <span
                                                    class="text-danger">*</span></label>
                                            <select id="status" name="status" class="form-select" required>
                                                <option value="active">نشط</option>
                                                <option value="expired">منتهي</option>
                                                <option value="archived">مؤرشف</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label" for="notes">ملاحظات</label>
                                            <textarea id="notes" name="notes" class="form-control" rows="3"
                                                placeholder="أي ملاحظات إضافية حول المستند"></textarea>
                                        </div>
                                    </div>
                                </div>

                                <div id="company-doc-step-2" class="content">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label class="form-label" for="doc_file">ملف المستند (PDF أو صورة)</label>
                                            <input type="file" id="doc_file" name="doc_file" class="form-control"
                                                accept=".pdf,.jpg,.jpeg,.png,.webp">
                                            <small class="text-body-secondary d-block mt-2">الامتدادات المسموحة: PDF, JPG,
                                                JPEG, PNG,
                                                WEBP</small>
                                            <small id="currentFileHint" class="text-body-secondary d-block mt-1"></small>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">معاينة الملف</label>
                                            <div id="docFilePreview" class="company-doc-preview">
                                                <span class="text-body-secondary">لم يتم اختيار ملف.</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="company-doc-step-3" class="content">
                                    <div class="alert alert-primary py-2 mb-3">
                                        <i class="icon-base ti tabler-info-circle me-2"></i>
                                        راجع البيانات النهائية قبل الحفظ.
                                    </div>

                                    <div class="row g-2">
                                        <x-vuexy.review-summary-item col="col-12" icon="ti ti-file-description text-primary"
                                            label="اسم المستند" value-key="docname" badge-class="bg-label-primary"
                                            value-class="text-truncate" value-style="max-width: 260px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-hash text-info" label="رقم المستند"
                                            value-key="doc_number" badge-class="bg-label-info" value-class="text-truncate"
                                            value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-category text-warning" label="نوع المستند"
                                            value-key="doc_type" badge-class="bg-label-warning"
                                            value-class="text-truncate" value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-calendar-event text-success"
                                            label="تاريخ الإصدار" value-key="doc_issue_date" badge-class="bg-label-success" />

                                        <x-vuexy.review-summary-item icon="ti ti-calendar-time text-danger"
                                            label="تاريخ الانتهاء" value-key="doc_end_date" badge-class="bg-label-danger" />

                                        <x-vuexy.review-summary-item icon="ti ti-activity-heartbeat text-primary"
                                            label="الحالة" value-key="status" badge-class="bg-label-primary"
                                            value-class="review-status-badge" />

                                        <x-vuexy.review-summary-item icon="ti ti-paperclip text-secondary" label="الملف"
                                            value-key="doc_file" badge-class="bg-label-secondary" value-class="text-truncate"
                                            value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item col="col-12" icon="ti ti-notes text-secondary"
                                            label="ملاحظات" value-key="notes" badge-class="bg-label-secondary"
                                            value-class="text-wrap text-end" value-style="max-width: 70%;"
                                            body-align="align-items-start" />
                                    </div>
                                </div>


                            </div>
                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" id="companyDocPrevBtn" class="btn btn-label-secondary">
                                    <i class="icon-base ti tabler-arrow-right me-2 scaleX-n1-rtl"></i>
                                    السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" id="companyDocNextBtn" class="btn btn-primary">
                                        التالي
                                        <i class="icon-base ti tabler-arrow-left ms-2 scaleX-n1-rtl"></i>
                                    </button>
                                    <button type="button" id="companyDocSaveBtn" class="btn btn-success d-none">
                                        <i class="icon-base ti tabler-device-floppy me-2"></i>
                                        حفظ البيانات
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
