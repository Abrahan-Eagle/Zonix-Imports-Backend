<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReferralController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'referrer_profile_id' => ['required', 'integer', 'exists:profiles,id'],
            'percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $ref = Referral::create([
            'product_id' => $data['product_id'],
            'referrer_profile_id' => $data['referrer_profile_id'],
            'percentage' => $data['percentage'],
            'commission_earned' => 0,
            'link' => uniqid('ref_', true),
            'active' => true,
        ]);

        return response()->json(['success' => true, 'data' => $ref], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $profileId = $request->query('profile_id');
        $query = Referral::query();
        if ($profileId) {
            $query->where('referrer_profile_id', (int) $profileId);
        }
        return response()->json(['data' => $query->paginate(15)]);
    }

    public function stats(Request $request): JsonResponse
    {
        $profileId = (int) $request->query('profile_id');
        $total = Referral::where('referrer_profile_id', $profileId)->sum('commission_earned');
        $count = Referral::where('referrer_profile_id', $profileId)->count();
        return response()->json(['data' => ['referrals' => $count, 'commission_total' => (float) $total]]);
    }
}


