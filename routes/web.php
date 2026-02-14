<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\language\LanguageController;
use App\Http\Controllers\laravel_example\CompanyDocumentManagement;
use App\Http\Controllers\laravel_example\RentalContractManagement;
use App\Http\Controllers\laravel_example\SalePurchaseContractController;

// App entry
Route::redirect('/', '/dashboard');

// locale
Route::get('/lang/{locale}', [LanguageController::class, 'swap'])->name('lang.swap');

Route::middleware([
  'auth:sanctum',
  config('jetstream.auth_session'),
  'verified',
])->group(function () {
  Route::get('/dashboard', function () {
    return redirect()->route('profile.show');
  })->name('dashboard');

  Route::get('/company-documents', [CompanyDocumentManagement::class, 'page'])->name('company-documents.index');
  Route::get('/company-documents/list', [CompanyDocumentManagement::class, 'index']);
  Route::get('/company-documents/{id}/edit', [CompanyDocumentManagement::class, 'edit']);
  Route::post('/company-documents', [CompanyDocumentManagement::class, 'store']);
  Route::delete('/company-documents/{id}', [CompanyDocumentManagement::class, 'destroy']);

  Route::get('/rental-contracts', [RentalContractManagement::class, 'page'])->name('rental-contracts.index');
  Route::get('/rental-contracts/list', [RentalContractManagement::class, 'index']);
  Route::get('/rental-contracts/{id}/edit', [RentalContractManagement::class, 'edit']);
  Route::get('/rental-contracts/{id}', [RentalContractManagement::class, 'show']);
  Route::post('/rental-contracts', [RentalContractManagement::class, 'store']);
  Route::delete('/rental-contracts/{id}', [RentalContractManagement::class, 'destroy']);

  Route::get('/sale-purchase-contracts', [SalePurchaseContractController::class, 'indexPage'])
    ->name('sale-purchase-contracts.index');
  Route::get('/sale-purchase-contracts/list', [SalePurchaseContractController::class, 'list'])
    ->name('sale-purchase-contracts.list');
  Route::get('/sale-purchase-contracts/{id}/edit', [SalePurchaseContractController::class, 'edit']);
  Route::get('/sale-purchase-contracts/{id}', [SalePurchaseContractController::class, 'show']);
  Route::post('/sale-purchase-contracts', [SalePurchaseContractController::class, 'store']);
  Route::delete('/sale-purchase-contracts/{id}', [SalePurchaseContractController::class, 'destroy']);
});
