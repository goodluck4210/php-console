<?php
/**
 * Created by PhpStorm.
 * User: Inhere
 * Date: 15-4-1
 * Time: 上午10:08
 * Used:
 * file: Color.php
 */

namespace Inhere\Console\Components\Style;

use Inhere\Console\Utils\Helper;

/**
 * Class Style
 * @package Inhere\Console\Components\Style
 * @link https://github.com/ventoviro/windwalker-IO
 *
 * @method string info(string $message)
 * @method string comment(string $message)
 * @method string success(string $message)
 * @method string warning(string $message)
 * @method string danger(string $message)
 * @method string error(string $message)
 */
class Style
{
    /**
     * there are some default style tags
     */
    const NORMAL = 'normal';
    const FAINTLY = 'faintly';
    const BOLD = 'bold';
    const NOTICE = 'notice';
    const PRIMARY = 'primary';
    const SUCCESS = 'success';
    const INFO = 'info';
    const NOTE = 'note';
    const WARNING = 'warning';
    const COMMENT = 'comment';
    const QUESTION = 'question';
    const DANGER = 'danger';
    const ERROR = 'error';

    /**
     * Regex to match tags
     * @var string
     */
    const COLOR_TAG = '/<([a-zA-Z=;]+)>(.*?)<\/\\1>/s';

    /**
     * Regex used for removing color codes
     */
    const STRIP_TAG = '/<[\/]?[a-zA-Z=;]+>/';

    /**
     * @var self
     */
    private static $instance;

    /**
     * Flag to remove color codes from the output
     * @var bool
     */
    protected static $noColor = false;

    /**
     * Array of Color objects
     * @var Color[]
     */
    private $styles = [];

    /**
     * @return Style
     */
    public static function create(): Style
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     * @param  string $fg 前景色(字体颜色)
     * @param  string $bg 背景色
     * @param  array $options 其它选项
     */
    public function __construct($fg = '', $bg = '', array $options = [])
    {
        if ($fg || $bg || $options) {
            $this->add('base', [
                'fg' => $fg,
                'bg' => $bg,
                'options' => $options
            ]);
        }

        $this->loadDefaultStyles();
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed|string
     * @throws \InvalidArgumentException
     */
    public function __call($method, array $args)
    {
        if (isset($args[0]) && $this->hasStyle($method)) {
            return $this->format(sprintf('<%s>%s</%s>', $method, $args[0], $method));
        }

        throw new \InvalidArgumentException("You called method is not exists: $method");
    }

    /**
     * Adds predefined color styles to the Color styles
     * default primary success info warning danger
     */
    protected function loadDefaultStyles()
    {
        $this
            ->add(self::NORMAL, ['fg' => 'normal'])
            // 不明显的 浅灰色的
            ->add(self::FAINTLY, ['fg' => 'normal', 'options' => ['italic']])
            ->add(self::BOLD, ['options' => ['bold']])
            ->add(self::INFO, ['fg' => 'green',])//'options' => ['bold']
            ->add(self::NOTE, ['fg' => 'cyan', 'options' => ['bold']])//'options' => ['bold']
            ->add(self::PRIMARY, ['fg' => 'yellow', 'options' => ['bold']])//
            ->add(self::SUCCESS, ['fg' => 'green', 'options' => ['bold']])
            ->add(self::NOTICE, ['options' => ['bold', 'underscore'],])
            ->add(self::WARNING, ['fg' => 'black', 'bg' => 'yellow',])//'options' => ['bold']
            ->add(self::COMMENT, ['fg' => 'yellow',])//'options' => ['bold']
            ->add(self::QUESTION, ['fg' => 'black', 'bg' => 'cyan'])
            ->add(self::DANGER, ['fg' => 'red',])// 'bg' => 'magenta', 'options' => ['bold']
            ->add(self::ERROR, ['fg' => 'black', 'bg' => 'red'])
            ->add('underline', ['fg' => 'normal', 'options' => ['underscore']])
            ->add('blue', ['fg' => 'blue'])
            ->add('cyan', ['fg' => 'cyan'])
            ->add('magenta', ['fg' => 'magenta'])
            ->add('mga', ['fg' => 'magenta'])
            ->add('red', ['fg' => 'red'])
            ->add('darkGray', ['fg' => 'black', 'extra' => true])
            ->add('yellow', ['fg' => 'yellow']);
    }

    /**
     * Process a string use style
     * @param string $style
     * @param $text
     * @return string
     */
    public function apply(string $style, $text): string
    {
        return $this->format(Helper::wrapTag($text, $style));
    }

    /**
     * Process a string.
     * @param $text
     * @return mixed
     */
    public function render($text)
    {
        return $this->format($text);
    }

    /**
     * @param string $text
     * @return mixed|string
     */
    public function format(string $text)
    {
        if (!$text || false === strpos($text, '<')) {
            return $text;
        }

        // if don't support output color text, clear color tag.
        if (!Helper::supportColor() || self::isNoColor()) {
            return static::stripColor($text);
        }

        if (!preg_match_all(self::COLOR_TAG, $text, $matches)) {
            return $text;
        }

        foreach ((array)$matches[0] as $i => $m) {
            $key = $matches[1][$i];

            if (array_key_exists($key, $this->styles)) {
                $text = $this->replaceColor($text, $key, $matches[2][$i], (string)$this->styles[$key]);

                /** Custom style format @see Color::makeByString() */
            } elseif (strpos($key, '=')) {
                $text = $this->replaceColor($text, $key, $matches[2][$i], (string)Color::makeByString($key));
            }
        }

        return $text;
    }

    /**
     * Replace color tags in a string.
     * @param string $text
     * @param   string $tag The matched tag.
     * @param   string $match The match.
     * @param   string $style The color style to apply.
     * @return  string
     */
    protected function replaceColor($text, $tag, $match, $style): string
    {
        $replace = self::$noColor ? $match : sprintf("\033[%sm%s\033[0m", $style, $match);

        return str_replace("<$tag>$match</$tag>", $replace, $text);
        // return sprintf("\033[%sm%s\033[%sm", implode(';', $setCodes), $text, implode(';', $unsetCodes));
    }

    /**
     * Strip color tags from a string.
     * @param string $string
     * @return mixed
     */
    public static function stripColor(string $string)
    {
        // $text = strip_tags($text);
        return preg_replace(self::STRIP_TAG, '', $string);
    }

    /****************************************************************************
     * Attr Color Style
     ****************************************************************************/

    /**
     * Add a style.
     * @param string $name
     * @param string|Color|array $fg 前景色|Color对象|也可以是style配置数组(@see self::addByArray())
     *                               当它为Color对象或配置数组时，后面两个参数无效
     * @param string $bg 背景色
     * @param array $options 其它选项
     * @param bool $extra
     * @return $this
     */
    public function add(string $name, $fg = '', $bg = '', array $options = [], $extra = false)
    {
        if (\is_array($fg)) {
            return $this->addByArray($name, $fg);
        }

        if (\is_object($fg) && $fg instanceof Color) {
            $this->styles[$name] = $fg;
        } else {
            $this->styles[$name] = Color::make($fg, $bg, $options, $extra);
        }

        return $this;
    }

    /**
     * Add a style by an array config
     * @param string $name
     * @param array $styleConfig 样式设置信息
     * e.g
     * [
     *     'fg' => 'white',
     *     'bg' => 'black',
     *     'extra' => true,
     *     'options' => ['bold', 'underscore']
     * ]
     * @return $this
     */
    public function addByArray(string $name, array $styleConfig)
    {
        $style = [
            'fg' => '',
            'bg' => '',
            'extra' => false,
            'options' => []
        ];

        $config = array_merge($style, $styleConfig);
        list($fg, $bg, $extra, $options) = array_values($config);

        $this->styles[$name] = Color::make($fg, $bg, $options, $extra);

        return $this;
    }

    /**
     * @return array
     */
    public function getStyleNames(): array
    {
        return array_keys($this->styles);
    }

    /**
     * @return array
     */
    public function getNames(): array
    {
        return array_keys($this->styles);
    }

    /**
     * @return array
     */
    public function getStyles(): array
    {
        return $this->styles;
    }

    /**
     * @param $name
     * @return Style|null
     */
    public function getStyle($name)
    {
        if (!isset($this->styles[$name])) {
            return null;
        }

        return $this->styles[$name];
    }

    /**
     * @param $name
     * @return bool
     */
    public function hasStyle($name): bool
    {
        return isset($this->styles[$name]);
    }

    /**
     * Method to get property NoColor
     */
    public static function isNoColor(): bool
    {
        return (bool)self::$noColor;
    }

    /**
     * Method to set property noColor
     * @param $noColor
     */
    public static function setNoColor($noColor = true)
    {
        self::$noColor = (bool)$noColor;
    }
}
