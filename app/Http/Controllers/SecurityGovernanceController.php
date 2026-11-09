<?php

namespace App\Http\Controllers;

use App\Models\AiGovernanceRecommendation;
use App\Models\AuthorizationDecision;
use App\Models\SecurityRiskEvent;
use App\Services\Governance\AIRecommendationService;
use App\Services\Governance\HybridAccessControlService;
use App\Services\Governance\RiskAssessmentService;
use Illuminate\Http\Request;

class SecurityGovernanceController extends Controller
{
    public function __construct(
        protected HybridAccessControlService $accessControl,
        protected AIRecommendationService $aiService,
        protected RiskAssessmentService $riskService
    ) {}

    public function index(Request $request)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_view', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return redirect()->route('dashboard')->with('error', 'Access denied: ' . $decision->getReason());
        }

        $riskEventsQuery = SecurityRiskEvent::with('user');
        if ($currentUser->role !== 'owner') {
            $riskEventsQuery->where(function ($q) use ($hotelId) {
                $q->whereNull('user_id')
                  ->orWhereHas('user', function ($uq) use ($hotelId) {
                      $uq->where('hotel_id', $hotelId);
                  });
            });
        }
        $riskEvents = $riskEventsQuery->latest()->paginate(15, ['*'], 'risk_page');

        $recommendationsQuery = AiGovernanceRecommendation::with(['user', 'reviewer']);
        if ($currentUser->role !== 'owner') {
            $recommendationsQuery->where(function ($q) use ($hotelId) {
                $q->whereNull('user_id')
                  ->orWhereHas('user', function ($uq) use ($hotelId) {
                      $uq->where('hotel_id', $hotelId);
                  });
            });
        }
        $recommendations = $recommendationsQuery->latest()->paginate(10, ['*'], 'rec_page');

        $decisions = AuthorizationDecision::with('user')->latest()->take(20)->get();

        return view('security_governance.index', compact(
            'riskEvents',
            'recommendations',
            'decisions'
        ));
    }

    public function updateRecommendation(Request $request, AiGovernanceRecommendation $recommendation)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_manage', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected',
            'review_notes' => 'required|string|min:5|max:1000',
        ]);

        $recommendation->update([
            'status' => $request->input('status'),
            'reviewed_by' => $currentUser->id,
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);

        AuthorizationDecision::create([
            'user_id' => $currentUser->id,
            'action' => 'governance.ai_recommendation_' . $request->input('status'),
            'decision' => 'allow',
            'reason' => "AI recommendation '{$recommendation->title}' was {$request->input('status')} by {$currentUser->name}. Notes: {$request->input('review_notes')}",
            'evaluated_at' => now(),
        ]);

        return back()->with('success', "AI Recommendation marked as {$request->input('status')}.");
    }

    public function generateAiReview(Request $request)
    {
        $currentUser = $request->user();
        $hotelId = $currentUser->hotel_id;

        $decision = $this->accessControl->evaluate($currentUser, 'governance.staff_manage', [
            'hotel_id' => $hotelId,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        if ($decision->isDenied()) {
            return back()->with('error', 'Access denied: ' . $decision->getReason());
        }

        $recommendation = $this->aiService->generateAdvisoryRecommendation(
            user: $currentUser,
            riskScore: 25,
            anomalies: [
                'type' => 'proactive_audit',
                'description' => 'Periodic IEEE 802 context-aware security sweep across active property accounts.',
            ],
            context: [
                'hotel_id' => $hotelId,
                'ip' => $request->ip(),
            ]
        );

        return back()->with('success', "New AI Security Recommendation generated: '{$recommendation->title}' (Confidence: " . round($recommendation->confidence_score * 100) . "%)");
    }
}
