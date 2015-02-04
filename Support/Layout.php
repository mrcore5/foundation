<?php namespace Mrcore\Modules\Foundation\Support;

use View;

class Layout {

	private $title;
	private $css;
	private $printCss;
	private $js;
	private $script;
	private $mode;
	private $hideAll;
	private $hideHeaderbar;
	private $hideFooterbar;
	private $hideTitlebar;
	private $hideMenubar;
	private $viewport;
	private $container;
	private $bodyAttr;

	public function __construct()
	{
		// Layout Defaults
		$this->title = '';
		$this->css = array();
		$this->printCss = array();
		$this->js = array();
		$this->script = array();
		$this->mode('default');
		$this->hideAll = false;
		$this->hideHeaderbar = false;
		$this->hideFooterbar = false;
		$this->hideTitlebar = false;
		$this->hideMenubar = true;
		$this->viewport = '<meta name="viewport" content="width=device-width, initial-scale=1" />';
		$this->container = true;
		$this->header_container = true;
		$this->footer_container = true;
		$this->bodyAttr = '';
	}

	public function title($value = null)
	{
		return $this->getSet(__function__, $value);
	}

	public function css($value = null, $prepend = false)
	{
		if (isset($value)) {
			if ($prepend) {
				// Add to beginning of array
				$this->css = array_pad($this->css, -(count($this->css) + 1), $value);
			} else {
				// Add to end of array
				$this->css[] = $value;
			}
		} else {
			return $this->css;
		}
	}

	public function removeCss($value)
	{
		for ($i=0; $i < count($this->css); $i++) {
			if (preg_match("'$value'", $this->css[$i])) {
				unset($this->css[$i]);
				$this->css = array_values($this->css);
				break;
			}
		}
	}

	public function replaceCss($search, $replace)
	{
		for ($i=0; $i < count($this->css); $i++) {
			if (preg_match("'$search'", $this->css[$i])) {
				$this->css[$i] = $replace;
				break;
			}
		}
	}

	public function printCss($value = null, $prepend = false)
	{
		if (isset($value)) {
			if ($prepend) {
				// Add to beginning of array
				$this->printCss = array_pad($this->printCss, -(count($this->printCss) + 1), $value);
			} else {
				// Add to end of array
				$this->printCss[] = $value;
			}
		} else {
			return $this->printCss;
		}
	}

	public function js($value = null, $prepend = false)
	{
		if (isset($value)) {
			if ($prepend) {
				// Add to beginning of array
				$this->js = array_pad($this->js, -(count($this->js) + 1), $value);
			} else {
				// Add to end of array
				$this->js[] = $value;
			}
		} else {
			return $this->js;
		}
	}

	public function removeJs($value)
	{
		for ($i=0; $i < count($this->js); $i++) {
			if (preg_match("'$value'", $this->js[$i])) {
				unset($this->js[$i]);
				$this->js = array_values($this->js);
				break;
			}
		}
	}

	public function replaceJs($search, $replace)
	{
		for ($i=0; $i < count($this->js); $i++) {
			if (preg_match("'$search'", $this->js[$i])) {
				$this->js[$i] = $replace;
				break;
			}
		}
	}

	public function script($value = null, $prepend = false)
	{
		if (isset($value)) {
			if ($prepend) {
				// Add to beginning of array
				$this->script = array_pad($this->script, -(count($this->script) + 1), $value);
			} else {
				// Add to end of array
				$this->script[] = $value;
			}
		} else {
			return $this->script;
		}
	}

	public function mode($value = null)
	{
		if (isset($value)) {
			$value = strtolower($value);
			$this->mode = $value;
			if ($value == 'simple' || $value == 'raw') {
				$this->hideAll(true);
			} else {
				$this->hideAll(false);
			}
		} else {
			return $this->mode;
		}
	}

	public function modeIs($value)
	{
		if (strtolower($this->mode) == strtolower($value)) {
			return true;
		} else {
			return false;
		}
	}


	public function hideAll($value = null)
	{
		$this->hideHeaderbar($value);
		$this->hideFooterbar($value);
		$this->hideTitlebar($value);
		$this->hideMenubar($value);
	}

	public function hideHeaderbar($value = null)
	{
		return $this->getSet(__function__, $value);
	}

	public function hideFooterbar($value = null)
	{
		return $this->getSet(__function__, $value);
	}

	public function hideTitlebar($value = null)
	{
		return $this->getSet(__function__, $value);
	}

	public function hideMenubar($value = null)
	{
		return $this->getSet(__function__, $value);
	}

	public function viewport($value = null)
	{
		return $this->getSet(__function__, $value);
	}

	public function container($container = true, $headerContainer = true, $footerContainer = true)
	{
		$container = ($container ? 'container' : 'no-container');
		$headerContainer = ($headerContainer ? 'container' : 'no-container');
		$footerContainer = ($footerContainer ? 'container' : 'no-container');
		View::share('container', $container);
		View::share('headerContainer', $headerContainer);
		View::share('footerContainer', $footerContainer);
	}

	public function bodyAttr($value = null)
	{
		return $this->getSet(__function__, $value);	
	}

	public function getSet($key, $value = null)
	{
		if (isset($value)) {
			$this->$key = $value;
		} else {
			return $this->$key;
		}
	}

}