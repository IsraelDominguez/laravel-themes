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
    'md5'    => false,

    // Relative to base_path()
    'themes_path' => 'resources',
    // Relative to public_path()
    'public_path' => 'assets',

    'themes' => array (
        'public' => array (
            /*
            |--------------------------------------------------------------------------
            | Groups
            |--------------------------------------------------------------------------
            |
            | Groups of assets to run over a set of filters into an output file.
            | By default, all paths to files begin in the public_path() directory.
            | The order of asset definition is maintained in the output file.
            |
            | Use the Asset::url() function to generate the link to the final file:
            | <script src="<?php echo Asset::url('main_js'); ?>"></script>
            |
             */
            'groups' => array(
                'main_js' => array(
                    'filters' => array(
                        'js_min',
                    ),
                    'assets' => array(                  // Paths relative to resources_path()
                        'jquery',                       // Named asset defined below
                        'assets/js/common/search.js',   // Single file
                        'assets/js/folder/*js',      // Folder inclusion
                    ),
                    'output' => '/assets/cache/scripts.js',           // Writable output relative to public_path()
                ),
                'main_css' => array(
                    'filters' => array(
                        'css_import',
                        'css_rewrite',
                        'css_min',
                    ),
                    'assets' => array(
                        'assets/css/bootstrap/css/bootstrap.min.css',
                        'assets/css/font-awesome/css/font-awesome.css',
                        'assets/css/prettyPhoto/css/*css',
                    ),
                    'output' => '/assets/cache/vendors.css'
                ),
                'main_less' => array(
                    'filters' => array(
                        'css_import',
                        'css_rewrite',
                        'less_php',
                        'css_min'
                    ),
                    'assets' => array(
                        'assets/less/master.less'
                    ),
                    'output' => '/assets/cache/styles.css'
                ),
            ),
        ),
        'admin' => array (
        )
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
        'jquery' => 'assets/javascripts/jquery.js',
    )
);