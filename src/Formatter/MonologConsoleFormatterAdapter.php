<?php

namespace App\Formatter;

use Monolog\Formatter\FormatterInterface;

/**
 * Version compatible avec Monolog 2.x
 */
class MonologConsoleFormatterAdapter implements FormatterInterface
{
    /**
     * Format conforme à Monolog 2.x
     */
    public function format(array $record)
    {
        
        return sprintf(
            "[%s] %s.%s: %s\n",
            $record['datetime']->format('Y-m-d H:i:s'),
            $record['channel'],
            $record['level_name'],
            $record['message']
        );
    }

    /**
     * Format de batch compatible avec Monolog 2.x
     */
    public function formatBatch(array $records)
    {
        $messages = '';
        foreach ($records as $record) {
            $messages .= $this->format($record);
        }
        return $messages;
    }
}
