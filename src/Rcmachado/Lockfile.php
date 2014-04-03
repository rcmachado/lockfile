<?php
namespace Rcmachado;

class LockFile
{
    private $fd;
    protected $name;
    protected $directory;

    public function __construct($name, $directory)
    {
        $this->name = $name;
        $this->directory = $directory;
    }

    public function acquire()
    {
        $fullPath = $this->directory.DIRECTORY_SEPARATOR.$this->name.'.pid';
        $this->fd = fopen($fullPath, 'w');

        $acquired = flock($this->fd, LOCK_EX | LOCK_NB);
        if (!$acquired) {
            throw new LockFileCouldNotAcquireLockException("Could not acquire lock $this->name: lock already handled by another proccess");
        }

        ftruncate($this->fd, 0);
        fwrite($this->fd, posix_getpid());
        fflush($this->fd);
    }

    public function release()
    {
        ftruncate($this->fd, 0);
        fflush($this->fd);
        flock($this->fd, LOCK_UN);
        fclose($this->fd);
    }
}

class LockFileCouldNotAcquireLockException extends \Exception
{
}
