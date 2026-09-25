<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    /**
     * Areas of the system the log can be narrowed to, keyed by action prefix.
     *
     * @var array<string, string>
     */
    protected const AREAS = [
        'auth' => 'Login / logout',
        'dog' => 'Dog records',
        'medical' => 'Medical',
        'placement' => 'Foster & adoption',
        'document' => 'Documents & photos',
        'user' => 'User management',
        'import' => 'Import / export',
    ];

    /**
     * Action prefixes stored for each area.
     *
     * @var array<string, list<string>>
     */
    protected const AREA_ACTIONS = [
        'auth' => ['auth.'],
        'dog' => ['dog.', 'identification.', 'rescue_intake.'],
        'medical' => ['medical_profile.', 'vaccination.'],
        'placement' => ['foster_record.', 'adoption_record.'],
        'document' => ['document.', 'photo.'],
        'user' => ['user.'],
        'import' => ['import.', 'export.'],
    ];

    /**
     * Display the system audit trail.
     */
    public function index(Request $request): Response
    {
        $area = $request->string('area')->toString();
        $userId = $request->integer('user') ?: null;

        $logs = AuditLog::query()
            ->with(['user:id,name', 'dog:id,name,scas_id'])
            ->when(isset(self::AREA_ACTIONS[$area]), function (Builder $query) use ($area): void {
                $query->where(function (Builder $query) use ($area): void {
                    foreach (self::AREA_ACTIONS[$area] as $prefix) {
                        $query->orWhere('action', 'like', $prefix.'%');
                    }
                });
            })
            ->when($userId, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($request->filled('q'), fn (Builder $query) => $query->where('description', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('from'), fn (Builder $query) => $query->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn (Builder $query) => $query->whereDate('created_at', '<=', $request->date('to')))
            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('settings/logs/index', [
            'logs' => $logs,
            'filters' => $request->only(['area', 'user', 'q', 'from', 'to']),
            'areaOptions' => collect(self::AREAS)->map(fn (string $label, string $value) => ['value' => $value, 'label' => $label])->values(),
            'userOptions' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $user) => ['value' => (string) $user->id, 'label' => $user->name]),
        ]);
    }
}
