<?php namespace Rollbar\Handlers;

use \Rollbar\Rollbar;
use \Rollbar\RollbarLogger;
use \Rollbar\BaseRollbarTest;

class ErrorHandlerTest extends BaseRollbarTest
{
    public function __construct()
    {
        self::$simpleConfig['access_token'] = $this->getTestAccessToken();
        self::$simpleConfig['included_errno'] = E_ALL;
        self::$simpleConfig['environment'] = 'test';
        
        parent::__construct();
    }

    private static $simpleConfig = array();
    
    public function testPreviousErrorHandler()
    {
        $testCase = $this;
        
        set_error_handler(function () use ($testCase) {
            
            $testCase->assertTrue(true, "Previous error handler invoked.");
            
            set_error_handler(null);
        });
        
        Rollbar::init(self::$simpleConfig);
        
        @trigger_error(E_USER_ERROR);
    }
    
    public function testRegister()
    {
        $handler = $this->getMockBuilder('Rollbar\\Handlers\\ErrorHandler')
                        ->setConstructorArgs(array(new RollbarLogger(self::$simpleConfig)))
                        ->setMethods(array('handle'))
                        ->getMock();
                        
        $handler->expects($this->once())
                ->method('handle');
        
        $handler->register();
        
        trigger_error(E_USER_ERROR);
    }
    
    public function testHandle()
    {
        $logger = $this->getMockBuilder('Rollbar\\RollbarLogger')
                        ->setConstructorArgs(array(self::$simpleConfig))
                        ->setMethods(array('log'))
                        ->getMock();
        
        $logger->expects($this->once())
                ->method('log');
        
        /**
         * Disable PHPUnit's error handler as it would get triggered as the
         * previously set error handler. No need for that here.
         */
        $phpunitHandler = set_error_handler(null);
        
        $handler = new ErrorHandler($logger);
        $handler->register();
        
        $handler->handle(E_USER_ERROR, "", "", "");
        
        /**
         * Clean up the error handler set up for this test.
         */
        set_error_handler($phpunitHandler);
    }
}