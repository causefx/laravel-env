<?php

namespace msztorc\LaravelEnv;

class Env {

    private $_envContent = null;
    private $_envVars = null;
    private $_envPath = null;
    private $_saved = false;
    private $_changed = false;

    public function __construct()
    {
        $this->_envPath = app()->environmentFilePath();
        $this->_envContent = file_get_contents($this->_envPath);

        $this->_parse();
    }

    /**
     *  Parse env content into array
     */
    private function _parse() {

        $env_lines = preg_split('/\r\n|\r|\n/', $this->_envContent);

        foreach ($env_lines as $line)
        {
            if (strlen($line)) {
                [$key, $val] = explode('=', $line);
                $this->_envVars[$key] = $this->_stripValue($val);
            }
        }
    }


    /**
     * Check if the variable exists
     * @param string $key Environment variable key
     * @return bool
     */
    public function exists(string $key): bool
    {
        if (is_null($this->_envVars))
            $this->_parse();

        return isset($this->_envVars[strtoupper($key)]);
    }

    /**
     * Get the current env variable value
     *
     * @param string $key Environment variable key
     * @return string
     */
    public function getValue(string $key): string
    {
        if (is_null($this->_envVars))
            $this->_parse();

        return $this->_envVars[strtoupper($key)] ?? '';
    }


    /**
     * Get env key-value
     *
     * @param string $key Environment variable key
     * @return array
     */
    public function getKeyValue(string $key): array
    {
        if (is_null($this->_envVars))
            $this->_parse();

        return [strtoupper($key) => $this->_envVars[strtoupper($key)]] ?? [];
    }


    /**
     * Set env variable value
     * @param string $key Environment variable key
     * @param string $value Variable value
     * @param bool $write Write changes to .env file
     * @return string
     */
    public function setValue(string $key, string $value, $write = true): string
    {
        $key = strtoupper($key);
        $value = $this->_prepareValue($value);

        if ($this->exists($key)) {
            $this->_envContent = preg_replace("/^{$key}=.*$/m", "{$key}={$value}", $this->_envContent);
        } else {
            $this->_envContent .= PHP_EOL . "{$key}={$value}" . PHP_EOL;
        }

        $this->_changed = true;
        $this->_saved = false;

        $this->_parse();
        if ($write) $this->write();

        return $this->getValue($key);

    }


    /**
     * Delete environment variable
     * @param string $key Environment variable key
     * @param bool $write Write changes to .env file
     * @return bool
     */
    public function deleteVariable(string $key, bool $write = true): bool
    {
        $key = strtoupper($key);

        if ($this->exists($key)) {
            $this->_envContent = preg_replace("/^{$key}=.*\s{0,1}/m", '', $this->_envContent);

            $this->_changed = true;
            $this->_saved = false;

            if ($write)
                $this->write();

        }

        return true;

    }

    /**
     * Check and prepare value to be safe
     * @param string $value
     * @return string
     */
    private function _prepareValue(string $value): string
    {
        if (false !== strpos($value, ' ') || in_array($value[0], ['=', '$'])) {
            $value = '"' . $value . '"';
        }

        return preg_quote($value);
    }

    /**
     * Strip output value
     * @param string $value
     * @return string
     */
    private function _stripValue(string $value): string
    {
        return trim($value, '"');
    }

    /**
     * Get all env variables
     * @return array
     */
    public function getVariables(): array
    {
        return $this->_envVars;
    }

    /**
     * Get current env entire content from memory
     * @return string
     */
    public function getEnvContent(): string
    {
        return $this->_envContent;
    }

    /**
     * Write env config to file
     * @return bool
     */
    public function write(): bool
    {
        return $this->_saved = file_put_contents($this->_envPath, $this->_envContent);
    }

    /**
     * Check if the changes has been saved
     * @return bool
     */
    public function isSaved(): bool
    {
        return $this->_saved;
    }

    /**
     * Check if there were any env content changes
     * @return bool
     */
    public function wasChanged(): bool
    {
        return $this->_changed;
    }


    /*public function getEnvValue(string $key): string
    {
        preg_match("/^{$key}=(.*)$/m", $this->_envContent, $matches);
        return $matches[1] ?? '';
    }*/
}