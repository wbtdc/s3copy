<?php
ini_set('display_errors', 1);
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../s3copy.php';

use PHPUnit\Framework\TestCase;
use Garden\Cli\Cli;

class S3CopyUnitTest extends TestCase 
{
    private $testTmpDir = '/tmp/s3copytest';
    private $s3;
    private $cli;
    
    protected function setUp(): void {
        mkdir($this->testTmpDir);
        touch ($this->testTmpDir . '/file1.txt');
        touch ($this->testTmpDir . '/file2.txt');
        $this->cli = new Cli();
        $this->cli->description('Copy data from the local filesystem to S3-compatible storage.')
        ->opt('region:r','S3 storage region',true,'string')
        ->opt('bucket:b','S3 bucket', true, 'string')
        ->opt('source:s', 'Local source files or directory. Can take an absolute or relative file name, directory name, or glob. IMPORTANT: make sure you enclose it in single or double quotes.', true, 'string')
        ->opt('host:h', 'S3 full URL (like "https://s3.wasabisys.com").', true, 'string')
        ->opt('destination:d', 'S3 folder destination within the bucket. IMPORTANT: If it has spaces make sure you enclose it in quotes.', true, 'string');
        $this->s3 = new S3Copy($this->cli);
    }
    protected function tearDown(): void {
        unlink ($this->testTmpDir. '/file1.txt');
        unlink ($this->testTmpDir . '/file2.txt');
        rmdir($this->testTmpDir);
    }
    public function testIdentifySource ()
    {
        $result = invokeMethod($this->s3, 'identifySource', array('/tmp/s3copytest'));
        $this->assertEquals('dir', $result);
        $result = invokeMethod($this->s3, 'identifySource', array('/tmp/s3copytest/file1.txt'));
        $this->assertEquals('file', $result);
        $result = invokeMethod($this->s3, 'identifySource', array('/tmp/s3copytest/file*.txt'));
        $this->assertEquals('glob', $result);
    }
    public function testMaybeAddTrailingSlashToDst() {
        $dst = 'test';
        $dst2 = 'test/';
        $result = invokeMethod($this->s3, 'maybeAddTrailingSlashToDst', array($dst));
        $this->assertEquals('test/', $result);
        $result = invokeMethod($this->s3, 'maybeAddTrailingSlashToDst', array($dst2));
        $this->assertEquals('test/', $result);        
    }
}