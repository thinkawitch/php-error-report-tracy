<?php

namespace Thinkawitch\PhpErrorReport;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOException;
use Tracy\BlueScreen;

class PhpErrorReport
{
    private static $errorReportsDir;

    public static function init($errorReportsDir)
    {
        self::$errorReportsDir = $errorReportsDir;

        set_error_handler(function ($errno, $errstr, $errfile, $errline, array $errcontex) {
            self::onException(new \ErrorException($errstr, 0, $errno, $errfile, $errline));
        }, E_ALL);

        set_exception_handler(['\Thinkawitch\PhpErrorReport\PhpErrorReport', 'onException']);
    }

    public static function onException1($exception)
    {
        $bs = new BlueScreen;
        $bs->render($exception);
    }

    public static function onException($exception)
    {
        ob_start(); // double buffer prevents sending HTTP headers in some PHP
        ob_start();
        $bs = new BlueScreen();
        $bs->render($exception);
        $report = ob_get_contents();
        ob_end_clean();
        ob_end_clean();

        self::saveReport($exception->getMessage(), $report);
    }

    public static function saveReport($title, $report)
    {
        $fs = new Filesystem();
        $d = new \DateTime();
        $dir = self::$errorReportsDir.$d->format('Y-m-d').'/';

        try {
            $fs->mkdir($dir);
        } catch (IOException $e) {
            return;
        }

        $m = explode(' ', microtime());
        $msec = substr($m[0], 2, 5);

        $file = $d->format('H:i:s').'.'.$msec.' - '.$title;
        $file = preg_replace('@[^a-zA-Z0-9 :.-]@', '', $file);
        $file = substr($file, 0, 100).'.html';

        try {
            $fs->dumpFile($dir.$file, $report);
        } catch (IOException $e) {
            return;
        }
    }
}