<?php

namespace App\Http\Controllers;

use App\Services\DogAlerts;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    /**
     * List every follow-up staff need to act on, grouped by type.
     */
    public function __invoke(DogAlerts $alerts): Response
    {
        return Inertia::render('alerts/index', [
            'groups' => $alerts->groups(),
        ]);
    }
}
