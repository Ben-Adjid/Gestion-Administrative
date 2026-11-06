<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Public\DemarchePublicController;
use App\Http\Controllers\Api\V1\Public\SignalementPublicController;
use App\Http\Controllers\Api\V1\Public\PageViewController;
use App\Http\Controllers\Api\V1\Public\ContactPublicController;
use App\Http\Controllers\Api\V1\Public\GeoCoverageController;
use App\Http\Controllers\Api\V1\Public\GeoPublicController;
use App\Http\Controllers\Api\V1\Public\ReferencePublicController;
use App\Http\Controllers\Api\V1\Public\CommunityPublicController;
use App\Http\Controllers\Api\V1\Public\WhatsAppController;
use App\Http\Controllers\Api\V1\Admin\AdminGeoController;
use App\Http\Controllers\Api\V1\Admin\AdminGeoImportController;
use App\Http\Controllers\Api\V1\Admin\AdminQualityController;
use App\Http\Controllers\Api\V1\Admin\AdminReferenceController;
use App\Http\Controllers\Api\V1\Admin\AdminCommunityController;
use App\Http\Controllers\Api\V1\Admin\AdminImportController;
use App\Http\Controllers\Api\V1\Admin\AuthAdminController;
use App\Http\Controllers\Api\V1\Admin\AdminDashboardController;
use App\Http\Controllers\Api\V1\Admin\AdminDemarcheControllerFull;
use App\Http\Controllers\Api\V1\Admin\AdminCategorieControllerFull;
use App\Http\Controllers\Api\V1\Admin\AdminSignalementControllerFull;
use App\Http\Controllers\Api\V1\Admin\AdminActivityLogController;
use App\Http\Controllers\Api\V1\Admin\AdminApiKeyController;
use App\Http\Controllers\Api\V1\Admin\AdminAnalyticsController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\ExportController;
use App\Http\Controllers\Api\V1\CsrfTokenController;
use App\Http\Controllers\Api\V1\EmailController;
use App\Http\Controllers\Api\V1\Auth\AuthUserController;
use App\Http\Controllers\Api\V1\Citizen\CitizenDashboardController;
use App\Http\Controllers\Api\V1\Citizen\UserDocumentController;
use App\Http\Controllers\Api\V1\FavoriteController;

/*
|--------------------------------------------------------------------------
| API DemarchesTchad v1
|--------------------------------------------------------------------------
| Matrice des rôles appliquée côté serveur :
|  - role:...  => compte administrateur actif (App\Models\Admin) avec l'un des rôles listés
|  - citizen   => compte citoyen actif (App\Models\User) ; un jeton admin y est refusé
| Les deux familles de jetons Sanctum ne sont jamais interchangeables.
*/

$staff = 'role:super_admin,admin,editor';
$moderators = 'role:super_admin,admin';
$superAdmin = 'role:super_admin';

Route::prefix('v1')->group(function () use ($staff, $moderators, $superAdmin) {

    Route::get('/csrf-token', [CsrfTokenController::class, 'token'])->middleware('throttle:60,1,csrf-token');

    // ==========================================
    // 1. PUBLIC (visiteurs et citoyens, lecture seule)
    // ==========================================
    // Limiteur « public-read » (AppServiceProvider) : 100/min par IP, illimité avec une clé partenaire
    // (X-Api-Key) ou pour le serveur du site (X-Internal-Key).
    Route::middleware('throttle:public-read')->group(function () {
        Route::get('/demarches', [DemarchePublicController::class, 'index']);
        Route::get('/demarches/{slug}', [DemarchePublicController::class, 'show']);
        Route::get('/demarches/{slug}/reviews', [CommunityPublicController::class, 'reviews']);
        Route::get('/cities', [ReferencePublicController::class, 'cities']);
        Route::get('/cities/{slug}', [ReferencePublicController::class, 'city']);
        Route::get('/life-events', [ReferencePublicController::class, 'lifeEvents']);
        Route::get('/life-events/{slug}', [ReferencePublicController::class, 'lifeEvent']);
        Route::get('/calendar', [ReferencePublicController::class, 'calendar']);
        Route::get('/document-validity', [ReferencePublicController::class, 'documentValidity']);
        Route::get('/geo/tree', [GeoPublicController::class, 'tree']);
        Route::get('/geo/search', [GeoPublicController::class, 'search']);
        Route::get('/geo/coverage', [GeoCoverageController::class, 'coverage']);
        Route::post('/geo/suggestions', [GeoCoverageController::class, 'suggest'])->middleware('throttle:10,1');
        Route::post('/contact', [ContactPublicController::class, 'store'])->middleware('throttle:10,1');
        Route::get('/calendar/{id}.ics', [ReferencePublicController::class, 'calendarIcs'])->whereNumber('id');
        Route::get('/questions', [CommunityPublicController::class, 'questions']);
        Route::get('/categories', [DemarchePublicController::class, 'categories']);
        Route::get('/categories/{slug}/demarches', [DemarchePublicController::class, 'categoryDemarches']);
        Route::get('/search/suggest', [DemarchePublicController::class, 'suggest']);
    });
    Route::post('/analytics/page-views', [PageViewController::class, 'store'])->middleware('throttle:120,1,post-analytics-page-views');
    Route::post('/demarches/{slug}/view', [DemarchePublicController::class, 'recordView'])->middleware('throttle:30,1,post-demarches-slug-view');
    Route::post('/signalements', [SignalementPublicController::class, 'store'])->middleware('throttle:10,1,post-signalements');
    // Contributions sans compte : la limite horaire réelle est appliquée dans le contrôleur (envois réussis uniquement).
    Route::post('/demarches/{slug}/reviews', [CommunityPublicController::class, 'storeReview'])->middleware('throttle:10,1,post-reviews');
    Route::post('/questions', [CommunityPublicController::class, 'storeQuestion'])->middleware('throttle:10,1,post-questions');
    Route::post('/questions/{question}/answers', [CommunityPublicController::class, 'storeAnswer'])->middleware('throttle:10,1,post-answers');

    // Assistant WhatsApp (actif seulement si les clés WhatsApp Business sont configurées)
    Route::get('/whatsapp/webhook', [WhatsAppController::class, 'verify'])->middleware('throttle:30,1,whatsapp-verify');
    Route::post('/whatsapp/webhook', [WhatsAppController::class, 'receive'])->middleware('throttle:120,1,whatsapp-webhook');

    // ==========================================
    // 2. ESPACE CITOYEN
    // ==========================================
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthUserController::class, 'register'])->middleware('throttle:5,1,citizen-auth-register')->name('auth.register');
        Route::post('/login', [AuthUserController::class, 'login'])->middleware('throttle:5,1,citizen-auth-login')->name('auth.login');
        Route::post('/forgot-password', [AuthUserController::class, 'forgotPassword'])->middleware('throttle:3,1,citizen-auth-forgot-password');
        Route::post('/reset-password', [AuthUserController::class, 'resetPassword'])->middleware('throttle:3,1,citizen-auth-reset-password');

        Route::middleware(['auth:sanctum', 'citizen', 'throttle:200,1,citizen-session'])->group(function () {
            Route::get('/me', [AuthUserController::class, 'me']);
            Route::post('/logout', [AuthUserController::class, 'logout']);
            Route::put('/profile', [AuthUserController::class, 'updateProfile'])->middleware('throttle:20,1,citizen-auth-profile');
            // Le mot de passe actuel est exigé : sans limite, un jeton volé permettrait de le deviner.
            Route::post('/change-password', [AuthUserController::class, 'changePassword'])->middleware('throttle:5,1,citizen-auth-change-password');
            Route::delete('/account', [AuthUserController::class, 'deleteAccount'])->middleware('throttle:5,1,citizen-auth-delete-account');
        });
    });

    Route::middleware(['auth:sanctum', 'citizen', 'throttle:200,1,citizen-space'])->group(function () {
        Route::get('/me/dashboard', [CitizenDashboardController::class, 'show']);

        Route::get('/favorites', [FavoriteController::class, 'index']);
        Route::post('/favorites/{demarche}', [FavoriteController::class, 'store'])->middleware('throttle:30,1,post-favorites-demarche');
        Route::delete('/favorites/{demarche}', [FavoriteController::class, 'destroy']);
        Route::get('/favorites/{demarche}/status', [FavoriteController::class, 'status']);
        Route::patch('/favorites/{demarche}/note', [FavoriteController::class, 'updateNote']);

        Route::get('/me/documents', [UserDocumentController::class, 'index']);
        Route::post('/me/documents', [UserDocumentController::class, 'store'])->middleware('throttle:30,1,post-me-documents');
        Route::put('/me/documents/{document}', [UserDocumentController::class, 'update']);
        Route::delete('/me/documents/{document}', [UserDocumentController::class, 'destroy']);
    });

    // ==========================================
    // 3. BACK-OFFICE
    // ==========================================
    Route::prefix('admin/auth')->group(function () use ($staff) {
        Route::post('/login', [AuthAdminController::class, 'login'])->middleware('throttle:5,1,admin-auth-login');

        Route::middleware(['auth:sanctum', $staff, 'throttle:200,1,admin-session'])->group(function () {
            Route::get('/me', [AuthAdminController::class, 'me']);
            Route::post('/logout', [AuthAdminController::class, 'logout']);
            Route::post('/change-password', [AuthAdminController::class, 'changePassword'])->middleware('throttle:5,1,admin-auth-change-password');
        });
    });

    Route::prefix('admin')->middleware(['auth:sanctum', 'throttle:200,1,admin-space'])->group(function () use ($staff, $moderators, $superAdmin) {

        // --- Tous les membres de l'équipe (editor, admin, super_admin) ---
        Route::middleware($staff)->group(function () {
            Route::get('/stats', [AdminDashboardController::class, 'show']);

            Route::get('/demarches', [AdminDemarcheControllerFull::class, 'index']);
            Route::post('/demarches', [AdminDemarcheControllerFull::class, 'store'])->middleware('throttle:20,1,admin-post-demarches');
            Route::get('/demarches/stats/summary', [AdminDemarcheControllerFull::class, 'statsSummary']);
            Route::get('/demarches/{demarche}', [AdminDemarcheControllerFull::class, 'show']);
            Route::put('/demarches/{demarche}', [AdminDemarcheControllerFull::class, 'update'])->middleware('throttle:20,1,admin-put-demarches-demarche');
            Route::get('/demarches/{demarche}/history', [AdminDemarcheControllerFull::class, 'history']);
            Route::post('/demarches/{demarche}/restore/{entry}', [AdminDemarcheControllerFull::class, 'restoreVersion'])->middleware('throttle:30,1,admin-write-reference');
            Route::post('/geo/import/preview', [AdminGeoImportController::class, 'preview']);
            Route::post('/geo/import/commit', [AdminGeoImportController::class, 'commit'])->middleware('throttle:10,1,admin-write-reference');

            Route::get('/categories', [AdminCategorieControllerFull::class, 'index']);
            Route::get('/categories/stats/summary', [AdminCategorieControllerFull::class, 'statsSummary']);
            Route::get('/categories/{categorie}', [AdminCategorieControllerFull::class, 'show']);

            // Lecture seule pour l'éditeur : il consulte les signalements afin de corriger les fiches.
            Route::get('/signalements', [AdminSignalementControllerFull::class, 'index']);
            Route::get('/signalements/stats/summary', [AdminSignalementControllerFull::class, 'statsSummary']);
            Route::get('/signalements/{signalement}', [AdminSignalementControllerFull::class, 'show']);

            Route::get('/email/preferences', [EmailController::class, 'getPreferences']);
            Route::put('/email/preferences', [EmailController::class, 'updatePreferences']);
            Route::post('/email/test', [EmailController::class, 'testEmail'])->middleware('throttle:5,1,admin-post-email-test');

            // Lecture : nécessaire au formulaire des fiches (bureaux liés) et pour savoir quelles fiches écrire.
            Route::get('/cities', [AdminReferenceController::class, 'cities']);
            Route::get('/offices', [AdminReferenceController::class, 'offices']);
            Route::get('/geo', [AdminGeoController::class, 'index']);
            Route::get('/quality', [AdminQualityController::class, 'index']);
            Route::get('/quality/drafts', [AdminQualityController::class, 'draftChecklist']);
            Route::get('/geo/suggestions', [AdminGeoController::class, 'suggestions']);
            Route::get('/search-misses', [AdminCommunityController::class, 'searchMisses']);
        });

        // --- Modérateurs (admin, super_admin) : validation, modération, catégories, export, analytics ---
        Route::middleware($moderators)->group(function () {
            Route::patch('/demarches/{demarche}/publish', [AdminDemarcheControllerFull::class, 'publish'])->middleware('throttle:20,1,admin-patch-demarches-demarche-publish');
            Route::patch('/demarches/{demarche}/unpublish', [AdminDemarcheControllerFull::class, 'unpublish'])->middleware('throttle:20,1,admin-patch-demarches-demarche-unpublish');
            Route::delete('/demarches/{demarche}', [AdminDemarcheControllerFull::class, 'destroy'])->middleware('throttle:10,1,admin-delete-demarches-demarche');
            Route::post('/demarches/{id}/restore', [AdminDemarcheControllerFull::class, 'restore'])->middleware('throttle:10,1,admin-post-demarches-id-restore');
            Route::post('/demarches/bulk', [AdminDemarcheControllerFull::class, 'bulk'])->middleware('throttle:20,1,admin-post-demarches-bulk');
            Route::post('/demarches/{demarche}/duplicate', [AdminDemarcheControllerFull::class, 'duplicate'])->middleware('throttle:20,1,admin-post-demarches-duplicate');

            Route::post('/categories', [AdminCategorieControllerFull::class, 'store'])->middleware('throttle:20,1,admin-post-categories');
            Route::post('/categories/reorder', [AdminCategorieControllerFull::class, 'reorder'])->middleware('throttle:20,1,admin-post-categories-reorder');
            Route::put('/categories/{categorie}', [AdminCategorieControllerFull::class, 'update'])->middleware('throttle:20,1,admin-put-categories-categorie');
            Route::delete('/categories/{categorie}', [AdminCategorieControllerFull::class, 'destroy'])->middleware('throttle:10,1,admin-delete-categories-categorie');
            Route::post('/categories/{categorie}/move-demarches', [AdminCategorieControllerFull::class, 'moveDemarches'])->middleware('throttle:10,1,admin-post-categories-move');

            Route::patch('/signalements/{signalement}/status', [AdminSignalementControllerFull::class, 'updateStatus'])->middleware('throttle:30,1,admin-patch-signalements-signalement-status');
            Route::delete('/signalements/{signalement}', [AdminSignalementControllerFull::class, 'destroy'])->middleware('throttle:10,1,admin-delete-signalements-signalement');
            Route::post('/signalements/{id}/restore', [AdminSignalementControllerFull::class, 'restore'])->middleware('throttle:10,1,admin-post-signalements-id-restore');

            Route::get('/export/demarches', [ExportController::class, 'demarches'])->middleware('throttle:10,1,admin-get-export-demarches');
            Route::get('/export/signalements', [ExportController::class, 'signalements'])->middleware('throttle:10,1,admin-get-export-signalements');

            Route::get('/analytics', [AdminAnalyticsController::class, 'index']);

            // Vérification et import des fiches
            Route::patch('/demarches/{demarche}/verify', [AdminDemarcheControllerFull::class, 'verify'])->middleware('throttle:30,1,admin-verify-demarche');
            Route::get('/import/template', [AdminImportController::class, 'template'])->middleware('throttle:10,1,admin-import-template');
            Route::post('/import', [AdminImportController::class, 'import'])->middleware('throttle:10,1,admin-import');

            // Villes et bureaux
            Route::post('/cities', [AdminReferenceController::class, 'storeCity'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/cities/{city}', [AdminReferenceController::class, 'updateCity'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/cities/{city}', [AdminReferenceController::class, 'destroyCity'])->middleware('throttle:30,1,admin-write-reference');
            Route::post('/offices', [AdminReferenceController::class, 'storeOffice'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/offices/{office}', [AdminReferenceController::class, 'updateOffice'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/offices/{office}', [AdminReferenceController::class, 'destroyOffice'])->middleware('throttle:30,1,admin-write-reference');
            // Référentiel géographique et services des bureaux (statut et source obligatoires)
            Route::post('/geo/units', [AdminGeoController::class, 'storeUnit'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/geo/units/{unit}', [AdminGeoController::class, 'updateUnit'])->middleware('throttle:30,1,admin-write-reference');
            Route::post('/geo/services', [AdminGeoController::class, 'storeService'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/geo/services/{service}', [AdminGeoController::class, 'updateService'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/geo/services/{service}', [AdminGeoController::class, 'destroyService'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/geo/suggestions/{suggestion}', [AdminGeoController::class, 'reviewSuggestion'])->middleware('throttle:30,1,admin-write-reference');

            // Parcours de vie
            Route::get('/life-events', [AdminReferenceController::class, 'lifeEvents']);
            Route::post('/life-events', [AdminReferenceController::class, 'storeLifeEvent'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/life-events/{lifeEvent}', [AdminReferenceController::class, 'updateLifeEvent'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/life-events/{lifeEvent}', [AdminReferenceController::class, 'destroyLifeEvent'])->middleware('throttle:30,1,admin-write-reference');

            // Synonymes de recherche
            Route::get('/synonyms', [AdminReferenceController::class, 'synonyms']);
            Route::post('/synonyms', [AdminReferenceController::class, 'storeSynonym'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/synonyms/{synonym}', [AdminReferenceController::class, 'updateSynonym'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/synonyms/{synonym}', [AdminReferenceController::class, 'destroySynonym'])->middleware('throttle:30,1,admin-write-reference');

            // Validité des documents
            Route::get('/document-validity', [AdminReferenceController::class, 'documentValidityRules']);
            Route::post('/document-validity', [AdminReferenceController::class, 'storeDocumentValidityRule'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/document-validity/{rule}', [AdminReferenceController::class, 'updateDocumentValidityRule'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/document-validity/{rule}', [AdminReferenceController::class, 'destroyDocumentValidityRule'])->middleware('throttle:30,1,admin-write-reference');

            // Calendrier
            Route::get('/calendar', [AdminReferenceController::class, 'calendar']);
            Route::post('/calendar', [AdminReferenceController::class, 'storeCalendarEvent'])->middleware('throttle:30,1,admin-write-reference');
            Route::put('/calendar/{calendarEvent}', [AdminReferenceController::class, 'updateCalendarEvent'])->middleware('throttle:30,1,admin-write-reference');
            Route::delete('/calendar/{calendarEvent}', [AdminReferenceController::class, 'destroyCalendarEvent'])->middleware('throttle:30,1,admin-write-reference');

            // Modération des contributions du public
            Route::get('/reviews', [AdminCommunityController::class, 'reviews']);
            Route::patch('/reviews/{review}', [AdminCommunityController::class, 'moderateReview'])->middleware('throttle:60,1,admin-moderate');
            Route::delete('/reviews/{review}', [AdminCommunityController::class, 'destroyReview'])->middleware('throttle:60,1,admin-moderate');
            Route::get('/questions', [AdminCommunityController::class, 'questions']);
            Route::patch('/questions/{question}', [AdminCommunityController::class, 'moderateQuestion'])->middleware('throttle:60,1,admin-moderate');
            Route::post('/questions/{question}/answers', [AdminCommunityController::class, 'answerQuestion'])->middleware('throttle:30,1,admin-answer');
            Route::delete('/questions/{question}', [AdminCommunityController::class, 'destroyQuestion'])->middleware('throttle:60,1,admin-moderate');
            Route::patch('/answers/{answer}', [AdminCommunityController::class, 'moderateAnswer'])->middleware('throttle:60,1,admin-moderate');
            Route::patch('/search-misses/{searchMiss}', [AdminCommunityController::class, 'dismissSearchMiss'])->middleware('throttle:60,1,admin-moderate');
        });

        // --- Super administrateur : comptes de l'équipe, journal d'audit, envoi d'emails libres ---
        Route::middleware($superAdmin)->group(function () {
            Route::get('/users', [AdminUserController::class, 'index']);
            Route::post('/users', [AdminUserController::class, 'store']);
            Route::put('/users/{admin}', [AdminUserController::class, 'update']);
            Route::delete('/users/{admin}', [AdminUserController::class, 'destroy']);
            Route::post('/users/{admin}/reset-password', [AdminUserController::class, 'resetPassword']);
            Route::post('/users/{admin}/unlock', [AdminUserController::class, 'unlock']);

            Route::get('/api-keys', [AdminApiKeyController::class, 'index']);
            Route::post('/api-keys', [AdminApiKeyController::class, 'store'])->middleware('throttle:10,1,admin-post-api-keys');
            Route::patch('/api-keys/{apiKey}', [AdminApiKeyController::class, 'update']);
            Route::delete('/api-keys/{apiKey}', [AdminApiKeyController::class, 'destroy']);

            Route::get('/activity-logs', [AdminActivityLogController::class, 'index']);
            Route::post('/email/send', [EmailController::class, 'sendCustomEmail'])->middleware('throttle:10,1,admin-post-email-send');
        });
    });
});
