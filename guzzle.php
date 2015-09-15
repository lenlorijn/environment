<?php
/**
 * Introduce a custom auto loader for PSR and GuzzleHTTP.
 */

foreach (glob(
    __DIR__ . '/vendor/guzzlehttp/*/src/functions_include.php'
) as $include) {
    require_once $include;
}

spl_autoload_register(
    function ($className) {
        static $sourceFiles;

        if (!isset($sourceFiles)) {
            $sourceFiles = array();

            $projects = array_merge(
                glob(__DIR__ . '/vendor/guzzlehttp/*'),
                glob(__DIR__ . '/vendor/psr/*')
            );

            foreach ($projects as $project) {
                $sourceDir = "{$project}/src/";

                $projectFiles = array_merge(
                    glob("{$sourceDir}*.php"),
                    glob("{$sourceDir}*/*.php")
                );

                $sourceFiles += array_combine(
                    array_map(
                        function ($file) use ($sourceDir) {
                            return str_replace(
                                array($sourceDir, '.php'),
                                '',
                                $file
                            );
                        },
                        $projectFiles
                    ),
                    $projectFiles
                );
            }
        }

        $classParts = explode('\\', $className);

        while (!empty($classParts)) {
            $key = implode(DIRECTORY_SEPARATOR, $classParts);

            if (array_key_exists($key, $sourceFiles)) {
                /** @noinspection PhpIncludeInspection */
                include_once $sourceFiles[$key];
                break;
            }

            array_shift($classParts);
        }
    }
);
