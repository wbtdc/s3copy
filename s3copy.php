<?php
ini_set('display_errors', 1);
require './vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\S3\MultipartUploader;
use Garden\Cli\Cli;

class S3Copy {
    private $s3Client;
    private $bucket;
    private $region;
    private $source;
    private $destination;
    private $host;
    /*
     * @var Cli
     */
    private $cli;
    public function __construct(Cli $cli) {  
        global $argv;
        $this->cli = $cli;
        if (count($argv) > 1) {
            $options = $this->cli->parse($argv, true)->getOpts();
            if (count($options) > 0) {
                $this->initializeS3Client($options);
                $this->doCopy();
            }
            else {
                $this->cli->writeHelp();
            }
        }
    } 

    private function initializeS3Client(array $options): S3Client {
        echo "Initializing S3 client...";
        $this->region = $options['region'];
        $this->bucket = $options['bucket'];
        $this->host = $options['host'];
        $this->source = $options['source'];
        $this->destination = $options['destination'];
        
        if (!($this->bucket && $this->host && $this->region)) {
            $this->cli->writeHelp();
        }

        $this->s3Client = S3Client::factory(array(
            'endpoint' => $this->host,
            'profile' => 's3copy',
            'version' => 'latest',
            'region' => $this->region
        ));
        if (!$this->s3Client) {
            die("Could not Initialize S3Client.\n");
        }
        else {
            echo "initialized!\n";
        }
        return $this->s3Client;
    }
    private function doCopy() {
        $srcType = $this->identifySource($this->source);
        switch ($srcType) {
            case 'dir' : 
                $this->copyDir($this->source);
                break;
            case 'file' :
                $this->copyObject($this->source);
                break;
            case 'glob' :
                $this->copyGlob($this->source);
                break;
            default:
                $this->cli->writeHelp();
                break;
        }
    }
    private function identifySource($src) {
        if (is_dir($src)) return 'dir';
        elseif (is_file($src)) return 'file';
        elseif (glob($src)) return 'glob';
    }
    private function copyGlob($src) {
        echo "Copying files and directories that match $src...\n";
        $results = array();
        foreach (glob($src) as $file) {
            if (is_dir($file)) {
                $subresults = $this->copyDir($file);
                foreach ($subresults as $result) {
                    array_push($results, $result);
                }
            }
            else {
                $result = $this->copyObject($file);
                array_push($results, $result);
            }
        }
        echo "Finished copying files and directories that match $src!\n";
        return $results;
    }
    private function copyDir($dir) {
        echo "Copying directory $dir...\n";
        $files = scandir($dir);
        $results = array();
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (is_dir($dir . '/'. $file)) {
                $subresults = $this->copyDir($dir . '/'. $file);
                foreach ($subresults as $result) {
                    array_push($results, $result);
                }
            }
            else {
                /*
                 * @var Aws\Result
                 */
                $result = $this->copyObject($dir . '/'. $file);
                array_push($results, $result);
            }
        }
        echo "Finished copying directory $dir!\n";
        return $results;
    }
    private function maybeAddTrailingSlashToDst($dst) {
        if (substr($dst, -1) !== '/') {
            $dst .= '/';
        }
        return $dst;
    }
    private function copyObject($srcFilePath) {
        $dst = $this->destination;
        $src = fopen($srcFilePath, 'rb');
        $key = preg_replace("/^\//", "", $srcFilePath);
        $dst = $this->maybeAddTrailingSlashToDst($dst);
        echo "Copying $srcFilePath to " . $dst . $key."\n";
        $uploader = new MultipartUploader($this->s3Client, $src, [
            'bucket' => $this->bucket,
            'key' => $dst . $key
        ]);
        /*
         * @var Aws\Result
         */
        $result = $uploader->upload();
        if ($result->get('@metadata')['statusCode'] != 200) {
            die("Failed to copy object $srcFilePath!");
        }
        echo "complete!\n";
        return $result;
    }
}
/*
 * @var Getopt
 */
global $argv;
$cli = new Cli();
$cli->description('Copy data from the local filesystem to S3-compatible storage.')
    ->opt('region:r','S3 storage region',true,'string')
    ->opt('bucket:b','S3 bucket', true, 'string')
    ->opt('source:s', 'Local source files or directory. Can take an absolute or relative file name, directory name, or glob. IMPORTANT: make sure you enclose it in single or double quotes.', true, 'string')
    ->opt('host:h', 'S3 full URL (like "https://s3.wasabisys.com").', true, 'string')
    ->opt('destination:d', 'S3 folder destination within the bucket. IMPORTANT: If it has spaces make sure you enclose it in quotes.', true, 'string');
if (count($argv) > 1) {
    echo "Starting S3Copy!\n";
    $s3 = new S3Copy($cli);
}
else {
    $cli->writeHelp();
}