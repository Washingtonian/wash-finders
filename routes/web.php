<?php

use App\Http\Controllers\ProviderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/admin');
    }

    return redirect('/admin/login');
});

Route::get('/providers', [ProviderController::class, 'index']);

Route::middleware(['auth'])->group(function () {
    Route::get('/admin/data-management/download/{file}', function ($file) {
        $filepath = storage_path('app/exports/'.$file);

        if (! file_exists($filepath)) {
            abort(404);
        }

        return response()->download($filepath)->deleteFileAfterSend(true);
    })->name('admin.data-management.download');
});
