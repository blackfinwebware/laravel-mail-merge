<?php

use Illuminate\Support\Facades\Route;
use BlackfinWebware\LaravelMailMerge\Http\Controllers\EmailTemplateController;

Route::resource('email-templates', EmailTemplateController::class)
     ->except(['show']);
Route::get('email-templates/clone/{id}', [EmailTemplateController::class, 'clone'])->name('email-templates.clone');
Route::post('email-templates/cloneUpdate', [EmailTemplateController::class, 'cloneUpdate'])->name('email-templates.cloneUpdate');
