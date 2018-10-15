<?php

return array(
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | The default settings for having assets sent over HTTPS and bust client
    | caches when files are changed.
    |
     */
    'secure' => false,
    'md5'    => true,

    // Relative to base_path()
    //'themes_path' => base_path('resources'),

    // Assets path where assets will be published, relative to Publish Path
    'public_assets_path' => 'assets',

    'create_symlinks' => true,

    'symlink_folders' => array (
        'fonts',
        'img',
        'pdf'
    ),

    // Add themes for packages
    'themes' => array (

    ),

    /*
    |--------------------------------------------------------------------------
    | Filters
    |--------------------------------------------------------------------------
    |
    | Name => class key-values for filters to use.
    | The use of closure based filters are also possible.
    |
     */
    'filters' => array(
        'css_min'       => 'Assetic\Filter\CssMinFilter',
        'css_import'    => 'Assetic\Filter\CssImportFilter',
        'css_rewrite'   => 'Assetic\Filter\CssRewriteFilter',
        'embed_css'     => 'Assetic\Filter\PhpCssEmbedFilter',
        'less_php'      => 'Assetic\Filter\LessphpFilter',
        'js_min'        => 'Assetic\Filter\JSMinFilter',
        'coffee_script' => 'Assetic\Filter\CoffeeScriptFilter',
        'yui_js' => function () {
            return new Assetic\Filter\Yui\JsCompressorFilter('yui-compressor.jar');
        },
    ),

    /*
    |--------------------------------------------------------------------------
    | Named Assets
    |--------------------------------------------------------------------------
    |
    | Name => path key-values for common files to be included in groups.
    |
     */
    'assets' => array(
        // Sample
        //'jquery' => 'assets/javascripts/jquery.js',
    )
);