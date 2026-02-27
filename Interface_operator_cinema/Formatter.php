<?php

namespace Cinema;

class Formatter
{
    /**
     * Format bytes to human readable.
     *
     * @param int $bytes
     * @return string
     */
    public static function formatSize(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = (int) floor(log($bytes, 1024));
        return round($bytes / pow(1024, $i), 2) . ' ' . $units[$i];
    }

    /**
     * Truncate text to specified length.
     *
     * @param string $text
     * @param int $length
     * @return string
     */
    public static function truncateText(string $text, int $length = 50): string
    {
        if (mb_strlen($text) > $length) {
            return mb_substr($text, 0, $length) . '...';
        }
        return $text;
    }

    /**
     * Escape output for HTML.
     *
     * @param string $data
     * @return string
     */
    public static function escape(string $data): string
    {
        return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Format date for display.
     *
     * @param string|null $date
     * @param string $format
     * @return string
     */
    public static function formatDate(?string $date, string $format = 'd.m.Y H:i'): string
    {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return '-';
        }
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : '-';
    }
}