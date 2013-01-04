<?php

    require __DIR__."/Application.php";

    // create a new application
    $myapp = new \Container\Application(array(
    	"test1" => 5,
    	"test2" => "Hello",
    	"test3" => array(
    				'date'      => date('d-m-Y')
    				),
    	"test4" => function(){
    					return "yeah!";
    				}		
   	));
   	
   	// add a function that show settings
   	$myapp->test5 = function() use($myapp){
   		$myapp($myapp);
   	};
    
   	$myapp->test5();
   	
   	// in linux shell will print
   	/*
   	 * Inspect
	   	
	   	---
	   	Container\Application Object
	   	(
	   			[values:protected] => Array
	   			(
	   					[test1] => 5
	   					[test2] => Hello
	   					[test3] => Array
	   					(
	   							[thisClass] =>
	   							[date] => 04-01-2013
	   					)
	   	
	   					[test4] => Closure Object
	   					(
	   					)
	   	
	   					[app_mode] => cli
	   					[test5] => Closure Object
	   					(
	   							[static] => Array
	   							(
	   									[myapp] => Container\Application Object
	   									*RECURSION*
	   							)
	   	
	   					)
	   	
	   			)
	   	
	   			[timers:protected] => Array
	   			(
	   					[start_timer] => Array
	   					(
	   							[global] => 1357286743.0784
	   					)
	   	
	   			)
	   	
	   			[callbacks:protected] => Array
	   			(
	   			)
	   	
	   	)
	   	
	   	---
   	*/
   	
   	//-----------------------------------------------------------------------------------------------------------------------------------
   	
   	echo $myapp->test3['date']; 
   	//04-01-2013
   	
   	echo "This is number ".$myapp->test1." from ".$myapp." and i say ".$myapp->test4();
   	// This is number 5 from Container\Application and i say yeah!
    
   	//-----------------------------------------------------------------------------------------------------------------------------------
   	
	$myapp->func1 = function($x) use($myapp){
		$myapp->number1 = (int)$x;
	};
	
	$myapp->func2 = function($y) use($myapp){
		$myapp->number2 = (int)$y;
	};
	
	$myapp->add = function() use($myapp){
		echo "result is ".($myapp->number1 + $myapp->number2);
	};
	
	$myapp->func1(4)->func2(3)->add();
	// result is 7
	
	//-----------------------------------------------------------------------------------------------------------------------------------
	
	$myapp->callBefore('testCall', function(){
		echo "<h2>Im called first</h2>";
	});
	
	$myapp->callAfter('testCall', function(){
		echo "<h2>Im called last</h2>";
	});
	
	$myapp->testCall = function() use($myapp){
		echo "<p>is a testCall on ".$myapp."</p>";
	};
	
	$myapp->testCall();
	/*
	 * <h2>Im called first</h2>
	 * <p>is a testCall on Container\Application</p>
	 * <h2>Im called last</h2>
	 */
	
	//-----------------------------------------------------------------------------------------------------------------------------------
	
	$myapp->callWhenShutdown(function() use($myapp){
		echo "<hr><small>terminated in ".$myapp->getTimer()." sec.</small>";
	});
    /*
     * <hr><small>terminated in 0.0005 sec.</small> at the end of application
     */
	
	