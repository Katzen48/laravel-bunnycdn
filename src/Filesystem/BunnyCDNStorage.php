<?php

namespace Katzen48\LaravelBunnyCdn\Filesystem;

use BunnyCDN\Storage\Exceptions\BunnyCDNStorageAuthenticationException;
use BunnyCDN\Storage\Exceptions\BunnyCDNStorageException;
use BunnyCDN\Storage\Exceptions\BunnyCDNStorageFileNotFoundException;

class BunnyCDNStorage extends \BunnyCDN\Storage\BunnyCDNStorage
{
    /**
     * @var string The name of the storage zone we are working on
     */
    public $storageZoneName = '';

    /**
     * @var string The API access key used for authentication
     */
    public $apiAccessKey = '';

    /**
     * @var string The region used for the request
     */
    public $storageZoneRegion = 'de';

    /**
     * Initializes a new instance of the BunnyCDNStorage class
     *
     * @param $storageZoneName
     * @param $apiAccessKey
     * @param $storageZoneRegion
     */
    public function __construct($storageZoneName, $apiAccessKey, $storageZoneRegion)
    {
        parent::__construct($storageZoneName, $apiAccessKey, $storageZoneRegion);
    }

    /**
     * Normalize a path string
     *
     * @param $path
     * @param null $isDirectory
     * @return false|string|string[]
     * @throws BunnyCDNStorageException
     */
    public function normalizePath($path, $isDirectory = NULL)
    {
        if (!$this->startsWith($path, "/{$this->storageZoneName}/") && !$this->startsWith($path, "{$this->storageZoneName}/")) {
            throw new BunnyCDNStorageException("Path validation failed. File path must begin with /{$this->storageZoneName}/");
        }

        $path = str_replace('\\', '/', $path);
        if ($isDirectory !== NULL) {
            if ($isDirectory) {
                if (!$this->endsWith($path, '/')) {
                    $path = $path . '/';
                }
            } else {
                if ($this->endsWith($path, '/') && $path !== '/') {
                    throw new BunnyCDNStorageException('The requested path is invalid.');
                }
            }
        }
    }

    /**
     * Starts With Helper
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function startsWith($haystack, $needle)
    {
        return (strpos($haystack, $needle) === 0);
    }

    /**
     * Ends with Helper
     *
     * @param $haystack
     * @param $needle
     * @return bool
     */
    private function endsWith($haystack, $needle)
    {
        $length = strlen($needle);
        if ($length === 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Sends a HTTP Request using cURL
     *
     * @param $url
     * @param string $method
     * @param null $uploadFile
     * @param null $uploadFileSize
     * @param null $downloadFileHandler
     * @return bool|string
     * @throws BunnyCDNStorageException
     */
    public function sendHttpRequest($url, $method = 'GET', $uploadFile = NULL, $uploadFileSize = NULL, $downloadFileHandler = NULL)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->getBaseUrl() . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
        curl_setopt($ch, CURLOPT_FAILONERROR, 0);

        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "AccessKey: {$this->apiAccessKey}",
        ));
        if ($method === 'PUT' && $uploadFile != NULL) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_UPLOAD, 1);
            curl_setopt($ch, CURLOPT_INFILE, $uploadFile);
            curl_setopt($ch, CURLOPT_INFILESIZE, $uploadFileSize);
        } else if ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        }

        if ($method === 'GET' && $downloadFileHandler != NULL) {
            curl_setopt($ch, CURLOPT_FILE, $downloadFileHandler);
        }

        $output = curl_exec($ch);
        $curlError = curl_errno($ch);
        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curlError) {
            throw new BunnyCDNStorageException('An unknown error has occurred during the request. Status code: ' . $curlError);
        }

        if ($responseCode === 404) {
            throw new BunnyCDNStorageFileNotFoundException($url);
        } else if ($responseCode === 401) {
            throw new BunnyCDNStorageAuthenticationException($this->storageZoneName, $this->apiAccessKey);
        } else if ($responseCode < 200 || $responseCode > 299) {
            throw new BunnyCDNStorageException('An unknown error has occurred during the request. Status code: ' . $responseCode);
        }

        return $output;
    }
}
