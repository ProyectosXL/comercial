<?php


class DotEnv
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;


    public function __construct(string $path)
    {
        if(!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    private function load(): array
    {
        if (!is_readable($this->path)) {
            error_log(sprintf('DotEnv Error: %s file is not readable', $this->path));
            return [];
        }

        $variables = [];
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }

            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Remove quotes if present
                if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                    $value = $matches[1];
                }

                $variables[$name] = $value;
                
                // Still set them in environment for compatibility, but prioritize our array
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
        return $variables;
    }

    public function listVars()
    {
        $loadedVars = $this->load();

        $defaultVars = array(
            'HOST_CENTRAL' => '',
            'HOST_LOCALES' => '',
            'DATABASE_CENTRAL' => '',
            'DATABASE_LOCALES' => '',
            'DATABASE_UY' => '',
            'DATABASE_TANGOBIS' => '',
            'DATABASE_SUC_UY' => '',
            'HOST_APPS' => '',
            'DATABASE_APPS' => '',
            'DATABASE_POWER' => '',
            'DATABASE_POWER_UY' => '',
            'DATABASE_POWER_FRANQUICIAS' => '',
            'DATABASE_FRANQUICIAS' => '',
            'USER' => '',
            'PASS' => '',
            'PASS_LOCALES' => '',
            'CHARACTER' => 'UTF-8',
            'ENV' => 'PROD',
            'HOST_EMAIL' => '',
            'USER_EMAIL' => '',
            'PASS_EMAIL' => '',
        );

        foreach ($defaultVars as $key => $default) {
            if (isset($loadedVars[$key])) {
                $defaultVars[$key] = $loadedVars[$key];
            } elseif (getenv($key) !== false) {
                $defaultVars[$key] = getenv($key);
            }
        }

        return $defaultVars;
    }

}