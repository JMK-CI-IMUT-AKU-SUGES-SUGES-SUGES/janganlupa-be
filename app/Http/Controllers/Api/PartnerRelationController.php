<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerRelation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PartnerRelationController extends Controller
{
    private function successResponse(string $message, mixed $data = [], int $code = 200): JsonResponse
    {
        return response()->json([
            'meta' => ['code' => $code, 'status' => 'success', 'message' => $message],
            'data' => $data,
        ], $code);
    }

    private function errorResponse(string $message, mixed $errors = [], int $code = 400): JsonResponse
    {
        return response()->json([
            'meta' => ['code' => $code, 'status' => 'error', 'message' => $message],
            'data' => $errors,
        ], $code);
    }

    // List partner terhubung (accepted)
    public function index(): JsonResponse
    {
        $userId = auth('api')->id();

        $relations = PartnerRelation::with(['requester', 'receiver'])
            ->where('status', 'accepted')
            ->where(function ($q) use ($userId) {
                $q->where('requester_user_id', $userId)
                  ->orWhere('receiver_user_id', $userId);
            })->get();

        $partners = $relations->map(function ($rel) use ($userId) {
            $partner = $rel->requester_user_id === $userId ? $rel->receiver : $rel->requester;

            return [
                ...$partner->toArray(),
                'relation_id' => $rel->id,
            ];
        })->values();

        return $this->successResponse('Partners fetched successfully.', ['partners' => $partners]);
    }

    // List request (incoming & outgoing pending)
    public function requests(): JsonResponse
    {
        $userId = auth('api')->id();

        $incoming = PartnerRelation::with('requester')
            ->where('receiver_user_id', $userId)
            ->where('status', 'pending')
            ->get();

        $outgoing = PartnerRelation::with('receiver')
            ->where('requester_user_id', $userId)
            ->where('status', 'pending')
            ->get();

        return $this->successResponse('Requests fetched successfully.', [
            'incoming' => $incoming,
            'outgoing' => $outgoing,
        ]);
    }

    // Request partner
    public function sendRequest(Request $request): JsonResponse
    {
        $payload = $request->only(['slug', 'note']);

        $validator = Validator::make($payload, [
            'slug' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!User::findBySlug($value)) {
                        $fail('User dengan slug tersebut tidak ditemukan.');
                    }
                },
            ],
            'note' => 'nullable|string',
        ]);

        if ($validator->fails()) return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);

        $receiver = User::findBySlug($payload['slug']);
        $requesterId = auth('api')->id();

        if ($receiver->id === $requesterId) {
            return $this->errorResponse('Tidak dapat mengirim request ke diri sendiri.', [], 400);
        }

        // Cek apakah sudah ada relasi (pending atau accepted)
        $existing = PartnerRelation::where(function ($q) use ($requesterId, $receiver) {
            $q->where(function ($query) use ($requesterId, $receiver) {
                $query->where('requester_user_id', $requesterId)
                    ->where('receiver_user_id', $receiver->id);
            })->orWhere(function ($query) use ($requesterId, $receiver) {
                $query->where('requester_user_id', $receiver->id)
                    ->where('receiver_user_id', $requesterId);
            });
        })->whereIn('status', ['pending', 'accepted'])->first();

        if ($existing) {
            return $this->errorResponse('Relasi partner sudah ada atau sedang menunggu persetujuan.', [], 400);
        }

        $relation = PartnerRelation::create([
            'requester_user_id' => $requesterId,
            'receiver_user_id' => $receiver->id,
            'status' => 'pending',
            'note' => $payload['note'] ?? null,
        ]);

        return $this->successResponse('Request partner terkirim.', ['relation' => $relation], 201);
    }

    // Respond request (accept / reject)
    public function respondRequest(Request $request, string $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:accepted,rejected',
        ]);

        if ($validator->fails()) return $this->errorResponse('Validasi gagal.', $validator->errors(), 422);

        $relation = PartnerRelation::find($id);

        if (!$relation) return $this->errorResponse('Request tidak ditemukan.', [], 404);

        if ($relation->receiver_user_id !== auth('api')->id()) {
            return $this->errorResponse('Unauthorized to respond to this request.', [], 403);
        }

        if ($relation->status !== 'pending') {
            return $this->errorResponse('Request sudah direspons sebelumnya.', [], 400);
        }

        $relation->update([
            'status' => $request->status,
            'responded_at' => now(),
        ]);

        return $this->successResponse('Request direspons.', ['relation' => $relation]);
    }

    // Remove partner / cancel request
    public function destroy(string $id): JsonResponse
    {
        $relation = PartnerRelation::find($id);
        if (!$relation) return $this->errorResponse('Relasi tidak ditemukan.', [], 404);

        $userId = auth('api')->id();
        if ($relation->requester_user_id !== $userId && $relation->receiver_user_id !== $userId) {
            return $this->errorResponse('Unauthorized to delete this relation.', [], 403);
        }

        $relation->delete();

        return $this->successResponse('Partner atau request berhasil dihapus.');
    }
}
