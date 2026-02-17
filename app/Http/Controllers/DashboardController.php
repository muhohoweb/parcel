<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\User;
use App\Models\MpesaTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Get stats
        $totalParcels = Parcel::count();
        $pendingPayment = Parcel::where('status', 'pending_payment')->count();
        $inTransit = Parcel::where('status', 'in_transit')->count();
        $delivered = Parcel::where('status', 'delivered')->count();

        $totalRevenue = Parcel::whereIn('status', ['received', 'in_transit', 'delivered'])
            ->sum('amount');

        $pendingRevenue = Parcel::where('status', 'pending_payment')->sum('amount');

        $totalCustomers = User::where('role', 'customer')->count();

        // Recent parcels
        $recentParcels = Parcel::with(['sender', 'recipient'])
            ->latest()
            ->limit(5)
            ->get();

        // Parcels by status
        $parcelsByStatus = Parcel::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        // Revenue by month (last 6 months) - Database agnostic
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            // PostgreSQL
            $revenueByMonth = Parcel::select(
                DB::raw("TO_CHAR(created_at, 'Mon YYYY') as month"),
                DB::raw('SUM(amount) as revenue')
            )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy(DB::raw("TO_CHAR(created_at, 'Mon YYYY')"), DB::raw("DATE_TRUNC('month', created_at)"))
                ->orderBy(DB::raw("DATE_TRUNC('month', created_at)"))
                ->get();
        } else {
            // MySQL
            $revenueByMonth = Parcel::select(
                DB::raw('DATE_FORMAT(created_at, "%b %Y") as month'),
                DB::raw('SUM(amount) as revenue')
            )
                ->where('created_at', '>=', now()->subMonths(6))
                ->groupBy('month')
                ->orderBy(DB::raw('MIN(created_at)'))
                ->get();
        }

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalParcels' => $totalParcels,
                'pendingPayment' => $pendingPayment,
                'inTransit' => $inTransit,
                'delivered' => $delivered,
                'totalRevenue' => (float) $totalRevenue,
                'pendingRevenue' => (float) $pendingRevenue,
                'totalCustomers' => $totalCustomers,
            ],
            'recentParcels' => $recentParcels,
            'parcelsByStatus' => $parcelsByStatus,
            'revenueByMonth' => $revenueByMonth,
        ]);
    }
}