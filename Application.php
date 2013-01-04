<?php

	/**
	 *
	 * Container
	 *
	 * http://github.com/mkis78/Container/
	 *
	 * An elementary system for the orderly management of application
	 *
	 * BSD Licensed.
	 *
	 * Copyright (c) 2013, Marco "mkis" Pennisi
	 * All rights reserved.
	 *
	 * Redistribution and use in source and binary forms, with or without
	 * modification, are permitted provided that the following conditions are met:
	 *
	 * * Redistributions of source code must retain the above copyright notice, this
	 *   list of conditions and the following disclaimer.
	 *
	 * * Redistributions in binary form must reproduce the above copyright notice,
	 *   this list of conditions and the following disclaimer in the documentation
	 *   and/or other materials provided with the distribution.
	 *
	 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
	 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
	 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE
	 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
	 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
	 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
	 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
	 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
	 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	 *
	 */

    namespace Container;

    class Application
    {
    	/*
    	 * Container of all elements of application
    	 */
        protected $values    = array();
        /*
         * Container of all timers
         */
        protected $timers    = array();
        /*
         * Containers of all callbacks
         */
        protected $callbacks = array();

        /*
         * The Container constructor
         * take an associative array for bootstrap configuration (may be empty)
         * ex: array("some" => "value") -> $app->some === value
         * if a value begins with '_' char, the value is protected and readonly 
         * if debug parameter is true (default) error messages are visible
         * are defined as the default execution environment (app_mode)  and timer start (['start_timer']['global'])
         */
        public function __construct(array $values = array(), $debug = true)
        {
            $this->timers['start_timer']['global'] = (float) array_sum(explode(' ', microtime()));
            
            $this->values = $values;
            
            $c = (php_sapi_name() == 'cli' && empty($_SERVER['REMOTE_ADDR']));
            $this->values['app_mode'] = $c ? 'cli' : 'web';
            
            if ($debug) {
                ini_set("display_errors", 1);
                ini_set("log_errors", 1);
                ini_set("php_error.force_disabled", 0);
                ini_set("php_error.autorun", 1);
                error_reporting(E_ALL);
            } else {
                ini_set("display_errors", 0);
                ini_set("log_errors", 1);
                ini_set("php_error.force_disabled", 1);
                ini_set("php_error.autorun", 0);
                error_reporting(E_ERROR);
            }
        }
        
        /*
         * Override of __get method
         * retrive target $key value in the $values store or throws exceptions
         */
        public function __get($key)
        {
            if (!isset($this->values[$key])) {
                throw new \InvalidArgumentException("Key value '".$key."' not found.");
            }
            return $this->values[$key];
        }
        
        /*
         * Override of __set method
         * store values (values, closures, instances) in the $values store
         * if a value begins with '_' char, the value is protected and readonly 
         */
        public function __set($key, $value)
        {
            if (isset($this->$key) && substr($key, 0) == "_") {
                throw new \Exception("This Container value [".$key."] is protected");
            } else {
                if (!isset($this->values[$key])) {
                    $this->values[$key] = (object) array();
                }
                $this->values[$key] = $value;
            }
        }
        
        /*
         * Override of __isset method
         * check if target $key value exists in the store
         */
        public function __isset($key)
        {
            return array_key_exists($key, $this->values);
        }
        
        /*
         * Override of __call method
         * if they are defined callbacks are performed here
         * each call returns the Container object, allowing you to chain methods (if the call have no returns)
         * ex: $app->some()->funny()->method();
         */
        public function __call($c, $args)
        {
            if (!isset($this->values[$c])) {
                throw new \InvalidArgumentException("Method ".$c." does not exists.");
            }
            
            if (isset($this->callbacks['before']['all']) && is_callable($this->callbacks['before']['all'])) {
            	call_user_func($this->callbacks['before']['all']);
            }
            if (isset($this->callbacks['before'][$c]) && is_callable($this->callbacks['before'][$c])) {
            	call_user_func($this->callbacks['before'][$c]);
            }
            
            $r = call_user_func_array($this->values[$c], $args);
            
            if (isset($this->callbacks['after'][$c]) && is_callable($this->callbacks['after'][$c])) {
            	call_user_func($this->callbacks['after'][$c]);
            }
            if (isset($this->callbacks['after']['all']) && is_callable($this->callbacks['after']['all'])) {
            	call_user_func($this->callbacks['after']['all']);
            }
            
            if (is_null($r)) {
                return $this;
            }
            return $r;
        }
        
        /*
         * Override of __invoke method
         * this is a simple debug method like var_dump
         * the disply template is relative to application environment (web or cli)
         * ex: $app(some);
         */
        public function __invoke($value)
        {
            $format = "<pre>%s<hr>---<br/>%s<br/>---</pre>";
            if ($this->values['app_mode'] == 'cli') {
                $format = "\n%s\n\n---\n%s\n---\n";
            }
            vprintf($format, array("Inspect", print_r($value, true)));
        }
        
        /*
         * Override of __toString method
         * return the class name
         */
        public function __toString()
        {
            return __CLASS__;
        }
        
        /*
         * Override of __sleep method
         * return the serialization of the instance, without error for Closures
         */
        public function __sleep()
        {
        	$func = function(){
        	};
        	$serializable = array();
        	foreach ($this->values as $key => $value) {
        		if ($value instanceof $func) {
        			continue;
        		}
        		$serializable[] = $key;
        	}
        	return $serializable;
        }
        
        /*
         * Insert a timer with given $label in the timers array
         */
        public function startTimer($label = 'local')
        {
            $this->timers['start_timer'][$label] = (float) array_sum(explode(' ', microtime()));
        }
        
        /*
         * Retrive a timer with the target $label from the timers store (in seconds) 
         */
        public function getTimer($label = 'global')
        {
            $this->timers['end_timer'][$label] = (float) array_sum(explode(' ', microtime()));
            return sprintf("%.4f", ($this->timers['end_timer'][$label] - $this->timers['start_timer'][$label]));
        }
        
        /*
         * Set to zero target $label timer
         */
        public function resetTimer($label = 'global')
        {
            $this->timers['start_timer'][$label] = 0;
            $this->timers['end_timer'][$label]   = 0;
        }

        /*
         * This method is like import in java
         * it retrive all the .php files with the given $pattern
         * and includes them in the application script
         * ex: $app->import(__DIR__."/lib/*"); // will include all php in the lib directory
         */
        public function import($pattern)
        {
            $imports = glob($pattern.".php", GLOB_ERR);

            if (!empty($imports)) {
                foreach ($imports as $import) {
                    include $import;
                }
            } else {
                throw new \InvalidArgumentException("Import patter return no elements: ".$pattern);
            }
        }
        
        /*
         * Set a callback function before target $action (by default all actions)
         * ex: 
         * $app->callBefore('test', function(){ echo "Im called ever first"; });
         * $app->test = function(){ echo "stupid test..."; };
         * 
         * $app->test(); // will print 
         * 					Im called ever first
         * 					stupid test...
         */
        public function callBefore($action = 'all', $callback)
        {
        	$this->callbacks['before'][$action] = $callback;
        }
        
        /*
         * Same as callBefore, but after
         */
        public function callAfter($action = 'all', $callback)
        {
        	$this->callbacks['after'][$action] = $callback;
        }
    
        /*
         * Simple method that adds a function to the end of the entire application
         * ex:
         * $app->callWhenShutdown(function() use($app){
		    	echo "terminated in ".$app->getTimer()." sec.";
		    });
		    // print terminated in 0.0001 sec. at the end of application
         */
    	public function callWhenShutdown($callback)
    	{
    		if (is_callable($callback)) {
    			register_shutdown_function($callback);
    		} else {
    			throw new \InvalidArgumentException("Invalid callback shutdown function.");
    		}
    	}
    }