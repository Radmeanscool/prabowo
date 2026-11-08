<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\Auth;
use App\Http\Controllers\DashboardRedirectController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Portal;
use App\Http\Controllers\PublicSite;
use App\Http\Controllers\Reviewer;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Situs publik
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicSite\HomeController::class, 'index'])->name('home');
Route::get('/tentang', [PublicSite\PageController::class, 'about'])->name('about');
Route::get('/ketentuan', [PublicSite\PageController::class, 'rules'])->name('rules');
Route::get('/privasi', [PublicSite\PageController::class, 'privacy'])->name('privacy');
Route::get('/syarat', [PublicSite\PageController::class, 'terms'])->name('terms');
Route::get('/faq', [PublicSite\PageController::class, 'faq'])->name('faq');

Route::get('/tantangan', [PublicSite\ChallengeController::class, 'index'])->name('challenges.index');
Route::get('/tantangan/{slug}', [PublicSite\ChallengeController::class, 'show'])->name('challenges.show');
Route::get('/mitra', [PublicSite\ChallengeOwnerController::class, 'index'])->name('owners.index');
Route::get('/mitra/{slug}', [PublicSite\ChallengeOwnerController::class, 'show'])->name('owners.show');

Route::get('/tahapan', [PublicSite\ProgramController::class, 'stages'])->name('stages');
Route::get('/linimasa', [PublicSite\ProgramController::class, 'timeline'])->name('timeline');
Route::get('/agenda', [PublicSite\ProgramController::class, 'agenda'])->name('agenda');
Route::get('/mentor-juri', [PublicSite\ProgramController::class, 'people'])->name('people.index');

Route::get('/produk', [PublicSite\ProductController::class, 'index'])->name('products.index');
Route::get('/produk/{slug}', [PublicSite\ProductController::class, 'show'])->name('products.show');

Route::get('/pengumuman', [PublicSite\AnnouncementController::class, 'index'])->name('announcements.index');
Route::get('/pengumuman/{slug}', [PublicSite\AnnouncementController::class, 'show'])->name('announcements.show');
Route::get('/sumber-daya', [PublicSite\ResourceController::class, 'index'])->name('resources.index');

// Media publik: hanya disajikan bila objeknya ditandai publik.
Route::middleware('throttle:120,1')->group(function () {
    Route::get('/media/produk/{slug}/gambar', [PublicSite\MediaController::class, 'productImage'])->name('media.product-image');
    Route::get('/media/mitra/{slug}/logo', [PublicSite\MediaController::class, 'ownerLogo'])->name('media.owner-logo');
    Route::get('/media/orang/{slug}/foto', [PublicSite\MediaController::class, 'personPhoto'])->name('media.person-photo');
});
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/produk/{slug}/proposal.pdf', [PublicSite\MediaController::class, 'productPdf'])->name('media.product-pdf');
    Route::get('/sumber-daya/{slug}/unduh', [PublicSite\ResourceController::class, 'download'])->name('resources.download');
});

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/daftar', [Auth\RegisterController::class, 'create'])->name('register');
    Route::post('/daftar', [Auth\RegisterController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/masuk', [Auth\LoginController::class, 'create'])->name('login');
    Route::post('/masuk', [Auth\LoginController::class, 'store'])->middleware('throttle:10,1');
    Route::get('/lupa-sandi', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/lupa-sandi', [Auth\PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-ulang-sandi/{token}', [Auth\PasswordResetController::class, 'reset'])->name('password.reset');
    Route::post('/atur-ulang-sandi', [Auth\PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::post('/keluar', [Auth\LoginController::class, 'destroy'])->middleware('auth')->name('logout');

// Wizard admin pertama (hanya aktif bila belum ada admin dan SETUP_TOKEN diisi).
Route::get('/setup', [Auth\SetupController::class, 'create'])->name('setup');
Route::post('/setup', [Auth\SetupController::class, 'store'])->middleware('throttle:5,1');

/*
|--------------------------------------------------------------------------
| Bersama (semua peran yang login)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardRedirectController::class)->name('dashboard');
    Route::get('/berkas/{file}', [FileController::class, 'show'])->middleware('throttle:60,1')->name('files.show');
    Route::get('/akun/kata-sandi', [Auth\PasswordController::class, 'edit'])->name('account.password');
    Route::put('/akun/kata-sandi', [Auth\PasswordController::class, 'update'])->name('account.password.update');
});

/*
|--------------------------------------------------------------------------
| Dashboard Peserta
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:participant'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [Portal\DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profil', [Portal\ProfileController::class, 'edit'])->name('profile');
    Route::put('/profil', [Portal\ProfileController::class, 'update'])->name('profile.update');

    Route::get('/tim', [Portal\TeamController::class, 'show'])->name('team');
    Route::post('/tim', [Portal\TeamController::class, 'store'])->name('team.store');
    Route::put('/tim', [Portal\TeamController::class, 'update'])->name('team.update');
    Route::post('/tim/gabung', [Portal\TeamController::class, 'join'])->middleware('throttle:10,1')->name('team.join');
    Route::post('/tim/batal-gabung', [Portal\TeamController::class, 'cancelJoin'])->name('team.cancel-join');
    Route::post('/tim/keluar', [Portal\TeamController::class, 'leave'])->name('team.leave');
    Route::post('/tim/kode-baru', [Portal\TeamController::class, 'regenerateCode'])->name('team.code');
    Route::post('/tim/anggota', [Portal\TeamController::class, 'addMember'])->name('team.members.store');
    Route::put('/tim/anggota/{member}', [Portal\TeamController::class, 'updateMember'])->name('team.members.update');
    Route::post('/tim/anggota/{member}/setujui', [Portal\TeamController::class, 'approveMember'])->name('team.members.approve');
    Route::delete('/tim/anggota/{member}', [Portal\TeamController::class, 'removeMember'])->name('team.members.destroy');

    Route::get('/proposal', [Portal\ProposalController::class, 'index'])->name('proposals.index');
    Route::get('/proposal/baru', [Portal\ProposalController::class, 'create'])->name('proposals.create');
    Route::post('/proposal', [Portal\ProposalController::class, 'store'])->name('proposals.store');
    Route::get('/proposal/{proposal}', [Portal\ProposalController::class, 'show'])->name('proposals.show');
    Route::get('/proposal/{proposal}/ubah', [Portal\ProposalController::class, 'edit'])->name('proposals.edit');
    Route::put('/proposal/{proposal}', [Portal\ProposalController::class, 'update'])->name('proposals.update');
    Route::get('/proposal/{proposal}/tantangan', [Portal\ProposalController::class, 'editChallenge'])->name('proposals.challenge');
    Route::put('/proposal/{proposal}/tantangan', [Portal\ProposalController::class, 'updateChallenge'])->name('proposals.challenge.update');
    Route::post('/proposal/{proposal}/riset', [Portal\ProposalController::class, 'storeResearch'])->name('proposals.research.store');
    Route::delete('/proposal/{proposal}/riset/{research}', [Portal\ProposalController::class, 'destroyResearch'])->name('proposals.research.destroy');
    Route::get('/proposal/{proposal}/berkas', [Portal\ProposalFileController::class, 'index'])->name('proposals.files');
    Route::post('/proposal/{proposal}/berkas', [Portal\ProposalFileController::class, 'store'])->middleware('throttle:20,1')->name('proposals.files.store');
    Route::delete('/proposal/{proposal}/berkas/{file}', [Portal\ProposalFileController::class, 'destroy'])->name('proposals.files.destroy');
    Route::get('/proposal/{proposal}/kirim', [Portal\ProposalController::class, 'confirm'])->name('proposals.confirm');
    Route::post('/proposal/{proposal}/kirim', [Portal\ProposalController::class, 'submit'])->name('proposals.submit');
    Route::get('/proposal/{proposal}/status', [Portal\ProposalController::class, 'status'])->name('proposals.status');

    // Pintasan navigasi (mengarah ke proposal tim yang aktif).
    Route::get('/pilih-tantangan', [Portal\ShortcutController::class, 'challenge'])->name('shortcut.challenge');
    Route::get('/berkas-proposal', [Portal\ShortcutController::class, 'files'])->name('shortcut.files');
    Route::get('/status-pengajuan', [Portal\ShortcutController::class, 'status'])->name('proposal.status');

    Route::get('/pengumuman', [Portal\InfoController::class, 'announcements'])->name('announcements');
    Route::get('/bantuan', [Portal\InfoController::class, 'help'])->name('help');
});

/*
|--------------------------------------------------------------------------
| Dashboard Reviewer/Juri
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:reviewer'])->prefix('reviewer')->name('reviewer.')->group(function () {
    Route::get('/', [Reviewer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/penugasan', [Reviewer\AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/penugasan/{assignment}', [Reviewer\AssignmentController::class, 'show'])->name('assignments.show');
    Route::put('/penugasan/{assignment}/penilaian', [Reviewer\AssignmentController::class, 'saveReview'])->name('assignments.review');
    Route::post('/penugasan/{assignment}/konflik', [Reviewer\AssignmentController::class, 'declareConflict'])->name('assignments.conflict');
    Route::get('/penilaian', [Reviewer\AssignmentController::class, 'reviews'])->name('reviews.index');
    Route::get('/rubrik', [Reviewer\InfoController::class, 'rubric'])->name('rubric');
    Route::get('/pengumuman', [Reviewer\InfoController::class, 'announcements'])->name('announcements');
    Route::get('/konflik', [Reviewer\ConflictController::class, 'index'])->name('conflicts.index');
    Route::post('/konflik', [Reviewer\ConflictController::class, 'store'])->name('conflicts.store');
    Route::get('/pilih-proposal', [Reviewer\SelfSelectionController::class, 'index'])->name('self-select.index');
    Route::post('/pilih-proposal/{proposal}', [Reviewer\SelfSelectionController::class, 'store'])->name('self-select.store');
});

/*
|--------------------------------------------------------------------------
| Dashboard Admin
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    Route::get('media/{type}/{id}', [Admin\MediaController::class, 'show'])->whereNumber('id')->name('media');

    Route::resource('users', Admin\UserController::class)->except(['show', 'destroy']);
    Route::post('users/{user}/toggle', [Admin\UserController::class, 'toggle'])->name('users.toggle');

    Route::get('teams', [Admin\TeamController::class, 'index'])->name('teams.index');
    Route::get('teams/{team}', [Admin\TeamController::class, 'show'])->name('teams.show');
    Route::put('teams/{team}', [Admin\TeamController::class, 'update'])->name('teams.update');
    Route::post('teams/{team}/members', [Admin\TeamController::class, 'storeMember'])->name('teams.members.store');
    Route::put('teams/{team}/members/{member}', [Admin\TeamController::class, 'updateMember'])->name('teams.members.update');
    Route::delete('teams/{team}/members/{member}', [Admin\TeamController::class, 'destroyMember'])->name('teams.members.destroy');
    Route::post('teams/{team}/mentors', [Admin\TeamController::class, 'storeMentor'])->name('teams.mentors.store');
    Route::delete('teams/{team}/mentors/{mentorAssignment}', [Admin\TeamController::class, 'destroyMentor'])->name('teams.mentors.destroy');

    Route::get('proposals', [Admin\ProposalController::class, 'index'])->name('proposals.index');
    Route::get('proposals/{proposal}', [Admin\ProposalController::class, 'show'])->name('proposals.show');
    Route::post('proposals/{proposal}/status', [Admin\ProposalController::class, 'transition'])->name('proposals.status');
    Route::post('proposals/{proposal}/phase', [Admin\ProposalController::class, 'setPhase'])->name('proposals.phase');
    Route::post('proposals/{proposal}/internal-files', [Admin\ProposalController::class, 'storeInternalFile'])->name('proposals.internal-files');

    Route::get('assignments', [Admin\AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('assignments', [Admin\AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('assignments/bulk', [Admin\AssignmentController::class, 'bulk'])->name('assignments.bulk');
    Route::post('assignments/{assignment}/remove', [Admin\AssignmentController::class, 'remove'])->name('assignments.remove');
    Route::post('assignments/{assignment}/replace', [Admin\AssignmentController::class, 'replace'])->name('assignments.replace');

    Route::get('reviews', [Admin\ReviewController::class, 'index'])->name('reviews.index');
    Route::get('reviews/{review}', [Admin\ReviewController::class, 'show'])->name('reviews.show');
    Route::post('reviews/{review}/reopen', [Admin\ReviewController::class, 'reopen'])->name('reviews.reopen');

    Route::get('products', [Admin\ProductController::class, 'index'])->name('products.index');
    Route::get('products/{proposal}/edit', [Admin\ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{proposal}', [Admin\ProductController::class, 'update'])->name('products.update');
    Route::post('products/{proposal}/unpublish', [Admin\ProductController::class, 'unpublish'])->name('products.unpublish');
    Route::post('products/{proposal}/publish', [Admin\ProductController::class, 'publish'])->name('products.publish');
    Route::post('products/{proposal}/pdf', [Admin\ProductController::class, 'pdf'])->name('products.pdf');

    Route::resource('themes', Admin\ThemeController::class)->only(['index', 'edit', 'update']);
    Route::resource('theme-questions', Admin\ThemeQuestionController::class)->except(['show']);
    Route::resource('challenges', Admin\ChallengeController::class)->except(['show']);
    Route::resource('challenge-owners', Admin\ChallengeOwnerController::class)->except(['show']);
    Route::resource('people', Admin\PersonController::class)->except(['show']);
    Route::resource('stages', Admin\ProgramStageController::class)->except(['show']);
    Route::resource('agenda', Admin\AgendaController::class)->except(['show']);
    Route::resource('review-phases', Admin\ReviewPhaseController::class)->except(['show']);
    Route::resource('criteria', Admin\ReviewCriterionController::class)->except(['show', 'index']);
    Route::resource('announcements', Admin\AnnouncementController::class)->except(['show']);
    Route::resource('documents', Admin\DocumentController::class)->except(['show']);
    Route::resource('pages', Admin\PageController::class)->only(['index', 'edit', 'update']);
    Route::resource('faqs', Admin\FaqController::class)->except(['show']);

    Route::get('exports', [Admin\ExportController::class, 'index'])->name('exports.index');
    Route::get('exports/{type}', [Admin\ExportController::class, 'download'])->name('exports.download');
    Route::get('audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit.index');
    Route::get('settings', [Admin\SettingController::class, 'edit'])->name('settings.edit');
    Route::put('settings', [Admin\SettingController::class, 'update'])->name('settings.update');
});
