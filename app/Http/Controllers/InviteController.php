<?php

namespace App\Http\Controllers;

use App\Http\Requests\Invite\RedeemInviteRequest;
use App\Services\InviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InviteController extends Controller
{
    public function __construct(
        private InviteService $invites
    ) {}

    public function index(Request $request): JsonResponse
    {
        $invite = $this->invites->activeForPatient($request->user());

        return response()->json([
            'invite' => $invite ? [
                'code'       => $invite->code,
                'expires_at' => $invite->expires_at,
            ] : null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $invite = $this->invites->generate($request->user());

        return response()->json([
            'code'       => $invite->code,
            'expires_at' => $invite->expires_at,
        ], 201);
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->invites->revokeActive($request->user());

        return response()->json([
            'message' => 'Convite revogado.',
        ]);
    }

    public function redeem(RedeemInviteRequest $request): JsonResponse
    {
        $link = $this->invites->redeem(
            $request->user(),
            $request->validated()['code']
        );

        return response()->json($link, 201);
    }
}