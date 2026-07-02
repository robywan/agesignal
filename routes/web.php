<?php

use App\Livewire\Pages;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', Pages\Dashboard\Index::class)->name('dashboard');
    Route::livewire('andamento', Pages\Trend\Index::class)->name('andamento');
    Route::livewire('referti/nuovo', Pages\Documents\Create::class)->name('documents.create');
    Route::livewire('referti', Pages\Documents\Index::class)->name('documents.index');
});

require __DIR__.'/settings.php';
