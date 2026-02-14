/**
 * Company Documents Ajax CRUD
 */

'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const tableElement = document.querySelector('.datatables-company-documents');
  const modalElement = document.getElementById('companyDocumentModal');
  const formElement = document.getElementById('companyDocumentForm');

  if (!tableElement || !modalElement || !formElement) {
    return;
  }

  const csrfToken =
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || window.csrfToken || '';
  const routes = window.companyDocumentsRoutes || {};
  const listUrl = routes.list || `${baseUrl}company-documents/list`;
  const storeUrl = routes.store || `${baseUrl}company-documents`;
  const editBaseUrl = routes.editBase || `${baseUrl}company-documents`;
  const deleteBaseUrl = routes.deleteBase || `${baseUrl}company-documents`;

  const statusLabels = {
    active: 'نشط',
    expired: 'منتهي',
    archived: 'مؤرشف'
  };

  const statusBadge = {
    active: 'success',
    expired: 'warning',
    archived: 'secondary'
  };

  const modalInstance = new bootstrap.Modal(modalElement);
  const stepperElement = document.getElementById('companyDocumentsStepper');
  const stepper = stepperElement ? new Stepper(stepperElement, { linear: false, animation: true }) : null;
  const modalTitle = document.getElementById('companyDocumentModalLabel');
  const addButton = document.getElementById('addCompanyDocumentBtn');
  const idInput = document.getElementById('company_document_id');
  const fileInput = document.getElementById('doc_file');
  const filePreview = document.getElementById('docFilePreview');
  const currentFileHint = document.getElementById('currentFileHint');
  const prevButton = document.getElementById('companyDocPrevBtn');
  const nextButton = document.getElementById('companyDocNextBtn');
  const saveButton = document.getElementById('companyDocSaveBtn');
  const reviewElements = document.querySelectorAll('[data-review]');
  const reviewStatusElement = formElement.querySelector('.review-status-badge[data-review="status"]');
  let tableInstance = null;
  let currentStep = 1;
  let previewObjectUrl = '';

  function updateWizardActions() {
    const onFirstStep = currentStep <= 1;
    const onReviewStep = currentStep >= 3;

    prevButton?.classList.toggle('d-none', onFirstStep);
    nextButton?.classList.toggle('d-none', onReviewStep);
    saveButton?.classList.toggle('d-none', !onReviewStep);
  }

  function goToStep(stepNumber) {
    const normalizedStep = Math.min(3, Math.max(1, stepNumber));
    currentStep = normalizedStep;

    if (stepper) {
      stepper.to(normalizedStep);
    }

    updateWizardActions();
  }

  function safeText(value) {
    if (value === null || value === undefined || value === '') {
      return '-';
    }
    return String(value);
  }

  function clearFieldErrors() {
    formElement.querySelectorAll('.is-invalid').forEach(element => element.classList.remove('is-invalid'));
    formElement.querySelectorAll('.invalid-feedback.dynamic-invalid').forEach(element => element.remove());
  }

  function setFieldError(fieldName, message) {
    const field = formElement.querySelector(`[name="${fieldName}"]`);
    if (!field) return;

    field.classList.add('is-invalid');

    let feedback = field.parentElement.querySelector('.invalid-feedback.dynamic-invalid');
    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'invalid-feedback dynamic-invalid';
      field.parentElement.appendChild(feedback);
    }

    feedback.textContent = Array.isArray(message) ? message[0] : message;
  }

  function showAlert(icon, title, text) {
    Swal.fire({
      icon,
      title,
      text,
      customClass: {
        confirmButton: 'btn btn-primary'
      },
      buttonsStyling: false
    });
  }

  function clearPreviewObjectUrl() {
    if (!previewObjectUrl) return;
    URL.revokeObjectURL(previewObjectUrl);
    previewObjectUrl = '';
  }

  function clearFilePreview() {
    clearPreviewObjectUrl();
    filePreview.innerHTML = '<span class="text-body-secondary">لم يتم اختيار ملف.</span>';
    currentFileHint.textContent = '';
    currentFileHint.dataset.fileUrl = '';
    currentFileHint.dataset.fileMime = '';
    currentFileHint.dataset.fileName = '';
  }

  function showExistingFile(fileUrl, mimeType, fileName) {
    clearPreviewObjectUrl();

    if (!fileUrl) {
      clearFilePreview();
      return;
    }

    currentFileHint.textContent = `الملف الحالي: ${safeText(fileName)}`;
    currentFileHint.dataset.fileUrl = fileUrl;
    currentFileHint.dataset.fileMime = mimeType || '';
    currentFileHint.dataset.fileName = fileName || '';

    if ((mimeType || '').includes('pdf')) {
      filePreview.innerHTML = `
        <a href="${fileUrl}" target="_blank" class="btn btn-label-danger btn-sm">
          <i class="icon-base ti tabler-file-type-pdf me-1"></i>
          فتح ملف PDF
        </a>
      `;
      return;
    }

    if ((mimeType || '').startsWith('image/')) {
      filePreview.innerHTML = `<img src="${fileUrl}" alt="${safeText(fileName)}">`;
      return;
    }

    filePreview.innerHTML = `
      <a href="${fileUrl}" target="_blank" class="btn btn-label-secondary btn-sm">
        <i class="icon-base ti tabler-file me-1"></i>
        فتح الملف
      </a>
    `;
  }

  function showSelectedFile(file) {
    if (!file) {
      clearFilePreview();
      updateReview();
      return;
    }

    clearPreviewObjectUrl();
    previewObjectUrl = URL.createObjectURL(file);
    currentFileHint.textContent = `ملف جديد: ${safeText(file.name)}`;
    currentFileHint.dataset.fileUrl = '';
    currentFileHint.dataset.fileMime = file.type || '';
    currentFileHint.dataset.fileName = file.name || '';

    if ((file.type || '').includes('pdf')) {
      filePreview.innerHTML = `
        <a href="${previewObjectUrl}" target="_blank" class="btn btn-label-danger btn-sm">
          <i class="icon-base ti tabler-file-type-pdf me-1"></i>
          معاينة PDF
        </a>
      `;
      updateReview();
      return;
    }

    if ((file.type || '').startsWith('image/')) {
      filePreview.innerHTML = `<img src="${previewObjectUrl}" alt="${safeText(file.name)}">`;
      updateReview();
      return;
    }

    filePreview.innerHTML = `<span class="text-body-secondary">${safeText(file.name)}</span>`;
    updateReview();
  }

  function validateStepOne() {
    let valid = true;
    clearFieldErrors();

    ['docname', 'doc_issue_date', 'status'].forEach(name => {
      const element = formElement.querySelector(`[name="${name}"]`);
      if (!element || String(element.value).trim() !== '') return;
      valid = false;
      setFieldError(name, 'هذا الحقل مطلوب.');
    });

    const issueDate = formElement.querySelector('[name="doc_issue_date"]')?.value || '';
    const endDate = formElement.querySelector('[name="doc_end_date"]')?.value || '';
    if (issueDate && endDate && endDate < issueDate) {
      valid = false;
      setFieldError('doc_end_date', 'تاريخ الانتهاء يجب أن يكون بعد أو يساوي تاريخ الإصدار.');
    }

    if (!valid) {
      showAlert('error', 'تحقق من البيانات', 'يرجى إكمال الحقول المطلوبة قبل المتابعة.');
    }

    return valid;
  }

  function updateReview() {
    const formData = new FormData(formElement);

    const reviewData = {
      docname: safeText(formData.get('docname')),
      doc_number: safeText(formData.get('doc_number')),
      doc_type: safeText(formData.get('doc_type')),
      doc_issue_date: safeText(formData.get('doc_issue_date')),
      doc_end_date: safeText(formData.get('doc_end_date')),
      status: statusLabels[formData.get('status')] || '-',
      notes: safeText(formData.get('notes')),
      doc_file: '-'
    };

    if (fileInput.files && fileInput.files.length > 0) {
      reviewData.doc_file = safeText(fileInput.files[0].name);
    } else if (currentFileHint.dataset.fileName) {
      reviewData.doc_file = safeText(currentFileHint.dataset.fileName);
    }

    reviewElements.forEach(element => {
      const key = element.dataset.review;
      element.textContent = reviewData[key] || '-';
    });

    if (reviewStatusElement) {
      const currentStatus = formData.get('status');
      const badgeClass = statusBadge[currentStatus] || 'secondary';
      reviewStatusElement.className = `badge review-status-badge bg-label-${badgeClass}`;
    }
  }

  function resetModalForCreate() {
    formElement.reset();
    idInput.value = '';
    clearFieldErrors();
    clearFilePreview();
    modalTitle.textContent = 'إضافة مستند جديد';
    updateReview();
    goToStep(1);
  }

  function fillModalForEdit(data) {
    formElement.reset();
    clearFieldErrors();

    idInput.value = data.id || '';
    formElement.querySelector('[name="docname"]').value = data.docname || '';
    formElement.querySelector('[name="doc_number"]').value = data.doc_number || '';
    formElement.querySelector('[name="doc_type"]').value = data.doc_type || '';
    formElement.querySelector('[name="doc_issue_date"]').value = data.doc_issue_date || '';
    formElement.querySelector('[name="doc_end_date"]').value = data.doc_end_date || '';
    formElement.querySelector('[name="status"]').value = data.status || 'active';
    formElement.querySelector('[name="notes"]').value = data.notes || '';

    showExistingFile(data.doc_file_url, data.doc_mime, data.doc_original_name);
    modalTitle.textContent = `تعديل المستند #${safeText(data.id)}`;
    updateReview();
    goToStep(1);
  }

  function initTooltips() {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(element => {
      new bootstrap.Tooltip(element);
    });
  }

  function renderFileCell(row) {
    if (!row.doc_file_url) {
      return '<span class="text-body-secondary">-</span>';
    }

    if ((row.doc_mime || '').startsWith('image/')) {
      return `<img src="${row.doc_file_url}" alt="${safeText(row.doc_original_name)}" class="rounded" width="40" height="40" style="object-fit: cover;">`;
    }

    if ((row.doc_mime || '').includes('pdf')) {
      return `
        <a href="${row.doc_file_url}" target="_blank" class="btn btn-sm btn-label-danger">
          <i class="icon-base ti tabler-file-type-pdf me-1"></i> PDF
        </a>
      `;
    }

    return `
      <a href="${row.doc_file_url}" target="_blank" class="btn btn-sm btn-label-secondary">
        <i class="icon-base ti tabler-file me-1"></i> فتح
      </a>
    `;
  }

  function renderStatusCell(status) {
    const badgeClass = statusBadge[status] || 'secondary';
    const label = statusLabels[status] || status;
    return `<span class="badge bg-label-${badgeClass}">${label}</span>`;
  }

  tableInstance = new DataTable(tableElement, {
    processing: true,
    serverSide: true,
    ajax: {
      url: listUrl,
      dataSrc: function (json) {
        if (typeof json.recordsTotal !== 'number') json.recordsTotal = 0;
        if (typeof json.recordsFiltered !== 'number') json.recordsFiltered = 0;
        return Array.isArray(json.data) ? json.data : [];
      }
    },
    columns: [
      { data: 'id' },
      { data: 'docname' },
      { data: 'doc_number' },
      { data: 'doc_type' },
      { data: 'doc_issue_date' },
      { data: 'doc_end_date' },
      { data: 'doc_file' },
      { data: 'status' },
      { data: 'action' }
    ],
    columnDefs: [
      {
        targets: [0, 4, 5],
        className: 'text-nowrap'
      },
      {
        targets: 5,
        render: function (data) {
          return safeText(data);
        }
      },
      {
        targets: 6,
        orderable: false,
        searchable: false,
        render: function (data, type, row) {
          return renderFileCell(row);
        }
      },
      {
        targets: 7,
        render: function (data) {
          return renderStatusCell(data);
        }
      },
      {
        targets: 8,
        orderable: false,
        searchable: false,
        className: 'text-nowrap',
        render: function (data, type, row) {
          return `
            <div class="d-flex align-items-center gap-2">
              <button type="button" class="btn btn-sm btn-icon text-primary edit-record" data-id="${row.id}" data-bs-toggle="tooltip" title="تعديل">
                <i class="icon-base ti tabler-edit icon-20px"></i>
              </button>
              <button type="button" class="btn btn-sm btn-icon text-danger delete-record" data-id="${row.id}" data-bs-toggle="tooltip" title="حذف">
                <i class="icon-base ti tabler-trash icon-20px"></i>
              </button>
            </div>
          `;
        }
      }
    ],
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
    drawCallback: function () {
      initTooltips();
    }
  });

  addButton?.addEventListener('click', function () {
    resetModalForCreate();
    modalInstance.show();
  });

  tableElement.addEventListener('click', function (event) {
    const editButton = event.target.closest('.edit-record');
    if (editButton) {
      const id = editButton.getAttribute('data-id');
      fetch(`${editBaseUrl}/${id}/edit`, {
        headers: {
          Accept: 'application/json'
        }
      })
        .then(async response => {
          const data = await response.json();
          if (!response.ok) {
            throw new Error(data.message || 'فشل تحميل البيانات.');
          }
          return data;
        })
        .then(data => {
          fillModalForEdit(data);
          modalInstance.show();
        })
        .catch(error => {
          showAlert('error', 'خطأ', error.message || 'تعذر تحميل بيانات المستند.');
        });
      return;
    }

    const deleteButton = event.target.closest('.delete-record');
    if (!deleteButton) {
      return;
    }

    const id = deleteButton.getAttribute('data-id');
    Swal.fire({
      title: 'تأكيد الحذف',
      text: 'هل أنت متأكد من حذف هذا المستند؟ لا يمكن التراجع بعد ذلك.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'نعم، احذف',
      cancelButtonText: 'إلغاء',
      customClass: {
        confirmButton: 'btn btn-danger me-2',
        cancelButton: 'btn btn-label-secondary'
      },
      buttonsStyling: false
    }).then(result => {
      if (!result.isConfirmed) return;

      fetch(`${deleteBaseUrl}/${id}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          Accept: 'application/json'
        }
      })
        .then(async response => {
          const data = await response.json();
          if (!response.ok) {
            throw new Error(data.message || 'فشل حذف المستند.');
          }
          return data;
        })
        .then(data => {
          tableInstance.ajax.reload(null, false);
          showAlert('success', 'تم الحذف', data.message || 'تم حذف المستند بنجاح.');
        })
        .catch(error => {
          showAlert('error', 'خطأ', error.message || 'تعذر حذف المستند.');
        });
    });
  });

  formElement.addEventListener('submit', function (event) {
    event.preventDefault();
    clearFieldErrors();

    if (!validateStepOne()) {
      if (stepper) stepper.to(1);
      return;
    }

    const formData = new FormData(formElement);
    if (saveButton) {
      saveButton.disabled = true;
    }

    fetch(storeUrl, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken,
        Accept: 'application/json'
      },
      body: formData
    })
      .then(async response => {
        const data = await response.json();
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

        return data;
      })
      .then(data => {
        modalInstance.hide();
        tableInstance.ajax.reload(null, false);
        showAlert('success', 'تم بنجاح', data.message || 'تم حفظ المستند بنجاح.');
      })
      .catch(error => {
        showAlert('error', 'تعذر الحفظ', error.message || 'تعذر حفظ المستند.');
      })
      .finally(() => {
        if (saveButton) {
          saveButton.disabled = false;
        }
      });
  });

  fileInput?.addEventListener('change', function () {
    const file = this.files && this.files.length ? this.files[0] : null;
    showSelectedFile(file);
  });

  formElement.addEventListener('input', updateReview);
  formElement.addEventListener('change', updateReview);

  modalElement.addEventListener('hidden.bs.modal', function () {
    resetModalForCreate();
  });

  nextButton?.addEventListener('click', function () {
    if (currentStep === 1 && !validateStepOne()) {
      return;
    }

    if (currentStep === 2) {
      updateReview();
    }

    goToStep(currentStep + 1);
  });

  prevButton?.addEventListener('click', function () {
    goToStep(currentStep - 1);
  });

  saveButton?.addEventListener('click', function () {
    if (typeof formElement.requestSubmit === 'function') {
      formElement.requestSubmit();
    } else {
      formElement.dispatchEvent(
        new Event('submit', {
          cancelable: true,
          bubbles: true
        })
      );
    }
  });

  stepperElement?.addEventListener('shown.bs-stepper', function (event) {
    if (typeof event.detail?.indexStep === 'number') {
      currentStep = event.detail.indexStep + 1;
      updateWizardActions();
    }
  });

  resetModalForCreate();
  updateWizardActions();
});
