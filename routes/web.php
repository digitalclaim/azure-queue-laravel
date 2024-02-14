<?php

use DigitalClaim\AzureQueue\Controller;
use Illuminate\Support\Facades\Route;

Route::post('/handle-queue', [Controller::class, 'handle'])->name('azure-queue-handle');
