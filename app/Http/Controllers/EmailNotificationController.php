<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class EmailNotificationController extends Controller
{
    public function index()
    {
        // Fetch the last 20 audit log entries that are email-related
        $emailLogs = AuditLog::where('record_type', 'Email')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('email-notifications.index', compact('emailLogs'));
    }
}
