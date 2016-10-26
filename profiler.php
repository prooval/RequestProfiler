<?php
class RequestProfiler {

    public static function Init()
    {
        $options = getopt('f:s:d:');
        $file = (isset($options['f']) && !empty($options['f']))?
            $options['f'] : '';

        $sortBy = (isset($options['s']) && !empty($options['s']))?
            $options['s'] : 'real_time';

        $download = (isset($options['d']) && !empty($options['d']))?
            $options['d'] : false;

        $rp = new RequestProfiler();
        $rp->Analyze($file, $sortBy, $download);
    }

    private function Analyze($file, $sortBy, $downloadFile)
    {
        if (!is_file($file) || !is_readable($file))
        {
            $this->Error('error: log file does not exist or is not readable');
            $this->Error('usage: php profiler.php -f <file> -s <real_time|user_time|system_time|marked_time|memory_usage>');
            exit(1);
        }

        if (!in_array($sortBy, ['real_time', 'user_time', 'system_time', 'marked_time', 'memory_usage']))
        {
            $this->Error('error: sort option missing');
            $this->Error('usage: php profiler.php -f <file> -s <real_time|user_time|system_time|marked_time|memory_usage>');
            exit(1);
        }

        $analyzedLog = [];
        $handle = fopen($file, 'r');

        while (($entry = fgetcsv($handle)) !== false)
        {
            list($realTime, $userTime, $systemTime, $markedTime, $memoryUsage, $uri) = $entry;
            $uriSegments = explode('/', $uri);

            $mergedUri = '';
            for ($segmentLength = 0; $segmentLength < count($uriSegments); $segmentLength++)
            {
                $segment = $uriSegments[$segmentLength];

                if (!$segment) continue;

                if ($segmentLength == count($uriSegments) - 1)
                {
                    $mergedUri .= $segment;
                    $segmentId = $mergedUri;
                }
                else
                {
                    $mergedUri .= sprintf('%s/', $segment);
                    $segmentId = sprintf('%s*', $mergedUri);
                }

                if (array_key_exists($segmentId, $analyzedLog))
                {
                    $logEntry = $analyzedLog[$segmentId];
                    $logEntry->count++;
                    $logEntry->real_time += $realTime;
                    $logEntry->user_time += $userTime;
                    $logEntry->system_time += $systemTime;
                    $logEntry->marked_time += $markedTime;
                    $logEntry->memory_usage = intval(($logEntry->memory_usage + $memoryUsage)
                        / ($logEntry->count));
                }
                else
                {
                    $logEntry = new stdClass();
                    $logEntry->real_time = $realTime;
                    $logEntry->user_time = $userTime;
                    $logEntry->system_time = $systemTime;
                    $logEntry->marked_time = $markedTime;
                    $logEntry->memory_usage = $memoryUsage;
                    $logEntry->count = 1;
                    $analyzedLog[$segmentId] = $logEntry;
                }
            }
        }

        $uris = array_keys($analyzedLog);
        $fields = array();
        foreach ($analyzedLog as $log)
            $fields[] = $log->{$sortBy};

        array_multisort($fields, SORT_DESC, $uris, SORT_ASC, $analyzedLog);

        ($downloadFile)?
            $this->Download($analyzedLog, $downloadFile) : $this->Display($analyzedLog);
    }

    private function Display($analyzed)
    {
        $handle = fopen('php://stdout', 'w');
        if (!$handle) return;

        array_walk($analyzed, function (&$stats, $uri) use ($handle) {
            fputcsv($handle, [
                $stats->real_time,
                $stats->user_time,
                $stats->system_time,
                $stats->marked_time,
                $stats->memory_usage,
                $uri
            ]);
        });

        fclose($handle);
    }

    private function Download($analyzed, $file)
    {
        $handle = fopen($file, 'w');
        if (!$handle){
            $this->Error('error: output file is not writable');
            exit(1);
        }

        fputcsv($handle, ['Real Time', 'User Time', 'System Time', 'Marked Time', 'Memory Usage', 'Uri']);

        array_walk($analyzed, function (&$stats, $uri) use ($handle) {
            fputcsv($handle, [
                $stats->real_time,
                $stats->user_time,
                $stats->system_time,
                $stats->marked_time,
                $stats->memory_usage,
                $uri
            ]);
        });

        fclose($handle);
    }

    private function Error($text)
    {
        flush();

        $handle = fopen('php://stderr', 'a');
        fwrite($handle, $text);
        fwrite($handle, PHP_EOL);
        fclose($handle);

        flush();
    }

}

RequestProfiler::Init();