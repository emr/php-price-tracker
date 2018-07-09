<?php

namespace App\Service;

use App\Command\RestartTrackingCommand;
use App\Command\StartTrackingCommand;
use App\Command\StopTrackingCommand;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Process\Process;

/**
 * Manager for tracking process.
 */
class TrackingManager
{
    /** @var Session */
    private $session;

    /** @var string */
    private $consolePath;

    /** @var string */
    private $processLockFile;

    /** @var int minutes */
    private $lockTime;

    /** @var bool */
    private $tracking;

    public function __construct(Session $session, string $consolePath, string $processLockFile, int $lockTime)
    {
        $this->session = $session;
        $this->consolePath = $consolePath;
        $this->processLockFile = $processLockFile;
        $this->lockTime = $lockTime;
    }

    public function setLocked(bool $locked)
    {
        if ($locked)
            $this->session->set('tracking-process-time', time());
        else
            $this->session->remove('tracking-process-time');
    }

    public function isLocked(): bool
    {
        $now = time();

        return $now - $this->session->get('tracking-process-time', $now) - $this->lockTime * 60 < 0;
    }

    /**
     * Is running tracking process?
     * @return bool
     */
    public function isTracking(): bool
    {
        if ($this->tracking !== null)
            return $this->tracking;

        return $this->tracking = file_exists($this->processLockFile);
    }

    /**
     * Start tracking process
     */
    public function startTracking()
    {
        if ($this->isTracking() && $this->isLocked())
            return;

        $process = new Process("{$this->consolePath} " . StartTrackingCommand::NAME);
        $process->run();

        $this->setLocked(true);
    }

    /**
     * Stop tracking process
     */
    public function stopTracking()
    {
        if ($this->isLocked())
            return;

        $process = new Process("{$this->consolePath} " . StopTrackingCommand::NAME);
        $process->run();

        $this->setLocked(false);
    }

    /**
     * Restart tracking process
     */
    public function restartTracking()
    {
        if ($this->isLocked())
            return;

        $process = new Process("{$this->consolePath} " . RestartTrackingCommand::NAME);
        $process->setTimeout(5);
        $process->run();

        $this->setLocked(false);
    }
}