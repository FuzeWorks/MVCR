<?php
/**
 * FuzeWorks Framework MVCR Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2018 TechFuze
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @author    TechFuze
 * @copyright Copyright (c) 2013 - 2018, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 0.0.1
 *
 * @version Version 1.2.0
 */

use FuzeWorks\Config;
use FuzeWorks\Events;
use FuzeWorks\Factory;
use FuzeWorks\Priority;
use FuzeWorks\Router;

/**
 * Class RouterTest
 * @coversDefaultClass \FuzeWorks\Router
 */
class RouterTest extends MVCRTestAbstract
{

    /**
     * Holds the Router object
     *
     * @var Router
     */
    protected $router;

    /**
     * Holds the Config object
     *
     * @var Config
     */
    protected $config;

    public function setUp()
    {
        // Get required classes
        $this->router = new Router();
        $this->config = Factory::getInstance()->config;

        // Append required routes
        Factory::getInstance()->controllers->addComponentPath('test' . DS . 'controllers');
        Factory::getInstance()->views->addComponentPath('test' . DS . 'views');
    }

    /**
     * @coversNothing
     */
    public function testGetRouterClass()
    {
        $this->assertInstanceOf('FuzeWorks\Router', $this->router);
    }

    /* Route Parsing ------------------------------------------------------ */

    /**
     * @depends testGetRouterClass
     * @covers ::addRoute
     * @covers ::getRoutes
     */
    public function testAddRoutes()
    {
        $routeConfig = function () {
        };
        $this->router->addRoute('testRoute', $routeConfig);
        $this->assertArrayHasKey('testRoute', $this->router->getRoutes());
        $this->assertEquals($routeConfig, $this->router->getRoutes()['testRoute']);
    }

    /**
     * @depends testAddRoutes
     * @covers ::addRoute
     * @covers ::getRoutes
     */
    public function testAddBlankRoute()
    {
        $this->router->addRoute('testBlankRoute');
        $this->assertArrayHasKey('testBlankRoute', $this->router->getRoutes());
        $this->assertEquals(['callable' => [$this->router, 'defaultCallable']], $this->router->getRoutes()['testBlankRoute']);
    }

    /**
     * @depends testAddRoutes
     * @covers ::addRoute
     * @covers ::getRoutes
     */
    public function testAppendRoutes()
    {
        $testRouteFunction = [function () {
        }];
        $testAppendRouteFunction = [function () {
        }];
        $this->router->addRoute('testRoute', $testRouteFunction);
        $this->router->addRoute('testAppendRoute', $testAppendRouteFunction, Priority::LOW);

        // Test if the order is correct
        // First for Priority::NORMAL
        $this->assertSame(['testRoute' => $testRouteFunction], $this->router->getRoutes(Priority::NORMAL));

        // Then for Priority::LOW
        $this->assertSame(['testAppendRoute' => $testAppendRouteFunction], $this->router->getRoutes(Priority::LOW));
    }

    /**
     * @depends testAddRoutes
     * @covers ::addRoute
     * @covers ::getRoutes
     * @covers ::removeRoute
     */
    public function testRemoveRoutes()
    {
        // First add routes
        $this->router->addRoute('testRemoveRoute', function () {
        });
        $this->assertArrayHasKey('testRemoveRoute', $this->router->getRoutes());

        // Then remove
        $this->router->removeRoute('testRemoveRoute');
        $this->assertArrayNotHasKey('testRemoveRoute', $this->router->getRoutes());
    }

    /**
     * @depends testAddRoutes
     * @covers ::init
     * @covers ::addRoute
     */
    public function testParseRouting()
    {
        // Prepare the routes so they can be parsed
        $this->config->routes->set('testParseRouting', function () {
        });
        $this->router->init();

        // Now verify whether the passing has been processed correctly
        $this->assertArrayHasKey('testParseRouting', $this->router->getRoutes());
    }

    /**
     * @depends testParseRouting
     * @covers ::init
     */
    public function testWildcardParsing()
    {
        // Prepare the routes so they can be parsed
        $this->config->routes->set('testWildcardParsing/:any/:num', function () {
        });
        $this->router->init();

        // Now verify whether the route has been skipped
        $this->assertArrayHasKey('testWildcardParsing/[^/]+/[0-9]+', $this->router->getRoutes());
    }

    /**
     * @depends testParseRouting
     * @covers ::init
     */
    public function testBlankRouteParsing()
    {
        // Prepare the routes so they can be parsed
        $this->config->routes->set(0, 'testBlankRouteParsing');
        $this->router->init();

        // Now verify whether the route has been parsed
        $this->assertArrayHasKey('testBlankRouteParsing', $this->router->getRoutes());
    }

    /* defaultCallable() -------------------------------------------------- */

    /**
     * @depends testGetRouterClass
     * @covers ::defaultCallable
     */
    public function testDefaultCallable()
    {
        $matches = [
            'viewName' => 'TestDefaultCallable',
            'viewType' => 'test',
            'viewMethod' => 'someMethod'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
        $this->assertEquals('Verify Output', $this->router->defaultCallable($matches, '.*$'));
        $this->assertInstanceOf('\Application\Controller\TestDefaultCallableController', $this->router->getCurrentController());
        $this->assertInstanceOf('\Application\View\TestDefaultCallableTestView', $this->router->getCurrentView());
    }

    /**
     * @depends testDefaultCallable
     * @covers ::defaultCallable
     */
    public function testDefaultCallableMissingMethod()
    {
        $matches = [
            'viewName' => 'TestDefaultCallable',
            'viewType' => 'test',
            'viewMethod' => 'missing'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
        $this->assertFalse($this->router->defaultCallable($matches, '.*$'));
        $this->assertInstanceOf('\Application\Controller\TestDefaultCallableController', $this->router->getCurrentController());
        $this->assertInstanceOf('\Application\View\TestDefaultCallableTestView', $this->router->getCurrentView());
    }

    /**
     * @depends testDefaultCallable
     * @covers ::defaultCallable
     */
    public function testDefaultCallableMissingView()
    {
        $matches = [
            'viewName' => 'TestDefaultCallableMissingView',
            'viewType' => 'test',
            'viewMethod' => 'missing'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
        $this->assertFalse($this->router->defaultCallable($matches, '.*$'));
        $this->assertInstanceOf('\Application\Controller\TestDefaultCallableMissingViewController', $this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
    }

    /**
     * @depends testDefaultCallable
     * @covers ::defaultCallable
     */
    public function testDefaultCallableMissingController()
    {
        $matches = [
            'viewName' => 'TestDefaultCallableMissingController',
            'viewType' => 'test',
            'viewMethod' => 'missing'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
        $this->assertFalse($this->router->defaultCallable($matches, '.*$'));
        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
    }

    /**
     * @depends testDefaultCallable
     * @covers ::defaultCallable
     * @expectedException \FuzeWorks\Exception\HaltException
     */
    public function testDefaultCallableHaltByView()
    {
        $matches = [
            'viewName' => 'TestDefaultCallableHalt',
            'viewType' => 'test',
            'viewMethod' => 'someMethod'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
        $this->router->defaultCallable($matches, '.*$');
        $this->assertInstanceOf('\Application\Controller\TestDefaultCallableHaltController', $this->router->getCurrentController());
        $this->assertInstanceOf('\Application\View\TestDefaultCallableHaltTestView', $this->router->getCurrentView());
    }

    /**
     * @depends testDefaultCallable
     * @covers ::defaultCallable
     */
    public function testDefaultCallableEmptyName()
    {
        $matches = [
            'viewType' => 'test',
            'viewMethod' => 'someMethod'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
        $this->assertFalse($this->router->defaultCallable($matches, '.*$'));
        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());
    }

    /**
     * @depends testDefaultCallable
     * @covers ::defaultCallable
     * @covers \FuzeWorks\Event\RouterLoadViewAndControllerEvent::init
     * @expectedException \FuzeWorks\Exception\HaltException
     */
    public function testDefaultCallableHaltByEvent()
    {
        $matches = [
            'viewName' => 'TestDefaultCallable',
            'viewType' => 'test',
            'viewMethod' => 'missing'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());

        // Create listener
        Events::addListener(function($event){
            $this->assertInstanceOf('\FuzeWorks\Event\RouterLoadViewAndControllerEvent', $event);
            $this->assertEquals('TestDefaultCallable', $event->viewName);
            $this->assertEquals('test', $event->viewType);
            $this->assertEquals([3=>['missing']], $event->viewMethods);
            $event->setCancelled(true);
        }, 'routerLoadViewAndControllerEvent');

        $this->router->defaultCallable($matches, '.*$');
    }

    /**
     * @depends testDefaultCallableHaltByEvent
     * @covers ::defaultCallable
     * @covers \FuzeWorks\Event\RouterLoadViewAndControllerEvent::overrideController
     */
    public function testDefaultCallableReplaceController()
    {
        $matches = [
            'viewName' => 'TestDefaultCallable',
            'viewType' => 'test',
            'viewMethod' => 'missing'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());

        $mockController = $this->getMockBuilder('\FuzeWorks\Controller')->getMock();

        // Create listener
        Events::addListener(function($event, $mockController){
            $event->overrideController($mockController);
        }, 'routerLoadViewAndControllerEvent', Priority::NORMAL, $mockController);

        $this->router->defaultCallable($matches, '.*$');
        $this->assertEquals($mockController, $this->router->getCurrentController());
    }

    /**
     * @depends testDefaultCallableReplaceController
     * @covers ::defaultCallable
     * @covers \FuzeWorks\Event\RouterLoadViewAndControllerEvent::addMethod
     */
    public function testDefaultCallableAddMethod()
    {
        $matches = [
            'viewName' => 'TestDefaultCallableChangeMethod',
            'viewType' => 'test',
            'viewMethod' => 'index'
        ];

        $this->assertNull($this->router->getCurrentController());
        $this->assertNull($this->router->getCurrentView());

        $mockController = $this->getMockBuilder('\FuzeWorks\Controller')->getMock();
        // Create listener
        Events::addListener(function($event, $mockController){
            $event->overrideController($mockController);
            $event->addMethod('altered', Priority::HIGH);
        }, 'routerLoadViewAndControllerEvent', Priority::NORMAL, $mockController);

        $this->assertEquals('Altered!', $this->router->defaultCallable($matches, '.*$'));
    }

    /* route() ------------------------------------------------------------ */

    /**
     * @depends testDefaultCallable
     * @covers ::route
     * @covers ::loadCallable
     */
    public function testRoute()
    {
        // Add route first
        $this->router->addRoute('(?P<viewName>.*?)(|\/(?P<viewMethod>.*?)(|\/(?P<viewParameters>.*?)))(|\.(?P<viewType>.*?))');

        // Create mock view and controller
        $mockController = $this->getMockBuilder('\FuzeWorks\Controller')->getMock();
        $mockView = $this->getMockBuilder('\FuzeWorks\View')->setMethods(['testMethod'])->getMock();
        class_alias(get_class($mockController), '\Application\Controller\TestRouteController');
        class_alias(get_class($mockView), '\Application\View\TestRouteTestView');

        // Attempt to route
        $this->assertnull($this->router->route('testRoute/testMethod/testParameters.test'));
        $this->assertInstanceOf('\Application\Controller\TestRouteController', $this->router->getCurrentController());
        $this->assertInstanceOf('\Application\View\TestRouteTestView', $this->router->getCurrentView());
    }

    /**
     * @depends testRoute
     * @covers ::route
     * @expectedException \FuzeWorks\Exception\NotFoundException
     */
    public function testRouteNotFound()
    {
        $this->router->route('NotFound');
    }

    /**
     * @depends testRouteNotFound
     * @covers ::route
     * @expectedException \FuzeWorks\Exception\NotFoundException
     */
    public function testRouteNotMatched()
    {
        $this->router->addRoute('NotMatched');
        $this->router->route('NotFound');
    }

    /**
     * @depends testRoute
     * @covers ::route
     * @covers ::loadCallable
     */
    public function testRouteStaticRewrite()
    {
        // Add route first
        $this->router->addRoute(
            'staticRewrite',
            [
                'viewName' => 'TestStaticRewrite',
                'viewMethod' => 'someMethod',
                'viewType' => 'static'
            ]
        );

        // Create mock view and controller
        $mockController = $this->getMockBuilder('\FuzeWorks\Controller')->getMock();
        $mockView = $this->getMockBuilder('\FuzeWorks\View')->setMethods(['someMethod'])->getMock();
        class_alias(get_class($mockController), '\Application\Controller\TestStaticRewriteController');
        class_alias(get_class($mockView), '\Application\View\TestStaticRewriteStaticView');

        // Attempt to route
        $this->assertnull($this->router->route('staticRewrite'));
        $this->assertInstanceOf('\Application\Controller\TestStaticRewriteController', $this->router->getCurrentController());
        $this->assertInstanceOf('\Application\View\TestStaticRewriteStaticView', $this->router->getCurrentView());
    }

    /**
     * @depends testRouteStaticRewrite
     * @covers ::route
     * @covers ::loadCallable
     */
    public function testRouteDynamicRewrite()
    {
        // Add route first
        $this->router->addRoute(
            'dynamicRewrite',
            function($matches){
                $this->assertEquals([0=>'dynamicRewrite'], $matches);
                return [
                    'viewName' => 'TestDynamicRewrite',
                    'viewMethod' => 'someMethod',
                    'viewType' => 'static'
                ];
            }
        );

        // Create mock view and controller
        $mockController = $this->getMockBuilder('\FuzeWorks\Controller')->getMock();
        $mockView = $this->getMockBuilder('\FuzeWorks\View')->setMethods(['someMethod'])->getMock();
        class_alias(get_class($mockController), '\Application\Controller\TestDynamicRewriteController');
        class_alias(get_class($mockView), '\Application\View\TestDynamicRewriteStaticView');

        // Attempt to route
        $this->assertNull($this->router->route('dynamicRewrite'));
        $this->assertInstanceOf('\Application\Controller\TestDynamicRewriteController', $this->router->getCurrentController());
        $this->assertInstanceOf('\Application\View\TestDynamicRewriteStaticView', $this->router->getCurrentView());
    }

    /**
     * @depends testRouteStaticRewrite
     * @covers ::route
     * @covers ::loadCallable
     */
    public function testRouteCustomCallable()
    {
        // Create custom callable
        $callable = function(array $matches, string $route){
            $this->assertEquals('customCallable', $route);
            $this->assertEquals([0=>'customCallable'], $matches);
        };

        // Add route
        $this->router->addRoute(
            'customCallable',
            [
                'callable' => $callable
            ]
        );

        $this->assertNull($this->router->route('customCallable'));
    }

    /**
     * @depends testRouteStaticRewrite
     * @covers ::route
     * @covers ::loadCallable
     * @expectedException \FuzeWorks\Exception\NotFoundException
     */
    public function testRouteUnsatisfiedCallable()
    {
        // Create custom callable
        $callable = function(array $matches, string $route){
            $this->assertEquals('unsatisfiedCallable', $route);
            $this->assertEquals([0=>'unsatisfiedCallable'], $matches);
            return false;
        };

        // Add route
        $this->router->addRoute(
            'unsatisfiedCallable',
            [
                'callable' => $callable
            ]
        );

        $this->assertNull($this->router->route('unsatisfiedCallable'));
    }

}
