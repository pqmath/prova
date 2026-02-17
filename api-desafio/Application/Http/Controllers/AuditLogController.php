<?php

namespace Application\Http\Controllers;

use Application\UseCases\ListAuditLogsUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(
        private readonly ListAuditLogsUseCase $listAuditLogsUseCase
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 20);
        $logs = $this->listAuditLogsUseCase->execute((int) $perPage);

        return response()->json($logs);
    }
}
