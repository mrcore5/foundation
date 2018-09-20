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

        if (substr($uri, 0, 1) != '/') $uri = '/'.$uri;
        $segments = explode("/", $uri);

        // Get modules defined in config/modules.php
        $config = require "$basePath/config/modules.php";

        // Instantiate Mrcore\Foundation\Module class
        require "$basePath/vendor/mrcore/foundation/src/Support/Module.php";
        $Module = new Module();
        $Module->loadConfig($config);

        // Define asset paths
        $paths = [];

        // Loading asset from mrcore app (assets/app/mrcore/wiki/images/logo.png)
        if (substr($uri, 0, 4) == '/app') {
            if (count($segments) >= 4) {
                $vendor = $segments[2];
                $package = $segments[3];
                $uri = substr($uri, strpos($uri, "$vendor/$package") + strlen("$vendor/$package"));

                // Get the asset path from config/modules.php
                $moduleName = studly_case($vendor).'\\'.studly_case($package);

                // Module not found, which means we are streaming an asset from a dynamic %app% not in the module config
                if (!$Module->exists($moduleName)) {
                    // Dyamically load module config WITHOUT registering it (false parameter)
                    $Module->addModule($moduleName, ['type' => 'app'], false);
                }

                // Add modules asset path
                $assetPath = $Module->getPath($moduleName, 'assets');
                $paths[] = $assetPath;
            }

        // Loading asset from cascading overriding list of module asset folders, first wins
        } else {

            $assets = $config['assets'];

            // Always add mrcore public at the end
            if ($path = realpath("$basePath/public")) $paths[] = $path;

            // Get asset paths obeying order in modules config
            $paths = array_merge($paths, $Module->assets());
        }

        // Stream asset
        if (count($paths) > 0) $this->streamFile($uri, $paths);
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
