<?php

namespace App\Monolog\Bridge;

use Monolog\Formatter\FormatterInterface;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;

class FixedConsoleFormatter implements FormatterInterface
{
    private $outputFormatter;

    public function __construct(OutputFormatterInterface $outputFormatter = null)
    {
        $this->outputFormatter = $outputFormatter;
    }

    
    public function format(array $record)
    {
        $levelName = strtolower($record['level_name']);
        
        return sprintf(
            "[%s] %s.%s: %s\n",
            $record['datetime']->format('Y-m-d H:i:s'),
            $record['channel'],
            $levelName,
            $record['message']
        );
    }

   
    public function formatBatch(array $records)
    {
        $message = '';
        foreach ($records as $record) {
            $message .= $this->format($record);
        }
        return $message;
    }
}
