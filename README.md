Yii widget for jQuery plugin IntroJs
===========================

## Description
Yii([?](yiiframework.com)) widget implementation of jQuery plugin [intro.js](http://usablica.github.io/intro.js/example/hello-world/index.html)

## Setup
Add this project as a git submodule in extensions folder 'yii-intro-js'

## Usage
###Basic usage:
```php
$this->widget('application.extensions.yii-intro-js.IntroJs', array(
    'options' => array(
        'steps' => array(
            array('id'=>'introID1','intro'=>'Hello World!','position'=>'right'),
            array('id'=>'IntroID2','intro'=>'Yii widget powered By Mohammad Moein Hosseini Manesh'),
            array('id'=>'introID3','intro'=>'Step 3'),
            array('id'=>'introID4','intro'=>'Final step'),
        )
    )
));
```

###With separate trigger element:
```php
$introJs = $this->createWidget('application.extensions.yii-intro-js.IntroJs', array(
    'options' => array(
        'delay' => 2000,
        'start' => false,
        'steps' => array(
            array('id' => 'introID1', 'intro' => 'Hello World!', 'position' => 'right'),
            array('id' => 'IntroID2', 'intro' => 'Yii widget powered By Mohammad Moein Hosseini Manesh'),
            array('id' => 'introID3', 'intro' => 'Step 3'),
            array('id' => 'introID4', 'intro' => 'Final step'),
        )))
);
$introId = $introJs->run();

echo $introJs->renderTriggerElement($introId, 'a', array(), 'Start intro');
```

Go to [IntroJs.php](IntroJs.php) to check full features and code of Yii widget.
Go to [jQuery plugin intro.js](https://github.com/usablica/intro.js#api) to check plugin's features.

## References and many thanks to
[jQuery plugin intro.js](https://github.com/usablica/intro.js)