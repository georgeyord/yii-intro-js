<?php

/*
 * author  : George Yord
 * website : georgeyord.github.io
 */

class IntroJs extends CWidget {

    CONST VERSION = '0.6.0';
    CONST LOGPATH = 'app.widgets.IntroJs';

    /**
     * @var array of plugin events and the params it takes.
     * {plugin} will be replaced by the plugin id
     */
    static $events = array(
        'oncomplete' => '{plugin}',
        'onexit' => '{plugin}',
        'onbeforechange' => 'targetElement, {plugin}',
        'onchange' => 'targetElement, {plugin}',
    );

    /**
     * @var bool wether to load files from cdn or not
     */
    public $loadFiles = true;

    /**
     * @var array of options to initialize plugin options (value=>display).
     * Please refer to {@link https://github.com/usablica/intro.js#options}
     * to read the acceptable key/value pairs
     * In addition, the following options are also supported:
     * <ul>
     * <li>delay: int, add a delay when plugin starts. Defaults to 0.</li>
     * <li>events: array  of event/function pair values
     * Please refer to {@link https://github.com/usablica/intro.js#introjsoncompleteprovidedcallback}
     * to read the acceptable events
     * </li>
     * </ul>
     */
    public $options;

    /**
     * Register script files
     */
    public function init() {
        Yii::app()->clientScript->registerCoreScript('jquery');

        // If <loadFiles> is false, then required js/css files must be loaded through a different process (example: custom compressor)
        if ($this->loadFiles) {
            Yii::app()->clientScript->registerCssFile('//cdn.jsdelivr.net/intro.js/' . self::VERSION . '/introjs.min.css');
            Yii::app()->clientScript->registerScriptFile('//cdn.jsdelivr.net/intro.js/' . self::VERSION . '/intro.min.js', CClientScript::POS_END);
            // ONLY FOR DEBUG
            // Yii::app()->clientScript->registerCssFile('https://rawgithub.com/usablica/intro.js/master/introjs.css');
            // Yii::app()->clientScript->registerScriptFile('https://rawgithub.com/usablica/intro.js/master/intro.js', CClientScript::POS_END);
        }
    }

    /**
     * Widget's run method
     * @return string the id of the plugin
     */
    public function run() {
        if (!is_array($this->options))
            return false;

        // Check steps
        foreach (self::getArrayValue('steps', $this->options, array()) as $key => $step) {
            if (!isset($step['element']) || !isset($step['intro'])) {
                Yii::log('Element and intro attributes are required for every step', CLogger::LEVEL_WARNING, self::LOGPATH);
                return false;
            }
            // Add label if needed, notice: default css does not suppoert styles for <header> use custom css
            if ($label = self::getArrayValue('label', $step, false))
                $this->options['steps'][$key]['intro'] = CHtml::tag('header', array(), $label) . $step['intro'];
        }

        // Init array with script directives/commands
        $directives = array();

        // Init id of plugin instance
        $id = self::popArrayValue('id', $this->options, uniqid(__CLASS__));


        // Initialize default options excluding what plugin does not recognize
        $options = array_diff_key($this->options, array_flip(array('delay', 'events', 'id')));

        // Directive to go to next page if needed
        if ($nextPage = self::popArrayValue('nextPage', $this->options, false)) {
            if (!self::getArrayValue('doneLabel', $options, false))
                $options['doneLabel'] = 'Next page';

            if (!isset($this->options['events']))
                $this->options['events'] = array();
            if (!isset($this->options['events']['oncomplete']))
                $this->options['events']['oncomplete'] = '';
            $this->options['events']['oncomplete'] = $this->options['events']['oncomplete'] . "window.location.href = '$nextPage';";
        }

        // Set default options
        self::defaultArrayValue('exitOnEsc', true, $options);
        self::defaultArrayValue('exitOnOverlayClick', false, $options);
        //self::defaultArrayValue('showStepNumbers', false, $options);
        self::defaultArrayValue('showBullets', true, $options);
        self::defaultArrayValue('doneLabel', 'Complete tour', $options);
        self::defaultArrayValue('skipLabel', 'Skip', $options);

        // Directive to initialize plugin
        array_push($directives, sprintf('var %s = introJs().setOptions(%s)', $id, CJavaScript::encode($options)));

        // Directive to start plugin
        if (self::popArrayValue('start', $this->options, true)) {
            $tmpDirective = sprintf('%s.start()', $id);

            // Set delay if needed
            if ($delay = self::popArrayValue('delay', $this->options, false))
                $tmpDirective = sprintf('setTimeout(function () { %s }, %d);', $tmpDirective, (int) $delay);
            array_push($directives, $tmpDirective);
            unset($tmpDirective);
        }

        foreach (self::getArrayValue('steps', $this->options, array()) as $step) {
            // Directives to implement not accessible feature
            if (!self::popArrayValue('accessible', $step, true)) {
                $event = 'onchange';
                if (!isset($this->options['events']))
                    $this->options['events'] = array();
                if (!isset($this->options['events'][$event]))
                    $this->options['events'][$event] = '';
                if (!isset($this->options['events']['oncomplete']))
                    $this->options['events']['oncomplete'] = '';
                if (!isset($this->options['events']['onexit']))
                    $this->options['events']['onexit'] = '';

                if (!isset($resetAccessible)) {
                    $resetAccessible = '
                        $(".intro-js-not-accessible").removeClass("intro-js-not-accessible").css("pointer-events", "inherit");' . PHP_EOL;
                    $this->options['events'][$event] = $resetAccessible . $this->options['events'][$event];
                    $this->options['events']['oncomplete'] = $resetAccessible . $this->options['events']['oncomplete'];
                    $this->options['events']['onexit'] = $resetAccessible . $this->options['events']['onexit'];
                }

                $this->options['events'][$event] = $this->options['events'][$event] . sprintf('var element = $(targetElement).filter("%s");if(element.length>0){element.addClass("intro-js-not-accessible").css("pointer-events", "none");}' . PHP_EOL, $step['element']);
            }
            // Directives for event callbacks specific to a step
            foreach (array('onbeforechange', 'onchange') as $event) {
                if (isset($step[$event])) {
                    if (!isset($this->options['events']))
                        $this->options['events'] = array();
                    if (!isset($this->options['events'][$event]))
                        $this->options['events'][$event] = '';
                    $this->options['events'][$event] = sprintf('if($(targetElement).filter("%s").length>0){%s}' . PHP_EOL, $step['element'], $step[$event]) . $this->options['events'][$event];
                }
            }
        }

        // Directives for event callbacks
        foreach (self::getArrayValue('events', $this->options, array()) as $event => $fn) {
            if (isset(self::$events[$event]))
                array_push($directives, sprintf('%s.%s(function(%s) {%s})', $id, $event, (self::$events[$event] ? str_replace('{plugin}', $id, self::$events[$event]) : ''), $fn));
        }

        $script = new CJavaScriptExpression(implode(';' . PHP_EOL, $directives));
        Yii::app()->clientScript->registerScript(uniqid(__CLASS__), $script, CClientScript::POS_END);

        return $id;
    }

    /**
     * Renders the element that triggers the start of the plugin
     */
    public function renderTriggerElement($pluginId, $tag = 'a', $tagOptions = array(), $tagContent = false) {
        $tagOptions['onclick'] = "javascript:$pluginId.refresh().start();return false;";

        if ($tag == 'htmlButton')
            $tag = $tagOptions['type'] = 'button';

        if (!$tagContent && isset($tagOptions['label']))
            $tagContent = $tagOptions['label'];

        return CHtml::tag($tag, $tagOptions, $tagContent);
    }

    /**
     * HELPER FUNCTIONS FOR ARRAYS
     * ---------------------------
     */

    /**
     * HELPER FUNCTION
     * Returns a specific value from the given array (or the default value if not set).
     * @param string $key the item key.
     * @param array $array the array to get from.
     * @param mixed $defaultValue the default value.
     * @return mixed the value.
     */
    public static function getArrayValue($key, array $array, $defaultValue = null) {
        return isset($array[$key]) ? $array[$key] : $defaultValue;
    }

    /**
     * HELPER FUNCTION
     * Removes and returns a specific value from the given array (or the default value if not set).
     * @param string $key the item key.
     * @param array $array the array to pop the item from.
     * @param mixed $defaultValue the default value.
     * @return mixed the value.
     */
    public static function popArrayValue($key, array &$array, $defaultValue = null) {
        $value = self::getArrayValue($key, $array, $defaultValue);
        unset($array[$key]);
        return $value;
    }

    /**
     * HELPER FUNCTION
     * Sets the default value for a specific key in the given array.
     * @param string $key the item key.
     * @param mixed $value the default value.
     * @param array $array the array.
     */
    public static function defaultArrayValue($key, $value, array &$array) {
        if (!isset($array[$key]) && $value !== null) {
            $array[$key] = $value;
        }
    }

}

?>
