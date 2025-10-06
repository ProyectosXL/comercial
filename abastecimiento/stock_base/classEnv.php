<?php

class DotEnv
{
    /**
     * El directorio donde se encuentra el archivo .env.
     * @var string
     */
    protected $path;

    /**
     * El constructor ahora se encarga de TODO:
     * 1. Recibe la ruta al DIRECTORIO que contiene el .env.
     * 2. Construye la ruta completa al archivo .env.
     * 3. Llama al método load() para cargar las variables inmediatamente.
     */
    public function __construct(string $directoryPath)
    {
        // Construimos la ruta completa al archivo .env
        $this->path = $directoryPath . '/.env';

        // Verificamos que el ARCHIVO .env exista y sea legible
        if (!is_readable($this->path) || !is_file($this->path)) {
            throw new \InvalidArgumentException(sprintf('El archivo .env no existe o no se puede leer en la ruta: %s', $this->path));
        }
        
        // ¡Este es el paso clave que faltaba! Llamamos a load() al crear el objeto.
        $this->load();
    }

    /**
     * El método load() ahora es privado y hace una sola cosa: leer el archivo y establecer las variables.
     */
    private function load(): void
    {
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }

            // Separar nombre y valor
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Establecer las variables de entorno
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }
    
    // El método listVars() no es necesario para que la clase funcione.
    // La forma correcta de acceder a las variables es usando getenv('NOMBRE_VARIABLE').
    // Lo eliminamos para simplificar y seguir las buenas prácticas.
}