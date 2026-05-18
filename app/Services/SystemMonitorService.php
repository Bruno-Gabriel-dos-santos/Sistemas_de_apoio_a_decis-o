<?php

namespace App\Services;

class SystemMonitorService
{
    public function getCpuUsage()
    {
        $load = sys_getloadavg();
        return [
            'load_1' => $load[0],
            'load_5' => $load[1],
            'load_15' => $load[2]
        ];
    }

    public function getMemoryInfo()
    {
        $free = shell_exec('free');
        $free = trim($free);
        $free_arr = explode("\n", $free);
        $mem = explode(" ", $free_arr[1]);
        $mem = array_filter($mem);
        $mem = array_merge($mem);
        
        return [
            'total' => round($mem[1] / 1024 / 1024, 2),
            'used' => round($mem[2] / 1024 / 1024, 2),
            'free' => round($mem[3] / 1024 / 1024, 2),
            'usage_percent' => round(($mem[2] / $mem[1]) * 100, 2)
        ];
    }

    public function getDiskUsage()
    {
        $df = shell_exec('df -h /');
        $lines = explode("\n", $df);
        $data = preg_split('/\s+/', $lines[1]);
        
        return [
            'total' => $data[1],
            'used' => $data[2],
            'free' => $data[3],
            'usage_percent' => str_replace('%', '', $data[4])
        ];
    }

    public function getRunningProcesses()
    {
        $processes = shell_exec('ps aux --sort=-%cpu | head -n 6');
        $lines = explode("\n", $processes);
        array_shift($lines); // Remove o cabeçalho
        array_pop($lines); // Remove a última linha vazia
        
        $processList = [];
        foreach ($lines as $line) {
            $data = preg_split('/\s+/', trim($line));
            $processList[] = [
                'user' => $data[0],
                'pid' => $data[1],
                'cpu' => $data[2],
                'mem' => $data[3],
                'command' => $data[10] ?? end($data)
            ];
        }
        
        return $processList;
    }

    public function getSystemInfo()
    {
        return [
            'hostname' => gethostname(),
            'os' => php_uname('s'),
            'kernel' => php_uname('r'),
            'uptime' => $this->getUptime(),
            'php_version' => PHP_VERSION
        ];
    }

    private function getUptime()
    {
        $uptime = shell_exec('uptime -p');
        return trim($uptime);
    }
} 