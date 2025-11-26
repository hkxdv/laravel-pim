<?php

declare(strict_types=1);

namespace Modules\WhatsApp\App\Http\Controllers\Logs;

use App\Models\AgentLog;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Modules\WhatsApp\App\Http\Controllers\WhatsAppBaseController;

final class ListController extends WhatsAppBaseController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $params = [
            'search' => is_string($request->input('search'))
                ? $request->input('search') : '',
            'module' => is_string($request->input('module'))
                ? $request->input('module') : '',
            'status' => is_string($request->input('status'))
                ? $request->input('status') : '',
            'intent' => is_string($request->input('intent'))
                ? $request->input('intent') : '',
            'sort_field' => is_string($request->input('sort_field'))
                ? $request->input('sort_field') : 'created_at',
            'sort_direction' => is_string($request->input('sort_direction'))
                ? $request->input('sort_direction') : 'desc',
            'per_page' => is_numeric($request->input('per_page'))
                ? (int) $request->input('per_page') : 10,
            'start_date' => is_string($request->input('start_date'))
                ? $request->input('start_date') : '',
            'end_date' => is_string($request->input('end_date'))
                ? $request->input('end_date') : '',
        ];

        $query = AgentLog::query()
            ->select([
                'id',
                'agent_name',
                'user_id',
                'module',
                'action',
                'status',
                'duration_ms',
                'ip_address',
                'user_agent',
                'created_at',
            ])
            ->with(['staffUser:id,name']);

        if ($params['search'] !== '') {
            $search = $params['search'];
            $query->where(static function ($q) use ($search): void {
                $q->where('agent_name', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('action', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('user_agent', 'like', sprintf('%%%s%%', $search))
                    ->orWhere('ip_address', 'like', sprintf('%%%s%%', $search));
            });
        }

        if ($params['module'] !== '') {
            $query->where('module', $params['module']);
        }

        if ($params['status'] !== '') {
            $query->where('status', $params['status']);
        }

        if ($params['intent'] !== '') {
            $query->where('meta->intent', $params['intent']);
        }

        if ($params['start_date'] !== '') {
            $query->whereDate('created_at', '>=', $params['start_date']);
        }

        if ($params['end_date'] !== '') {
            $query->whereDate('created_at', '<=', $params['end_date']);
        }

        $sortable = [
            'created_at',
            'duration_ms',
            'agent_name',
            'module',
            'status',
        ];

        $sortField = in_array(
            $params['sort_field'],
            $sortable,
            true
        ) ? $params['sort_field'] : 'created_at';

        $sortDirection = $params['sort_direction'] === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sortField, $sortDirection);

        $perPage = max(1, min($params['per_page'], 100));
        $logs = $query->paginate($perPage)->appends($request->except('page'));

        $additionalData = [
            'logs' => $logs,
            'filters' => $request->only([
                'search',
                'module',
                'status',
                'intent',
                'sort_field',
                'sort_direction',
                'start_date',
                'end_date',
                'per_page',
            ]),
        ];

        return $this->prepareAndRenderModuleView(
            view: 'logs/list',
            request: $request,
            additionalData: $additionalData,
            routeSuffix: 'logs.index'
        );
    }
}
