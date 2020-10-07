<?php
namespace Embrati;

class Embrati
{
    protected static $instance;
    protected static $assetUrl;
    protected static $options = array(
        'admin_scripts_registered' => false,
        'scripts_registered' => false,
    );
    protected static $ratings = array();

    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    private function __construct()
    {
        $this->defineConstants();
    }

    private function define($name, $value)
    {
        if (!defined($name)) {
            define($name, $value);
        }
    }

    private function defineConstants()
    {
        $this->define('EMBRATI_ABSPATH', dirname(__DIR__));
    }

    public function registerAdminScripts()
    {
        if (static::$options['admin_scripts_registered']) {
            return;
        }
        add_action('admin_enqueue_scripts', array($this, '_registerScripts'));
        add_action('admin_print_footer_scripts', array($this, 'configurations'));

        static::$options['admin_scripts_registered'] = true;
    }

    public function registerScripts()
    {
        if (static::$options['scripts_registered']) {
            return;
        }
        add_action('wp_enqueue_scripts', array($this, '_registerScripts'));
        add_action('wp_print_footer_scripts', array($this, 'configurations'));

        static::$options['scripts_registered'] = true;
    }

    public function registerStyles() {
        wp_register_style(
            'css-star-rating',
            $this->assetUrl('css-star-rating/css/star-rating.css'),
            array(),
            '1.1.3'
        );

        // Call the style just registered
        wp_enqueue_style('css-star-rating');
    }

    protected function assetUrl($path = '')
    {
        if (is_null(static::$assetUrl)) {
            static::$assetUrl = str_replace(ABSPATH, site_url('/'), EMBRATI_ABSPATH);
        }
        return sprintf(
            '%s/assets/%s',
            static::$assetUrl,
            $path
        );
    }

    public function _registerScripts()
    {
        wp_register_script('embrati', $this->assetUrl('rater-js.js'), null, '1.0.1', true);
        wp_enqueue_script('embrati');
    }

    public function transformConfigurations($id, $options)
    {
        $output = sprintf('element: document.querySelector("#embrati-%s"),%s', $id, PHP_EOL);
        foreach ($options as $option => $value) {
            switch ($option) {
                default:
                    $output .= sprintf('%s: %s,%s', $option, $value, PHP_EOL);
                    break;
            }
        }

        return rtrim($output, ',' . PHP_EOL);
    }

    public function configurations()
    {
        if (count(static::$ratings) <= 0) {
            return;
        }
        echo '<script>';
        foreach (static::$ratings as $id => $configurations) {
            echo sprintf('raterJs({%2$s%1$s%2$s});%2$s', $this->transformConfigurations($id, $configurations), PHP_EOL) ;
        }
        echo '</script>';
    }

    /**
     * This method use to create star rating support interaction via WordPress ajax.
     * It's will render HTML and use JS to render the star
     */
    public function create($id, $args)
    {
        if (isset(static::$ratings[$id])) {
            return;
        }
        static::$ratings[$id] = $args;

        $args = wp_parse_args($args, array(
            'echo' => true,
        ));
        $html = sprintf('<div id="embrati-%s"></div>', esc_attr($id)); // WPCS: XSS OK
        if (!$args['echo']) {
            return $html;
        }
        echo $html;
    }

    /**
     * This method use to show star rating only.
     * It's will render HTML and use CSS to styling the star
     */
    public function display($id, $args) {
    }
}
