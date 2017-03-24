<?php

namespace Jybeul\LegacyBridgeBundle\Kernel;

/**
 * HtaccessHandler.
 * Read .htaccess file and return SetEnv definition.
 */
class HtaccessHandler
{
    /**
     * Return SetEnv definition by priority.
     *
     * @param string $from
     * @param string $until
     *
     * @return array
     */
    public function getSetEnvs($from, $until)
    {
        $ret = [];
        $lenUntil = strlen(realpath($until));
        // To avoid recursions etc...
        $seens = [];

        while (1) {
            $realFrom = realpath($from);
            if (strlen($realFrom) < $lenUntil || isset($seens[$realFrom])) {
                break;
            }

            $accessVars = $this->readAccessFileVars($from);
            // merge by keeping the oldest
            $ret = array_merge($accessVars, $ret);
            // up one level
            $from = $from.'/../';
            // directory has been verified
            $seens[$realFrom] = true;
        }

        return $ret;
    }

    /**
     * Read each line from .htaccess file from a directory.
     *
     * @param string $from
     *
     * @return array
     */
    private function readAccessFileVars($dir)
    {
        $ret = [];
        $file = rtrim($dir, '/').'/.htaccess';
        if (file_exists($file) && is_readable($file)) {
            $lines = file($file);
            foreach ($lines as $line) {
                $line = trim($line);
                $matches = [];
                if (preg_match('/^SetEnv\s+(.+?)\s+(.+)/i', $line, $matches)) {
                    $ret[trim($matches[1], "\x22\x27")] = trim($matches[2], "\x22\x27");
                }
            }
        }

        return $ret;
    }
}
