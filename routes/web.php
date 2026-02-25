<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Mail\AssetAssigned;

use App\Http\Controllers\ForgotPasswordController;



// Show login form
Route::get('/', [AuthController::class, 'showLoginForm'])->name('login');
Route::get('/login', [AuthController::class, 'showLoginForm']); // optional, also show login page

// Handle login submission
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');

// Show register form
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register');
Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');

Route::post('/register', [AuthController::class, 'register'])->name('register.submit');

// Logout
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password reset
Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('/password/reset/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
Route::post('/password/reset', [ForgotPasswordController::class, 'reset'])->name('password.update');

// Auth-protected dashboard - moved to DashboardController

use App\Http\Controllers\UserController;
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::get('/users/export', [UserController::class, 'export'])->name('users.export');
Route::post('/users', [UserController::class, 'store'])->name('users.store');
Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');
Route::put('/users/{id}', [UserController::class, 'update'])->name('users.update');
Route::delete('/users/{id}', [UserController::class, 'destroy'])->name('users.destroy');

use App\Http\Controllers\ActivityLogController;
Route::get('/activity-logs', [ActivityLogController::class, 'index'])->middleware(['auth', 'admin'])->name('activity-logs.index');

use App\Http\Controllers\DashboardController;
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/dashboard/export', [DashboardController::class, 'export'])->middleware('auth')->name('dashboard.export');

// Database diagnostic route (remove after debugging)
use App\Http\Controllers\DatabaseCheckController;
Route::get('/db-check', [DatabaseCheckController::class, 'check'])->name('db.check');

use App\Http\Controllers\AssetCategoryController;
Route::get('/manage-categories', [AssetCategoryController::class, 'index'])->name('categories.manage');
Route::get('/brand-management/add-brand-model', [AssetCategoryController::class, 'addBrandModelPage'])->middleware('auth')->name('brand-management.add-brand-model');
Route::get('/brand-management/model-values', [AssetCategoryController::class, 'modelValuesPage'])->middleware('auth')->name('brand-management.model-values');
Route::post('/categories', [AssetCategoryController::class, 'storeCategory'])->name('categories.store');
Route::get('/categories', [AssetCategoryController::class, 'index'])->name('categories.index');

use App\Http\Controllers\BrandController;
Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
Route::post('/brands/store', [AssetCategoryController::class, 'storeBrand'])->name('brands.store');
Route::post('/brand-models', [AssetCategoryController::class, 'storeModel'])->name('brand-models.store');
Route::get('/brand-models/{id}/edit-features', [AssetCategoryController::class, 'editModelFeatures'])->name('brand-models.edit-features');
Route::match(['put', 'post'], '/brand-models/{id}/update-feature-values', [AssetCategoryController::class, 'updateModelFeatureValues'])->name('brand-models.update-feature-values');
Route::delete('/brand-models/{id}', [AssetCategoryController::class, 'destroyModel'])->name('brand-models.destroy');
Route::get('/brands/{id}/edit', [BrandController::class, 'edit'])->name('brands.edit');
Route::put('/brands/{id}', [BrandController::class, 'update'])->name('brands.update');
Route::delete('/brands/{id}', [BrandController::class, 'destroy'])->name('brands.destroy');


use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\AssetController;
// Employee and Asset API endpoints - All authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/employees/autocomplete', [EmployeeController::class, 'autocomplete'])->name('employees.autocomplete');
    Route::get('/employees/{id}/assets', [AssetController::class, 'getAssetsByEmployee'])->name('employees.assets');
    Route::get('/features/by-brand/{id}', [AssetController::class, 'getFeaturesByBrand']);
    Route::get('/assets/next-id/{categoryId}', [AssetController::class, 'getNextAssetId'])->name('assets.nextId');
    Route::get('/assets/autocomplete-serial', [AssetController::class, 'autocompleteSerialNumber'])->name('assets.autocompleteSerial');
    Route::get('/models-by-category/{categoryId}', [AssetController::class, 'getModelsByCategory'])->name('assets.modelsByCategory');
    Route::get('/model-feature-values/{modelId}', [AssetController::class, 'getModelFeatureValues'])->name('assets.modelFeatureValues');
});
Route::get('/assets/create', [AssetController::class, 'create'])->middleware('auth')->name('assets.create');
Route::post('/assets', [AssetController::class, 'store'])->name('assets.store');
Route::get('/assets', [AssetController::class, 'index'])->name('assets.index');


use App\Http\Controllers\CategoryFeatureController;
// Features and Categories - Admin only for create/edit, all users can view
Route::middleware(['auth'])->group(function () {
    Route::get('/brands/by-category/{categoryId}', [BrandController::class, 'getByCategory']);
    Route::get('/models-by-brand/{brandId}', [BrandController::class, 'getModelsByBrand'])->name('brands.modelsByBrand');
    Route::get('/assets/category/{id}', [AssetController::class, 'assetsByCategory'])->name('assets.byCategory');
    Route::get('/api/assets/by-category/{id}', [AssetController::class, 'getAssetsByCategoryApi'])->name('api.assets.byCategory');
    Route::get('/api/assets/serial-numbers', [AssetController::class, 'getSerialNumbersApi'])->name('api.assets.serialNumbers');
    Route::get('/api/assets/filter', [AssetController::class, 'filterAssetsApi'])->name('api.assets.filter');
    Route::get('/assets/category/{id}/export', [AssetController::class, 'exportByCategory'])->name('assets.byCategory.export');
    Route::get('/assets/filter/export', [AssetController::class, 'exportFiltered'])->name('assets.filter.export');
    Route::get('/category-features/{category}', [CategoryFeatureController::class, 'getByCategory']);
    Route::get('/features/by-brand/{brandId}', [CategoryFeatureController::class, 'getByBrand']);
    Route::get('/features/by-brand/{id}', [AssetController::class, 'getFeaturesByBrand']);
});
Route::post('/features/store', [AssetCategoryController::class, 'storeFeature'])->name('features.store');
Route::get('/features/{id}/edit', [CategoryFeatureController::class, 'edit'])->name('features.edit');
Route::put('/features/{id}', [CategoryFeatureController::class, 'update'])->name('features.update');
Route::delete('/features/{id}', [CategoryFeatureController::class, 'destroy'])->name('features.destroy');
Route::middleware(['auth'])->group(function () {
    Route::get('/employee-master', [EmployeeController::class, 'index'])->name('employees.index');
    Route::get('/employee-master/search', [EmployeeController::class, 'search'])->name('employees.search');
    Route::get('/employee-master/export', [EmployeeController::class, 'export'])->name('employees.export');
    Route::get('/employee-master/import', [EmployeeController::class, 'showImportForm'])->name('employees.import.form');
    Route::post('/employee-master/import', [EmployeeController::class, 'import'])->name('employees.import');
    Route::get('/employee-master/create', [EmployeeController::class, 'create'])->name('employees.create');
    Route::post('/employee-master', [EmployeeController::class, 'store'])->name('employees.store');
    Route::get('/employee-master/{employee}/edit', [EmployeeController::class, 'edit'])->name('employees.edit');
    Route::delete('/employee-master/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
    Route::put('/employee-master/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
});



use App\Http\Controllers\LocationController;
Route::get('/location-master', [LocationController::class, 'index'])->name('location-master.index');
Route::get('/location-master/search', [LocationController::class, 'search'])->name('location-master.search');
Route::get('/location-master/export', [LocationController::class, 'export'])->name('location-master.export');
Route::post('/location-master', [LocationController::class, 'store'])->name('location-master.store');
Route::put('/location-master/{id}', [LocationController::class, 'update'])->name('location-master.update');
Route::delete('/location-master/{id}', [LocationController::class, 'destroy'])->name('location-master.destroy');
Route::get('/location-master/{id}/edit', [LocationController::class, 'edit'])->name('location.edit');
Route::get('/location-autocomplete', [App\Http\Controllers\LocationController::class, 'autocomplete'])->name('location.autocomplete');
Route::get('/locations/{id}/assets/export', [LocationController::class, 'exportAssets'])->name('location.assets.export');
Route::get('/locations/{id}/assets', [LocationController::class, 'assets']);


use App\Http\Controllers\EmployeeAssetController;
// Employee Asset Lookup - All authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/employee-assets', [EmployeeAssetController::class, 'index'])->name('employee.assets');
    Route::get('/employee-assets/{id}/export', [EmployeeAssetController::class, 'export'])->name('employee.assets.export');
    Route::get('/employee/search', [App\Http\Controllers\EmployeeController::class, 'search'])->name('employee.search');
});


use App\Http\Controllers\LocationAssetController;
Route::middleware(['auth'])->group(function () {
    Route::get('/location-assets', [LocationAssetController::class, 'index'])->name('location.assets');
    Route::get('/locations/autocomplete', [LocationController::class, 'autocomplete'])->name('locations.autocomplete');
});

use App\Http\Controllers\AssetTransactionController;



// Asset Transactions
Route::prefix('asset-transactions')->group(function () {
    Route::get('/', [AssetTransactionController::class, 'index'])->name('asset-transactions.index');
    Route::get('/view', [AssetTransactionController::class, 'view'])->name('asset-transactions.view');
    Route::get('/export', [AssetTransactionController::class, 'export'])->name('asset-transactions.export');
    Route::get('/create', [AssetTransactionController::class, 'create'])->name('asset-transactions.create');
    Route::get('/maintenance', [AssetTransactionController::class, 'maintenance'])->name('asset-transactions.maintenance');
    Route::post('/maintenance-store', [AssetTransactionController::class, 'maintenanceStore'])->name('asset-transactions.maintenance-store');
    Route::post('/maintenance-reassign', [AssetTransactionController::class, 'maintenanceReassign'])->name('asset-transactions.maintenance-reassign');
    Route::post('/maintenance-assign', [AssetTransactionController::class, 'maintenanceAssign'])->name('asset-transactions.maintenance-assign');
    Route::post('/maintenance-approve/{id}', [AssetTransactionController::class, 'maintenanceApprove'])->name('asset-transactions.maintenance-approve');
    Route::post('/maintenance-reject/{id}', [AssetTransactionController::class, 'maintenanceReject'])->name('asset-transactions.maintenance-reject');
    Route::post('/maintenance-request-approval', [AssetTransactionController::class, 'requestMaintenanceApproval'])->name('asset-transactions.maintenance-request-approval');
    Route::get('/maintenance-approval-request/{id}', [AssetTransactionController::class, 'showMaintenanceApprovalRequest'])->name('asset-transactions.maintenance-approval-show')->middleware('auth');
    Route::get('/maintenance-approval/approve/{id}', [AssetTransactionController::class, 'approveMaintenanceRequestSigned'])->name('asset-transactions.maintenance-approval-approve-signed');
    Route::get('/maintenance-approval/reject/{id}', [AssetTransactionController::class, 'rejectMaintenanceRequestSigned'])->name('asset-transactions.maintenance-approval-reject-signed');
    Route::post('/maintenance-approval-request-approve/{id}', [AssetTransactionController::class, 'approveMaintenanceRequest'])->name('asset-transactions.maintenance-approval-request-approve');
    Route::post('/maintenance-approval-request-reject/{id}', [AssetTransactionController::class, 'rejectMaintenanceRequest'])->name('asset-transactions.maintenance-approval-request-reject');
    Route::post('/store', [AssetTransactionController::class, 'store'])->name('asset-transactions.store');
    Route::get('/{id}/edit', [AssetTransactionController::class, 'edit'])->name('asset-transactions.edit');
    Route::put('/{id}', [AssetTransactionController::class, 'update'])->name('asset-transactions.update');
    Route::delete('/{id}', [AssetTransactionController::class, 'destroy'])->name('asset-transactions.destroy');
Route::get('/asset-transactions/get-latest-employee/{asset}', [AssetTransactionController::class, 'getLatestEmployee']);

    // Ajax helpers
    Route::get('/get-assets-by-category/{id}', [AssetTransactionController::class, 'getAssetsByCategory']);
    Route::get('/get-maintenance-assets-by-category/{id}', [AssetTransactionController::class, 'getMaintenanceAssetsByCategory']);
    Route::get('/get-category-name/{id}', [AssetTransactionController::class, 'getCategoryName']);
    Route::get('/get-latest-employee/{assetId}', [AssetTransactionController::class, 'getLatestEmployee']);
    Route::get('/get-asset-details/{assetId}', [AssetTransactionController::class, 'getAssetDetails']);
    Route::get('/get-locations', [AssetTransactionController::class, 'getLocations']);
});

// Asset filters - All users can filter/view
Route::middleware(['auth'])->group(function () {
    Route::get('/assets/filter', [AssetController::class, 'filter'])->name('assets.filter');
    Route::delete('/assets/{asset}', [AssetController::class, 'destroy'])->name('assets.destroy');
});
Route::get('/get-asset-full-details/{asset_id}', [AssetController::class, 'getFullDetails']);



use App\Http\Controllers\AssetHistoryController;
// Asset History - All authenticated users can view
Route::middleware(['auth'])->group(function () {
    Route::get('/asset-history/{asset_id}', [AssetHistoryController::class, 'show'])->name('asset.history');
});
Route::get('/categories/{id}/edit', [AssetCategoryController::class, 'edit'])->name('categories.edit');
Route::get('/categories/{id}/export', [AssetCategoryController::class, 'export'])->name('categories.export');
Route::put('/categories/{id}', [AssetCategoryController::class, 'update'])->name('categories.update');
Route::delete('/categories/{id}', [AssetCategoryController::class, 'destroy'])->name('categories.destroy');
// Test routes removed - closures cannot be cached. Move to controllers if needed.
use App\Http\Controllers\EntityController;
Route::middleware(['auth'])->group(function () {
    Route::get('/entity-master', [EntityController::class, 'index'])->name('entity-master.index');
    Route::post('/entity-master', [EntityController::class, 'store'])->name('entity-master.store');
    Route::post('/entity-master/sync-from-csv', [EntityController::class, 'syncFromCsv'])->name('entity-master.sync-from-csv');
    Route::post('/entity-master/sync-from-employees', [EntityController::class, 'syncFromEmployees'])->name('entity-master.sync-from-employees');
    Route::get('/entity-master/{id}/edit', [EntityController::class, 'edit'])->name('entity-master.edit');
    Route::put('/entity-master/{id}', [EntityController::class, 'update'])->name('entity-master.update');
    Route::delete('/entity-master/{id}', [EntityController::class, 'destroy'])->name('entity-master.destroy');
});

use App\Http\Controllers\AssetManagerController;
Route::get('/asset-manager', [AssetManagerController::class, 'index'])->name('asset-manager.index');
Route::get('/asset-manager/{id}/edit', [AssetManagerController::class, 'edit'])->name('asset-manager.edit');
Route::put('/asset-manager/{id}', [AssetManagerController::class, 'update'])->name('asset-manager.update');

use App\Http\Controllers\EntityBudgetController;
Route::get('/entity-budget/create', [EntityBudgetController::class, 'create'])->name('entity_budget.create');
Route::get('/entity-budget/transaction-history', [EntityBudgetController::class, 'transactionHistory'])->name('entity_budget.transaction-history');
Route::get('/entity-budget/transaction-history/print', [EntityBudgetController::class, 'transactionHistoryPrint'])->name('entity_budget.transaction-history.print');
Route::get('/entity-budget/transaction-history/download', [EntityBudgetController::class, 'transactionHistoryDownload'])->name('entity_budget.transaction-history.download');
Route::get('/entity-budget/export', [EntityBudgetController::class, 'export'])->name('entity_budget.export');
Route::post('/entity-budget/store', [EntityBudgetController::class, 'store'])->name('entity_budget.store');
Route::get('/entity-budget/{id}/download-form', [EntityBudgetController::class, 'downloadForm'])->name('entity_budget.download-form');
Route::get('/entity-budget/{id}/print-form', [EntityBudgetController::class, 'printForm'])->name('entity_budget.print-form');

use App\Http\Controllers\BudgetExpenseController;
Route::get('/budget-expenses/create', [BudgetExpenseController::class, 'create'])->name('budget-expenses.create');
Route::get('/budget-expenses/history', [BudgetExpenseController::class, 'expenseHistory'])->name('budget-expenses.history');
Route::post('/budget-expenses/store', [BudgetExpenseController::class, 'store'])->name('budget-expenses.store');
Route::get('/budget-expenses/get-details', [BudgetExpenseController::class, 'getBudgetDetails'])->name('budget-expenses.get-details');
Route::get('/budget-expenses/{id}/edit', [BudgetExpenseController::class, 'edit'])->name('budget-expenses.edit');
Route::put('/budget-expenses/{id}', [BudgetExpenseController::class, 'update'])->name('budget-expenses.update');
Route::get('/budget-expenses/{id}/print', [BudgetExpenseController::class, 'printExpense'])->name('budget-expenses.print');

use App\Http\Controllers\TimeManagementController;
// Time Management - All authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/time-management', [TimeManagementController::class, 'index'])->name('time.index');
    Route::get('/time-management/export', [TimeManagementController::class, 'export'])->name('time.export');
    Route::get('/time-management/create', [TimeManagementController::class, 'create'])->name('time.create');
    Route::post('/time-management/store', [TimeManagementController::class, 'store'])->name('time.store');
    Route::get('/time-management/{id}/edit', [TimeManagementController::class, 'edit'])->name('time.edit');
    Route::post('/time-management/{id}/update', [TimeManagementController::class, 'update'])->name('time.update');
    Route::delete('/time-management/{id}', [TimeManagementController::class, 'destroy'])->name('time.destroy');
});


use App\Http\Controllers\IssueNoteController;
// IT Forms - All authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('/issue-note', [IssueNoteController::class, 'index'])->name('issue-note.index');
    Route::get('/issue-note/export', [IssueNoteController::class, 'export'])->name('issue-note.export');
    Route::get('/issue-note/create', [IssueNoteController::class, 'create'])->name('issue-note.create');
    Route::post('/issue-note/store', [IssueNoteController::class, 'store'])->name('issue-note.store');
    Route::get('/issue-note/create-return', [IssueNoteController::class, 'createReturn'])->name('issue-note.create-return');
    Route::post('/issue-note/store-return', [IssueNoteController::class, 'storeReturn'])->name('issue-note.store-return');
    Route::get('/issue-note/{id}/details', [IssueNoteController::class, 'getIssueNoteDetails'])->name('issue-note.details');
    Route::get('/issue-note/{id}/download-form', [IssueNoteController::class, 'downloadForm'])->name('issue-note.download-form');
    Route::get('/employee/{id}/details', [IssueNoteController::class, 'getEmployeeDetails'])->name('employee.details');
});


use App\Http\Controllers\ProjectController;
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/autocomplete', [ProjectController::class, 'autocomplete'])->name('projects.autocomplete');
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::get('/projects/export', [ProjectController::class, 'export'])->name('projects.export');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
Route::put('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');


use App\Http\Controllers\InternetServiceController;
// Internet Services - All authenticated users
Route::middleware(['auth'])->group(function () {
    Route::get('internet-services', [InternetServiceController::class, 'index'])->name('internet-services.index');
    Route::get('internet-services/create', [InternetServiceController::class, 'create'])->name('internet-services.create');
    Route::get('internet-services/export', [InternetServiceController::class, 'export'])->name('internet-services.export');
    Route::post('internet-services', [InternetServiceController::class, 'store'])->name('internet-services.store');
    Route::get('internet-services/{id}/details', [InternetServiceController::class, 'getServiceDetails'])->name('internet-services.details');
    Route::get('internet-services/{internetService}/edit', [InternetServiceController::class, 'edit'])->name('internet-services.edit');
    Route::get('internet-services/{internetService}/return', [InternetServiceController::class, 'return'])->name('internet-services.return');
    Route::post('internet-services/{internetService}/return', [InternetServiceController::class, 'processReturn'])->name('internet-services.process-return');
    Route::put('internet-services/{internetService}', [InternetServiceController::class, 'update'])->name('internet-services.update');
    Route::delete('internet-services/{internetService}', [InternetServiceController::class, 'destroy'])->name('internet-services.destroy');
    Route::get('internet-services/{id}/download-form', [InternetServiceController::class, 'downloadForm'])->name('internet-services.download-form');
});



use App\Http\Controllers\PreventiveMaintenanceController;

Route::get('/preventive-maintenance/create', [PreventiveMaintenanceController::class, 'create'])->name('preventive-maintenance.create');
Route::post('/preventive-maintenance/store', [PreventiveMaintenanceController::class, 'store'])->name('preventive-maintenance.store');
Route::get('/preventive-maintenance', [PreventiveMaintenanceController::class, 'index'])->name('preventive-maintenance.index');
Route::get('/asset/{id}/details', [PreventiveMaintenanceController::class, 'getAssetDetails'])->name('asset.details');

use App\Http\Controllers\ReportController;
Route::get('/reports/internet', [ReportController::class, 'internet'])->name('reports.internet');
Route::get('/reports/asset-summary', [ReportController::class, 'assetSummary'])->name('reports.asset-summary');

// Debug routes - REMOVE AFTER FIXING
Route::get('/debug', function() {
    return response()->json([
        'php_version' => phpversion(),
        'laravel_version' => app()->version(),
        'environment' => app()->environment(),
        'debug_mode' => config('app.debug'),
        'app_key_set' => !empty(config('app.key')),
        'database_connected' => \Illuminate\Support\Facades\DB::connection()->getPdo() ? 'Yes' : 'No',
        'storage_writable' => is_writable(storage_path()),
        'cache_writable' => is_writable(base_path('bootstrap/cache')),
    ]);
});

Route::get('/test', function() {
    return 'Laravel is working!';
});
