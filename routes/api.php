<?php
use App\Http\Controllers\HandoverController;

Route::get('/handover/nowplay', [HandoverController::class, 'nowplay']);
