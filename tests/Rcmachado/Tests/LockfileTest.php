<?php
namespace Rcmachado\Tests;

use org\bovigo\vfs\vfsStream;
use Rcmachado\LockFile;
use Rcmachado\LockFileCouldNotAcquireLockException;

class LockFileTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->var_run = vfsStream::setup('var_run');
    }

    public function testAcquireLockShouldCreatePidFile()
    {
        $lockfile = new LockFile('myproc', vfsStream::url('var_run'));

        $this->assertFalse($this->var_run->hasChild('myproc.pid'));
        $lockfile->acquire();
        $this->assertTrue($this->var_run->hasChild('myproc.pid'));
    }

    public function testAcquireLockShouldWriteCurrentPidToFile()
    {
        $lockfile = new LockFile('myproc', vfsStream::url('var_run'));
        $lockfile->acquire();

        $file = $this->var_run->getChild('myproc.pid');
        $this->assertEquals(posix_getpid(), $file->getContent());
    }

    public function testAcquireLockShouldForbidFurtherLocks()
    {
        $lockfile = new LockFile('myproc', vfsStream::url('var_run'));
        $lockfile->acquire();

        $path = vfsStream::url('var_run/myproc.pid');
        $fd = fopen($path, 'r');
        $acquired = flock($fd, LOCK_EX | LOCK_NB);
        // cleanup
        if ($acquired) {
            flock($fd, LOCK_UN);
        }
        fclose($fd);

        $this->assertFalse($acquired);
    }

    public function testReleaseLockShouldAllowFurtherLocks()
    {
        $lockfile = new LockFile('myproc', vfsStream::url('var_run'));
        $lockfile->acquire();

        $lockfile->release();

        $path = vfsStream::url('var_run/myproc.pid');
        $fd = fopen($path, 'r');
        $acquired = flock($fd, LOCK_EX | LOCK_NB);
        // cleanup
        if ($acquired) {
            flock($fd, LOCK_UN);
        }
        fclose($fd);

        $this->assertTrue($acquired);
    }

    public function testReleaseLockShouldEmptyFile()
    {
        $lockfile = new LockFile('myproc', vfsStream::url('var_run'));
        $lockfile->acquire();

        $lockfile->release();

        $path = vfsStream::url('var_run/myproc.pid');
        $content = file_get_contents($path);
        $this->assertEquals('', $content);
    }

    public function testAcquireLockMoreThanOneTimeShouldThrowException()
    {
        $lockfile = new LockFile('myproc', vfsStream::url('var_run'));
        $lockfile->acquire();

        $lockfile2 = new LockFile('myproc', vfsStream::url('var_run'));
        try {
            $lockfile2->acquire();
        } catch (LockFileCouldNotAcquireLockException $e) {
            return;
        }

        $this->fail('LockFileCouldNotAcquireLockException exception not raised.');
    }
}
