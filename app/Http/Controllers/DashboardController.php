<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SystemMonitorService;

class DashboardController extends Controller
{
    protected $systemMonitor;

    public function __construct(SystemMonitorService $systemMonitor)
    {
        $this->systemMonitor = $systemMonitor;
    }

    public function index()
    {
        $systemInfo = $this->systemMonitor->getSystemInfo();
        $cpuUsage = $this->systemMonitor->getCpuUsage();
        $memoryInfo = $this->systemMonitor->getMemoryInfo();
        $diskUsage = $this->systemMonitor->getDiskUsage();
        $processes = $this->systemMonitor->getRunningProcesses();

        return view('dashboard.index', compact(
            'systemInfo',
            'cpuUsage',
            'memoryInfo',
            'diskUsage',
            'processes'
        ));
    }
} 