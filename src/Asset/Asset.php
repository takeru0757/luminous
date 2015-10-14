<?php

namespace Luminous\Asset;

use InvalidArgumentException;

class Asset
{
    /**
     * The manifest data.
     *
     * @var array
     */
    protected $manifest;

    /**
     * The prefix.
     *
     * @var string
     */
    protected $prefix;

    /**
     * Create a new asset instance.
     *
     * @param string $manifestPath
     * @param string $prefix
     * @return void
     */
    public function __construct($manifestPath, $prefix)
    {
        $this->manifest = json_decode(file_get_contents($manifestPath), true);
        $this->prefix = trim($prefix, '/').'/';
    }

    /**
     * Get the path of the file.
     *
     * @param string $file
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function path($file)
    {
        if (! isset($this->manifest[$file])) {
            throw new InvalidArgumentException("File {$file} not defined in asset manifest.");
        }

        return $this->prefix.$this->manifest[$file];
    }
}
