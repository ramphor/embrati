<?php
namespace Embrati;

class Embrati
{
    protected static $instances = array();
    protected static $assetUrl;
    protected static $options = array(
        'admin_scripts_registered' => false,
        'scripts_registered' => false,
    );
    protected static $ratings = array();
    protected $rateCallback;
    protected $workspace;

    public static function getInstance($name = 'default')
    {
        $name = preg_replace('/\s/', '_', $name);
        if (!isset(static::$instances[$name])) {
            static::$instances[$name] = new static($name);
        }
        return static::$instances[$name];
    }

    private function __construct($name)
    {
        $this->workspace = $name;
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

    public function registerStyles()
    {
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
            $abspath = constant('ABSPATH');
            $embratiAbspath = constant('EMBRATI_ABSPATH');
            if (PHP_OS === 'WINNT') {
                $abspath = str_replace('\\', '/', $abspath);
                $embratiAbspath = str_replace('\\', '/', $embratiAbspath);
            }
            static::$assetUrl = str_replace($abspath, site_url('/'), $embratiAbspath);
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

        // Support legacy structure
        if ($this->workspace === 'default') {
            do_action('embrati_registered_scripts');
            wp_enqueue_script(apply_filters('embrati_enqueue_script', 'embrati'));
        } else {
            do_action("embrati_{$this->workspace}_registered_scripts");
            wp_enqueue_script(apply_filters(
                "embrati_{$this->workspace}_enqueue_script",
                'embrati'
            ));
        }
    }

    public function transformConfigurations($id, $options)
    {
        $output = sprintf('element: document.querySelector("#embrati-%s"),%s', $id, PHP_EOL);
        if ($this->rateCallback) {
            $options['rateCallback'] = $this->rateCallback;
        }
        foreach ($options as $option => $value) {
            switch (gettype($value)) {
                case 'boolean':
                    $output .= sprintf('%s: %s,%s', $option, $value ? 'true' : 'false', PHP_EOL);
                    break;
                default:
                    $output .= sprintf('%s: %s,%s', $option, (string)$value, PHP_EOL);
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
        echo '<script id="' . $this->workspace . '">';
        foreach (static::$ratings as $id => $configurations) {
            unset($configurations['echo']);
            echo sprintf('var ' . preg_replace('/[-]/', '_', $id) . ' = raterJs({%2$s%1$s%2$s});%2$s', $this->transformConfigurations($id, $configurations), PHP_EOL) ;
        }
        echo '</script>';
    }

    /**
     * This method use to create star rating support interaction via WordPress ajax.
     * It's will render HTML and use JS to render the star
     */
    public function create($id, $args = array())
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

    protected function generateHtmlAttributes($attributes)
    {
        if (!is_array($attributes)) {
            return '';
        }
        $attributesStr = '';
        foreach ($attributes as $attribute => $value) {
            $attributesStr .= sprintf(
                '%s="%s" ',
                $attribute,
                is_array($value) ? implode(' ', $value) : $value
            );
        }
        return rtrim($attributesStr);
    }

    /**
     * This method use to show star rating only.
     * It's will render HTML and use CSS to styling the star
     */
    public function display($id, $args)
    {
        $cssClasses = array('rating', 'medium');
        $args = wp_parse_args($args, array(
            'max' => 5,
            'use_svg' => true,
            'rating' => 0
        ));
        $ratingValue = floor($args['rating']);

        if (isset($args['wrap_class'])) {
            $cssClasses = array_merge($cssClasses, $args['wrap_class']);
        }
        if ($args['use_svg']) {
            $cssClasses[] = 'svg';
        }

        if ($args['rating'] > 0) {
            $cssClasses[] = sprintf('value-%d', $ratingValue);
        }
        if ($args['rating'] - $ratingValue > 0) {
            $cssClasses[] = 'half';
        }

        $attributes = array(
            'class' => $cssClasses,
        );

        $starTemplate = locate_template(apply_filters("embrati_{$id}_templates", array(
            'templates/embrati/star.php',
            'templates/star.php',
        )));
        if (empty($starTemplate)) {
            $starTemplate = sprintf('%s/templates/star.php', dirname(__DIR__));
            $svgStar = $this->assetUrl('star-rating.icons.svg');
        }
        ?>
        <div <?php echo $this->generateHtmlAttributes(apply_filters(
            "embrati_{$id}_star_wrapper_attributes",
            $attributes
        )); ?>>
            <div class="star-container">
                <?php
                ob_start();
                include $starTemplate;
                $starHTML = str_repeat(
                    ob_get_clean(),
                    $args['max']
                );
                echo $starHTML;
                ?>
            </div>
        </div>
        <?php
    }

    public function setJsRateCallback($rateCallback)
    {
        if (empty($rateCallback)) {
            return;
        }
        $this->rateCallback = $rateCallback;
    }
}
