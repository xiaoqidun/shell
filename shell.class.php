<?php
/**
 * @author 肖其顿 <xiaoqidun@gmail.com>
 */

class shell
{
    public static $version = 'v1.0.0';

    public static function Convert($string, $to = 'utf-8', $from = 'auto,cp936')
    {
        $func = 'mb_convert_encoding';
        if (!function_exists($func)) {
            return $string;
        }
        return $func($string, $to, $from);
    }

    public static function Command($command, $testCommand = null, $convert = false)
    {
        $typeA = 'proc_open';
        $typeB = 'shell_exec';
        $typeC = 'exec';
        $typeD = 'system';
        $typeE = 'passthru';
        $typeF = 'popen';
        $typeG = 'pcntl_exec';
        $typeH = 'com';
        $typeRank = [
            $typeH,
            $typeA,
            $typeB,
            $typeD,
            $typeE,
            $typeC,
            $typeF,
            $typeG
        ];
        if (!is_array($command)) $command = [$command];
        if (!is_array($testCommand) && $testCommand !== null) $testCommand = [$testCommand];
        $commandFunctions = [
            $typeA => [
                'command' =>
                    function ($command) {
                        $cmd = "exec " . self::GetShellFile();
                        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
                            $cmd = self::GetShellFile();
                        }
                        if (!is_resource($sh = proc_open($cmd, [
                            0 =>
                                ["pipe", "r"],
                            1 =>
                                ["pipe", "w"]
                        ], $pipes))) {
                            return false;
                        }
                        foreach ($command as $commandLine) {
                            fwrite($pipes[0], $commandLine . PHP_EOL);
                        }
                        fclose($pipes[0]);
                        $result = stream_get_contents($pipes[1]);
                        fclose($pipes[1]);
                        proc_close($sh);
                        return $result;
                    },
                'function' =>
                    [
                        'proc_open',
                        'stream_get_contents'
                    ]
            ],
            $typeB => [
                'command' => function ($command) {
                    $commandResult = "";
                    foreach ($command as $commandLine) {
                        $commandResult .= shell_exec($commandLine);
                    }
                    return $commandResult;
                },
                'function' => [
                    'shell_exec'
                ]
            ],
            $typeC => [
                'command' => function ($command) {
                    $commandResult = "";
                    foreach ($command as $commandLine) {
                        exec($commandLine, $commandOutput);
                        $commandResult .= implode(PHP_EOL, $commandOutput);
                    }
                    return $commandResult;
                },
                'function' => [
                    'exec'
                ]
            ],
            $typeD => [
                'command' => function ($command) {
                    ob_start();
                    foreach ($command as $commandLine) {
                        system($commandLine);
                    }
                    $commandResult = ob_get_clean();
                    return $commandResult;
                },
                'function' => [
                    'system',
                    'ob_start',
                    'ob_get_clean'
                ]
            ],
            $typeE => [
                'command' => function ($command) {
                    ob_start();
                    foreach ($command as $commandLine) {
                        passthru($commandLine);
                    }
                    $commandResult = ob_get_clean();
                    return $commandResult;
                },
                'function' => [
                    'passthru',
                    'ob_start',
                    'ob_get_clean'
                ]
            ],
            $typeF => [
                'command' => function ($command) {
                    $commandResult = "";
                    foreach ($command as $commandLine) {
                        $p = popen($commandLine, "r");
                        while (!feof($p)) {
                            $commandResult .= fgets($p);
                        }
                    }
                },
                'function' => [
                    'popen'
                ]
            ],
            $typeG => [
                'command' => function ($command) {
                    $command[] = '';
                    $outputFile = tempnam(null, 'commandOutput_');
                    $outputFilePipe = sprintf(" >> %s 2>&1%s", $outputFile, PHP_EOL);
                    $commandString = implode($outputFilePipe, $command);
                    $commandProcess = pcntl_fork();
                    if ($commandProcess == 0) {
                        pcntl_exec(self::GetShellFile(), ["-c", $commandString]);
                    }
                    pcntl_waitpid($commandProcess, $status);
                    $commandResult = file_get_contents($outputFile);
                    unlink($outputFile);
                    return $commandResult;
                },
                'function' => [
                    'pcntl_exec',
                    'pcntl_fork',
                    'pcntl_waitpid'
                ]
            ],
            $typeH => [
                'command' => function ($command) {
                    $commandResult = "";
                    try {
                        $ws = new \COM("wscript.shell");
                        $exec = $ws->Exec(self::GetShellFile());
                        foreach ($command as $commandLine) {
                            $exec->StdIn->WriteLine("$commandLine");
                        }
                        $exec->StdIn->WriteLine("exit");
                        $exec->StdIn->Close();
                        while (0 === $exec->Status) {
                            time_nanosleep(0, 1e8);
                        }
                        $commandResult = $exec->StdOut->ReadAll() . $exec->StdErr->ReadAll();
                    } catch (\Exception $exception) {

                    }
                    return $commandResult;
                },
                'class' => [
                    '\COM'
                ]
            ]
        ];
        foreach ($commandFunctions as $tag => $item) {
            if (isset($item['class'])) {
                foreach ($item['class'] as $cls) {
                    if (!class_exists($cls)) {
                        unset($func);
                        unset($commandFunctions[$tag]);
                    }
                }
            }
            if (isset($item['function'])) {
                foreach ($item['function'] as $func) {
                    if (!function_exists($func)) {
                        unset($func);
                        unset($commandFunctions[$tag]);
                        break;
                    }
                    unset($func);
                }
            }
            unset($tag);
            unset($item);
        }
        if ($testCommand !== null) {
            foreach ($commandFunctions as $tag => $item) {
                $commandResult = $item['command']($testCommand);
                if (1 > strlen($commandResult)) {
                    unset($commandFunctions[$tag]);
                }
                unset($tag);
                unset($item);
            }
        }
        foreach ($typeRank as $type) {
            if (isset($commandFunctions[$type])) {
                $commandReturn = $commandFunctions[$type]['command']($command);
                if ($convert) {
                    return self::Convert($commandReturn);
                }
                return $commandReturn;
            }
            unset($type);
        }
        return false;
    }

    public static function GetShellFile()
    {
        $shellFileList = [
            'C:\Windows\System32\cmd.exe',
            'C:\Windows\SysWOW64\cmd.exe',
            '/system/bin/sh',
            '/bin/bash',
            '/bin/sh'
        ];
        foreach ($shellFileList as $shellFile) {
            if (is_executable($shellFile)) {
                return $shellFile;
            }
        }
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return 'cmd.exe';
        }
        return 'sh';
    }
}