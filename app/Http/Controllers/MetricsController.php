<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\Stage;
use App\Models\LeadTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MetricsController extends Controller
{
    /**
     * Get comprehensive dashboard metrics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;
        
        // Get current and last month dates
        $currentMonth = Carbon::now()->startOfMonth();
        $lastMonth = Carbon::now()->subMonth()->startOfMonth();
        $endOfCurrentMonth = Carbon::now()->endOfMonth();
        $endOfLastMonth = Carbon::now()->subMonth()->endOfMonth();

        // 1. Lead Statistics by Stage Type
        $leadStats = $this->getLeadStatistics($companyId);
        $lastMonthLeadStats = $this->getLeadStatistics($companyId, $lastMonth, $endOfLastMonth);

        // 2. Conversion Rate
        $conversionRate = $this->getConversionRate($companyId);
        $lastMonthConversionRate = $this->getConversionRate($companyId, $lastMonth, $endOfLastMonth);

        // 3. Average Conversion Time
        $avgConversionTime = $this->getAverageConversionTime($companyId);
        $lastMonthAvgConversionTime = $this->getAverageConversionTime($companyId, $lastMonth, $endOfLastMonth);

        // 4. Monthly Lead Creation Data (Current Year)
        $monthlyLeadData = $this->getMonthlyLeadData($companyId);

        // 5. Month-over-Month Performance Comparison
        $performance = $this->calculatePerformanceComparison(
            $leadStats,
            $lastMonthLeadStats,
            $conversionRate,
            $lastMonthConversionRate,
            $avgConversionTime,
            $lastMonthAvgConversionTime
        );

        return response()->json([
            'data' => [
                'lead_statistics' => $leadStats,
                'conversion_rate' => round($conversionRate, 2),
                'average_conversion_time' => $avgConversionTime,
                'monthly_lead_data' => $monthlyLeadData,
                'performance_comparison' => $performance,
                'summary' => [
                    'period' => [
                        'current_month' => $currentMonth->format('F Y'),
                        'last_month' => $lastMonth->format('F Y'),
                    ],
                    'company_id' => $companyId,
                    'generated_at' => now()->toISOString(),
                ],
            ],
        ]);
    }

    /**
     * Get lead statistics by stage type.
     */
    private function getLeadStatistics(int $companyId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $query = Lead::query()
            ->fromCompany($companyId)
            ->join('stages', 'leads.stage_id', '=', 'stages.id');

        // Apply date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('leads.created_at', [$startDate, $endDate]);
        }

        // Get total leads
        $totalLeads = $query->count();

        // Get leads by stage type
        $leadsByType = $query
            ->select('stages.type', DB::raw('COUNT(*) as count'))
            ->groupBy('stages.type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'total_leads' => $totalLeads,
            'total_leads_lost' => $leadsByType['lost'] ?? 0,
            'converted_leads' => $leadsByType['conversion'] ?? 0,
            'in_service' => $leadsByType['service'] ?? 0,
            'in_pipeline' => $leadsByType['pipeline'] ?? 0,
            'breakdown' => $leadsByType,
        ];
    }

    /**
     * Calculate conversion time comparison with detailed breakdown.
     */
    private function calculateConversionTimeComparison(array $currentTime, array $lastMonthTime): array
    {
        $currentDays = $currentTime['total_days'];
        $previousDays = $lastMonthTime['total_days'];
        
        if ($previousDays == 0) {
            $percentageChange = $currentDays > 0 ? 100.0 : 0.0;
        } else {
            $percentageChange = (($currentDays - $previousDays) / $previousDays) * 100;
        }

        // For conversion time, lower is better
        $isImprovement = $percentageChange < 0;

        return [
            'current_value' => $currentTime,
            'previous_value' => $lastMonthTime,
            'percentage_change' => round($percentageChange, 2),
            'performance_indicator' => $isImprovement ? 'improvement' : 'worsening',
            'change_direction' => $percentageChange > 0 ? 'increase' : ($percentageChange < 0 ? 'decrease' : 'no_change'),
            'summary' => $this->getConversionTimeChangeSummary($currentTime, $lastMonthTime, $percentageChange),
        ];
    }

    /**
     * Get human-readable summary of conversion time changes.
     */
    private function getConversionTimeChangeSummary(array $currentTime, array $lastMonthTime, float $percentageChange): string
    {
        if ($percentageChange == 0) {
            return "Conversion time remained the same at {$currentTime['human_readable']}";
        }
        
        $direction = $percentageChange > 0 ? 'increased' : 'decreased';
        $improvement = $percentageChange < 0 ? 'improvement' : 'decline';
        
        return "Conversion time {$direction} from {$lastMonthTime['human_readable']} to {$currentTime['human_readable']} ({$improvement})";
    }

    /**
     * Calculate conversion rate.
     */
    private function getConversionRate(int $companyId, Carbon $startDate = null, Carbon $endDate = null): float
    {
        $query = Lead::query()
            ->fromCompany($companyId)
            ->join('stages', 'leads.stage_id', '=', 'stages.id');

        // Apply date filters if provided
        if ($startDate && $endDate) {
            $query->whereBetween('leads.created_at', [$startDate, $endDate]);
        }

        $totalLeads = $query->count();
        $convertedLeads = $query->where('stages.type', 'conversion')->count();

        if ($totalLeads === 0) {
            return 0.0;
        }

        return ($convertedLeads / $totalLeads) * 100;
    }

    /**
     * Calculate average conversion time in days with detailed breakdown.
     */
    private function getAverageConversionTime(int $companyId, Carbon $startDate = null, Carbon $endDate = null): array
    {
        // Get all leads that are currently in conversion stage
        $convertedLeadsQuery = Lead::query()
            ->fromCompany($companyId)
            ->join('stages', 'leads.stage_id', '=', 'stages.id')
            ->where('stages.type', 'conversion');

        // Apply date filters if provided
        if ($startDate && $endDate) {
            $convertedLeadsQuery->whereBetween('leads.created_at', [$startDate, $endDate]);
        }

        $convertedLeads = $convertedLeadsQuery->select('leads.id', 'leads.created_at')->get();

        if ($convertedLeads->isEmpty()) {
            return [
                'total_days' => 0,
                'total_hours' => 0,
                'total_minutes' => 0,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'human_readable' => 'No conversions yet',
                'converted_leads_count' => 0,
            ];
        }

        $totalMinutes = 0;
        $validConversions = 0;
        $conversionTimes = [];

        foreach ($convertedLeads as $lead) {
            // Find the transaction where lead moved to conversion stage
            $conversionTransaction = LeadTransaction::where('lead_id', $lead->id)
                ->whereHas('toStage', function ($query) {
                    $query->where('type', 'conversion');
                })
                ->orderBy('created_at', 'asc')
                ->first();

            if ($conversionTransaction) {
                $leadCreatedAt = Carbon::parse($lead->created_at);
                $conversionAt = Carbon::parse($conversionTransaction->created_at);
                
                $diffInMinutes = $leadCreatedAt->diffInMinutes($conversionAt);
                $totalMinutes += $diffInMinutes;
                $validConversions++;
                
                $conversionTimes[] = [
                    'lead_id' => $lead->id,
                    'minutes' => $diffInMinutes,
                    'days' => floor($diffInMinutes / (24 * 60)),
                    'hours' => floor(($diffInMinutes % (24 * 60)) / 60),
                    'remaining_minutes' => $diffInMinutes % 60,
                ];
            }
        }

        if ($validConversions === 0) {
            return [
                'total_days' => 0,
                'total_hours' => 0,
                'total_minutes' => 0,
                'days' => 0,
                'hours' => 0,
                'minutes' => 0,
                'human_readable' => 'No valid conversions found',
                'converted_leads_count' => 0,
            ];
        }

        $averageMinutes = $totalMinutes / $validConversions;
        
        // Convert to different time units
        $totalDays = round($averageMinutes / (24 * 60), 2);
        $totalHours = round($averageMinutes / 60, 2);
        
        // Break down into days, hours, minutes
        $days = floor($averageMinutes / (24 * 60));
        $remainingMinutes = $averageMinutes % (24 * 60);
        $hours = floor($remainingMinutes / 60);
        $minutes = round($remainingMinutes % 60);

        // Create human readable string
        $humanReadable = $this->formatConversionTime($days, $hours, $minutes);

        return [
            'total_days' => $totalDays,
            'total_hours' => $totalHours,
            'total_minutes' => round($averageMinutes, 2),
            'days' => $days,
            'hours' => $hours,
            'minutes' => $minutes,
            'human_readable' => $humanReadable,
            'converted_leads_count' => $validConversions,
            'detailed_breakdown' => [
                'fastest_conversion' => $conversionTimes ? min(array_column($conversionTimes, 'minutes')) : 0,
                'slowest_conversion' => $conversionTimes ? max(array_column($conversionTimes, 'minutes')) : 0,
                'median_conversion' => $this->calculateMedian(array_column($conversionTimes, 'minutes')),
            ],
        ];
    }

    /**
     * Format conversion time into human readable string.
     */
    private function formatConversionTime(int $days, int $hours, int $minutes): string
    {
        $parts = [];
        
        if ($days > 0) {
            $parts[] = $days . ' ' . ($days === 1 ? 'day' : 'days');
        }
        
        if ($hours > 0) {
            $parts[] = $hours . ' ' . ($hours === 1 ? 'hour' : 'hours');
        }
        
        if ($minutes > 0 || empty($parts)) {
            $parts[] = $minutes . ' ' . ($minutes === 1 ? 'minute' : 'minutes');
        }
        
        if (count($parts) === 1) {
            return $parts[0];
        } elseif (count($parts) === 2) {
            return implode(' and ', $parts);
        } else {
            $lastPart = array_pop($parts);
            return implode(', ', $parts) . ', and ' . $lastPart;
        }
    }

    /**
     * Calculate median from array of values.
     */
    private function calculateMedian(array $values): float
    {
        if (empty($values)) {
            return 0;
        }
        
        sort($values);
        $count = count($values);
        
        if ($count % 2 === 0) {
            return ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
        } else {
            return $values[floor($count / 2)];
        }
    }

    /**
     * Get monthly lead creation data for current year.
     */
    private function getMonthlyLeadData(int $companyId): array
    {
        $currentYear = Carbon::now()->year;
        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'
        ];

        // Get lead counts by month for current year
        $leadCountsByMonth = Lead::fromCompany($companyId)
            ->whereYear('created_at', $currentYear)
            ->select(DB::raw('MONTH(created_at) as month'), DB::raw('COUNT(*) as leads'))
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->pluck('leads', 'month')
            ->toArray();

        // Format the data
        $monthlyData = [];
        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[] = [
                'month' => $months[$month],
                'leads' => $leadCountsByMonth[$month] ?? 0,
            ];
        }

        return $monthlyData;
    }

    /**
     * Calculate month-over-month performance comparison.
     */
    private function calculatePerformanceComparison(
        array $currentStats,
        array $lastMonthStats,
        float $currentConversionRate,
        float $lastMonthConversionRate,
        array $currentAvgTime,
        array $lastMonthAvgTime
    ): array {
        return [
            'total_leads' => $this->calculateMetricComparison(
                $currentStats['total_leads'],
                $lastMonthStats['total_leads'],
                'higher_is_better'
            ),
            'converted_leads' => $this->calculateMetricComparison(
                $currentStats['converted_leads'],
                $lastMonthStats['converted_leads'],
                'higher_is_better'
            ),
            'total_leads_lost' => $this->calculateMetricComparison(
                $currentStats['total_leads_lost'],
                $lastMonthStats['total_leads_lost'],
                'lower_is_better'
            ),
            'in_service' => $this->calculateMetricComparison(
                $currentStats['in_service'],
                $lastMonthStats['in_service'],
                'higher_is_better'
            ),
            'conversion_rate' => $this->calculateMetricComparison(
                $currentConversionRate,
                $lastMonthConversionRate,
                'higher_is_better'
            ),
            'average_conversion_time' => $this->calculateConversionTimeComparison(
                $currentAvgTime,
                $lastMonthAvgTime
            ),
        ];
    }

    /**
     * Calculate metric comparison between current and previous period.
     */
    private function calculateMetricComparison(
        float $currentValue,
        float $previousValue,
        string $improvementDirection
    ): array {
        if ($previousValue == 0) {
            $percentageChange = $currentValue > 0 ? 100.0 : 0.0;
        } else {
            $percentageChange = (($currentValue - $previousValue) / $previousValue) * 100;
        }

        // Determine if the change represents improvement
        $isImprovement = match($improvementDirection) {
            'higher_is_better' => $percentageChange > 0,
            'lower_is_better' => $percentageChange < 0,
            default => false
        };

        return [
            'current_value' => round($currentValue, 2),
            'previous_value' => round($previousValue, 2),
            'percentage_change' => round($percentageChange, 2),
            'performance_indicator' => $isImprovement ? 'improvement' : 'worsening',
            'change_direction' => $percentageChange > 0 ? 'increase' : ($percentageChange < 0 ? 'decrease' : 'no_change'),
        ];
    }

    /**
     * Get lead statistics for a specific period (helper method).
     */
    public function getLeadStatsPeriod(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;
        
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $stats = $this->getLeadStatistics($companyId, $startDate, $endDate);
        $conversionRate = $this->getConversionRate($companyId, $startDate, $endDate);
        $avgConversionTime = $this->getAverageConversionTime($companyId, $startDate, $endDate);

        return response()->json([
            'data' => [
                'period' => [
                    'start_date' => $startDate->toDateString(),
                    'end_date' => $endDate->toDateString(),
                ],
                'lead_statistics' => $stats,
                'conversion_rate' => round($conversionRate, 2),
                'average_conversion_time' => $avgConversionTime,
            ],
        ]);
    }

    /**
     * Get funnel performance metrics.
     */
    public function getFunnelMetrics(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        // Get funnel performance data
        $funnelMetrics = DB::table('leads')
            ->join('funnels', 'leads.funnel_id', '=', 'funnels.id')
            ->join('stages', 'leads.stage_id', '=', 'stages.id')
            ->where('leads.company_id', $companyId)
            ->select(
                'funnels.id as funnel_id',
                'funnels.name as funnel_name',
                'stages.type as stage_type',
                DB::raw('COUNT(*) as lead_count')
            )
            ->groupBy('funnels.id', 'funnels.name', 'stages.type')
            ->get()
            ->groupBy('funnel_id');

        $formattedMetrics = [];
        foreach ($funnelMetrics as $funnelId => $metrics) {
            $funnelData = [
                'funnel_id' => $funnelId,
                'funnel_name' => $metrics->first()->funnel_name,
                'total_leads' => $metrics->sum('lead_count'),
                'stage_breakdown' => $metrics->pluck('lead_count', 'stage_type')->toArray(),
            ];
            
            // Calculate funnel-specific conversion rate
            $totalLeads = $funnelData['total_leads'];
            $convertedLeads = $funnelData['stage_breakdown']['conversion'] ?? 0;
            $funnelData['conversion_rate'] = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0;
            
            $formattedMetrics[] = $funnelData;
        }

        return response()->json([
            'data' => $formattedMetrics,
        ]);
    }

    /**
     * Get user performance metrics.
     */
    public function getUserMetrics(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        // Get user performance data
        $userMetrics = DB::table('leads')
            ->join('users', 'leads.assigned_to', '=', 'users.id')
            ->join('stages', 'leads.stage_id', '=', 'stages.id')
            ->where('leads.company_id', $companyId)
            ->whereNotNull('leads.assigned_to')
            ->select(
                'users.id as user_id',
                'users.name as user_name',
                'stages.type as stage_type',
                DB::raw('COUNT(*) as lead_count')
            )
            ->groupBy('users.id', 'users.name', 'stages.type')
            ->get()
            ->groupBy('user_id');

        $formattedMetrics = [];
        foreach ($userMetrics as $userId => $metrics) {
            $userData = [
                'user_id' => $userId,
                'user_name' => $metrics->first()->user_name,
                'total_assigned_leads' => $metrics->sum('lead_count'),
                'stage_breakdown' => $metrics->pluck('lead_count', 'stage_type')->toArray(),
            ];
            
            // Calculate user-specific conversion rate
            $totalLeads = $userData['total_assigned_leads'];
            $convertedLeads = $userData['stage_breakdown']['conversion'] ?? 0;
            $userData['conversion_rate'] = $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0;
            
            $formattedMetrics[] = $userData;
        }

        return response()->json([
            'data' => $formattedMetrics,
        ]);
    }

    /**
     * Get real-time dashboard summary.
     */
    public function getSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $summary = [
            'total_leads_today' => Lead::fromCompany($companyId)->whereDate('created_at', today())->count(),
            'total_leads_this_week' => Lead::fromCompany($companyId)->whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'total_leads_this_month' => Lead::fromCompany($companyId)->whereMonth('created_at', Carbon::now()->month)->count(),
            'conversion_rate_this_month' => $this->getConversionRate($companyId),
            'active_funnels' => $user->company->activeFunnels()->count(),
            'active_users' => $user->company->activeUsers()->count(),
        ];

        return response()->json([
            'data' => $summary,
        ]);
    }
}