/**
 * Sale & Purchase Contracts Ajax CRUD
 */
'use strict';

const WIZARD_STEPS = {
  PARTIES: 1,
  UNIT_AND_PAYMENT: 2,
  REVIEW: 3,
  MIN: 1,
  MAX: 3
};

const DEBOUNCE_DELAY_MS = 200;
const MIN_UNIT_AREA = 0;
const MIN_PRICE_AMOUNT = 0;
const MIN_DOWN_PAYMENT = 0;
const MIN_INSTALLMENTS_COUNT = 1;
const MIN_INSTALLMENT_AMOUNT = 0;

const HTML_ESCAPE_MAP = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;'
};

document.addEventListener('DOMContentLoaded', () => {
  const tableElement = document.querySelector('.datatables-sale-purchase-contracts');
  const modalElement = document.getElementById('salePurchaseContractModal');
  const viewModalElement = document.getElementById('salePurchaseContractViewModal');
  const formElement = document.getElementById('salePurchaseContractForm');
  if (!tableElement || !modalElement || !formElement) return;

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '';
  const routes = window.salePurchaseContractsRoutes || {};
  const listUrl = routes.list || `${baseUrl}sale-purchase-contracts/list`;
  const storeUrl = routes.store || `${baseUrl}sale-purchase-contracts`;
  const showBaseUrl = routes.showBase || `${baseUrl}sale-purchase-contracts`;
  const editBaseUrl = routes.editBase || `${baseUrl}sale-purchase-contracts`;
  const deleteBaseUrl = routes.deleteBase || `${baseUrl}sale-purchase-contracts`;

  const statusLabels = { draft: 'مسودة', active: 'نشط', completed: 'مكتمل', cancelled: 'ملغي' };
  const statusBadge = { draft: 'secondary', active: 'success', completed: 'primary', cancelled: 'danger' };
  const entityTypeLabels = { individual: 'فرد', company: 'شركة', sole_proprietorship: 'مؤسسة فردية' };
  const paymentMethodLabels = {
    cash: 'نقدي',
    bank_transfer: 'تحويل بنكي',
    installments: 'أقساط'
  };

  const modalInstance = new bootstrap.Modal(modalElement);
  const viewModalInstance = viewModalElement ? new bootstrap.Modal(viewModalElement) : null;
  const stepperElement = document.getElementById('salePurchaseContractsStepper');
  const stepper = stepperElement ? new Stepper(stepperElement, { linear: false, animation: true }) : null;

  const modalTitle = document.getElementById('salePurchaseContractModalLabel');
  const addButton = document.getElementById('addSalePurchaseContractBtn');
  const idInput = document.getElementById('sale_purchase_contract_id');
  const contractNumberInput = document.getElementById('sp_contract_number_display');

  const contractWordInput = document.getElementById('contract_word');
  const signedPdfInput = document.getElementById('signed_pdf');
  const contractWordPreview = document.getElementById('contractWordPreview');
  const signedPdfPreview = document.getElementById('signedPdfPreview');
  const currentContractWordHint = document.getElementById('currentContractWordHint');
  const currentSignedPdfHint = document.getElementById('currentSignedPdfHint');

  const remainingAmountDisplay = document.getElementById('remaining_amount_display');

  const prevButton = document.getElementById('spPrevBtn');
  const nextButton = document.getElementById('spNextBtn');
  const saveButton = document.getElementById('spSaveBtn');

  const reviewElements = document.querySelectorAll('[data-review]');
  const reviewStatusElement = formElement.querySelector('.review-status-badge[data-review="status"]');

  const columnsModalEl = document.getElementById('salePurchaseContractsColumnsModal');
  const columnsChecklistElement = document.getElementById('spColumnsChecklist');
  const applyColumnsButton = document.getElementById('applySpColumnsBtn');
  const resetColumnsButton = document.getElementById('resetSpColumnsBtn');
  const columnsModal = columnsModalEl ? new bootstrap.Modal(columnsModalEl) : null;

  const columnsMeta = [
    { idx: 0, key: 'id', label: 'م', defaultVisible: false, exportable: true },
    { idx: 1, key: 'contract_number', label: 'رقم العقد', defaultVisible: true, exportable: true },
    { idx: 2, key: 'parties', label: 'الأطراف', defaultVisible: true, exportable: true },
    { idx: 3, key: 'unit', label: 'الوحدة', defaultVisible: true, exportable: true },
    { idx: 4, key: 'contract_date', label: 'تاريخ العقد', defaultVisible: true, exportable: true },
    { idx: 5, key: 'total_price', label: 'إجمالي الثمن', defaultVisible: true, exportable: true },
    { idx: 6, key: 'status', label: 'الحالة', defaultVisible: true, exportable: true },
    { idx: 7, key: 'action', label: 'إجراءات', defaultVisible: true, exportable: false }
  ];
  const columnsStorageKey = 'sp_visible_columns';
  const visibleExportColumnsSelector = ':visible:not(.no-export)';

  const editableFieldNames = [
    'seller_name',
    'seller_entity_type',
    'seller_national_id',
    'seller_address',
    'buyer_name',
    'buyer_entity_type',
    'buyer_national_id',
    'buyer_address',
    'unit_number',
    'unit_address',
    'unit_area_sqm',
    'unit_description',
    'contract_date',
    'delivery_date',
    'currency',
    'total_price',
    'down_payment',
    'payment_method',
    'installments_count',
    'installment_amount',
    'first_installment_date',
    'status',
    'notes'
  ];

  const STEP_ONE_RULES = [
    { name: 'seller_name', rules: { required: true } },
    { name: 'seller_entity_type', rules: { required: true } },
    { name: 'buyer_name', rules: { required: true } },
    { name: 'buyer_entity_type', rules: { required: true } }
  ];

  const STEP_TWO_RULES = [
    { name: 'unit_number', rules: { required: true } },
    { name: 'unit_address', rules: { required: true } },
    { name: 'contract_date', rules: { required: true } },
    {
      name: 'unit_area_sqm',
      rules: { type: 'number', min: MIN_UNIT_AREA, minMessage: 'المساحة يجب أن تكون رقمًا موجبًا أو صفر.' }
    },
    {
      name: 'total_price',
      rules: {
        required: true,
        type: 'number',
        min: MIN_PRICE_AMOUNT,
        minMessage: 'إجمالي الثمن يجب أن يكون رقمًا موجبًا أو صفر.'
      }
    },
    {
      name: 'down_payment',
      rules: {
        type: 'number',
        min: MIN_DOWN_PAYMENT,
        minMessage: 'المقدم يجب أن يكون رقمًا موجبًا أو صفر.'
      }
    },
    { name: 'payment_method', rules: { required: true } },
    { name: 'status', rules: { required: true } },
    {
      name: 'installments_count',
      rules: {
        type: 'number',
        min: MIN_INSTALLMENTS_COUNT,
        minMessage: 'عدد الأقساط يجب أن يكون 1 على الأقل.'
      }
    },
    {
      name: 'installment_amount',
      rules: {
        type: 'number',
        min: MIN_INSTALLMENT_AMOUNT,
        minMessage: 'قيمة القسط يجب أن تكون رقمًا موجبًا أو صفر.'
      }
    }
  ];

  const viewElements = {
    contractNumber: document.getElementById('sp_view_contract_number'),
    status: document.getElementById('sp_view_status'),
    sellerName: document.getElementById('sp_view_seller_name'),
    sellerMeta: document.getElementById('sp_view_seller_meta'),
    buyerName: document.getElementById('sp_view_buyer_name'),
    buyerMeta: document.getElementById('sp_view_buyer_meta'),
    unitNumber: document.getElementById('sp_view_unit_number'),
    unitArea: document.getElementById('sp_view_unit_area'),
    currency: document.getElementById('sp_view_currency'),
    unitAddress: document.getElementById('sp_view_unit_address'),
    unitDescription: document.getElementById('sp_view_unit_description'),
    contractDate: document.getElementById('sp_view_contract_date'),
    deliveryDate: document.getElementById('sp_view_delivery_date'),
    paymentMethod: document.getElementById('sp_view_payment_method'),
    totalPrice: document.getElementById('sp_view_total_price'),
    downPayment: document.getElementById('sp_view_down_payment'),
    remainingAmount: document.getElementById('sp_view_remaining_amount'),
    installmentsCount: document.getElementById('sp_view_installments_count'),
    installmentAmount: document.getElementById('sp_view_installment_amount'),
    firstInstallmentDate: document.getElementById('sp_view_first_installment_date'),
    contractWord: document.getElementById('sp_view_contract_word'),
    signedPdf: document.getElementById('sp_view_signed_pdf'),
    notes: document.getElementById('sp_view_notes')
  };

  const elementCache = new Map();
  const pendingRequests = new Map();
  const activeFetchControllers = new Map();

  let tableInstance = null;
  let currentStep = WIZARD_STEPS.PARTIES;
  let previewObjectUrls = { contractWord: '', signedPdf: '' };
  let formSnapshot = null;
  let isSubmitting = false;

  const clearElementCache = () => {
    elementCache.clear();
  };

  const getCachedElement = selector => {
    if (!elementCache.has(selector)) {
      elementCache.set(selector, formElement.querySelector(selector));
    }
    return elementCache.get(selector);
  };

  const getFieldElement = name => getCachedElement(`[name="${name}"]`);
  const getFieldValue = name => String(getFieldElement(name)?.value ?? '').trim();

  const safeText = (value, fallback = '-') => (value ?? '').toString().trim() || fallback;

  const stripHtml = data => {
    const tmp = document.createElement('div');
    tmp.innerHTML = String(data ?? '');
    return (tmp.textContent || tmp.innerText || '').trim();
  };

  const escapeHtml = text => safeText(text, '').replace(/[&<>"']/g, ch => HTML_ESCAPE_MAP[ch]);

  const sanitizeUrl = url => {
    if (!url) return '';
    try {
      const parsed = new URL(String(url), window.location.origin);
      if (!['http:', 'https:'].includes(parsed.protocol)) return '';
      return parsed.href;
    } catch (error) {
      return '';
    }
  };

  const sanitizePreviewUrl = (url, allowBlob = false) => {
    const normalized = String(url ?? '').trim();
    if (!normalized) return '';
    if (allowBlob && normalized.startsWith('blob:')) return normalized;
    return sanitizeUrl(normalized);
  };

  const formatMoney = value => {
    const normalized = (value ?? '').toString().trim();
    if (!normalized) return '-';
    const num = Number(normalized);
    if (Number.isNaN(num)) return '-';
    return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  };

  const showAlert = (icon, title, text) =>
    Swal.fire({
      icon,
      title,
      text,
      customClass: { confirmButton: 'btn btn-primary' },
      buttonsStyling: false
    });

  const hasSelectedRows = dt => dt && dt.rows({ selected: true }).count() > 0;
  const requireSelectedRowsForExport = dt => {
    if (hasSelectedRows(dt)) return true;
    showAlert('error', 'لا توجد صفوف محددة', 'حدد صفاً واحداً على الأقل قبل التصدير.');
    return false;
  };

  const runDefaultExportAction = (buttonType, context, e, dt, button, config) => {
    const action = $.fn?.dataTable?.ext?.buttons?.[buttonType]?.action;
    if (typeof action === 'function') {
      action.call(context, e, dt, button, config);
    }
  };

  const setButtonLoading = (button, isLoading, loadingText = 'جاري التحميل...') => {
    if (!button) return;
    if (isLoading) {
      if (!button.dataset.originalText) {
        button.dataset.originalText = button.innerHTML;
      }
      button.disabled = true;
      button.innerHTML =
        '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' +
        loadingText;
      return;
    }

    button.disabled = false;
    if (button.dataset.originalText) {
      button.innerHTML = button.dataset.originalText;
      delete button.dataset.originalText;
    }
  };

  const getDefaultVisibleColumns = () => {
    const result = new Set();
    for (const column of columnsMeta) {
      if (column.key !== 'action' && column.defaultVisible) {
        result.add(column.idx);
      }
    }
    return result;
  };

  const getStoredVisibleColumns = () => {
    try {
      const raw = localStorage.getItem(columnsStorageKey);
      if (!raw) return null;

      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) return null;

      const allowedIndexes = new Set();
      const visibleIndexes = new Set();

      for (const column of columnsMeta) {
        if (column.key !== 'action') {
          allowedIndexes.add(column.idx);
        }
      }

      for (const value of parsed) {
        const num = Number(value);
        if (Number.isInteger(num) && allowedIndexes.has(num)) {
          visibleIndexes.add(num);
        }
      }

      return visibleIndexes;
    } catch (error) {
      console.warn('Failed to retrieve stored columns:', error);
      return null;
    }
  };

  const saveVisibleColumns = visibleSet => {
    try {
      localStorage.setItem(columnsStorageKey, JSON.stringify(Array.from(visibleSet)));
    } catch (error) {
      console.error('Failed to save column visibility:', error);
      showAlert('warning', 'تحذير', 'تعذر حفظ تفضيلات الأعمدة.');
    }
  };

  const clearStoredVisibleColumns = () => {
    try {
      localStorage.removeItem(columnsStorageKey);
    } catch (error) {
      console.warn('Failed to clear column visibility preferences:', error);
    }
  };

  const applyColumnsVisibility = (dt, visibleSet) => {
    if (!dt) return;
    dt.columns().every(function (idx) {
      const column = columnsMeta[idx];
      const shouldBeVisible = column?.key === 'action' ? true : visibleSet.has(idx);
      this.visible(shouldBeVisible, false);
    });
    dt.columns.adjust();
    dt.draw(false);
  };

  const buildColumnsChecklist = () => {
    if (!columnsChecklistElement) return;
    columnsChecklistElement.innerHTML = columnsMeta
      .filter(column => column.key !== 'action')
      .map(
        column => `
          <div class="col-12 col-sm-6">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="sp-col-${column.idx}" data-col="${column.idx}">
              <label
                class="form-check-label d-flex align-items-center justify-content-between w-100 gap-2"
                for="sp-col-${column.idx}"
              >
                <span>${escapeHtml(column.label)}</span>
                <span class="badge bg-label-secondary">${escapeHtml(column.key)}</span>
              </label>
            </div>
          </div>`
      )
      .join('');
  };

  const syncChecklistFromTable = dt => {
    if (!columnsChecklistElement || !dt) return;
    columnsChecklistElement.querySelectorAll('input[data-col]').forEach(input => {
      const columnIndex = Number(input.getAttribute('data-col'));
      input.checked = dt.column(columnIndex).visible();
    });
  };

  const updateWizardActions = () => {
    const onFirst = currentStep <= WIZARD_STEPS.MIN;
    const onReview = currentStep >= WIZARD_STEPS.REVIEW;
    prevButton?.classList.toggle('d-none', onFirst);
    nextButton?.classList.toggle('d-none', onReview);
    saveButton?.classList.toggle('d-none', !onReview);
  };

  const goToStep = stepNumber => {
    currentStep = Math.min(WIZARD_STEPS.MAX, Math.max(WIZARD_STEPS.MIN, stepNumber));
    if (stepper) {
      stepper.to(currentStep);
    }
    updateWizardActions();
  };

  const clearFieldErrors = () => {
    formElement.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    formElement.querySelectorAll('.invalid-feedback.dynamic-invalid').forEach(el => el.remove());
  };

  const setFieldError = (fieldName, message) => {
    const field = getFieldElement(fieldName);
    if (!field) return;
    field.classList.add('is-invalid');

    const fieldWrapper = field.parentElement || field;
    let feedback = fieldWrapper.querySelector('.invalid-feedback.dynamic-invalid');
    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback dynamic-invalid';
      fieldWrapper.appendChild(feedback);
    }

    feedback.textContent = Array.isArray(message) ? message[0] : message;
  };

  const validateField = (name, rules = {}) => {
    const element = getFieldElement(name);
    if (!element) return true;

    const value = String(element.value ?? '').trim();

    if (rules.required && value === '') {
      setFieldError(name, rules.requiredMessage || 'هذا الحقل مطلوب.');
      return false;
    }

    if (value === '' && !rules.required) return true;

    if (rules.type === 'number') {
      const num = Number(value);
      if (Number.isNaN(num)) {
        setFieldError(name, 'يجب أن يكون رقمًا صحيحًا.');
        return false;
      }

      if (rules.min !== undefined && num < rules.min) {
        setFieldError(name, rules.minMessage || `القيمة يجب أن تكون ${rules.min} على الأقل.`);
        return false;
      }
    }

    return true;
  };

  const isInstallmentsPayment = () => getFieldValue('payment_method') === 'installments';

  const toggleInstallmentsFields = () => {
    const shouldShow = $('#payment_method').val() === 'installments';
    $('#installmentsFieldsWrapper').toggleClass('d-none', !shouldShow);

    ['installments_count', 'installment_amount', 'first_installment_date'].forEach(fieldName => {
      const field = getFieldElement(fieldName);
      if (!field) return;
      field.required = shouldShow;
      if (!shouldShow) {
        field.value = '';
      }
    });
  };

  const calculateRemainingAmount = () => {
    const totalPrice = Number(getFieldValue('total_price') || 0);
    const downPayment = Number(getFieldValue('down_payment') || 0);
    if (Number.isNaN(totalPrice) || Number.isNaN(downPayment)) return 0;
    return Math.max(0, totalPrice - downPayment);
  };

  const updateRemainingAmountDisplay = () => {
    if (!remainingAmountDisplay) return;
    remainingAmountDisplay.value = formatMoney(calculateRemainingAmount());
  };

  const validateStepOne = () => {
    clearFieldErrors();

    let valid = true;
    for (const { name, rules } of STEP_ONE_RULES) {
      if (!validateField(name, rules)) {
        valid = false;
      }
    }

    if (!valid) {
      showAlert('error', 'تحقق من البيانات', 'يرجى استكمال بيانات البائع والمشتري.');
    }

    return valid;
  };

  const validateStepTwo = () => {
    clearFieldErrors();

    let valid = true;

    for (const { name, rules } of STEP_TWO_RULES) {
      const isInstallmentRule = ['installments_count', 'installment_amount'].includes(name);
      if (isInstallmentRule && !isInstallmentsPayment()) continue;

      if (!validateField(name, rules)) {
        valid = false;
      }
    }

    const totalPrice = Number(getFieldValue('total_price') || 0);
    const downPayment = Number(getFieldValue('down_payment') || 0);
    if (!Number.isNaN(totalPrice) && !Number.isNaN(downPayment) && downPayment > totalPrice) {
      valid = false;
      setFieldError('down_payment', 'المقدم يجب أن يكون أقل من أو يساوي إجمالي الثمن.');
    }

    if (isInstallmentsPayment()) {
      if (
        !validateField('installments_count', {
          required: true,
          type: 'number',
          min: MIN_INSTALLMENTS_COUNT,
          minMessage: 'عدد الأقساط يجب أن يكون 1 على الأقل.'
        })
      ) {
        valid = false;
      }

      if (
        !validateField('installment_amount', {
          required: true,
          type: 'number',
          min: MIN_INSTALLMENT_AMOUNT,
          minMessage: 'قيمة القسط يجب أن تكون رقمًا موجبًا أو صفر.'
        })
      ) {
        valid = false;
      }

      if (!validateField('first_installment_date', { required: true })) {
        valid = false;
      }
    }

    if (!valid) {
      showAlert('error', 'تحقق من البيانات', 'يرجى استكمال بيانات الوحدة والسداد بشكل صحيح.');
    }

    return valid;
  };

  const updateContractNumberInput = value => {
    if (!contractNumberInput) return;
    contractNumberInput.value = safeText(value, '');
    contractNumberInput.placeholder = 'سيتم التوليد تلقائيًا';
  };

  const createFilePreview = (fileUrl, mimeType, fileName, options = {}) => {
    const isNewFile = Boolean(options.isNewFile);
    const allowBlob = Boolean(options.allowBlob);
    const emptyHtml = options.emptyHtml || '<span class="text-body-secondary">لا يوجد ملف.</span>';
    const pdfLabel = options.pdfLabel || (isNewFile ? 'معاينة PDF' : 'فتح PDF');
    const genericLabel = options.genericLabel || 'فتح الملف';
    const showPlainTextForGeneric = Boolean(options.showPlainTextForGeneric);

    const cleanUrl = sanitizePreviewUrl(fileUrl, allowBlob);
    if (!cleanUrl) {
      return { html: emptyHtml, hint: '', cleanUrl: '' };
    }

    const safeFileNameText = safeText(fileName, 'الملف');
    const safeFileNameHtml = escapeHtml(safeFileNameText);
    const hintPrefix = isNewFile ? 'ملف جديد:' : 'الملف الحالي:';

    if ((mimeType || '').includes('pdf')) {
      return {
        html:
          `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-label-danger btn-sm">` +
          `<i class="icon-base ti tabler-file-type-pdf me-1"></i> ${escapeHtml(pdfLabel)}` +
          '</a>',
        hint: `${hintPrefix} ${safeFileNameText}`,
        cleanUrl
      };
    }

    if (showPlainTextForGeneric) {
      return {
        html: `<span class="text-body-secondary">${safeFileNameHtml}</span>`,
        hint: `${hintPrefix} ${safeFileNameText}`,
        cleanUrl
      };
    }

    return {
      html:
        `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-label-secondary btn-sm">` +
        `<i class="icon-base ti tabler-file me-1"></i> ${escapeHtml(genericLabel)}` +
        '</a>',
      hint: `${hintPrefix} ${safeFileNameText}`,
      cleanUrl
    };
  };

  const clearPreviewObjectUrl = key => {
    const url = previewObjectUrls[key];
    if (!url) return;
    URL.revokeObjectURL(url);
    previewObjectUrls[key] = '';
  };

  const clearAllPreviewObjectUrls = () => {
    clearPreviewObjectUrl('contractWord');
    clearPreviewObjectUrl('signedPdf');
  };

  const clearContractWordPreview = () => {
    clearPreviewObjectUrl('contractWord');
    if (contractWordPreview) {
      contractWordPreview.innerHTML = '<span class="text-body-secondary">لا يوجد ملف.</span>';
    }
    if (currentContractWordHint) {
      currentContractWordHint.textContent = '';
      currentContractWordHint.dataset.fileUrl = '';
      currentContractWordHint.dataset.fileMime = '';
      currentContractWordHint.dataset.fileName = '';
    }
  };

  const clearSignedPdfPreview = () => {
    clearPreviewObjectUrl('signedPdf');
    if (signedPdfPreview) {
      signedPdfPreview.innerHTML = '<span class="text-body-secondary">لا يوجد ملف.</span>';
    }
    if (currentSignedPdfHint) {
      currentSignedPdfHint.textContent = '';
      currentSignedPdfHint.dataset.fileUrl = '';
      currentSignedPdfHint.dataset.fileMime = '';
      currentSignedPdfHint.dataset.fileName = '';
    }
  };

  const showExistingContractWord = (fileUrl, mimeType, fileName) => {
    if (!contractWordPreview || !currentContractWordHint) return;
    clearPreviewObjectUrl('contractWord');

    const preview = createFilePreview(fileUrl, mimeType, fileName, {
      isNewFile: false,
      allowBlob: false,
      emptyHtml: '<span class="text-body-secondary">لا يوجد ملف Word.</span>',
      genericLabel: 'فتح Word'
    });

    contractWordPreview.innerHTML = preview.html;
    currentContractWordHint.textContent = preview.hint;
    currentContractWordHint.dataset.fileUrl = preview.cleanUrl;
    currentContractWordHint.dataset.fileMime = mimeType || '';
    currentContractWordHint.dataset.fileName = safeText(fileName, '');
  };

  const showSelectedContractWord = file => {
    if (!contractWordPreview || !currentContractWordHint) return;

    if (!file) {
      clearContractWordPreview();
      updateReview();
      return;
    }

    clearPreviewObjectUrl('contractWord');
    previewObjectUrls.contractWord = URL.createObjectURL(file);

    const preview = createFilePreview(previewObjectUrls.contractWord, file.type, file.name, {
      isNewFile: true,
      allowBlob: true,
      emptyHtml: '<span class="text-body-secondary">لا يوجد ملف.</span>',
      genericLabel: 'معاينة Word',
      showPlainTextForGeneric: true
    });

    contractWordPreview.innerHTML = preview.html;
    currentContractWordHint.textContent = preview.hint;
    currentContractWordHint.dataset.fileUrl = '';
    currentContractWordHint.dataset.fileMime = file.type || '';
    currentContractWordHint.dataset.fileName = safeText(file.name, '');

    updateReview();
  };

  const showExistingSignedPdf = (fileUrl, mimeType, fileName) => {
    if (!signedPdfPreview || !currentSignedPdfHint) return;
    clearPreviewObjectUrl('signedPdf');

    const preview = createFilePreview(fileUrl, mimeType, fileName, {
      isNewFile: false,
      allowBlob: false,
      emptyHtml: '<span class="text-body-secondary">لا يوجد PDF موقع.</span>',
      pdfLabel: 'فتح PDF'
    });

    signedPdfPreview.innerHTML = preview.html;
    currentSignedPdfHint.textContent = preview.hint;
    currentSignedPdfHint.dataset.fileUrl = preview.cleanUrl;
    currentSignedPdfHint.dataset.fileMime = mimeType || '';
    currentSignedPdfHint.dataset.fileName = safeText(fileName, '');
  };

  const showSelectedSignedPdf = file => {
    if (!signedPdfPreview || !currentSignedPdfHint) return;

    if (!file) {
      clearSignedPdfPreview();
      updateReview();
      return;
    }

    clearPreviewObjectUrl('signedPdf');
    previewObjectUrls.signedPdf = URL.createObjectURL(file);

    const preview = createFilePreview(previewObjectUrls.signedPdf, file.type, file.name, {
      isNewFile: true,
      allowBlob: true,
      emptyHtml: '<span class="text-body-secondary">لا يوجد ملف.</span>',
      pdfLabel: 'معاينة PDF'
    });

    signedPdfPreview.innerHTML = preview.html;
    currentSignedPdfHint.textContent = preview.hint;
    currentSignedPdfHint.dataset.fileUrl = '';
    currentSignedPdfHint.dataset.fileMime = file.type || '';
    currentSignedPdfHint.dataset.fileName = safeText(file.name, '');

    updateReview();
  };

  const updateReview = () => {
    updateRemainingAmountDisplay();

    const currentStatus = getFieldValue('status');
    const reviewData = {
      contract_number: safeText(contractNumberInput?.value, 'سيتم التوليد تلقائيًا بعد الحفظ'),
      seller_name: safeText(getFieldValue('seller_name')),
      buyer_name: safeText(getFieldValue('buyer_name')),
      unit_number: safeText(getFieldValue('unit_number')),
      contract_date: safeText(getFieldValue('contract_date')),
      total_price: formatMoney(getFieldValue('total_price')),
      remaining_amount: formatMoney(calculateRemainingAmount()),
      payment_method: paymentMethodLabels[getFieldValue('payment_method')] || '-',
      contract_word: '-',
      signed_pdf: '-',
      status: statusLabels[currentStatus] || '-',
      notes: safeText(getFieldValue('notes'))
    };

    const uploadedWord = contractWordInput?.files && contractWordInput.files.length ? contractWordInput.files[0] : null;
    const uploadedSignedPdf = signedPdfInput?.files && signedPdfInput.files.length ? signedPdfInput.files[0] : null;

    if (uploadedWord) {
      reviewData.contract_word = safeText(uploadedWord.name);
    } else if (currentContractWordHint?.dataset.fileName) {
      reviewData.contract_word = safeText(currentContractWordHint.dataset.fileName);
    }

    if (uploadedSignedPdf) {
      reviewData.signed_pdf = safeText(uploadedSignedPdf.name);
    } else if (currentSignedPdfHint?.dataset.fileName) {
      reviewData.signed_pdf = safeText(currentSignedPdfHint.dataset.fileName);
    }

    reviewElements.forEach(element => {
      const key = element.dataset.review;
      element.textContent = reviewData[key] || '-';
    });

    if (reviewStatusElement) {
      reviewStatusElement.className = `badge review-status-badge bg-label-${statusBadge[currentStatus] || 'secondary'}`;
    }
  };

  const debounce = (func, wait) => {
    let timeoutId = null;
    return (...args) => {
      if (timeoutId) {
        window.clearTimeout(timeoutId);
      }
      timeoutId = window.setTimeout(() => {
        timeoutId = null;
        func(...args);
      }, wait);
    };
  };

  const debouncedUpdateReview = debounce(updateReview, DEBOUNCE_DELAY_MS);

  const saveFormState = () => {
    formSnapshot = new FormData(formElement);
  };

  const restoreFormState = () => {
    if (!formSnapshot) return;

    for (const [key, value] of formSnapshot.entries()) {
      const field = getFieldElement(key);
      if (!field || field.type === 'file') continue;
      field.value = value;
    }

    toggleInstallmentsFields();
    updateReview();
  };

  const clearFormState = () => {
    formSnapshot = null;
  };

  const populateFormFields = (data, fieldNames) => {
    fieldNames.forEach(name => {
      const field = getFieldElement(name);
      if (field) {
        field.value = data[name] ?? '';
      }
    });
  };

  const resetModalForCreate = () => {
    clearElementCache();
    formElement.reset();
    idInput.value = '';
    updateContractNumberInput('');

    const statusSelect = getFieldElement('status');
    if (statusSelect) statusSelect.value = 'active';

    const currencyField = getFieldElement('currency');
    if (currencyField) currencyField.value = 'EGP';

    const downPaymentField = getFieldElement('down_payment');
    if (downPaymentField) downPaymentField.value = '0';

    const paymentMethod = getFieldElement('payment_method');
    if (paymentMethod) paymentMethod.value = 'cash';

    clearFieldErrors();
    clearContractWordPreview();
    clearSignedPdfPreview();
    clearFormState();

    modalTitle.textContent = 'إضافة عقد جديد';
    toggleInstallmentsFields();
    updateReview();
    goToStep(WIZARD_STEPS.PARTIES);
  };

  const fillModalForEdit = data => {
    clearElementCache();
    formElement.reset();
    clearFieldErrors();

    idInput.value = data.id ?? '';
    updateContractNumberInput(data.contract_number);

    populateFormFields(data, editableFieldNames);
    showExistingContractWord(data.contract_word_file_url, data.contract_word_mime, data.contract_word_original_name);
    showExistingSignedPdf(data.signed_pdf_file_url, data.signed_pdf_mime, data.signed_pdf_original_name);

    modalTitle.textContent = `تعديل العقد #${safeText(data.contract_number)}`;
    toggleInstallmentsFields();
    updateReview();
    goToStep(WIZARD_STEPS.PARTIES);
  };

  const renderStatusCell = status => {
    const badgeClass = statusBadge[status] || 'secondary';
    const label = escapeHtml(statusLabels[status] || safeText(status));
    return `<span class="badge bg-label-${badgeClass}">${label}</span>`;
  };

  const renderContractNumberCell = row => {
    const number = escapeHtml(safeText(row.contract_number));
    const paperclip = row.has_files
      ? '<i class="icon-base ti tabler-paperclip ms-1 text-secondary" data-bs-toggle="tooltip" title="يوجد مرفقات"></i>'
      : '';
    return `<span class="fw-semibold text-nowrap">${number}</span>${paperclip}`;
  };

  const renderPartiesCell = row => {
    const seller = escapeHtml(safeText(row.seller_name));
    const buyer = escapeHtml(safeText(row.buyer_name));
    return `
      <div class="sp-parties-cell">
        <div class="seller">${seller}</div>
        <small class="buyer text-body-secondary">${buyer}</small>
      </div>
    `;
  };

  const renderUnitCell = row => {
    const unitNumber = escapeHtml(safeText(row.unit_number));
    const address = safeText(row.unit_address);
    const addressHtml = escapeHtml(address);
    return `
      <div class="sp-unit-cell">
        <div class="fw-semibold text-nowrap">${unitNumber}</div>
        <small class="address text-body-secondary" title="${addressHtml}">${addressHtml}</small>
      </div>
    `;
  };

  const setViewFilePreview = (targetElement, fileUrl, mimeType, fileName, options = {}) => {
    if (!targetElement) return;

    const preview = createFilePreview(fileUrl, mimeType, fileName, {
      isNewFile: false,
      allowBlob: false,
      emptyHtml: '<span class="text-body-secondary">لا يوجد ملف.</span>',
      pdfLabel: options.pdfLabel || safeText(fileName, 'فتح PDF'),
      genericLabel: options.genericLabel || safeText(fileName, 'فتح الملف')
    });

    targetElement.innerHTML = preview.html;
  };

  const formatMeta = (entityType, nationalId, address) =>
    [entityTypeLabels[entityType] || '-', safeText(nationalId), safeText(address)].join(' | ');

  const fillViewModal = data => {
    if (!viewModalInstance) return;

    if (viewElements.contractNumber) viewElements.contractNumber.textContent = safeText(data.contract_number);
    if (viewElements.status) viewElements.status.innerHTML = renderStatusCell(data.status);

    if (viewElements.sellerName) viewElements.sellerName.textContent = safeText(data.seller_name);
    if (viewElements.sellerMeta) {
      viewElements.sellerMeta.textContent = formatMeta(data.seller_entity_type, data.seller_national_id, data.seller_address);
    }

    if (viewElements.buyerName) viewElements.buyerName.textContent = safeText(data.buyer_name);
    if (viewElements.buyerMeta) {
      viewElements.buyerMeta.textContent = formatMeta(data.buyer_entity_type, data.buyer_national_id, data.buyer_address);
    }

    if (viewElements.unitNumber) viewElements.unitNumber.textContent = safeText(data.unit_number);
    if (viewElements.unitArea) {
      viewElements.unitArea.textContent = data.unit_area_sqm ? `${safeText(data.unit_area_sqm)} م²` : '-';
    }
    if (viewElements.currency) viewElements.currency.textContent = safeText(data.currency, 'EGP');
    if (viewElements.unitAddress) viewElements.unitAddress.textContent = safeText(data.unit_address);
    if (viewElements.unitDescription) viewElements.unitDescription.textContent = safeText(data.unit_description);

    if (viewElements.contractDate) viewElements.contractDate.textContent = safeText(data.contract_date);
    if (viewElements.deliveryDate) viewElements.deliveryDate.textContent = safeText(data.delivery_date);
    if (viewElements.paymentMethod) {
      viewElements.paymentMethod.textContent = paymentMethodLabels[data.payment_method] || safeText(data.payment_method);
    }

    if (viewElements.totalPrice) viewElements.totalPrice.textContent = formatMoney(data.total_price);
    if (viewElements.downPayment) viewElements.downPayment.textContent = formatMoney(data.down_payment);
    if (viewElements.remainingAmount) {
      viewElements.remainingAmount.textContent = formatMoney(data.remaining_amount);
    }
    if (viewElements.installmentsCount) {
      viewElements.installmentsCount.textContent = safeText(data.installments_count);
    }
    if (viewElements.installmentAmount) {
      viewElements.installmentAmount.textContent = formatMoney(data.installment_amount);
    }
    if (viewElements.firstInstallmentDate) {
      viewElements.firstInstallmentDate.textContent = safeText(data.first_installment_date);
    }

    if (viewElements.notes) viewElements.notes.textContent = safeText(data.notes);

    setViewFilePreview(
      viewElements.contractWord,
      data.contract_word_file_url,
      data.contract_word_mime,
      data.contract_word_original_name,
      { genericLabel: 'فتح Word' }
    );

    setViewFilePreview(viewElements.signedPdf, data.signed_pdf_file_url, data.signed_pdf_mime, data.signed_pdf_original_name, {
      pdfLabel: 'فتح PDF'
    });
  };

  const exportToWord = dt => {
    if (!requireSelectedRowsForExport(dt)) return;

    const exportIndexes = dt
      .columns(':visible')
      .indexes()
      .toArray()
      .filter(index => !dt.column(index).header().classList.contains('no-export'));

    if (!exportIndexes.length) {
      showAlert('error', 'لا توجد أعمدة للتصدير', 'اختر أعمدة أولاً.');
      return;
    }

    const exportData = dt.buttons.exportData({
      columns: exportIndexes,
      modifier: { selected: true },
      format: { body: data => stripHtml(data) }
    });

    const headerCells = exportData.header
      .map(header => `<th style="padding:8px;background:#f5f5f5;text-align:right;">${escapeHtml(header)}</th>`)
      .join('');

    const bodyRows = exportData.body
      .map(row => {
        const cells = row.map(cell => `<td style="padding:8px;text-align:right;">${escapeHtml(cell)}</td>`).join('');
        return `<tr>${cells}</tr>`;
      })
      .join('');

    const tableHtml = `
      <table border="1" style="border-collapse:collapse;width:100%;font-family:Arial,sans-serif;">
        <thead><tr>${headerCells}</tr></thead>
        <tbody>${bodyRows}</tbody>
      </table>
    `;

    const html = `
      <!DOCTYPE html>
      <html>
        <head>
          <meta charset="UTF-8">
          <title>Sale & Purchase Contracts</title>
        </head>
        <body dir="rtl">
          <h3 style="font-family:Arial,sans-serif;">تقرير عقود البيع والشراء</h3>
          ${tableHtml}
        </body>
      </html>
    `;

    const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `sale-purchase-contracts-${new Date().toISOString().slice(0, 10)}.doc`;
    document.body.appendChild(anchor);
    anchor.click();
    anchor.remove();
    URL.revokeObjectURL(url);
  };

  const initTooltips = () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
      const existingTooltip = bootstrap.Tooltip.getInstance(element);
      if (existingTooltip) {
        existingTooltip.dispose();
      }
      new bootstrap.Tooltip(element);
    });
  };

  const getSelectedRowsExportOptions = () => ({
    columns: visibleExportColumnsSelector,
    modifier: { selected: true },
    format: { body: data => stripHtml(data) }
  });

  const createExportButtonsConfig = () => [
    {
      text: '<i class="icon-base ti tabler-file-type-doc me-2"></i> Word',
      className: 'dropdown-item',
      action: (e, dt) => exportToWord(dt)
    },
    {
      extend: 'csv',
      text: '<i class="icon-base ti tabler-file-type-csv me-2"></i> CSV',
      className: 'dropdown-item',
      exportOptions: getSelectedRowsExportOptions(),
      action: function (e, dt, button, config) {
        if (!requireSelectedRowsForExport(dt)) return;
        runDefaultExportAction('csvHtml5', this, e, dt, button, config);
      }
    },
    {
      extend: 'pdf',
      text: '<i class="icon-base ti tabler-file-type-pdf me-2"></i> PDF',
      className: 'dropdown-item',
      exportOptions: getSelectedRowsExportOptions(),
      action: function (e, dt, button, config) {
        if (!requireSelectedRowsForExport(dt)) return;
        runDefaultExportAction('pdfHtml5', this, e, dt, button, config);
      }
    },
    {
      extend: 'print',
      text: '<i class="icon-base ti tabler-printer me-2"></i> Print',
      className: 'dropdown-item',
      exportOptions: getSelectedRowsExportOptions(),
      action: function (e, dt, button, config) {
        if (!requireSelectedRowsForExport(dt)) return;
        runDefaultExportAction('print', this, e, dt, button, config);
      }
    }
  ];

  const createDataTableButtons = () => [
    {
      extend: 'collection',
      className: 'btn btn-label-primary btn-sm dropdown-toggle',
      text: '<i class="icon-base ti tabler-download me-1"></i><span>تصدير</span>',
      buttons: createExportButtonsConfig()
    },
    {
      className: 'btn btn-label-secondary btn-sm',
      text: '<i class="icon-base ti tabler-columns me-1"></i><span>الأعمدة</span>',
      action: () => {
        if (!columnsModal) return;
        syncChecklistFromTable(tableInstance);
        columnsModal.show();
      }
    },
    {
      className: 'btn btn-label-secondary btn-sm',
      text: '<i class="icon-base ti tabler-checks me-1"></i><span>تحديد الكل</span>',
      action: (e, dt) => dt.rows({ page: 'current' }).select()
    },
    {
      className: 'btn btn-label-secondary btn-sm',
      text: '<i class="icon-base ti tabler-square-x me-1"></i><span>إلغاء</span>',
      action: (e, dt) => dt.rows({ selected: true }).deselect()
    }
  ];

  const isAbortError = error => error?.name === 'AbortError';

  const parseJsonResponse = async response => {
    try {
      return await response.json();
    } catch (error) {
      return {};
    }
  };

  const safeFetch = (key, url, options = {}) => {
    const previousController = activeFetchControllers.get(key);
    if (previousController) {
      previousController.abort();
    }

    const controller = new AbortController();
    activeFetchControllers.set(key, controller);

    return fetch(url, { ...options, signal: controller.signal }).finally(() => {
      if (activeFetchControllers.get(key) === controller) {
        activeFetchControllers.delete(key);
      }
    });
  };

  const deduplicatedFetch = (requestKey, url, options = {}, abortKey = requestKey) => {
    if (pendingRequests.has(requestKey)) {
      return pendingRequests.get(requestKey);
    }

    const requestPromise = safeFetch(abortKey, url, options).finally(() => {
      pendingRequests.delete(requestKey);
    });

    pendingRequests.set(requestKey, requestPromise);
    return requestPromise;
  };

  const handleViewRecord = async id => {
    try {
      const response = await deduplicatedFetch(`view:${id}`, `${showBaseUrl}/${id}`, { headers: { Accept: 'application/json' } }, 'view');
      const data = await parseJsonResponse(response);
      if (!response.ok) {
        throw new Error(data.message || 'فشل تحميل بيانات العرض.');
      }

      fillViewModal(data);
      viewModalInstance?.show();
    } catch (error) {
      if (isAbortError(error)) return;
      showAlert('error', 'خطأ', error.message || 'تعذر تحميل بيانات العقد.');
    }
  };

  const handleEditRecord = async id => {
    try {
      const response = await deduplicatedFetch(
        `edit:${id}`,
        `${editBaseUrl}/${id}/edit`,
        { headers: { Accept: 'application/json' } },
        'edit'
      );
      const data = await parseJsonResponse(response);
      if (!response.ok) {
        throw new Error(data.message || 'فشل تحميل البيانات.');
      }

      fillModalForEdit(data);
      modalInstance.show();
    } catch (error) {
      if (isAbortError(error)) return;
      showAlert('error', 'خطأ', error.message || 'تعذر تحميل بيانات العقد.');
    }
  };

  const handleDeleteRecord = async (id, deleteButton = null) => {
    const result = await Swal.fire({
      title: 'تأكيد الحذف',
      text: 'هل أنت متأكد من حذف هذا العقد؟ لا يمكن التراجع بعد ذلك.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'نعم، احذف',
      cancelButtonText: 'إلغاء',
      customClass: { confirmButton: 'btn btn-danger me-2', cancelButton: 'btn btn-label-secondary' },
      buttonsStyling: false
    });
    if (!result.isConfirmed) return;

    setButtonLoading(deleteButton, true, 'جاري الحذف...');

    try {
      const response = await deduplicatedFetch(
        `delete:${id}`,
        `${deleteBaseUrl}/${id}`,
        { method: 'DELETE', headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' } },
        `delete:${id}`
      );
      const data = await parseJsonResponse(response);
      if (!response.ok) {
        throw new Error(data.message || 'فشل حذف العقد.');
      }

      tableInstance.ajax.reload(null, false);
      showAlert('success', 'تم الحذف', data.message || 'تم حذف العقد بنجاح.');
    } catch (error) {
      if (isAbortError(error)) return;
      showAlert('error', 'خطأ', error.message || 'تعذر حذف العقد.');
    } finally {
      setButtonLoading(deleteButton, false);
    }
  };

  tableInstance = new DataTable(tableElement, {
    processing: true,
    serverSide: true,
    rowId: 'id',
    select: {
      style: 'multi',
      selector: 'td:not(.no-select)'
    },
    ajax: {
      url: listUrl,
      dataSrc: json => {
        if (typeof json.recordsTotal !== 'number') json.recordsTotal = 0;
        if (typeof json.recordsFiltered !== 'number') json.recordsFiltered = 0;
        return Array.isArray(json.data) ? json.data : [];
      }
    },
    columns: [
      { data: 'id' },
      { data: 'contract_number' },
      { data: 'seller_name' },
      { data: 'unit_number' },
      { data: 'contract_date' },
      { data: 'total_price' },
      { data: 'status' },
      { data: 'action' }
    ],
    columnDefs: [
      { targets: [0, 1, 4, 5], className: 'text-nowrap' },
      { targets: 0, visible: false },
      {
        targets: 1,
        render: (d, t, row) => renderContractNumberCell(row)
      },
      {
        targets: 2,
        render: (d, t, row) => renderPartiesCell(row)
      },
      {
        targets: 3,
        render: (d, t, row) => renderUnitCell(row)
      },
      { targets: 5, className: 'text-nowrap', render: data => formatMoney(data) },
      { targets: 6, render: data => renderStatusCell(data) },
      {
        targets: 7,
        orderable: false,
        searchable: false,
        className: 'text-nowrap no-export no-select',
        render: (d, t, row) => {
          const rowId = escapeHtml(safeText(row.id, ''));
          return `
          <div class="dropdown">
            <button
              type="button"
              class="btn btn-sm btn-icon btn-label-secondary dropdown-toggle hide-arrow"
              data-bs-toggle="dropdown"
              aria-expanded="false"
            >
              <i class="icon-base ti tabler-dots-vertical icon-20px"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end">
              <button type="button" class="dropdown-item d-flex align-items-center gap-2 view-record" data-id="${rowId}">
                <i class="icon-base ti tabler-eye text-info"></i>
                <span>عرض</span>
              </button>
              <button type="button" class="dropdown-item d-flex align-items-center gap-2 edit-record" data-id="${rowId}">
                <i class="icon-base ti tabler-edit text-primary"></i>
                <span>تعديل</span>
              </button>
              <button type="button" class="dropdown-item d-flex align-items-center gap-2 delete-record" data-id="${rowId}">
                <i class="icon-base ti tabler-trash text-danger"></i>
                <span>حذف</span>
              </button>
            </div>
          </div>`;
        }
      }
    ],
    dom:
      '<"row mx-1"' +
      '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-start gap-2 flex-wrap dt-action-buttons"B>' +
      '<"col-sm-12 col-md-6 d-flex align-items-center justify-content-end"f>' +
      '>' +
      '<"row mx-1"<"col-sm-12"tr>>' +
      '<"row mt-2 justify-content-between"' +
      '<"d-md-flex justify-content-between align-items-center dt-layout-start col-md-auto me-auto"i>' +
      '<"cd-md-flex justify-content-between align-items-center dt-layout-end col-md-auto ms-auto"p>' +
      '>',
    buttons: createDataTableButtons(),
    order: [[0, 'desc']],
    displayLength: 10,
    language: {
      search: '',
      searchPlaceholder: 'بحث...',
      paginate: {
        first: '<i class="icon-base ti tabler-chevrons-left scaleX-n1-rtl icon-18px"></i>',
        last: '<i class="icon-base ti tabler-chevrons-right scaleX-n1-rtl icon-18px"></i>',
        next: '<i class="icon-base ti tabler-chevron-right scaleX-n1-rtl icon-18px"></i>',
        previous: '<i class="icon-base ti tabler-chevron-left scaleX-n1-rtl icon-18px"></i>'
      }
    },
    drawCallback: initTooltips
  });

  buildColumnsChecklist();
  const storedVisibleColumns = getStoredVisibleColumns();
  const initialVisibleColumns = storedVisibleColumns || getDefaultVisibleColumns();
  applyColumnsVisibility(tableInstance, initialVisibleColumns);
  syncChecklistFromTable(tableInstance);

  columnsModalEl?.addEventListener('shown.bs.modal', () => {
    syncChecklistFromTable(tableInstance);
  });

  applyColumnsButton?.addEventListener('click', () => {
    const selectedVisibleColumns = new Set();
    columnsChecklistElement?.querySelectorAll('input[data-col]:checked').forEach(input => {
      const columnIndex = Number(input.getAttribute('data-col'));
      if (Number.isInteger(columnIndex)) {
        selectedVisibleColumns.add(columnIndex);
      }
    });

    applyColumnsVisibility(tableInstance, selectedVisibleColumns);
    saveVisibleColumns(selectedVisibleColumns);
    syncChecklistFromTable(tableInstance);
    columnsModal?.hide();
  });

  resetColumnsButton?.addEventListener('click', () => {
    clearStoredVisibleColumns();
    const defaultVisibleColumns = getDefaultVisibleColumns();
    applyColumnsVisibility(tableInstance, defaultVisibleColumns);
    syncChecklistFromTable(tableInstance);
  });

  addButton?.addEventListener('click', () => {
    clearElementCache();
    resetModalForCreate();
    modalInstance.show();
  });

  tableElement.addEventListener('click', event => {
    const viewButton = event.target.closest('.view-record');
    if (viewButton) {
      const id = viewButton.getAttribute('data-id');
      if (id) {
        handleViewRecord(id);
      }
      return;
    }

    const editButton = event.target.closest('.edit-record');
    if (editButton) {
      const id = editButton.getAttribute('data-id');
      if (id) {
        handleEditRecord(id);
      }
      return;
    }

    const deleteButton = event.target.closest('.delete-record');
    if (!deleteButton) return;
    const id = deleteButton.getAttribute('data-id');
    if (id) {
      handleDeleteRecord(id, deleteButton);
    }
  });

  formElement.addEventListener('submit', async event => {
    event.preventDefault();

    if (isSubmitting) return;

    if (!validateStepOne()) {
      goToStep(WIZARD_STEPS.PARTIES);
      return;
    }

    if (!validateStepTwo()) {
      goToStep(WIZARD_STEPS.UNIT_AND_PAYMENT);
      return;
    }

    const formData = new FormData(formElement);
    saveFormState();
    isSubmitting = true;
    setButtonLoading(saveButton, true);

    try {
      const response = await deduplicatedFetch(
        'save-sp-contract',
        storeUrl,
        {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
          body: formData
        },
        'save-sp-contract'
      );
      const data = await parseJsonResponse(response);

      if (response.status === 422) {
        const errors = data.errors || {};
        Object.keys(errors).forEach(field => setFieldError(field, errors[field]));
        const firstField = Object.keys(errors)[0];
        const firstMessage = firstField ? errors[firstField][0] : data.message;
        throw new Error(firstMessage || 'فشل التحقق من صحة البيانات.');
      }

      if (!response.ok) {
        throw new Error(data.message || 'حدث خطأ أثناء الحفظ.');
      }

      clearFormState();
      modalInstance.hide();
      tableInstance.ajax.reload(null, false);
      showAlert('success', 'تم بنجاح', data.message || 'تم حفظ العقد بنجاح.');
    } catch (error) {
      if (!isAbortError(error)) {
        restoreFormState();
        showAlert('error', 'تعذر الحفظ', error.message || 'تعذر حفظ العقد.');
      }
    } finally {
      isSubmitting = false;
      setButtonLoading(saveButton, false);
    }
  });

  contractWordInput?.addEventListener('change', function () {
    const file = this.files && this.files.length ? this.files[0] : null;
    showSelectedContractWord(file);
  });

  signedPdfInput?.addEventListener('change', function () {
    const file = this.files && this.files.length ? this.files[0] : null;
    showSelectedSignedPdf(file);
  });

  $(document).on('change', '#payment_method', toggleInstallmentsFields);

  ['total_price', 'down_payment'].forEach(name => {
    getFieldElement(name)?.addEventListener('input', () => {
      updateRemainingAmountDisplay();
      debouncedUpdateReview();
    });
  });

  formElement.addEventListener('input', debouncedUpdateReview);
  formElement.addEventListener('change', updateReview);

  modalElement.addEventListener('hidden.bs.modal', clearAllPreviewObjectUrls);

  modalElement.addEventListener('hidden.bs.modal', () => {
    clearAllPreviewObjectUrls();
    clearElementCache();
    clearFormState();
    resetModalForCreate();
  });

  window.addEventListener('beforeunload', clearAllPreviewObjectUrls);

  nextButton?.addEventListener('click', () => {
    if (currentStep === WIZARD_STEPS.PARTIES && !validateStepOne()) return;
    if (currentStep === WIZARD_STEPS.UNIT_AND_PAYMENT && !validateStepTwo()) return;
    if (currentStep === WIZARD_STEPS.UNIT_AND_PAYMENT) {
      updateReview();
    }
    goToStep(currentStep + 1);
  });

  prevButton?.addEventListener('click', () => goToStep(currentStep - 1));

  saveButton?.addEventListener('click', () => {
    if (typeof formElement.requestSubmit === 'function') {
      formElement.requestSubmit();
    } else {
      formElement.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
  });

  stepperElement?.addEventListener('shown.bs-stepper', event => {
    if (typeof event.detail?.indexStep === 'number') {
      currentStep = event.detail.indexStep + 1;
      updateWizardActions();
    }
  });

  resetModalForCreate();
  updateWizardActions();
});
