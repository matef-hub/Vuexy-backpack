@extends('layouts.layoutMaster')

@section('title', 'عقود البيع والشراء')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('page-style')
    <style>
        #salePurchaseContractModal .bs-stepper-content {
            max-height: calc(100vh - 375px);
            overflow-y: auto;
            padding-top: 0.5rem !important;
            padding-inline-end: 0.25rem;
        }

        #salePurchaseContractModal .card .card-body {
            padding: 0.875rem 1rem;
        }

        .sp-file-preview,
        .sp-file-view {
            min-height: 56px;
            border: 1px dashed var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-paper-bg);
        }

        #spColumnsChecklist .form-check {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            min-height: 48px;
            padding: 0.625rem 0.75rem;
            background-color: var(--bs-paper-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #spColumnsChecklist .form-check-label {
            margin-inline-start: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        #spColumnsChecklist .form-check-input {
            margin: 0;
            flex-shrink: 0;
        }

        .sp-parties-cell .seller {
            font-weight: 600;
            color: var(--bs-heading-color);
        }

        .sp-parties-cell .buyer {
            font-size: .8125rem;
        }

        .sp-unit-cell .address {
            max-width: 260px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dt-action-buttons .dt-buttons {
            display: flex !important;
            gap: .5rem !important;
            flex-wrap: wrap !important;
            align-items: center !important;
        }

        .dt-action-buttons .dt-buttons .btn {
            display: inline-flex !important;
            align-items: center !important;
            gap: .35rem !important;
            line-height: 1 !important;
        }

        .dt-action-buttons .dt-button-collection .dropdown-item {
            display: flex;
            align-items: center;
        }

        @media (max-width: 767.98px) {
            #salePurchaseContractModal .bs-stepper-content {
                max-height: calc(100vh - 430px);
            }
        }
    </style>
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('page-script')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.baseUrl = window.baseUrl || '{{ url('/') }}/';
        window.salePurchaseContractsRoutes = {
            list: '{{ url('/sale-purchase-contracts/list') }}',
            store: '{{ url('/sale-purchase-contracts') }}',
            showBase: '{{ url('/sale-purchase-contracts') }}',
            editBase: '{{ url('/sale-purchase-contracts') }}',
            deleteBase: '{{ url('/sale-purchase-contracts') }}'
        };
    </script>
    @vite('resources/js/laravel-sale-purchase-contracts.js')
@endsection

@section('content')
    <div class="card">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">إدارة عقود البيع والشراء</h5>
                <p class="mb-0 text-body-secondary">إضافة، تعديل، حذف، واستعراض عقود البيع والشراء عبر Ajax.</p>
            </div>
            <button type="button" id="addSalePurchaseContractBtn" class="btn btn-primary">
                <i class="icon-base ti tabler-plus me-2"></i>
                إضافة عقد
            </button>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table border-top datatables-sale-purchase-contracts">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>رقم العقد</th>
                        <th>الأطراف</th>
                        <th>الوحدة</th>
                        <th>تاريخ العقد</th>
                        <th>إجمالي الثمن</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="modal fade" id="salePurchaseContractModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="salePurchaseContractModalLabel">إضافة عقد جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="salePurchaseContractForm" enctype="multipart/form-data" novalidate>
                    <div class="modal-body">
                        <input type="hidden" id="sale_purchase_contract_id" name="id">

                        <div id="salePurchaseContractsStepper" class="bs-stepper wizard-modern">
                            <div class="bs-stepper-header">
                                <div class="step" data-target="#sp-step-1">
                                    <button type="button" class="step-trigger" aria-selected="true">
                                        <span class="bs-stepper-circle"><i class="icon-base ti tabler-users"></i></span>
                                        <span class="bs-stepper-label">
                                            <span class="bs-stepper-title">البائع والمشتري</span>
                                            <span class="bs-stepper-subtitle">بيانات الأطراف</span>
                                        </span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#sp-step-2">
                                    <button type="button" class="step-trigger" aria-selected="false">
                                        <span class="bs-stepper-circle"><i
                                                class="icon-base ti tabler-file-description"></i></span>
                                        <span class="bs-stepper-label">
                                            <span class="bs-stepper-title">الوحدة والدفعات</span>
                                            <span class="bs-stepper-subtitle">العقار والسداد والمرفقات</span>
                                        </span>
                                    </button>
                                </div>
                                <div class="line"></div>
                                <div class="step" data-target="#sp-step-3">
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
                                <div id="sp-step-1" class="content">
                                    <div class="row g-3">
                                        <div class="col-12 col-xl-6">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <h6 class="mb-3">بيانات البائع</h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-7">
                                                            <label class="form-label" for="seller_name">اسم البائع <span class="text-danger">*</span></label>
                                                            <input type="text" id="seller_name" name="seller_name" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label" for="seller_entity_type">نوع كيان البائع <span class="text-danger">*</span></label>
                                                            <select id="seller_entity_type" name="seller_entity_type" class="form-select" required>
                                                                <option value="individual">فرد</option>
                                                                <option value="company">شركة</option>
                                                                <option value="sole_proprietorship">مؤسسة فردية</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="seller_national_id">هوية البائع</label>
                                                            <input type="text" id="seller_national_id" name="seller_national_id" maxlength="20" class="form-control">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="seller_address">عنوان البائع</label>
                                                            <textarea id="seller_address" name="seller_address" rows="2" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12 col-xl-6">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <h6 class="mb-3">بيانات المشتري</h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-7">
                                                            <label class="form-label" for="buyer_name">اسم المشتري <span class="text-danger">*</span></label>
                                                            <input type="text" id="buyer_name" name="buyer_name" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-5">
                                                            <label class="form-label" for="buyer_entity_type">نوع كيان المشتري <span class="text-danger">*</span></label>
                                                            <select id="buyer_entity_type" name="buyer_entity_type" class="form-select" required>
                                                                <option value="individual">فرد</option>
                                                                <option value="company">شركة</option>
                                                                <option value="sole_proprietorship">مؤسسة فردية</option>
                                                            </select>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="buyer_national_id">هوية المشتري</label>
                                                            <input type="text" id="buyer_national_id" name="buyer_national_id" maxlength="20" class="form-control">
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label" for="buyer_address">عنوان المشتري</label>
                                                            <textarea id="buyer_address" name="buyer_address" rows="2" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="sp-step-2" class="content">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h6 class="mb-3">معلومات العقد</h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="sp_contract_number_display">رقم العقد</label>
                                                            <input type="text" id="sp_contract_number_display" class="form-control" readonly placeholder="سيتم التوليد تلقائيًا">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="contract_date">تاريخ العقد <span class="text-danger">*</span></label>
                                                            <input type="date" id="contract_date" name="contract_date" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="delivery_date">تاريخ التسليم</label>
                                                            <input type="date" id="delivery_date" name="delivery_date" class="form-control">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="status">الحالة <span class="text-danger">*</span></label>
                                                            <select id="status" name="status" class="form-select" required>
                                                                <option value="draft">مسودة</option>
                                                                <option value="active">نشط</option>
                                                                <option value="completed">مكتمل</option>
                                                                <option value="cancelled">ملغي</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h6 class="mb-3">بيانات الوحدة</h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="unit_number">رقم الوحدة <span class="text-danger">*</span></label>
                                                            <input type="text" id="unit_number" name="unit_number" maxlength="100" class="form-control" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="unit_area_sqm">المساحة (م²)</label>
                                                            <input type="number" id="unit_area_sqm" name="unit_area_sqm" class="form-control" min="0" step="0.01">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="currency">العملة</label>
                                                            <input type="text" id="currency" name="currency" class="form-control" maxlength="10" value="EGP">
                                                        </div>
                                                        <div class="col-md-8">
                                                            <label class="form-label" for="unit_address">عنوان الوحدة <span class="text-danger">*</span></label>
                                                            <textarea id="unit_address" name="unit_address" rows="2" class="form-control" required></textarea>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label" for="unit_description">وصف الوحدة</label>
                                                            <textarea id="unit_description" name="unit_description" rows="2" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h6 class="mb-3">المدفوعات</h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="total_price">إجمالي الثمن <span class="text-danger">*</span></label>
                                                            <input type="number" id="total_price" name="total_price" class="form-control" min="0" step="0.01" required>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="down_payment">المقدم</label>
                                                            <input type="number" id="down_payment" name="down_payment" class="form-control" min="0" step="0.01" value="0">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="remaining_amount_display">المتبقي</label>
                                                            <input type="text" id="remaining_amount_display" class="form-control" readonly value="0.00">
                                                        </div>
                                                        <div class="col-md-3">
                                                            <label class="form-label" for="payment_method">طريقة السداد <span class="text-danger">*</span></label>
                                                            <select id="payment_method" name="payment_method" class="form-select" required>
                                                                <option value="cash">نقدي</option>
                                                                <option value="bank_transfer">تحويل بنكي</option>
                                                                <option value="installments">أقساط</option>
                                                            </select>
                                                        </div>

                                                        <div class="col-12 d-none" id="installmentsFieldsWrapper">
                                                            <div class="card border bg-body-tertiary mb-0">
                                                                <div class="card-body p-3">
                                                                    <div class="row g-3">
                                                                        <div class="col-md-4">
                                                                            <label class="form-label" for="installments_count">عدد الأقساط</label>
                                                                            <input type="number" id="installments_count" name="installments_count" class="form-control" min="1" step="1">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label class="form-label" for="installment_amount">قيمة القسط</label>
                                                                            <input type="number" id="installment_amount" name="installment_amount" class="form-control" min="0" step="0.01">
                                                                        </div>
                                                                        <div class="col-md-4">
                                                                            <label class="form-label" for="first_installment_date">تاريخ أول قسط</label>
                                                                            <input type="date" id="first_installment_date" name="first_installment_date" class="form-control">
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-12">
                                                            <label class="form-label" for="notes">ملاحظات</label>
                                                            <textarea id="notes" name="notes" rows="2" class="form-control"></textarea>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-12">
                                            <div class="card border">
                                                <div class="card-body">
                                                    <h6 class="mb-3">المرفقات</h6>
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="contract_word">ملف العقد Word</label>
                                                            <input type="file" id="contract_word" name="contract_word" class="form-control" accept=".doc,.docx">
                                                            <small class="text-body-secondary d-block mt-2">DOC / DOCX - بحد أقصى 10 MB</small>
                                                            <small id="currentContractWordHint" class="text-body-secondary d-block mt-1"></small>
                                                            <div id="contractWordPreview" class="sp-file-preview mt-2">
                                                                <span class="text-body-secondary">لا يوجد ملف.</span>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="signed_pdf">نسخة PDF موقعة</label>
                                                            <input type="file" id="signed_pdf" name="signed_pdf" class="form-control" accept=".pdf">
                                                            <small class="text-body-secondary d-block mt-2">PDF - بحد أقصى 10 MB</small>
                                                            <small id="currentSignedPdfHint" class="text-body-secondary d-block mt-1"></small>
                                                            <div id="signedPdfPreview" class="sp-file-preview mt-2">
                                                                <span class="text-body-secondary">لا يوجد ملف.</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="sp-step-3" class="content">
                                    <div class="alert alert-primary py-2 mb-3">
                                        <i class="icon-base ti tabler-info-circle me-2"></i>
                                        راجع بيانات عقد البيع والشراء قبل الحفظ.
                                    </div>

                                    <div class="row g-2">
                                        <x-vuexy.review-summary-item icon="ti ti-id text-primary" label="رقم العقد"
                                            value-key="contract_number" badge-class="bg-label-primary"
                                            value-class="text-truncate" value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-user text-info" label="البائع"
                                            value-key="seller_name" badge-class="bg-label-info" value-class="text-truncate"
                                            value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-user-check text-info" label="المشتري"
                                            value-key="buyer_name" badge-class="bg-label-info" value-class="text-truncate"
                                            value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-building text-warning" label="الوحدة"
                                            value-key="unit_number" badge-class="bg-label-warning"
                                            value-class="text-truncate" value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-calendar-event text-success"
                                            label="تاريخ العقد" value-key="contract_date" badge-class="bg-label-success" />

                                        <x-vuexy.review-summary-item icon="ti ti-cash text-primary" label="إجمالي الثمن"
                                            value-key="total_price" badge-class="bg-label-primary" />

                                        <x-vuexy.review-summary-item icon="ti ti-credit-card text-danger" label="المتبقي"
                                            value-key="remaining_amount" badge-class="bg-label-danger" />

                                        <x-vuexy.review-summary-item icon="ti ti-adjustments text-secondary"
                                            label="طريقة السداد" value-key="payment_method"
                                            badge-class="bg-label-secondary" />

                                        <x-vuexy.review-summary-item icon="ti ti-paperclip text-secondary"
                                            label="Word" value-key="contract_word" badge-class="bg-label-secondary"
                                            value-class="text-truncate" value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-file-type-pdf text-secondary"
                                            label="PDF موقع" value-key="signed_pdf" badge-class="bg-label-secondary"
                                            value-class="text-truncate" value-style="max-width: 220px;" />

                                        <x-vuexy.review-summary-item icon="ti ti-activity-heartbeat text-primary"
                                            label="الحالة" value-key="status" badge-class="bg-label-primary"
                                            value-class="review-status-badge" />

                                        <x-vuexy.review-summary-item col="col-12" icon="ti ti-notes text-secondary"
                                            label="ملاحظات" value-key="notes" badge-class="bg-label-secondary"
                                            value-class="text-wrap text-end" value-style="max-width: 70%;"
                                            body-align="align-items-start" />
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 pt-3 border-top">
                                <button type="button" id="spPrevBtn" class="btn btn-label-secondary">
                                    <i class="icon-base ti tabler-arrow-right me-2 scaleX-n1-rtl"></i>
                                    السابق
                                </button>
                                <div class="d-flex gap-2">
                                    <button type="button" id="spNextBtn" class="btn btn-primary">
                                        التالي
                                        <i class="icon-base ti tabler-arrow-left ms-2 scaleX-n1-rtl"></i>
                                    </button>
                                    <button type="button" id="spSaveBtn" class="btn btn-success d-none">
                                        <i class="icon-base ti tabler-device-floppy me-2"></i>
                                        حفظ العقد
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="salePurchaseContractViewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تفاصيل العقد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">رقم العقد</label>
                            <div class="fw-semibold" id="sp_view_contract_number">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">الحالة</label>
                            <div id="sp_view_status">-</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">البائع</label>
                            <div class="fw-semibold" id="sp_view_seller_name">-</div>
                            <small class="text-body-secondary d-block" id="sp_view_seller_meta">-</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">المشتري</label>
                            <div class="fw-semibold" id="sp_view_buyer_name">-</div>
                            <small class="text-body-secondary d-block" id="sp_view_buyer_meta">-</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">رقم الوحدة</label>
                            <div class="fw-semibold" id="sp_view_unit_number">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">المساحة</label>
                            <div class="fw-semibold" id="sp_view_unit_area">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">العملة</label>
                            <div class="fw-semibold" id="sp_view_currency">-</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-body-secondary">عنوان الوحدة</label>
                            <div class="fw-semibold" id="sp_view_unit_address">-</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label text-body-secondary">وصف الوحدة</label>
                            <div class="fw-semibold" id="sp_view_unit_description">-</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">تاريخ العقد</label>
                            <div class="fw-semibold" id="sp_view_contract_date">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">تاريخ التسليم</label>
                            <div class="fw-semibold" id="sp_view_delivery_date">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">طريقة السداد</label>
                            <div class="fw-semibold" id="sp_view_payment_method">-</div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label text-body-secondary">إجمالي الثمن</label>
                            <div class="fw-semibold" id="sp_view_total_price">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-body-secondary">المقدم</label>
                            <div class="fw-semibold" id="sp_view_down_payment">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-body-secondary">المتبقي</label>
                            <div class="fw-semibold" id="sp_view_remaining_amount">-</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-body-secondary">عدد الأقساط</label>
                            <div class="fw-semibold" id="sp_view_installments_count">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">قيمة القسط</label>
                            <div class="fw-semibold" id="sp_view_installment_amount">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">تاريخ أول قسط</label>
                            <div class="fw-semibold" id="sp_view_first_installment_date">-</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">ملف Word</label>
                            <div id="sp_view_contract_word" class="sp-file-view">
                                <span class="text-body-secondary">لا يوجد ملف.</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">PDF موقع</label>
                            <div id="sp_view_signed_pdf" class="sp-file-view">
                                <span class="text-body-secondary">لا يوجد ملف.</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-body-secondary">ملاحظات</label>
                            <div class="fw-semibold" id="sp_view_notes">-</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="salePurchaseContractsColumnsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Columns / الأعمدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="spColumnsChecklist" class="row g-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="applySpColumnsBtn" class="btn btn-primary">تطبيق</button>
                    <button type="button" id="resetSpColumnsBtn" class="btn btn-label-secondary">إعادة ضبط</button>
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection
