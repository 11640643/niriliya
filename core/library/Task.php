<?php

namespace C\L;

class Task extends \Phalcon\Cli\Task
{

    protected $logs;
    protected $pid = 0;

    protected function runCheck($taskName = 'task', $runTime = 60)
    {
        $this->logs = $this->log->set($taskName . '_' . date('md') . '.log');
        $pidPath = APP_PUBLIC . '/task';
        if (!file_exists($pidPath)) {
            $oldumask = @umask(0);
            if (@mkdir($pidPath, 0777, true)) {
                return false;
            }
            @umask($oldumask);
        }

        $filemtime = 0;
        $pidFile = $pidPath . '/' . $taskName . '.pid';
        if (file_exists($pidFile)) {
            $this->pid = file_get_contents($pidFile);
            $filemtime = filemtime($pidFile);
        }

        if ($this->pid > 0 && posix_kill($this->pid, 0)) {

            if (time() - $runTime > $filemtime) {
                posix_kill($this->pid, 9);
                $this->logs('旧的进程过期已终止', 'warning');
            } else {
                $this->logs('旧的进程未过期退出当前', 'warning');
                exit();
            }

        }

        $this->pid = posix_getpid();
        file_put_contents($pidFile, $this->pid);
        $this->logs('新的进程开始了');

        return $this->pid;
    }

    protected function logs($msg, $type = 'info')
    {
        if (is_array($msg)) {
            $msg = json_encode($msg, JSON_UNESCAPED_UNICODE);
        }

        $msg = 'pid:' . $this->pid . ' >>>> ' . $msg;
        if (false) {

            $this->logs->$type($msg);
        } else {

            echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
        }

        return true;
    }

    protected function isRun($fileName)
    {
        $this->logs = $this->log->set('checkIsRun_' . date('md') . '.log');
        $command = "ps -ef | grep '" . $fileName . "'| grep -v 'grep' | wc -l";
        $out = exec($command);
        if ($out > 2) {
            $this->logs("{$fileName}有运行中的进程，退出当前。");
            exit(0);
        }
    }
}