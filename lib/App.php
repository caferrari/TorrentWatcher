<?php

require_once('Show.php');

class App{

    private $shows = array();
    static $configs = array();

    public function __construct($ini){
        $ini = parse_ini_file($ini, true);
        $this->loadConfig($ini);
        $this->setDefaults($ini);
        $this->load($ini);
        $this->showTime();
    }
    
    private function loadConfig(&$ini){
        $configs = $ini['config'];
        foreach ($configs as $config => $value){
            preg_match_all('@{([a-zA-Z0-9_]+)}@', $value, $match, PREG_SET_ORDER);            
            foreach ($match as $mat){
                if (!isset($_SERVER[$mat[1]])) throw new Exception("Server variable '{$mat[1]}' not found!");
                $value = str_replace($mat[0], $_SERVER[$mat[1]], $value);
            }
            App::$configs[$config] = $value;
        }
        App::$configs = (object)App::$configs;
        unset($ini['config']);
        
    }
    
    private function setDefaults(&$ini){
        if (isset($ini['default'])){
            $reflectShow = new ReflectionClass('Show');
            foreach ($ini['default'] as $k => $v)
                $reflectShow->setStaticPropertyValue('default' . ucfirst($k), $v);
            unset($ini['default']);
        }
    }
    
    public function load($ini){
        foreach ($ini as $section => $options){
            if (preg_match('@^show:(.*)@', $section, $mat)){
                $show = new Show($mat[1]);
                $reflectShow = new ReflectionObject($show);
                foreach ($options as $property => $value){
                    $reflectProperty = $reflectShow->getProperty($property);
                    $reflectProperty->setAccessible(true);
                    $reflectProperty->setValue($show, $value);
                }
                $this->shows[] = $show;
            }    
            
        }
    }
    
    public function showTime(){
    
        while (true){
        
            foreach ($this->shows as $show){
            
                if ($show->onReleasePeriod()){
                
                    echo "Checking: {$show->getName()}" . PHP_EOL;
                
                    $show->check();
                    
                }else{
                    echo "Waiting for the {$show->getName()} release period" . PHP_EOL;
                }
            
            }
            print 'Sleep' . PHP_EOL;
            sleep (App::$configs->sleepTime * 60);
        }
    
    }

}
