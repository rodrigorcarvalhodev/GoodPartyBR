<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Limpa os dados do Pulse que tenham mais de 7 dias, rodando todo dia à meia-noite
Schedule::command('pulse:purge')->daily();