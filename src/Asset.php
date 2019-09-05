<?php namespace Genetsis\Themes;

use Assetic\Asset\AssetCollection;
use Assetic\Asset\AssetInterface;
use Assetic\Asset\FileAsset;
use Assetic\Asset\GlobAsset;
use Assetic\Asset\HttpAsset;
use Assetic\AssetManager;
use Assetic\AssetWriter;
use Assetic\Filter\FilterInterface;
use Assetic\FilterManager;
use Genetsis\Themes\Exceptions\AssetsException;

class Asset
{
    public $groups = array();

    /** @var FilterManager */
    public $filters;

    /** @var AssetManager */
    public $assets;

    protected $md5;
    protected $secure;


    /**
     * Asset constructor.
     *
     */
    public function __construct()
    {
        $this->createFilterManager();
        $this->createAssetManager();

        $this->md5 = config('theme.md5', false);
        $this->secure = config('theme.secure', false);
    }

    /**
     * Create a new AssetCollection instance for the given group.
     *
     * @param  string                         $name
     * @param  bool                           $overwrite force writing
     * @return \Assetic\Asset\AssetCollection
     */
    public function createGroup($name, $overwrite = false)
    {
        if (isset($this->groups[$name])) {
            return $this->groups[$name];
        }

        $assets = $this->createAssetArray($name);
        $filters = $this->createFilterArray($name);
        $coll = new AssetCollection($assets, $filters);
        if ($output = $this->getConfig($name, 'output')) {
            $coll->setTargetPath(\Theme::assetsPath($output));
        }

        // check output cache
        $write_output = true;
        if (!$overwrite) {
            if (file_exists($output = $coll->getTargetPath())) {
                $output_mtime = filemtime($output);
                $asset_mtime = $coll->getLastModified();

                if ($asset_mtime && $output_mtime >= $asset_mtime) {
                    $write_output = false;
                }
            }
        }

        // store assets
        if ($overwrite || $write_output) {
            $writer = new AssetWriter(public_path());
            $writer->writeAsset($coll);
        }

        return $this->groups[$name] = $coll;
    }

    /**
     * Generate the URL for a given asset group.
     *
     * @param $name
     * @param  array  $options options: array(secure => bool, md5 => bool)
     * @return string
     */
    public function url($name, array $options = null)
    {
        $options = is_null($options) ? array() : $options;
        $group = $this->createGroup($name);

        $cache_buster = '';
        if (\Arr::get($options, 'md5', $this->md5)) {
            $cache_buster = '?'.md5_file($this->file($name));
        }

        //$secure = array_get($options, 'secure', $this->secure);
        return '/'.$group->getTargetPath().$cache_buster;
    }

    /**
     * Get the output filename for an asset group.
     *
     * @param $name
     * @return string
     */
    public function file($name)
    {
        $group = $this->createGroup($name);

        return $group->getTargetPath();
    }

    /**
     * Create an array of AssetInterface objects for a group.
     *
     * @param $name
     * @throws \InvalidArgumentException for undefined assets
     * @return array
     */
    protected function createAssetArray($name)
    {
        $config = $this->getConfig($name, 'assets', array());
        $assets = array();
        foreach ($config as $asset) {
            // existing asset definition
            if ($this->assets->has($asset)) {
                $assets[] = $this->assets->get($asset);
            }
            // looks like a file
            elseif (\Str::contains($asset, array('/', '.', '-'))) {
                $assets[] = $this->parseAssetDefinition($asset);
            }
            // unknown asset
            else {
                throw new \InvalidArgumentException("No asset '$asset' defined");
            }
        }

        return $assets;
    }

    /**
     * Create an array of FilterInterface objects for a group.
     *
     * @param $name
     * @return array
     */
    protected function createFilterArray($name)
    {
        $config = $this->getConfig($name, 'filters', array());
        $filters = array();
        foreach ($config as $filter) {
            $filters[] = $this->filters->get($filter);
        }

        return $filters;
    }

    /**
     * Creates the filter manager from the config file's filter array.
     *
     * @return FilterManager
     */
    protected function createFilterManager()
    {
        $manager = new FilterManager();
        $filters = config('theme.filters', array());
        foreach ($filters as $name => $filter) {
            $manager->set($name, $this->createFilter($filter));
        }

        return $this->filters = $manager;
    }

    /**
     * Create a filter object from a value in the config file.
     *
     * @param  callable|string|FilterInterface $filter
     * @return FilterInterface
     * @throws \InvalidArgumentException       when a filter cannot be created
     */
    protected function createFilter($filter)
    {
        if (is_callable($filter)) {
            return call_user_func($filter);
        } elseif (is_string($filter)) {
            return new $filter();
        } elseif (is_object($filter)) {
            return $filter;
        } else {
            throw new \InvalidArgumentException("Cannot convert $filter to filter");
        }
    }

    protected function createAssetManager()
    {
        $manager = new AssetManager();
        $config = config('theme.assets', array());

        foreach ($config as $key => $refs) {
            if (!is_array($refs)) {
                $refs = array($refs);
            }

            $asset = array();
            foreach ($refs as $ref) {
                $asset[] = $this->parseAssetDefinition($ref);
            }

            if (count($asset) > 0) {
                $manager->set($key,
                    count($asset) > 1
                        ? new AssetCollection($asset)
                        : $asset[0]
                );
            }
        }

        return $this->assets = $manager;
    }


    public function img($file) {
        return $this->getAsset($file, 'img');
    }

    public function pdf($file) {
        return $this->getAsset($file, 'pdf');
    }

    private function getAsset($file, $type) {
        return '/'.\Theme::assetsPath($type.'/'.$file);
    }


    /**
     * Create an asset object from a string definition.
     *
     * @param  string         $asset
     * @return AssetInterface
     */
    protected function parseAssetDefinition($asset)
    {
        if (\Str::startsWith($asset, 'http://')) {
            return new HttpAsset($asset);
        } elseif (\Str::contains($asset, array('*', '?'))) {
            return new GlobAsset(\Theme::themePath(\Theme::current().'/'.$asset));
        } else {
            return new FileAsset(\Theme::themePath(\Theme::current().'/'.$asset));
        }
    }

    protected function getConfig($group, $key, $default = null)
    {
        if ((!$value = config(\Theme::current().'_theme.'.\Theme::current().'.groups.'.$group.'.'.$key, $default))&&(!is_array($value))) {
            throw new AssetsException('Config value: ' .\Theme::current().'_theme.'.\Theme::current().'.groups.'.$group.'.'.$key);
        }

        return $value;
    }

}
