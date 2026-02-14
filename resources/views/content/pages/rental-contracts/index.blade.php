@extends('layouts.layoutMaster')

@section('title', 'عقود الإيجار')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    <style>
        #rentalContractFormCard .card .card-body {
            padding: 0.5rem 0.75rem !important;
        }

        #rentalContractFormCard .mb-4 {
            margin-bottom: 0.875rem !important;
        }

        #rentalContractFormCard .form-label,
        #rentalContractFormCard .col-form-label {
            margin-bottom: 0.35rem;
            font-size: 0.875rem;
        }

        #rentalContractFormCard .form-control,
        #rentalContractFormCard .form-select {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }

        #rentalContractTabs {
            flex-wrap: nowrap;
            overflow-x: auto;
            overflow-y: hidden;
            scrollbar-width: thin;
            gap: 0.25rem;
        }

        #rentalContractTabs .nav-link {
            white-space: nowrap;
            min-width: 170px;
            text-align: center;
        }

        #rentalContractTabsContent .tab-pane {
            padding-top: 0.25rem;
        }

        #rentalContractFormCard .col-form-label {
            margin-bottom: 0;
            padding-top: calc(0.5rem + 1px);
        }

        #rentalContractTabsContent textarea.form-control {
            min-height: 84px;
        }

        .rental-contract-preview,
        .rental-contract-view-file {
            min-height: 56px;
            border: 1px dashed var(--bs-border-color);
            border-radius: 0.5rem;
            padding: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: var(--bs-paper-bg);
        }

        .rental-contract-preview img,
        .rental-contract-view-file img {
            width: 100%;
            max-width: 220px;
            max-height: 170px;
            object-fit: contain;
            border-radius: 0.5rem;
        }

        #rentalColumnsChecklist .form-check {
            border: 1px solid var(--bs-border-color);
            border-radius: 0.5rem;
            min-height: 48px;
            padding: 0.625rem 0.75rem;
            background-color: var(--bs-paper-bg);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        #rentalColumnsChecklist .form-check-label {
            margin-inline-start: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
        }

        #rentalColumnsChecklist .form-check-input {
            margin: 0;
            flex-shrink: 0;
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
            #rentalContractTabs .nav-link {
                min-width: 150px;
            }
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/cleave-zen/cleave-zen.js'])
@endsection

@section('page-script')
    <script>
        window.csrfToken = '{{ csrf_token() }}';
        window.baseUrl = window.baseUrl || '{{ url('/') }}/';
        window.rentalContractsRoutes = {
            list: '{{ url('/rental-contracts/list') }}',
            store: '{{ url('/rental-contracts') }}',
            showBase: '{{ url('/rental-contracts') }}',
            editBase: '{{ url('/rental-contracts') }}',
            deleteBase: '{{ url('/rental-contracts') }}'
        };
    </script>
    @vite(['resources/js/laravel-rental-contracts.js'])
@endsection

@section('content')
    <div class="card" id="rentalContractsListCard">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h5 class="mb-1">إدارة عقود الإيجار</h5>
                <p class="mb-0 text-body-secondary">إضافة وتعديل وحذف واستعراض عقود الإيجار عبر Ajax بأسلوب Vuexy.</p>
            </div>
            <button type="button" id="addRentalContractBtn" class="btn btn-primary">
                <i class="icon-base ti tabler-plus me-2"></i>
                إضافة عقد
            </button>
        </div>

        <div class="card-datatable table-responsive">
            <table class="table border-top datatables-rental-contracts">
                <thead>
                    <tr>
                        <th>م</th>
                        <th>رقم العقد</th>
                        <th>المؤجر</th>
                        <th>المستأجر</th>
                        <th>رقم الوحدة</th>
                        <th>بداية العقد</th>
                        <th>نهاية العقد</th>
                        <th>الإيجار الشهري</th>
                        <th>المرفق</th>
                        <th>الحالة</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

    <div class="card mb-6" id="rentalContractFormCard">
        <div class="card-header border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1" id="rentalContractFormTitle">إضافة عقد جديد</h5>
                <p class="mb-0 text-body-secondary">استخدم التبويبات لإدخال بيانات العقد ثم الحفظ.</p>
            </div>
            <button type="button" id="cancelRentalFormBtn" class="btn btn-label-secondary">
                <i class="icon-base ti tabler-arrow-right me-2 scaleX-n1-rtl"></i>
                الرجوع للقائمة
            </button>
        </div>
        <div class="card-body">
            <form id="rentalContractForm" enctype="multipart/form-data" novalidate>
                <input type="hidden" id="rental_contract_id" name="id">

                <ul class="nav nav-tabs mb-4" id="rentalContractTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="rental-tab-landlord-trigger" data-bs-toggle="tab"
                            data-bs-target="#rental-tab-landlord" type="button" role="tab"
                            aria-controls="rental-tab-landlord" aria-selected="true">المؤجر</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rental-tab-tenant-trigger" data-bs-toggle="tab"
                            data-bs-target="#rental-tab-tenant" type="button" role="tab"
                            aria-controls="rental-tab-tenant" aria-selected="false">المستأجر</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rental-tab-unit-trigger" data-bs-toggle="tab"
                            data-bs-target="#rental-tab-unit" type="button" role="tab" aria-controls="rental-tab-unit"
                            aria-selected="false">الوحدة</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rental-tab-contract-trigger" data-bs-toggle="tab"
                            data-bs-target="#rental-tab-contract" type="button" role="tab"
                            aria-controls="rental-tab-contract" aria-selected="false">العقد والدفعات</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="rental-tab-review-trigger" data-bs-toggle="tab"
                            data-bs-target="#rental-tab-review" type="button" role="tab"
                            aria-controls="rental-tab-review" aria-selected="false">المراجعة</button>
                    </li>
                </ul>

                <div class="tab-content" id="rentalContractTabsContent">
                    <div class="tab-pane fade show active" id="rental-tab-landlord" role="tabpanel"
                        aria-labelledby="rental-tab-landlord-trigger">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="landlord_name">اسم المؤجر</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="landlord_name" name="landlord_name" class="form-control"
                                            placeholder="مثال: محمد أحمد" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="landlord_entity_type">نوع كيان
                                        المؤجر</label>
                                    <div class="col-sm-9">
                                        <select id="landlord_entity_type" name="landlord_entity_type" class="form-select" required>
                                            <option value="individual">فرد</option>
                                            <option value="company">شركة</option>
                                            <option value="sole_proprietorship">مؤسسة فردية</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="landlord_national_id">الرقم القومى</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="landlord_national_id" name="landlord_national_id"
                                            class="form-control" maxlength="14" inputmode="numeric" autocomplete="off"
                                            placeholder="أدخل 14 رقم" pattern="\d{14}">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="landlord_address">عنوان المؤجر</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="landlord_address" name="landlord_address" class="form-control"
                                            placeholder="أدخل عنوان المؤجر">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="rental-tab-tenant" role="tabpanel"
                        aria-labelledby="rental-tab-tenant-trigger">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="tenant_name">اسم المستأجر <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" id="tenant_name" name="tenant_name" class="form-control"
                                            placeholder="مثال: شركة النور" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="tenant_entity_type">نوع كيان
                                        المستأجر <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select id="tenant_entity_type" name="tenant_entity_type" class="form-select" required>
                                            <option value="individual">فرد</option>
                                            <option value="company">شركة</option>
                                            <option value="sole_proprietorship">مؤسسة فردية</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="tenant_national_id">هوية المستأجر</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="tenant_national_id" name="tenant_national_id" class="form-control"
                                            maxlength="20" placeholder="رقم الهوية/السجل">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row align-items-start">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="tenant_address">عنوان المستأجر</label>
                                    <div class="col-sm-9">
                                        <textarea id="tenant_address" name="tenant_address" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="rental-tab-unit" role="tabpanel"
                        aria-labelledby="rental-tab-unit-trigger">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="unit_number">رقم الوحدة <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="text" id="unit_number" name="unit_number" class="form-control"
                                            maxlength="100" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="unit_area_sqm">مساحة الوحدة (م²)</label>
                                    <div class="col-sm-9">
                                        <input type="number" id="unit_area_sqm" name="unit_area_sqm" class="form-control"
                                            min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-start">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="unit_address">عنوان الوحدة <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <textarea id="unit_address" name="unit_address" class="form-control" rows="2"
                                            required></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="rental-tab-contract" role="tabpanel"
                        aria-labelledby="rental-tab-contract-trigger">
                        <div class="row g-6">
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="contract_number_display">رقم العقد</label>
                                    <div class="col-sm-9">
                                        <input type="text" id="contract_number_display" class="form-control"
                                            placeholder="سيتم التوليد تلقائيًا" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="status">الحالة <span class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <select id="status" name="status" class="form-select" required>
                                            <option value="draft">مسودة</option>
                                            <option value="active">نشط</option>
                                            <option value="expired">منتهي</option>
                                            <option value="terminated">منتهي بالفسخ</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="lease_start_date">تاريخ بداية العقد <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="date" id="lease_start_date" name="lease_start_date" class="form-control"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="lease_end_date">تاريخ نهاية العقد <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="date" id="lease_end_date" name="lease_end_date" class="form-control"
                                            required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="lease_duration_months">مدة الإيجار (شهر)</label>
                                    <div class="col-sm-9">
                                        <input type="number" id="lease_duration_months" name="lease_duration_months"
                                            class="form-control" min="1" step="1">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="monthly_rent">الإيجار الشهري <span
                                            class="text-danger">*</span></label>
                                    <div class="col-sm-9">
                                        <input type="number" id="monthly_rent" name="monthly_rent" class="form-control"
                                            min="0" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="row">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="security_deposit">التأمين</label>
                                    <div class="col-sm-9">
                                        <input type="number" id="security_deposit" name="security_deposit" class="form-control"
                                            min="0" step="0.01">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-start">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="contract_file">مرفق العقد (PDF أو صورة)</label>
                                    <div class="col-sm-9">
                                        <input type="file" id="contract_file" name="contract_file" class="form-control"
                                            accept=".pdf,.jpg,.jpeg,.png,.webp">
                                        <small class="text-body-secondary d-block mt-2">الامتدادات المسموحة: PDF, JPG, JPEG, PNG, WEBP</small>
                                        <small id="currentContractFileHint" class="text-body-secondary d-block mt-1"></small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-start">
                                    <label class="col-sm-3 col-form-label text-sm-end">معاينة المرفق</label>
                                    <div class="col-sm-9">
                                        <div id="contractFilePreview" class="rental-contract-preview">
                                            <span class="text-body-secondary">لم يتم اختيار ملف.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="row align-items-start">
                                    <label class="col-sm-3 col-form-label text-sm-end" for="notes">ملاحظات</label>
                                    <div class="col-sm-9">
                                        <textarea id="notes" name="notes" class="form-control" rows="2"
                                            placeholder="ملاحظات إضافية على العقد"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="rental-tab-review" role="tabpanel"
                        aria-labelledby="rental-tab-review-trigger">
                        <div class="alert alert-primary py-2 mb-3">
                            <i class="icon-base ti tabler-info-circle me-2"></i>
                            راجع بيانات عقد الإيجار قبل الحفظ.
                        </div>

                        <div class="row g-2">
                            <x-vuexy.review-summary-item icon="ti ti-id text-primary" label="رقم العقد"
                                value-key="contract_number" badge-class="bg-label-primary" value-class="text-truncate"
                                value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-user text-info" label="المؤجر"
                                value-key="landlord_name" badge-class="bg-label-info" value-class="text-truncate"
                                value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-user-check text-info" label="المستأجر"
                                value-key="tenant_name" badge-class="bg-label-info" value-class="text-truncate"
                                value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-building text-warning" label="رقم الوحدة"
                                value-key="unit_number" badge-class="bg-label-warning" value-class="text-truncate"
                                value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-calendar-event text-success" label="بداية العقد"
                                value-key="lease_start_date" badge-class="bg-label-success" />

                            <x-vuexy.review-summary-item icon="ti ti-calendar-time text-danger" label="نهاية العقد"
                                value-key="lease_end_date" badge-class="bg-label-danger" />

                            <x-vuexy.review-summary-item icon="ti ti-cash text-primary" label="الإيجار الشهري"
                                value-key="monthly_rent" badge-class="bg-label-primary" />

                            <x-vuexy.review-summary-item icon="ti ti-paperclip text-secondary" label="المرفق"
                                value-key="contract_file" badge-class="bg-label-secondary" value-class="text-truncate"
                                value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-file-invoice text-secondary" label="نوع كيان المؤجر"
                                value-key="landlord_entity_type" badge-class="bg-label-secondary"
                                value-class="text-truncate" value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-file-invoice text-secondary"
                                label="نوع كيان المستأجر" value-key="tenant_entity_type" badge-class="bg-label-secondary"
                                value-class="text-truncate" value-style="max-width: 220px;" />

                            <x-vuexy.review-summary-item icon="ti ti-activity-heartbeat text-primary" label="الحالة"
                                value-key="status" badge-class="bg-label-primary" value-class="review-status-badge" />

                            <x-vuexy.review-summary-item col="col-12" icon="ti ti-notes text-secondary" label="ملاحظات"
                                value-key="notes" badge-class="bg-label-secondary" value-class="text-wrap text-end"
                                value-style="max-width: 70%;" body-align="align-items-start" />
                        </div>
                    </div>
                </div>

                <div class="row mt-6 pt-3 border-top">
                    <div class="col-md-6">
                        <div class="row justify-content-end">
                            <div class="col-sm-9">
                                <button type="submit" id="rentalSaveBtn"
                                    class="btn btn-primary me-4 waves-effect waves-light">
                                    <i class="icon-base ti tabler-device-floppy me-2"></i>
                                    حفظ العقد
                                </button>
                                <button type="button" id="cancelRentalFormBtnInline"
                                    class="btn btn-label-secondary waves-effect">
                                    إلغاء متابعة
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="rentalContractViewModal" tabindex="-1" aria-hidden="true">
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
                            <div class="fw-semibold" id="view_contract_number">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">الحالة</label>
                            <div id="view_status">-</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">المؤجر</label>
                            <div class="fw-semibold" id="view_landlord_name">-</div>
                            <small class="text-body-secondary d-block" id="view_landlord_meta">-</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">المستأجر</label>
                            <div class="fw-semibold" id="view_tenant_name">-</div>
                            <small class="text-body-secondary d-block" id="view_tenant_meta">-</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">الوحدة</label>
                            <div class="fw-semibold" id="view_unit_number">-</div>
                            <small class="text-body-secondary d-block" id="view_unit_area">-</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">عنوان الوحدة</label>
                            <div class="fw-semibold" id="view_unit_address">-</div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">بداية العقد</label>
                            <div class="fw-semibold" id="view_lease_start_date">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">نهاية العقد</label>
                            <div class="fw-semibold" id="view_lease_end_date">-</div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-body-secondary">المدة</label>
                            <div class="fw-semibold" id="view_lease_duration_months">-</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">الإيجار الشهري</label>
                            <div class="fw-semibold" id="view_monthly_rent">-</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-body-secondary">التأمين</label>
                            <div class="fw-semibold" id="view_security_deposit">-</div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-body-secondary">مرفق العقد</label>
                            <div id="view_contract_file" class="rental-contract-view-file">
                                <span class="text-body-secondary">لا يوجد مرفق.</span>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label text-body-secondary">ملاحظات</label>
                            <div class="fw-semibold" id="view_notes">-</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="rentalContractsColumnsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Columns / الأعمدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="rentalColumnsChecklist" class="row g-2"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="applyRentalColumnsBtn" class="btn btn-primary">تطبيق</button>
                    <button type="button" id="resetRentalColumnsBtn" class="btn btn-label-secondary">إعادة ضبط</button>
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection

