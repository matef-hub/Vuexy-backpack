/**
 * Rental Contracts Ajax CRUD
 */
'use strict';

const FORM_TAB_IDS = {
  LANDLORD: 'rental-tab-landlord',
  TENANT: 'rental-tab-tenant',
  UNIT: 'rental-tab-unit',
  CONTRACT: 'rental-tab-contract',
  REVIEW: 'rental-tab-review'
};

const DEBOUNCE_DELAY_MS = 200;

const HTML_ESCAPE_MAP = {
  '&': '&amp;',
  '<': '&lt;',
  '>': '&gt;',
  '"': '&quot;',
  "'": '&#039;'
};

document.addEventListener('DOMContentLoaded', () => {
  const tableElement = document.querySelector('.datatables-rental-contracts');
  const listCardElement = document.getElementById('rentalContractsListCard');
  const formCardElement = document.getElementById('rentalContractFormCard');
  const viewModalElement = document.getElementById('rentalContractViewModal');
  const formElement = document.getElementById('rentalContractForm');
  if (!tableElement || !listCardElement || !formCardElement || !formElement) return;

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '';
  const routes = window.rentalContractsRoutes || {};
  const listUrl = routes.list || `${baseUrl}rental-contracts/list`;
  const storeUrl = routes.store || `${baseUrl}rental-contracts`;
  const showBaseUrl = routes.showBase || `${baseUrl}rental-contracts`;
  const editBaseUrl = routes.editBase || `${baseUrl}rental-contracts`;
  const deleteBaseUrl = routes.deleteBase || `${baseUrl}rental-contracts`;

  const statusLabels = { draft: 'مسودة', active: 'نشط', expired: 'منتهي', terminated: 'منتهي بالفسخ' };
  const statusBadge = { draft: 'secondary', active: 'success', expired: 'warning', terminated: 'danger' };
  const entityTypeLabels = { individual: 'فرد', company: 'شركة', sole_proprietorship: 'مؤسسة فردية' };

  const viewModalInstance = viewModalElement ? new bootstrap.Modal(viewModalElement) : null;
  const formTitle = document.getElementById('rentalContractFormTitle');
  const addButton = document.getElementById('addRentalContractBtn');
  const cancelFormButtons = [
    document.getElementById('cancelRentalFormBtn'),
    document.getElementById('cancelRentalFormBtnInline')
  ].filter(Boolean);
  const idInput = document.getElementById('rental_contract_id');
  const contractNumberInput = document.getElementById('contract_number_display');
  const contractFileInput = document.getElementById('contract_file');
  const contractFilePreview = document.getElementById('contractFilePreview');
  const currentContractFileHint = document.getElementById('currentContractFileHint');
  const saveButton = document.getElementById('rentalSaveBtn');
  const tabsElement = document.getElementById('rentalContractTabs');
  const reviewElements = document.querySelectorAll('[data-review]');
  const reviewStatusElement = formElement.querySelector('.review-status-badge[data-review="status"]');
  const columnsModalEl = document.getElementById('rentalContractsColumnsModal');
  const columnsChecklistElement = document.getElementById('rentalColumnsChecklist');
  const applyColumnsButton = document.getElementById('applyRentalColumnsBtn');
  const resetColumnsButton = document.getElementById('resetRentalColumnsBtn');
  const columnsModal = columnsModalEl ? new bootstrap.Modal(columnsModalEl) : null;

  const columnsMeta = [
    { idx: 0, key: 'id', label: 'م', defaultVisible: false, exportable: true },
    { idx: 1, key: 'contract_number', label: 'رقم العقد', defaultVisible: true, exportable: true },
    { idx: 2, key: 'landlord_name', label: 'المؤجر', defaultVisible: true, exportable: true },
    { idx: 3, key: 'tenant_name', label: 'المستأجر', defaultVisible: true, exportable: true },
    { idx: 4, key: 'unit_number', label: 'رقم الوحدة', defaultVisible: true, exportable: true },
    { idx: 5, key: 'lease_start_date', label: 'بداية العقد', defaultVisible: true, exportable: true },
    { idx: 6, key: 'lease_end_date', label: 'نهاية العقد', defaultVisible: false, exportable: true },
    { idx: 7, key: 'monthly_rent', label: 'الإيجار الشهري', defaultVisible: true, exportable: true },
    { idx: 8, key: 'contract_file', label: 'المرفق', defaultVisible: false, exportable: true },
    { idx: 9, key: 'status', label: 'الحالة', defaultVisible: true, exportable: true },
    { idx: 10, key: 'action', label: 'إجراءات', defaultVisible: true, exportable: false }
  ];
  const columnsStorageKey = 'rental_contracts_visible_columns';
  const visibleExportColumnsSelector = ':visible:not(.no-export)';
  const editableFieldNames = [
    'landlord_name',
    'landlord_entity_type',
    'landlord_national_id',
    'landlord_address',
    'tenant_name',
    'tenant_entity_type',
    'tenant_national_id',
    'tenant_address',
    'unit_number',
    'unit_address',
    'unit_area_sqm',
    'lease_duration_months',
    'lease_start_date',
    'lease_end_date',
    'monthly_rent',
    'security_deposit',
    'status',
    'notes'
  ];

  const viewElements = {
    contractNumber: document.getElementById('view_contract_number'),
    status: document.getElementById('view_status'),
    landlordName: document.getElementById('view_landlord_name'),
    landlordMeta: document.getElementById('view_landlord_meta'),
    tenantName: document.getElementById('view_tenant_name'),
    tenantMeta: document.getElementById('view_tenant_meta'),
    unitNumber: document.getElementById('view_unit_number'),
    unitArea: document.getElementById('view_unit_area'),
    unitAddress: document.getElementById('view_unit_address'),
    leaseStartDate: document.getElementById('view_lease_start_date'),
    leaseEndDate: document.getElementById('view_lease_end_date'),
    leaseDurationMonths: document.getElementById('view_lease_duration_months'),
    monthlyRent: document.getElementById('view_monthly_rent'),
    securityDeposit: document.getElementById('view_security_deposit'),
    contractFile: document.getElementById('view_contract_file'),
    notes: document.getElementById('view_notes')
  };

  const elementCache = new Map();
  const pendingRequests = new Map();
  const activeFetchControllers = new Map();
  const formTabTriggers = new Map();

  let tableInstance = null;
  let previewObjectUrl = '';
  let formSnapshot = null;
  let contractNumberValue = '';
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
  const getFieldValue = name => {
    const field = getFieldElement(name);
    if (!field) return '';

    if (field.type === 'radio') {
      const checkedField = formElement.querySelector(`[name="${name}"]:checked`);
      return String(checkedField?.value ?? '').trim();
    }

    if (field.type === 'checkbox') {
      return field.checked ? String(field.value ?? '').trim() : '';
    }

    return String(field.value ?? '').trim();
  };

  const setFieldValue = (name, value) => {
    const field = getFieldElement(name);
    if (!field) return;

    if (field.type === 'radio') {
      const normalizedValue = String(value ?? '').trim();
      const radios = formElement.querySelectorAll(`[name="${name}"]`);
      let matched = false;
      radios.forEach(radio => {
        const shouldCheck = String(radio.value).trim() === normalizedValue;
        radio.checked = shouldCheck;
        if (shouldCheck) matched = true;
      });
      if (!matched) {
        radios.forEach(radio => {
          radio.checked = false;
        });
      }
      return;
    }

    if (field.type === 'checkbox') {
      field.checked = Boolean(value);
      return;
    }

    field.value = value ?? '';
  };

  const sanitizeLandlordNationalId = value => String(value ?? '').replace(/\D/g, '').slice(0, 14);

  const applyLandlordNationalIdMaskValue = () => {
    const nationalIdField = getFieldElement('landlord_national_id');
    if (!nationalIdField) return;

    const cleanValue = sanitizeLandlordNationalId(nationalIdField.value);
    const generalFormatter =
      window.formatGeneral ||
      window.CleaveZen?.formatGeneral ||
      window.cleaveZen?.formatGeneral ||
      (typeof formatGeneral === 'function' ? formatGeneral : null);
    if (typeof generalFormatter === 'function') {
      nationalIdField.value = generalFormatter(cleanValue, { blocks: [14] });
      return;
    }

    nationalIdField.value = cleanValue;
  };

  const initLandlordNationalIdMask = () => {
    const nationalIdField = getFieldElement('landlord_national_id');
    if (!nationalIdField) return;

    nationalIdField.addEventListener('input', applyLandlordNationalIdMaskValue);
    nationalIdField.addEventListener('blur', applyLandlordNationalIdMaskValue);
    applyLandlordNationalIdMaskValue();
  };

  const safeText = (value, fallback = '-') => (value ?? '').toString().trim() || fallback;

  const stripHtml = data => {
    const tmp = document.createElement('div');
    tmp.innerHTML = String(data ?? '');
    return (tmp.textContent || tmp.innerText || '').trim();
  };

  const escapeHtml = text => safeText(text, '').replace(/[&<>"']/g, ch => HTML_ESCAPE_MAP[ch]);

  /**
   * Sanitizes server-provided URLs and blocks non-http(s) schemes.
   * @param {string} url
   * @returns {string}
   */
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

  /**
   * Adds a loading state to prevent duplicate click actions.
   * @param {HTMLElement|null} button
   * @param {boolean} isLoading
   * @param {string} loadingText
   */
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
              <input class="form-check-input" type="checkbox" id="rental-col-${column.idx}" data-col="${column.idx}">
              <label
                class="form-check-label d-flex align-items-center justify-content-between w-100 gap-2"
                for="rental-col-${column.idx}"
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

  const activateTabByPaneId = paneId => {
    const trigger = formTabTriggers.get(paneId);
    if (!trigger) return;
    const tabInstance = bootstrap.Tab.getOrCreateInstance(trigger);
    tabInstance.show();
  };

  const activateFirstFormTab = () => {
    activateTabByPaneId(FORM_TAB_IDS.LANDLORD);
  };

  const focusFirstFieldInPane = paneId => {
    const pane = formElement.querySelector(`#${paneId}`);
    if (!pane) return;
    const firstField = pane.querySelector(
      'input:not([type="hidden"]):not([disabled]):not([readonly]), select:not([disabled]), textarea:not([disabled])'
    );
    if (!firstField) return;
    firstField.focus({ preventScroll: true });
  };

  const scrollFieldIntoView = field => {
    if (!field) return;
    requestAnimationFrame(() => {
      field.scrollIntoView({ behavior: 'smooth', block: 'center' });
      field.focus({ preventScroll: true });
    });
  };

  const activateTabForField = field => {
    if (!field) return;
    const tabPane = field.closest('.tab-pane');
    if (!tabPane?.id) return;
    activateTabByPaneId(tabPane.id);
    scrollFieldIntoView(field);
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

  const updateContractNumberInput = value => {
    contractNumberValue = safeText(value, '');
    if (!contractNumberInput) return;
    contractNumberInput.value = contractNumberValue;
    contractNumberInput.placeholder = 'سيتم التوليد تلقائيًا';
  };

  /**
   * Centralized file preview factory to avoid duplicated rendering logic.
   * @param {string} fileUrl
   * @param {string} mimeType
   * @param {string} fileName
   * @param {{
   *  isNewFile?: boolean,
   *  allowBlob?: boolean,
   *  emptyHtml?: string,
   *  pdfLabel?: string,
   *  genericLabel?: string,
   *  showPlainTextForGeneric?: boolean
   * }} options
   * @returns {{html: string, hint: string, cleanUrl: string}}
   */
  const createFilePreview = (fileUrl, mimeType, fileName, options = {}) => {
    const isNewFile = Boolean(options.isNewFile);
    const allowBlob = Boolean(options.allowBlob);
    const emptyHtml = options.emptyHtml || '<span class="text-body-secondary">لا يوجد مرفق.</span>';
    const pdfLabel = options.pdfLabel || (isNewFile ? 'معاينة PDF' : 'فتح PDF');
    const genericLabel = options.genericLabel || 'فتح المرفق';
    const showPlainTextForGeneric = Boolean(options.showPlainTextForGeneric);

    const cleanUrl = sanitizePreviewUrl(fileUrl, allowBlob);
    if (!cleanUrl) {
      return { html: emptyHtml, hint: '', cleanUrl: '' };
    }

    const safeFileNameText = safeText(fileName, 'المرفق');
    const safeFileNameHtml = escapeHtml(safeFileNameText);
    const hintPrefix = isNewFile ? 'مرفق جديد:' : 'المرفق الحالي:';

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

    if ((mimeType || '').startsWith('image/')) {
      return {
        html: `<img src="${cleanUrl}" alt="${safeFileNameHtml}" loading="lazy">`,
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

  const clearPreviewObjectUrl = () => {
    if (!previewObjectUrl) return;
    URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = '';
  };

  const clearContractFilePreview = () => {
    clearPreviewObjectUrl();
    if (contractFilePreview) {
      contractFilePreview.innerHTML = '<span class="text-body-secondary">لم يتم اختيار ملف.</span>';
    }
    if (currentContractFileHint) {
      currentContractFileHint.textContent = '';
      currentContractFileHint.dataset.fileUrl = '';
      currentContractFileHint.dataset.fileMime = '';
      currentContractFileHint.dataset.fileName = '';
    }
  };

  const showExistingContractFile = (fileUrl, mimeType, fileName) => {
    if (!contractFilePreview && !currentContractFileHint) return;
    clearPreviewObjectUrl();

    const preview = createFilePreview(fileUrl, mimeType, fileName, {
      isNewFile: false,
      allowBlob: false,
      emptyHtml: '<span class="text-body-secondary">لا يوجد مرفق.</span>'
    });

    if (!preview.cleanUrl) {
      if (contractFilePreview) {
        contractFilePreview.innerHTML = '<span class="text-body-secondary">لا يوجد مرفق.</span>';
      }
      if (currentContractFileHint) {
        currentContractFileHint.textContent = '';
        currentContractFileHint.dataset.fileUrl = '';
        currentContractFileHint.dataset.fileMime = '';
        currentContractFileHint.dataset.fileName = '';
      }
      return;
    }

    if (contractFilePreview) {
      contractFilePreview.innerHTML = preview.html;
    }
    if (currentContractFileHint) {
      currentContractFileHint.textContent = preview.hint;
      currentContractFileHint.dataset.fileUrl = preview.cleanUrl;
      currentContractFileHint.dataset.fileMime = mimeType || '';
      currentContractFileHint.dataset.fileName = safeText(fileName, '');
    }
  };

  const showSelectedContractFile = file => {
    if (!contractFilePreview && !currentContractFileHint) return;

    if (!file) {
      clearContractFilePreview();
      updateReview();
      return;
    }

    clearPreviewObjectUrl();
    previewObjectUrl = URL.createObjectURL(file);

    const preview = createFilePreview(previewObjectUrl, file.type, file.name, {
      isNewFile: true,
      allowBlob: true,
      emptyHtml: '<span class="text-body-secondary">لم يتم اختيار ملف.</span>',
      showPlainTextForGeneric: true
    });

    if (contractFilePreview) {
      contractFilePreview.innerHTML = preview.html;
    }
    if (currentContractFileHint) {
      currentContractFileHint.textContent = preview.hint;
      currentContractFileHint.dataset.fileUrl = '';
      currentContractFileHint.dataset.fileMime = file.type || '';
      currentContractFileHint.dataset.fileName = safeText(file.name, '');
    }

    updateReview();
  };

  const updateReview = () => {
    const currentStatus = getFieldValue('status');
    const reviewData = {
      contract_number: safeText(contractNumberInput?.value || contractNumberValue, 'سيتم التوليد تلقائيًا بعد الحفظ'),
      landlord_name: safeText(getFieldValue('landlord_name')),
      landlord_entity_type: entityTypeLabels[getFieldValue('landlord_entity_type')] || '-',
      tenant_name: safeText(getFieldValue('tenant_name')),
      tenant_entity_type: entityTypeLabels[getFieldValue('tenant_entity_type')] || '-',
      unit_number: safeText(getFieldValue('unit_number')),
      lease_start_date: safeText(getFieldValue('lease_start_date')),
      lease_end_date: safeText(getFieldValue('lease_end_date')),
      monthly_rent: formatMoney(getFieldValue('monthly_rent')),
      contract_file: '-',
      status: statusLabels[currentStatus] || '-',
      notes: safeText(getFieldValue('notes'))
    };

    const uploadedFile = contractFileInput?.files && contractFileInput.files.length ? contractFileInput.files[0] : null;
    if (uploadedFile) {
      reviewData.contract_file = safeText(uploadedFile.name);
    } else if (currentContractFileHint?.dataset.fileName) {
      reviewData.contract_file = safeText(currentContractFileHint.dataset.fileName);
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

  const autoResizeNotesTextarea = () => {
    const notesField = getFieldElement('notes');
    if (!notesField) return;
    notesField.style.height = 'auto';
    notesField.style.height = `${Math.max(notesField.scrollHeight, 40)}px`;
  };

  const initNotesAutoResize = () => {
    const notesField = getFieldElement('notes');
    if (!notesField) return;
    notesField.addEventListener('input', autoResizeNotesTextarea);
    autoResizeNotesTextarea();
  };

  const saveFormState = () => {
    formSnapshot = new FormData(formElement);
  };

  const restoreFormState = () => {
    if (!formSnapshot) return;

    for (const [key, value] of formSnapshot.entries()) {
      const field = getFieldElement(key);
      if (!field || field.type === 'file') continue;
      setFieldValue(key, value);
    }

    autoResizeNotesTextarea();
    updateReview();
  };

  const clearFormState = () => {
    formSnapshot = null;
  };

  const populateFormFields = (data, fieldNames) => {
    fieldNames.forEach(name => {
      setFieldValue(name, data[name] ?? '');
    });
  };

  const scrollToFormCard = () => {
    formCardElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const scrollToListCard = () => {
    listCardElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  const showFormPage = () => {
    listCardElement.classList.add('d-none');
    formCardElement.classList.remove('d-none');
  };

  const showListPage = () => {
    formCardElement.classList.add('d-none');
    listCardElement.classList.remove('d-none');
  };

  const resetFormForCreate = (options = {}) => {
    const focusFirstField = options.focusFirstField !== false;

    clearElementCache();
    formElement.reset();
    formElement.classList.remove('was-validated');
    idInput.value = '';
    updateContractNumberInput('');

    setFieldValue('status', 'active');

    applyLandlordNationalIdMaskValue();
    autoResizeNotesTextarea();
    clearFieldErrors();
    clearContractFilePreview();
    clearFormState();

    if (formTitle) {
      formTitle.textContent = 'إضافة عقد جديد';
    }
    updateReview();
    activateFirstFormTab();
    if (focusFirstField) {
      focusFirstFieldInPane(FORM_TAB_IDS.LANDLORD);
    }
  };

  const fillFormForEdit = data => {
    clearElementCache();
    formElement.reset();
    formElement.classList.remove('was-validated');
    clearFieldErrors();

    idInput.value = data.id ?? '';
    updateContractNumberInput(data.contract_number);

    populateFormFields(data, editableFieldNames);
    applyLandlordNationalIdMaskValue();
    autoResizeNotesTextarea();
    showExistingContractFile(data.contract_file_url, data.contract_mime, data.contract_original_name);

    if (formTitle) {
      formTitle.textContent = `تعديل العقد #${safeText(data.contract_number)}`;
    }
    updateReview();
    activateFirstFormTab();
  };

  const renderStatusCell = status => {
    const badgeClass = statusBadge[status] || 'secondary';
    const label = escapeHtml(statusLabels[status] || safeText(status));
    return `<span class="badge bg-label-${badgeClass}">${label}</span>`;
  };

  const renderFileCell = row => {
    const cleanUrl = sanitizeUrl(row.contract_file_url);
    if (!cleanUrl) return '<span class="text-body-secondary">-</span>';

    const fileName = escapeHtml(safeText(row.contract_original_name, 'المرفق'));
    if ((row.contract_mime || '').startsWith('image/')) {
      return (
        `<img src="${cleanUrl}" alt="${fileName}" class="rounded" width="40" height="40" ` +
        'style="object-fit: cover;" loading="lazy">'
      );
    }

    if ((row.contract_mime || '').includes('pdf')) {
      return (
        `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-label-danger">` +
        '<i class="icon-base ti tabler-file-type-pdf me-1"></i> PDF</a>'
      );
    }

    return (
      `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-label-secondary">` +
      '<i class="icon-base ti tabler-file me-1"></i> فتح</a>'
    );
  };

  const setViewFilePreview = (fileUrl, mimeType, fileName) => {
    if (!viewElements.contractFile) return;

    const preview = createFilePreview(fileUrl, mimeType, fileName, {
      isNewFile: false,
      allowBlob: false,
      emptyHtml: '<span class="text-body-secondary">لا يوجد مرفق.</span>',
      pdfLabel: safeText(fileName, 'فتح PDF'),
      genericLabel: safeText(fileName, 'فتح المرفق')
    });

    viewElements.contractFile.innerHTML = preview.html;
  };

  const formatMeta = (entityType, nationalId, address) =>
    [entityTypeLabels[entityType] || '-', safeText(nationalId), safeText(address)].join(' | ');

  const fillViewModal = data => {
    if (!viewModalInstance) return;

    if (viewElements.contractNumber) viewElements.contractNumber.textContent = safeText(data.contract_number);
    if (viewElements.status) viewElements.status.innerHTML = renderStatusCell(data.status);
    if (viewElements.landlordName) viewElements.landlordName.textContent = safeText(data.landlord_name);
    if (viewElements.landlordMeta) {
      viewElements.landlordMeta.textContent = formatMeta(
        data.landlord_entity_type,
        data.landlord_national_id,
        data.landlord_address
      );
    }
    if (viewElements.tenantName) viewElements.tenantName.textContent = safeText(data.tenant_name);
    if (viewElements.tenantMeta) {
      viewElements.tenantMeta.textContent = formatMeta(
        data.tenant_entity_type,
        data.tenant_national_id,
        data.tenant_address
      );
    }
    if (viewElements.unitNumber) viewElements.unitNumber.textContent = safeText(data.unit_number);
    if (viewElements.unitArea) {
      viewElements.unitArea.textContent = data.unit_area_sqm ? `${safeText(data.unit_area_sqm)} م²` : '-';
    }
    if (viewElements.unitAddress) viewElements.unitAddress.textContent = safeText(data.unit_address);
    if (viewElements.leaseStartDate) viewElements.leaseStartDate.textContent = safeText(data.lease_start_date);
    if (viewElements.leaseEndDate) viewElements.leaseEndDate.textContent = safeText(data.lease_end_date);
    if (viewElements.leaseDurationMonths) {
      viewElements.leaseDurationMonths.textContent = data.lease_duration_months
        ? `${safeText(data.lease_duration_months)} شهر`
        : '-';
    }
    if (viewElements.monthlyRent) viewElements.monthlyRent.textContent = formatMoney(data.monthly_rent);
    if (viewElements.securityDeposit) viewElements.securityDeposit.textContent = formatMoney(data.security_deposit);
    if (viewElements.notes) viewElements.notes.textContent = safeText(data.notes);

    setViewFilePreview(data.contract_file_url, data.contract_mime, data.contract_original_name);
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
        const cells = row
          .map(cell => `<td style="padding:8px;text-align:right;">${escapeHtml(cell)}</td>`)
          .join('');
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
          <title>Rental Contracts</title>
        </head>
        <body dir="rtl">
          <h3 style="font-family:Arial,sans-serif;">تقرير عقود الإيجار</h3>
          ${tableHtml}
        </body>
      </html>
    `;

    const blob = new Blob(['\ufeff', html], { type: 'application/msword;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `rental-contracts-${new Date().toISOString().slice(0, 10)}.doc`;
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

  /**
   * Aborts older in-flight requests by key to avoid race conditions.
   * @param {string} key
   * @param {string} url
   * @param {RequestInit} options
   * @returns {Promise<Response>}
   */
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

  /**
   * Deduplicates in-flight requests by requestKey while still supporting per-action abort keys.
   * @param {string} requestKey
   * @param {string} url
   * @param {RequestInit} options
   * @param {string} abortKey
   * @returns {Promise<Response>}
   */
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
      const response = await deduplicatedFetch(
        `view:${id}`,
        `${showBaseUrl}/${id}`,
        { headers: { Accept: 'application/json' } },
        'view'
      );
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

      fillFormForEdit(data);
      showFormPage();
      scrollToFormCard();
      focusFirstFieldInPane(FORM_TAB_IDS.LANDLORD);
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
      { data: 'landlord_name' },
      { data: 'tenant_name' },
      { data: 'unit_number' },
      { data: 'lease_start_date' },
      { data: 'lease_end_date' },
      { data: 'monthly_rent' },
      { data: 'contract_file' },
      { data: 'status' },
      { data: 'action' }
    ],
    columnDefs: [
      { targets: [0, 1, 4, 5, 6], className: 'text-nowrap' },
      { targets: 7, className: 'text-nowrap', render: data => formatMoney(data) },
      {
        targets: 8,
        orderable: false,
        searchable: false,
        className: 'text-nowrap',
        render: (d, t, row) => renderFileCell(row)
      },
      { targets: 9, render: data => renderStatusCell(data) },
      {
        targets: 10,
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
              <button
                type="button"
                class="dropdown-item d-flex align-items-center gap-2 view-record"
                data-id="${rowId}"
              >
                <i class="icon-base ti tabler-eye text-info"></i>
                <span>عرض</span>
              </button>
              <button
                type="button"
                class="dropdown-item d-flex align-items-center gap-2 edit-record"
                data-id="${rowId}"
              >
                <i class="icon-base ti tabler-edit text-primary"></i>
                <span>تعديل</span>
              </button>
              <button
                type="button"
                class="dropdown-item d-flex align-items-center gap-2 delete-record"
                data-id="${rowId}"
              >
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
    resetFormForCreate({ focusFirstField: true });
    showFormPage();
    scrollToFormCard();
  });

  cancelFormButtons.forEach(button => {
    button.addEventListener('click', () => {
      resetFormForCreate({ focusFirstField: false });
      showListPage();
      scrollToListCard();
    });
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

    clearFieldErrors();

    if (!formElement.checkValidity()) {
      formElement.classList.add('was-validated');
      const firstInvalidField = formElement.querySelector(':invalid, .is-invalid');
      activateTabForField(firstInvalidField);
      return;
    }

    const startDate = getFieldValue('lease_start_date');
    const endDate = getFieldValue('lease_end_date');
    if (startDate && endDate && endDate < startDate) {
      setFieldError('lease_end_date', 'تاريخ نهاية العقد يجب أن يكون بعد أو يساوي تاريخ بداية العقد.');
      const dateField = getFieldElement('lease_end_date');
      activateTabForField(dateField);
      showAlert('error', 'تحقق من البيانات', 'تاريخ نهاية العقد يجب أن يكون بعد أو يساوي تاريخ بداية العقد.');
      return;
    }

    const landlordNationalIdField = getFieldElement('landlord_national_id');
    if (landlordNationalIdField) {
      landlordNationalIdField.value = sanitizeLandlordNationalId(landlordNationalIdField.value);
    }

    const formData = new FormData(formElement);
    saveFormState();
    isSubmitting = true;
    setButtonLoading(saveButton, true);

    try {
      const response = await deduplicatedFetch(
        'save-contract',
        storeUrl,
        {
          method: 'POST',
          headers: { 'X-CSRF-TOKEN': csrfToken, Accept: 'application/json' },
          body: formData
        },
        'save-contract'
      );
      const data = await parseJsonResponse(response);

      if (response.status === 422) {
        const errors = data.errors || {};
        Object.keys(errors).forEach(field => setFieldError(field, errors[field]));
        const firstInvalidField = formElement.querySelector('.is-invalid');
        activateTabForField(firstInvalidField);
        const firstField = Object.keys(errors)[0];
        const firstMessage = firstField ? errors[firstField][0] : data.message;
        throw new Error(firstMessage || 'فشل التحقق من صحة البيانات.');
      }

      if (!response.ok) {
        throw new Error(data.message || 'حدث خطأ أثناء الحفظ.');
      }

      clearFormState();
      resetFormForCreate({ focusFirstField: false });
      showListPage();
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

  contractFileInput?.addEventListener('change', function () {
    const file = this.files && this.files.length ? this.files[0] : null;
    showSelectedContractFile(file);
  });

  formElement.addEventListener('input', debouncedUpdateReview);
  formElement.addEventListener('change', updateReview);

  tabsElement?.querySelectorAll('[data-bs-toggle="tab"][data-bs-target]').forEach(trigger => {
    const targetPane = String(trigger.getAttribute('data-bs-target') || '').replace('#', '').trim();
    if (targetPane) {
      formTabTriggers.set(targetPane, trigger);
    }
  });

  const switchToFirstTabWithInvalidField = () => {
    const invalidField = formElement.querySelector('.is-invalid');
    if (!invalidField) return false;
    activateTabForField(invalidField);
    return true;
  };

  tabsElement?.addEventListener('shown.bs.tab', event => {
    const targetSelector = event.target?.getAttribute('data-bs-target') || '';
    if (targetSelector === `#${FORM_TAB_IDS.REVIEW}`) {
      updateReview();
    }
  });

  const bindEntityTypeToggle = config => {
    const selectElement = document.getElementById(config.selectId) || document.getElementById(config.fallbackSelectId);
    if (!selectElement) return;

    const updateVisibility = type => {
      const nationalWrapper = document.getElementById(config.nationalWrapperId);
      const commercialWrapper = document.getElementById(config.commercialWrapperId);
      const nationalInput = document.getElementById(config.nationalInputId);
      const commercialInput = document.getElementById(config.commercialInputId);
      if (!nationalWrapper || !commercialWrapper || !nationalInput || !commercialInput) return;

      if (type === 'individual') {
        nationalWrapper.classList.remove('d-none');
        commercialWrapper.classList.add('d-none');
        nationalInput.required = true;
        commercialInput.required = false;
        commercialInput.value = '';
        return;
      }

      nationalWrapper.classList.add('d-none');
      commercialWrapper.classList.remove('d-none');
      nationalInput.required = false;
      commercialInput.required = true;
      nationalInput.value = '';
    };

    selectElement.addEventListener('change', function () {
      updateVisibility(this.value);
    });

    updateVisibility(selectElement.value);
  };

  bindEntityTypeToggle({
    selectId: 'landlord_entity_type',
    fallbackSelectId: 'entity_type',
    nationalWrapperId: 'national_id_wrapper',
    commercialWrapperId: 'commercial_register_wrapper',
    nationalInputId: 'landlord_national_id',
    commercialInputId: 'commercial_register'
  });

  bindEntityTypeToggle({
    selectId: 'tenant_entity_type',
    fallbackSelectId: 'tenant_entity_type',
    nationalWrapperId: 'tenant_national_id_wrapper',
    commercialWrapperId: 'tenant_commercial_register_wrapper',
    nationalInputId: 'tenant_national_id',
    commercialInputId: 'tenant_commercial_register'
  });
  window.addEventListener('beforeunload', clearPreviewObjectUrl);

  initLandlordNationalIdMask();
  initNotesAutoResize();

  if (switchToFirstTabWithInvalidField()) {
    showFormPage();
    formElement.classList.add('was-validated');
    updateReview();
  } else {
    resetFormForCreate({ focusFirstField: false });
    showListPage();
  }
});
