<?php namespace Mrcore\Modules\Foundation\Support;

class Assets {

	public function __construct($basePath, $uri)
	{
		// Remove ? query string
		$uri = strtok($uri, '?');

		// Remove leading /assets
		$uri = substr($uri, 7); //ex: /css/bootstrap.css
		$segments = explode("/", $uri);

		// Define asset paths
		$paths = [];


		if (substr($uri, 0, 4) == '/app') {
			// Load css from an mrcore application
			if (count($segments) >= 4) {
				$vendor = $segments[2];
				$package = $segments[3];
				$uri = substr($uri, strpos($uri, "$vendor/$package") + strlen("$vendor/$package"));
				if ($path = realpath("$basePath/../Apps/".$this->studly($vendor)."/".$this->studly($package)."/Assets")) {
					$paths[] = $path;
				}
			}

		} else {
			// Load css from module assets
			$config = require "$basePath/config/modules.php";
			$modules = $config['modules'];
			$assets = $config['assets'];

			// Always add mrcore public at the end
			if ($path = realpath("$basePath/public")) $paths[] = $path;

			// Add module assets
			foreach ($assets as $moduleName) {
				if (isset($modules[$moduleName])) {
					$module = $modules[$moduleName];
					if (isset($module['enabled']) && $module['enabled'] == true) {
						if (isset($module['assets'])) {
							$paths[] = realpath("$basePath/$module[path]/$module[assets]");
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

				if ($ext == 'php') $this->notFound();

				// Inline Stream with caching
				// Uses file modified date to refresh cache, so you always get a new file if modified!
				$headers = apache_request_headers(); //works fine on nginx too!
				header("Content-type: $mimetype");
				header("Content-Disposition: inline; filename=\"$filename\"");

				// Checking if the client is validating his cache and if it is current.
				if (isset($headers['If-Modified-Since']) && (strtoupper($headers['If-Modified-Since']) == strtoupper(gmdate('D, d M Y H:i:s', filemtime($file)).' GMT'))) {
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
