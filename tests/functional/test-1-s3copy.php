<?php
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../s3copy.php';

use PHPUnit\Framework\TestCase;
use Garden\Cli\Cli;

class S3CopyFunctionalTest extends TestCase
{
    private $testTmpDir = '/tmp/s3copytest';
    private $s3;
    private $cli;
    
    protected function setUp(): void {    
        global $argv;
        $argv = [
            's3copy.php',
            '-r',
            'us-west-1',
            '-h',
            'https://s3.us-west-1.wasabisys.com',
            '-b',
            'wbtdc-test-bucket-ahdfjhunjpu08707yhyu',
            '-s',
            __DIR__ .'/../data/test.txt',
            '-d',
            'webuildthe.com'
        ];
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
    }
    public function testInitializeS3Client() {
        global $argv;
        $this->assertEquals('S3Copy', get_class($this->s3));
        $result = invokeMethod($this->s3, 'initializeS3Client',array($this->cli->parse($argv, true)->getOpts()));
        $this->assertEquals('Aws\S3\S3Client', get_class($result));
    }
    public function testCopyObject() {
        echo "Running testCopyObject\n";
        /*
         * @var Aws\Result
         */
        $result = invokeMethod($this->s3, 'copyObject', array(__DIR__. '/../data/testDataFiles/test.txt'));
        $this->assertStringContainsString("test.txt", $result->get('ObjectURL'));   
    }
    public function testCopyDir() {
        echo "Running testCopyDir\n";        
        /* 
         * @var Aws\Result         
         */
        $results = invokeMethod($this->s3, 'copyDir', array(__DIR__.'/../data/testDataFiles'));
        $this->assertTrue(is_array($results) && count($results) > 0, "testCopyDir failed.\n");        
    }
    public function testCopyGlob() {
        echo "Running testCopyGlob\n";
        
        $results = invokeMethod($this->s3, 'copyGlob', array(__DIR__.'/../data/testDataFiles/globs/*test.txt'));
        $this->assertTrue(is_array($results) && count($results) > 0, "testCopyGlob failed.\n");
        $results = invokeMethod($this->s3, 'copyGlob', array(__DIR__.'/../data/testDataFiles/glob*'));
        $this->assertTrue(is_array($results) && count($results) > 0, "testCopyGlob with dir glob failed.\n");
        
    }
}