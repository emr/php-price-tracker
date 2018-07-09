<?php

namespace App\Command\Traits;

trait Lock
{
    protected $lockFile;

    public function getLockFile(): string
    {
        if (!$this->lockFile)
            $this->lockFile = $this->getContainer()->getParameter('tracking_process.lock_file');

        return $this->lockFile;
    }

    protected function setLocked(bool $locked)
    {
        if ($locked)
            touch($this->getLockFile());
        else
            unlink($this->getLockFile());
    }

    protected function isLocked(): bool
    {
        return file_exists($this->getLockFile());
    }
}