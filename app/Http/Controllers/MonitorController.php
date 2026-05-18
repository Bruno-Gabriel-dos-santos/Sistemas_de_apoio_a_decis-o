<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function index()
    {
        $systemInfo = $this->getSystemInfo();
        $cpuUsage = $this->getCpuUsage();
        $memoryInfo = $this->getMemoryInfo();
        $diskUsage = $this->getDiskUsage();
        $processes = $this->getProcesses();

        return view('monitor.index', compact('systemInfo', 'cpuUsage', 'memoryInfo', 'diskUsage', 'processes'));
    }

    private function getSystemInfo()
    {
        return [
            'os' => php_uname('s'),
            'kernel' => php_uname('r'),
            'hostname' => php_uname('n'),
            'uptime' => $this->getUptime(),
        ];
    }

    private function getUptime()
    {
        $uptime = shell_exec('uptime -p');
        return trim($uptime);
    }

    private function getCpuUsage()
    {
        $load = sys_getloadavg();
        return [
            'load_1' => $load[0],
            'load_5' => $load[1],
            'load_15' => $load[2],
        ];
    }

    private function getMemoryInfo()
    {
        $meminfo = file_get_contents('/proc/meminfo');
        preg_match_all('/^(\w+):\s+(\d+)/m', $meminfo, $matches, PREG_SET_ORDER);
        
        $memory = [];
        foreach ($matches as $match) {
            $memory[$match[1]] = $match[2];
        }

        $total = round($memory['MemTotal'] / 1024);
        $free = round($memory['MemFree'] / 1024);
        $used = $total - $free;
        $usage_percent = round(($used / $total) * 100, 1);

        return [
            'total' => $this->formatSize($total * 1024),
            'used' => $this->formatSize($used * 1024),
            'free' => $this->formatSize($free * 1024),
            'usage_percent' => $usage_percent,
        ];
    }

    private function getDiskUsage()
    {
        $df = shell_exec('df -h /');
        $lines = explode("\n", trim($df));
        $parts = preg_split('/\s+/', $lines[1]);

        return [
            'total' => $parts[1],
            'used' => $parts[2],
            'free' => $parts[3],
            'usage_percent' => rtrim($parts[4], '%'),
        ];
    }

    private function getProcesses()
    {
        $ps = shell_exec('ps aux --sort=-%cpu | head -11');
        $lines = explode("\n", trim($ps));
        array_shift($lines); // Remove o cabeçalho

        $processes = [];
        foreach ($lines as $line) {
            $parts = preg_split('/\s+/', trim($line), 11);
            if (count($parts) >= 11) {
                $processes[] = [
                    'user' => $parts[0],
                    'pid' => $parts[1],
                    'cpu' => $parts[2],
                    'mem' => $parts[3],
                    'command' => $parts[10],
                ];
            }
        }

        return $processes;
    }

    private function formatSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
} 