<?php

use App\Http\Controllers\Api\TableDependentController;
use App\Http\Controllers\Api\AppTableController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\AppFieldController;
use App\Http\Controllers\Api\FormLayoutController;
use App\Http\Controllers\Api\AppRecordController;
use App\Http\Controllers\Api\TableStructureController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TablePromotionController;
use App\Http\Controllers\Api\TableLinkController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\TablePermissionController;
use App\Http\Controllers\Api\FieldPermissionController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\UploadController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\OnlineTestController;
use App\Http\Controllers\Api\StudentProfileController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\ReceiptController;
use App\Http\Controllers\Api\AccountsController;
use App\Http\Controllers\Api\WhatsappController;
use App\Http\Controllers\Api\BusTrackingController;
use App\Http\Controllers\Api\SyllabusController;
use App\Http\Controllers\Api\LiveSessionController;
use App\Http\Controllers\Api\RazorpayController;

use Illuminate\Support\Facades\Route;

// ── Razorpay webhook (public, no auth) ───────────────────────────────────────
Route::post('/razorpay/webhook', [RazorpayController::class, 'webhook']);

// ── Public auth routes ────────────────────────────────────────────────────────
Route::middleware(\App\Http\Middleware\ResolveTenant::class)->group(function () {

Route::post('/auth/register', [AuthController::class, 'register']);
Route::post('/auth/login',    [AuthController::class, 'login']);

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/auth/me',     [AuthController::class, 'me']);
    Route::post('/auth/logout',[AuthController::class, 'logout']);

    // ── Razorpay (parent + admin) ─────────────────────────────────────────────
    Route::get('/razorpay/pending-fees/{studentId}', [RazorpayController::class, 'pendingFees']);
    Route::post('/razorpay/create-order',            [RazorpayController::class, 'createOrder']);
    Route::post('/razorpay/verify-payment',          [RazorpayController::class, 'verifyPayment']);

    // ── Role-based dashboard ──────────────────────────────────────────────
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ── Menu (all authenticated users can read) ───────────────────────────
    Route::get('/menus', [MenuController::class, 'index']);

    // ── Admin-only: role & permission management ──────────────────────────
    Route::middleware('role:admin')->group(function () {
        Route::get('/roles',                    [RoleController::class, 'index']);
        Route::post('/roles',                   [RoleController::class, 'store']);
        Route::delete('/roles/{role}',          [RoleController::class, 'destroy']);
        Route::get('/users',                    [RoleController::class, 'users']);
        Route::get('/users/unlinked-records',   [RoleController::class, 'unlinkedRecords']);
        Route::put('/users/{user}/roles',       [RoleController::class, 'assignRoles']);
        Route::post('/users',                   [RoleController::class, 'createUser']);
        Route::put('/users/{user}',             [RoleController::class, 'updateUser']);
        Route::delete('/users/{user}',          [RoleController::class, 'destroyUser']);

        // Menu builder (admin only)
        Route::get('/admin/menus',                          [MenuController::class, 'adminIndex']);
        Route::post('/admin/menus',                         [MenuController::class, 'store']);
        Route::put('/admin/menus/{menu}',                   [MenuController::class, 'update']);
        Route::delete('/admin/menus/{menu}',                [MenuController::class, 'destroy']);
        Route::post('/admin/menus/{menu}/items',            [MenuController::class, 'storeItem']);
        Route::put('/admin/menu-items/{item}',              [MenuController::class, 'updateItem']);
        Route::delete('/admin/menu-items/{item}',           [MenuController::class, 'destroyItem']);
        Route::post('/admin/menus/reorder',                 [MenuController::class, 'reorder']);

        // Table & field builder (admin only)
        Route::post('/tables',                                          [AppTableController::class, 'store']);
        Route::put('/tables/{appTable}',                                [AppTableController::class, 'update']);
        Route::delete('/tables/{appTable}',                             [AppTableController::class, 'destroy']);
        Route::post('/tables/{appTable}/fields',                        [AppFieldController::class, 'store']);
        Route::put('/tables/{appTable}/fields/{appField}',              [AppFieldController::class, 'update']);
        Route::delete('/tables/{appTable}/fields/{appField}',           [AppFieldController::class, 'destroy']);
        Route::post('/tables/{appTable}/form-layout',                   [FormLayoutController::class, 'save']);

        // Permission management
        Route::get('/tables/{appTable}/permissions',                    [TablePermissionController::class, 'index']);
        Route::post('/tables/{appTable}/permissions',                   [TablePermissionController::class, 'save']);
        Route::get('/tables/{appTable}/field-permissions',              [FieldPermissionController::class, 'index']);
        Route::post('/tables/{appTable}/field-permissions',             [FieldPermissionController::class, 'save']);
    });

    // ── All authenticated users: read table/field metadata ───────────────
    Route::get('/tables',                           [AppTableController::class, 'index']);
    Route::get('/tables/{appTable}',                [AppTableController::class, 'show']);
    Route::get('/tables/{appTable}/all-fields',     [AppTableController::class, 'fields']);
    Route::get('/tables/{appTable}/visible-fields', [AppTableController::class, 'visibleFields']);
    Route::get('/tables/{appTable}/structure',      [TableStructureController::class, 'show']);
    Route::get('/tables/{appTable}/fields',         [AppFieldController::class, 'index']);
    Route::get('/tables/{appTable}/fields/{appField}', [AppFieldController::class, 'show']);
    Route::get('/tables/{appTable}/fields/{appField}/next-number', [AppFieldController::class, 'nextNumber']);
    Route::post('/tables/{appTable}/fields/{appField}/query-value', [AppFieldController::class, 'runQuery']);
    Route::get('/tables/{appTable}/form-layout',    [FormLayoutController::class, 'show']);

    // ── Records: table permission middleware applied ───────────────────────
    Route::middleware(\App\Http\Middleware\CheckTablePermission::class)->group(function () {
        Route::get('/tables/{appTable}/records',                                                [AppRecordController::class, 'index']);
        Route::post('/tables/{appTable}/records',                                               [AppRecordController::class, 'store']);
        Route::get('/tables/{appTable}/records/{appRecord}',                                    [AppRecordController::class, 'show']);
        Route::put('/tables/{appTable}/records/{appRecord}',                                    [AppRecordController::class, 'update']);
        Route::delete('/tables/{appTable}/records/{appRecord}',                                 [AppRecordController::class, 'destroy']);
        Route::get('/tables/{appTable}/lookup',                                                 [AppRecordController::class, 'lookup']);
        Route::get('/tables/{appTable}/records/{appRecord}/details/{detailTable}',              [AppRecordController::class, 'detailRecords']);
        Route::post('/tables/{appTable}/records/{appRecord}/details/{detailTable}',             [AppRecordController::class, 'saveDetailRecords']);
        Route::post('/tables/{appTable}/upload',                                                [UploadController::class, 'store']);
        Route::post('/tables/{appTable}/import',                                                [ImportController::class, 'store']);

        // ── Promotion (base → linked table) ───────────────────────────────
        Route::get('/tables/{appTable}/promotion-config',                                       [TablePromotionController::class, 'config']);
        Route::get('/tables/{appTable}/records/{appRecord}/promote-preview',                    [TablePromotionController::class, 'preview']);
        Route::post('/tables/{appTable}/records/{appRecord}/promote',                           [TablePromotionController::class, 'promote']);
    });

    Route::delete('/upload', [UploadController::class, 'destroy']);

    // ── Receipts ──────────────────────────────────────────────────────────────
    Route::get('/receipts',                          [ReceiptController::class, 'index']);
    Route::post('/receipts',                         [ReceiptController::class, 'store']);
    Route::get('/receipts/{receiptTemplate}',        [ReceiptController::class, 'show']);
    Route::put('/receipts/{receiptTemplate}',        [ReceiptController::class, 'update']);
    Route::delete('/receipts/{receiptTemplate}',     [ReceiptController::class, 'destroy']);
    Route::post('/receipts/{receiptTemplate}/render',[ReceiptController::class, 'render']);

    // ── Reports ───────────────────────────────────────────────────────────────
    Route::get('/reports',                          [ReportController::class, 'index']);
    Route::post('/reports/{report}/run',            [ReportController::class, 'run']);
    Route::post('/reports/custom',                  [ReportController::class, 'custom']);
    Route::get('/reports/tables',                   [ReportController::class, 'tables']);
    Route::get('/reports/table-fields/{tableId}',   [ReportController::class, 'tableFields']);

    // ── Table Dependents (auto-fill linked tables on save) ────────────────────
    Route::get('/tables/{appTable}/dependents',              [TableDependentController::class, 'index']);
    Route::post('/tables/{appTable}/dependents',             [TableDependentController::class, 'store']);
    Route::put('/tables/{appTable}/dependents/{dependent}',  [TableDependentController::class, 'update']);
    Route::delete('/tables/{appTable}/dependents/{dependent}',[TableDependentController::class, 'destroy']);

    // ── Table Links (Order→Invoice style pull system) ─────────────────────────
    Route::get('/table-links',                                          [TableLinkController::class, 'index']);
    Route::post('/table-links',                                         [TableLinkController::class, 'store']);
    Route::get('/table-links/for-target/{appTable}',                    [TableLinkController::class, 'forTarget']);
    Route::get('/table-links/{tableLink}',                              [TableLinkController::class, 'show']);
    Route::put('/table-links/{tableLink}',                              [TableLinkController::class, 'update']);
    Route::delete('/table-links/{tableLink}',                           [TableLinkController::class, 'destroy']);
    Route::get('/table-links/{tableLink}/pending',                      [TableLinkController::class, 'pending']);
    Route::post('/table-links/{tableLink}/pull',                        [TableLinkController::class, 'pull']);
    Route::post('/table-links/{tableLink}/enqueue/{sourceRecord}',      [TableLinkController::class, 'enqueue']);
    Route::get('/student-profile/me',          [StudentProfileController::class, 'me']);
    Route::get('/student-profile/search',      [StudentProfileController::class, 'search']);
    Route::get('/student-profile/{studentId}', [StudentProfileController::class, 'show']);

    // ── Accounts (admin only) ─────────────────────────────────────────────────
    Route::get('/accounts/summary',                    [AccountsController::class, 'summary']);
    Route::get('/accounts/staff-list',                 [AccountsController::class, 'staffList']);
    Route::get('/accounts/ledger',                     [AccountsController::class, 'ledger']);
    Route::get('/accounts/categories',                 [AccountsController::class, 'categories']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/accounts/categories',            [AccountsController::class, 'storeCategory']);
        Route::delete('/accounts/categories/{category}', [AccountsController::class, 'destroyCategory']);
        Route::get('/accounts/expenses',               [AccountsController::class, 'expenses']);
        Route::post('/accounts/expenses',              [AccountsController::class, 'storeExpense']);
        Route::put('/accounts/expenses/{expense}',     [AccountsController::class, 'updateExpense']);
        Route::delete('/accounts/expenses/{expense}',  [AccountsController::class, 'destroyExpense']);
        Route::get('/accounts/salaries',               [AccountsController::class, 'salaries']);
        Route::post('/accounts/salaries',              [AccountsController::class, 'storeSalary']);
        Route::put('/accounts/salaries/{salary}',      [AccountsController::class, 'updateSalary']);
        Route::delete('/accounts/salaries/{salary}',   [AccountsController::class, 'destroySalary']);
    });

    // ── WhatsApp ──────────────────────────────────────────────────────────────
    Route::get('/whatsapp/config',                    [WhatsappController::class, 'config']);
    Route::get('/whatsapp/stats',                     [WhatsappController::class, 'stats']);
    Route::get('/whatsapp/logs',                      [WhatsappController::class, 'logs']);
    Route::get('/whatsapp/broadcast-tables',          [WhatsappController::class, 'broadcastTables']);
    Route::get('/whatsapp/recipients',                [WhatsappController::class, 'recipients']);
    Route::get('/whatsapp/templates',                 [WhatsappController::class, 'templates']);
    // Mobile: any authenticated user can send a single message & view their own logs
    Route::post('/whatsapp/contact-school',           [WhatsappController::class, 'contactSchool']);
    Route::post('/whatsapp/send-message',             [WhatsappController::class, 'sendMessage']);
    Route::get('/whatsapp/my-logs',                   [WhatsappController::class, 'myLogs']);
    Route::get('/whatsapp/received',                  [WhatsappController::class, 'receivedLogs']);
    Route::middleware('role:admin')->group(function () {
        Route::post('/whatsapp/send',                 [WhatsappController::class, 'send']);
        Route::post('/whatsapp/send-hello-world',     [WhatsappController::class, 'sendHelloWorld']);
        Route::post('/whatsapp/send-bulk',            [WhatsappController::class, 'sendBulk']);
        Route::post('/whatsapp/send-payment-reminder',[WhatsappController::class, 'sendPaymentReminder']);
        Route::post('/whatsapp/send-attendance-warning',[WhatsappController::class, 'sendAttendanceWarning']);
        Route::post('/whatsapp/templates',            [WhatsappController::class, 'storeTemplate']);
        Route::put('/whatsapp/templates/{template}',  [WhatsappController::class, 'updateTemplate']);
        Route::delete('/whatsapp/templates/{template}',[WhatsappController::class, 'destroyTemplate']);
    });

    // ── Chat ──────────────────────────────────────────────────────────────────
    Route::get('/chat/allowed-users',                        [ChatController::class, 'allowedUsers']);
    Route::get('/chat/conversations',                        [ChatController::class, 'conversations']);
    Route::get('/chat/unread',                               [ChatController::class, 'unreadCount']);
    Route::get('/chat/with/{targetUserId}',                  [ChatController::class, 'getOrCreateConversation']);
    Route::get('/chat/conversations/{conversationId}/messages',  [ChatController::class, 'messages']);
    Route::post('/chat/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/chat/permissions',  [ChatController::class, 'getPermissions']);
        Route::post('/chat/permissions', [ChatController::class, 'savePermissions']);
    });

    // ── Bus Tracking ──────────────────────────────────────────────────────────
    // Driver routes
    Route::post('/bus-tracking/location',      [BusTrackingController::class, 'updateLocation']);
    Route::post('/bus-tracking/stop',          [BusTrackingController::class, 'stopSharing']);

    // Parent / Student / Teacher — view assigned bus
    Route::get('/bus-tracking/my-bus',         [BusTrackingController::class, 'myBus']);
    Route::get('/bus-tracking/buses/{bus}',    [BusTrackingController::class, 'busLocation']);

    // Admin — manage buses & assignments
    Route::middleware('role:admin')->group(function () {
        Route::get('/bus-tracking/buses',                          [BusTrackingController::class, 'index']);
        Route::post('/bus-tracking/buses',                         [BusTrackingController::class, 'store']);
        Route::put('/bus-tracking/buses/{bus}',                    [BusTrackingController::class, 'update']);
        Route::delete('/bus-tracking/buses/{bus}',                 [BusTrackingController::class, 'destroy']);
        Route::get('/bus-tracking/buses/{bus}/assignments',        [BusTrackingController::class, 'assignments']);
        Route::post('/bus-tracking/buses/{bus}/assign',            [BusTrackingController::class, 'assign']);
        Route::delete('/bus-tracking/buses/{bus}/assign/{sid}',    [BusTrackingController::class, 'unassign']);
    });

    // ── Syllabus ──────────────────────────────────────────────────────────────
    // Student / Parent: view published syllabuses
    Route::get('/syllabus/published', [SyllabusController::class, 'published']);
    Route::get('/syllabus/{syllabus}', [SyllabusController::class, 'show']);

    // Admin / Teacher: full CRUD
    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/syllabus',                  [SyllabusController::class, 'index']);
        Route::post('/syllabus',                 [SyllabusController::class, 'store']);
        Route::put('/syllabus/{syllabus}',       [SyllabusController::class, 'update']);
        Route::delete('/syllabus/{syllabus}',    [SyllabusController::class, 'destroy']);
    });

    // ── Live Sessions ─────────────────────────────────────────────────────────
    Route::get('/live-sessions/published',                          [LiveSessionController::class, 'published']);
    Route::get('/live-sessions/{liveSession}',                      [LiveSessionController::class, 'show']);
    Route::post('/live-sessions/{liveSession}/join',                [LiveSessionController::class, 'join']);
    Route::post('/live-sessions/{liveSession}/leave',               [LiveSessionController::class, 'leave']);
    Route::get('/live-sessions/{liveSession}/participants',         [LiveSessionController::class, 'participants']);
    Route::post('/live-sessions/{liveSession}/signal',              [LiveSessionController::class, 'sendSignal']);
    Route::get('/live-sessions/{liveSession}/signals',              [LiveSessionController::class, 'pollSignals']);
    Route::post('/live-sessions/{liveSession}/messages',            [LiveSessionController::class, 'sendMessage']);
    Route::get('/live-sessions/{liveSession}/messages',             [LiveSessionController::class, 'messages']);

    Route::middleware('role:admin,teacher')->group(function () {
        Route::get('/live-sessions',                                [LiveSessionController::class, 'index']);
        Route::post('/live-sessions',                               [LiveSessionController::class, 'store']);
        Route::put('/live-sessions/{liveSession}',                  [LiveSessionController::class, 'update']);
        Route::delete('/live-sessions/{liveSession}',               [LiveSessionController::class, 'destroy']);
        Route::post('/live-sessions/{liveSession}/start',           [LiveSessionController::class, 'start']);
        Route::post('/live-sessions/{liveSession}/end',             [LiveSessionController::class, 'end']);
    });

    // ── Online Tests ──────────────────────────────────────────────────────────
    Route::get('/online-tests/published',                          [OnlineTestController::class, 'published']);
    Route::post('/online-tests/{onlineTest}/attempt',              [OnlineTestController::class, 'startAttempt']);
    Route::post('/online-tests/attempts/{attempt}/answer',         [OnlineTestController::class, 'saveAnswer']);
    Route::post('/online-tests/attempts/{attempt}/submit',         [OnlineTestController::class, 'submitAttempt']);
    Route::get('/online-tests/attempts/{attempt}/result',          [OnlineTestController::class, 'result']);

    Route::middleware('role:admin')->group(function () {
        Route::get('/online-tests',                                [OnlineTestController::class, 'index']);
        Route::post('/online-tests',                               [OnlineTestController::class, 'store']);
        Route::get('/online-tests/{onlineTest}',                   [OnlineTestController::class, 'show']);
        Route::put('/online-tests/{onlineTest}',                   [OnlineTestController::class, 'update']);
        Route::delete('/online-tests/{onlineTest}',                [OnlineTestController::class, 'destroy']);
        Route::post('/online-tests/{onlineTest}/sections',         [OnlineTestController::class, 'saveSections']);
        Route::get('/online-tests/{onlineTest}/attempts',          [OnlineTestController::class, 'attempts']);
    });
}); // end auth:sanctum

}); // end ResolveTenant
