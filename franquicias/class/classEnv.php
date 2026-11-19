<?php


class DotEnv
{
    /**
     * The directory where the .env file can be located.
     *
     * @var string
     */
    protected $path;


    public function __construct($path)
    {
        if(!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }

    private function load()
    {
        if (!is_readable($this->path)) {
            throw new RuntimeException(sprintf('%s file is not readable', $this->path));
        }

        $parsed = [];
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {

            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            $parsed[$name] = $value;

            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                @putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }

        return $parsed;
    }

    public function listVars(){
        $parsed = (new DotEnv(__DIR__ . '/../../../.env'))->load();

        $keys = [
            'HOST_CENTRAL',
            'HOST_LOCALES',
            'DATABASE_CENTRAL',
            'DATABASE_LOCALES',
            'DATABASE_UY',
            'DATABASE_TANGOBIS',
            'DATABASE_SUC_UY',
            'HOST_APPS',
            'DATABASE_APPS',
            'DATABASE_POWER',
            'USER',
            'PASS',
            'PASS_LOCALES',
            'CHARACTER',
            'ENV',
            'HOST_EMAIL',
            'USER_EMAIL',
            'PASS_EMAIL',
        ];

        $vars = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $parsed)) {
                $vars[$key] = $parsed[$key];
            } elseif (array_key_exists($key, $_ENV)) {
                $vars[$key] = $_ENV[$key];
            } elseif (array_key_exists($key, $_SERVER)) {
                $vars[$key] = $_SERVER[$key];
            } else {
                $val = getenv($key);
                $vars[$key] = $val !== false ? $val : '';
            }
        }

        return $vars;
    }

}
