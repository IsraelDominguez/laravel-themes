<?php namespace Genetsis\Themes;

use Genetsis\Themes\Exceptions\AssetsException;
use Genetsis\Themes\Exceptions\ThemeNotFoundException;
use Illuminate\Container\Container;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\View\ViewFinderInterface;

class Theme
{
    /**
     * Theme Root Path.
     *
     * @var string
     */
    protected $themePath;


    /**
     * Assets path where assets will be published, relative to Publish Path
     *
     * @var string
     */
    protected $publicAssetsPath;

    /**
     *
     * Theme Public Path
     *
     * @var string
     */
    protected $publicPath;

    /**
     * All Theme Information.
     *
     * @var collection
     */
    protected $themes;

    /**
     * Blade View Finder.
     *
     * @var \Illuminate\View\ViewFinderInterface
     */
    protected $finder;

    /**
     * Application Container.
     *
     * @var \Illuminate\Container\Container
     */
    protected $app;

    /**
     * Translator.
     *
     * @var \Illuminate\Contracts\Translation\Translator
     */
    protected $lang;

    /**
     * Current Active Theme.
     *
     * @var string|collection
     */
    private $activeTheme = null;

    /**
     * Folders to generate symlink
     *
     * @var mixed|null
     */
    private $symlinkFolders = null;

    /**
     * Determine create symlinks
     * @var bool
     */
    private $createSymlinks = false;

    /**
     * Theme constructor.
     *
     * @param Container $app
     * @param ViewFinderInterface $finder
     * @param Translator $lang
     * @throws AssetsException
     */
    public function __construct(Container $app, ViewFinderInterface $finder, Translator $lang)
    {
        $this->app = $app;
        $this->finder = $finder;
        $this->lang = $lang;

        $this->publicAssetsPath = config('theme.public_assets_path');
        $this->createSymlinks = config('theme.create_symlinks');
        $this->symlinkFolders = config('theme.symlink_folders', array());
        $this->themes = config('theme.themes', array());

        if (!is_dir($dir = public_path($this->publicAssetsPath)) && false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException('Unable to create directory '.$dir);
        }

        if (!is_dir($dir)) {
            throw new AssetsException('Unable to locate directory '.$dir);
        }
    }

    /**
     * Return public absolute Path
     *
     * @param $path
     * @return string
     */
    public function publicPath($path = "") {
        return public_path($this->publicAssetsPath . DIRECTORY_SEPARATOR . $path);
    }

    /**
     * Return Themes relative path
     *
     * @param string $path
     * @return string
     */
    public function themePath($path = "") {
        return $this->themePath . DIRECTORY_SEPARATOR . $path;
    }


    /**
     * Return Relative assets public path for the current theme
     *
     * @param string $path
     * @return string
     */
    public function assetsPath($path = ""){
        return $this->publicAssetsPath . DIRECTORY_SEPARATOR . $this->activeTheme . DIRECTORY_SEPARATOR . $path;
    }


    /**
     * Set current theme.
     *
     * @param string $theme
     *
     * @return void
     */
    public function set($theme)
    {
        $this->loadTheme($theme);
        $this->activeTheme = $theme;
    }

    /**
     * Check if theme exists.
     *
     * @param string $theme
     *
     * @return bool
     */
    public function has($theme)
    {
        return array_key_exists($theme, $this->themes);
    }

    /**
     * Get particular theme all information.
     *
     * @param string $themeName
     *
     * @return null|ThemeInfo
     */
    private function getThemeInfo($themeName)
    {
        // Search inf theme default config
        if (isset($this->themes[$themeName])) {
            return $this->themes[$themeName];
        } else {
            //Load theme config
            $this->themes[$themeName] = config($themeName.'_theme.'.$themeName);
        }
        return isset($this->themes[$themeName]) ? $this->themes[$themeName] : null;
    }

    /**
     * Returns current theme or particular theme information.
     *
     * @param string $theme
     * @param bool   $collection
     *
     * @return array|null|ThemeInfo
     */
    public function get($theme = null, $collection = false)
    {
        if (is_null($theme) || !$this->has($theme)) {
            return !$collection ? $this->themes[$this->activeTheme]->all() : $this->themes[$this->activeTheme];
        }

        return !$collection ? $this->themes[$theme]->all() : $this->themes[$theme];
    }

    /**
     * Get current active theme name only or theme info collection.
     *
     * @param bool $collection
     *
     * @return null|ThemeInfo
     */
    public function current($collection = false)
    {
        return !$collection ? $this->activeTheme : $this->getThemeInfo($this->activeTheme);
    }


    /**
     * Get lang content from current theme.
     *
     * @param string $fallback
     *
     * @return \Illuminate\Contracts\Translation\Translator|string
     */
    public function lang($fallback, $replace = [])
    {
        $splitLang = explode('::', $fallback);

        if (count($splitLang) > 1) {
            if (is_null($splitLang[0])) {
                $fallback = $splitLang[1];
            } else {
                $fallback = $splitLang[0].'::'.$splitLang[1];
            }
        } else {
            $fallback = $this->activeTheme.'::'.$splitLang[0];
            if (!$this->lang->has($fallback)) {
                $fallback = $splitLang[0];
            }
        }

        return trans($fallback, $replace, $this->lang->getLocale());
    }


    /**
     * Map view map for particular theme.
     *
     * @param string $theme
     *
     * @return void
     */
    private function loadTheme($theme)
    {
        if (!is_null($this->activeTheme)) {
            return;
        }

        if (is_null($theme)) {
            throw new \InvalidArgumentException('Invalid theme: '. $theme);
        }

        // Load Theme Info
        $themeInfo = $this->getThemeInfo($theme);

        if (is_null($themeInfo)) {
            throw new \InvalidArgumentException('Invalid theme info: '. $theme);
        }

        // Create Theme public folder where compiled assets will be stored
        if (!is_dir($dir = $this->publicPath($theme)) && false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException('Unable to create directory '.$dir);
        }

        $this->themePath = $themeInfo['theme_path'];

        $assetsPath = $themeInfo['theme_assets_path'];

        if ($this->createSymlinks){
            $symlinks = collect($this->symlinkFolders)->map(function ($link) use ($theme, $assetsPath) {
                $origin = $this->themePath($theme . DIRECTORY_SEPARATOR . $assetsPath . $link);
                $destiny = $this->publicPath($theme . DIRECTORY_SEPARATOR . $link);
                if (!is_link($destiny) && (is_dir($origin))) {
                    $this->app->make('files')->link($origin, $destiny);
                }
            });
        }

        $viewPath = $this->themePath($theme . '/views');
        $langPath = $this->themePath($theme . '/lang');

        $this->finder->prependLocation($this->themePath);
        $this->finder->prependLocation($viewPath);
        $this->finder->prependNamespace($theme, $viewPath);

        $this->lang->addNamespace($theme, $langPath);
    }
}