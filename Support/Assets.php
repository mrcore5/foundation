<?php namespace Mrcore\Foundation\Support;

class Assets
{
    public function __construct($basePath, $uri)
    {
        // Remove ? query string
        $uri = strtok($uri, '?');

        // Remove leading /assets
        // If /fonts, leave $uri as is, we wants /fonts in there
        if (substr($_SERVER['REQUEST_URI'], 0, 7) == '/assets') {
            $uri = substr($uri, 7); //ex: removes /assets so URL = /css/bootstrap.css
        }

        // Detect ../ in path to prevent users from hoping outside our defined assets path
        // Without this check users can do this http://mrcore.local/assets../.gitignore
        if (stristr($uri, '../') !== false) {
            $this->notFound();
        }

        if (substr($uri, 0, 1) != '/') $uri = '/'.$uri;
        $segments = explode("/", $uri);

        // Define asset paths
        $paths = [];

        // Get modules defined in config/modules.php
        $config = require "$basePath/config/modules.php";
        $modules = $config['modules'];

        if (substr($uri, 0, 4) == '/app') {
            // Load css from an mrcore application
            if (count($segments) >= 4) {
                $vendor = $segments[2];
                $package = $segments[3];
                $uri = substr($uri, strpos($uri, "$vendor/$package") + strlen("$vendor/$package"));

                // Get the asset path from config/modules.php
                $moduleName = $this->studly($vendor).'\\'.$this->studly($package);
                $module = isset($modules[$moduleName]) ? $modules[$moduleName] : null;

                // Try Assets folder
                $assetPath = isset($module['assets']) ? $module['assets'] : 'Assets';
                if (!$path = realpath("$basePath/vendor/$vendor/$package/$assetPath")) {
                    // Try public folder
                    $assetPath = isset($module['assets']) ? $module['assets'] : 'public';
                    $path = realpath("$basePath/vendor/$vendor/$package/$assetPath");
                }
                if ($path) $paths[] = $path;
            }
        } else {
            // Load css from module assets
            $assets = $config['assets'];

            // Always add mrcore public at the end
            if ($path = realpath("$basePath/public")) {
                $paths[] = $path;
            }

            // Add module assets
            foreach ($assets as $moduleName) {
                if (isset($modules[$moduleName])) {
                    $module = $modules[$moduleName];
                    if (!isset($module['enabled']) || $module['enabled'] == true) {
                        if (isset($module['assets'])) {
                            // Can have multiple paths, first one wins
                            $modulePaths = is_array($module['path']) ? $module['path'] : [$module['path']];
                            foreach ($modulePaths as $path) {
                                if (substr($path, 0, 1) != '/') {
                                    $path = "$basePath/$path";
                                } // relatove to absolute
                                $path = "$path/$module[assets]";
                                if ($path = realpath($path)) {
                                    $paths[] = $path;
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        // Stream asset
        if (count($paths) > 0) {
            $this->streamFile($uri, $paths);
        }
    }

    /**
     * Convert a value to studly caps case
     * @param  string $value
     * @return string
     */
    private function studly($value)
    {
        $value = ucwords(str_replace(array('-', '_'), ' ', $value));
        return str_replace(' ', '', $value);
    }

    /**
     * Stream first file in $paths by $uri
     * @param  string $uri   ex: /css/bootstrap.css
     * @param  array $paths array of paths, first found wins
     * @return void
     */
    private function streamFile($uri, $paths)
    {
        // Use first file found in $paths array
        foreach ($paths as $path) {
            $file = $path.$uri;
            if (file_exists($file) && !is_dir($file)) {
                // Asset file found in $path
                $filename = pathinfo($file)['basename'];
                $size = filesize($file);
                $ext = strtolower(pathinfo($file)['extension']);
                $mimetype = $this->mimetype($file);

                if ($ext == 'php') {
                    $this->notFound();
                }

                // Inline Stream with caching
                // Uses file modified date to refresh cache, so you always get a new file if modified!
                header("Content-type: $mimetype");
                header("Content-Disposition: inline; filename=\"$filename\"");

                // Checking if the client is validating his cache and if it is current.
                $expires = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? strtoupper($_SERVER['HTTP_IF_MODIFIED_SINCE']) : 'now'; //FRI, 22 MAY 2015 19:02:08 GMT
                $fileModified = strtoupper(gmdate('D, d M Y H:i:s', filemtime($file)).' GMT');                                 //FRI, 22 MAY 2015 19:02:08 GMT
                if ($expires == $fileModified) {
                    // Client's cache IS current, so we just respond '304 Not Modified'.
                    $this->notModified();
                } else {
                    // Image not cached or cache outdated, we respond '200 OK' and output the image.
                    header('Last-Modified: '.gmdate('D, d M Y H:i:s', filemtime($file)).' GMT', true, 200);
                }
                #this control doesn't seem to matter
                #header("Cache-control: public"); //required for If-Modified-Since header to exist from browser

                header("Content-length: $size");

                // Trick PHP into thinking this page is done, so it unlocks the session file to allow for further site navigation and downloading
                session_write_close();

                // Return file content
                readfile($file); // reads directly to output buffer, faster than file_get_contents which reads into variable first
                exit();
            }
        }

        // 404 Not Found
        $this->notFound();
    }

    /**
     * Get mimetype of a file
     * @param  string $file full path to file
     * @return string
     */
    private function mimetype($file)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimetype = strtolower(finfo_file($finfo, $file));
        finfo_close($finfo);

        // Override, php does not find these mimes correctly
        // Sometimes complex html is seen as text/c-c++
        $ext = strtolower(pathinfo($file)['extension']);
        if ($ext == 'css') {
            return 'text/css';
        } elseif ($ext == 'js') {
            return 'application/javascript';
        } elseif ($ext == 'html' || $ext == 'htm') {
            return 'text/html';
        }

        return $mimetype;
    }

    /**
     * Set 304 Not Modified header and exit
     */
    private function notModified()
    {
        header('HTTP/1.1 304 Not Modified');
        exit();
    }

    /**
     * Set 404 Not Found header and exit
     */
    private function notFound()
    {
        header("HTTP/1.0 404 Not Found");
        exit();
    }
}
