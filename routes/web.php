<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::dashboard.index')->name('dashboard');
    Route::livewire('andamento', 'pages::andamento.index')->name('andamento');
    Route::livewire('referti/nuovo', 'pages::documents.create')->name('documents.create');
    Route::livewire('referti', 'pages::documents.index')->name('documents.index');
});

require __DIR__.'/settings.php';
