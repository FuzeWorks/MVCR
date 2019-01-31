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
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

use FuzeWorks\Controller;
use FuzeWorks\Controllers;
use FuzeWorks\Event\ControllerGetEvent;
use FuzeWorks\Events;
use FuzeWorks\Priority;

/**
 * Class ControllersTest
 * @coversDefaultClass \FuzeWorks\Controllers
 */
class ControllersTest extends MVCRTestAbstract
{

    /**
     * @var Controllers
     */
    protected $controllers;

    public function setUp()
    {
        $this->controllers = new Controllers();
        $this->controllers->addComponentPath('test'.DS.'controllers');
    }

    /**
     * @covers ::get
     * @covers ::loadController
     */
    public function testGetControllerFromClass()
    {
        // Create mock controller
        $mockController = $this->getMockBuilder(Controller::class)->getMock();
        $mockControllerClass = get_class($mockController);
        class_alias($mockControllerClass, $mockControllerClass . 'Controller');

        // Try and fetch this controller from the Controllers class
        $this->assertInstanceOf($mockControllerClass, $this->controllers->get($mockControllerClass, [], '\\'));
    }

    /**
     * @depends testGetControllerFromClass
     * @covers ::get
     * @covers ::loadController
     * @expectedException \FuzeWorks\Exception\ControllerException
     */
    public function testGetControllerFromClassInvalidInstance()
    {
        // Create invalid mock
        $mockFakeController = $this->getMockBuilder(stdClass::class)->getMock();
        $mockFakeControllerClass = get_class($mockFakeController);
        class_alias($mockFakeControllerClass, $mockFakeControllerClass . 'Controller');

        // Try and fetch
        $this->controllers->get($mockFakeControllerClass, [], '\\');
    }

    /**
     * @depends testGetControllerFromClass
     * @covers ::get
     * @covers ::loadController
     */
    public function testGetControllerFromClassDefaultNamespace()
    {
        // Create mock controller
        $mockController = $this->getMockBuilder(Controller::class)->getMock();
        $mockControllerClass = get_class($mockController);
        class_alias($mockControllerClass, '\Application\Controller\DefaultNamespaceController');

        // Try and fetch
        $this->assertInstanceOf('\Application\Controller\DefaultNamespaceController', $this->controllers->get('DefaultNamespace'));
    }

    /**
     * @depends testGetControllerFromClass
     * @covers ::get
     * @covers ::loadController
     * @todo Implement. Mock constructor arguments doesn't work yet
     */
    public function testGetControllerWithArguments()
    {
        // Can't be tested right now
        $this->assertTrue(true);
    }

    /**
     * @covers ::get
     * @expectedException \FuzeWorks\Exception\ControllerException
     */
    public function testGetControllerInvalidName()
    {
        $this->controllers->get('', [], '\\');
    }

    /**
     * @depends testGetControllerFromClass
     * @covers ::get
     * @covers ::loadController
     */
    public function testGetControllerFromFile()
    {
        $this->assertInstanceOf('\Application\Controller\TestGetControllerController', $this->controllers->get('TestGetController'));
    }

    /**
     * @depends testGetControllerFromFile
     * @covers ::get
     * @covers ::loadController
     * @expectedException \FuzeWorks\Exception\ControllerException
     */
    public function testGetControllerFromFileInvalidInstance()
    {
        $this->controllers->get('ControllerInvalidInstance');
    }

    /**
     * @depends testGetControllerFromFile
     * @covers ::get
     * @covers ::loadController
     */
    public function testDifferentComponentPathPriority()
    {
        // Add the directories for this test
        $this->controllers->addComponentPath('test'.DS.'controllers'.DS.'TestDifferentComponentPathPriority'.DS.'Lowest', Priority::LOWEST);
        $this->controllers->addComponentPath('test'.DS.'controllers'.DS.'TestDifferentComponentPathPriority'.DS.'Highest', Priority::HIGHEST);

        // Load the controller and assert it is the correct type
        $controller = $this->controllers->get('TestDifferentComponentPathPriority');
        $this->assertInstanceOf('\Application\Controller\TestDifferentComponentPathPriorityController', $controller);
        $this->assertEquals('highest', $controller->type);

        // Clean up the test
        $this->controllers->setDirectories([]);
    }

    /**
     * @depends testGetControllerFromFile
     * @covers ::get
     * @covers ::loadController
     */
    public function testGetSubdirectory()
    {
        $this->assertInstanceOf('\Application\Controller\TestGetSubdirectoryController', $this->controllers->get('TestGetSubdirectory'));
    }

    /**
     * @depends testGetControllerFromFile
     * @covers ::get
     * @covers ::loadController
     * @expectedException \FuzeWorks\Exception\NotFoundException
     */
    public function testControllerNotFound()
    {
        $this->controllers->get('NotFound');
    }

    /**
     * @depends testGetControllerFromClass
     * @covers ::get
     * @covers \FuzeWorks\Event\ControllerGetEvent::init
     * @expectedException \FuzeWorks\Exception\ControllerException
     */
    public function testControllerGetEvent()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ControllerGetEvent $event */
            $this->assertInstanceOf('\FuzeWorks\Event\ControllerGetEvent', $event);
            $this->assertEquals('SomeControllerName', $event->controllerName);
            $this->assertEquals([3 => ['some_path']], $event->controllerPaths);
            $this->assertEquals('SomeNamespace', $event->namespace);
            $this->assertEquals(['Some Argument'], $event->arguments);
            $event->setCancelled(true);
        }, 'controllerGetEvent', Priority::NORMAL);

        $this->controllers->get('SomeControllerName', ['some_path'], 'SomeNamespace', 'Some Argument');
    }

    /**
     * @depends testControllerGetEvent
     * @covers ::get
     * @expectedException \FuzeWorks\Exception\ControllerException
     */
    public function testCancelGetController()
    {
        // Register listener
        Events::addListener(function($event){
            $event->setCancelled(true);
        }, 'controllerGetEvent', Priority::NORMAL);

        $this->controllers->get('SomeController', [], '\\');
    }

    /**
     * @depends testControllerGetEvent
     * @covers ::get
     * @covers ::loadController
     */
    public function testControllerGetEventIntervene()
    {
        // Register listener
        Events::addListener(function($event){
            /** @var ControllerGetEvent $event */
            $event->controllerName = 'TestControllerGetEventIntervene';
            $event->namespace = '\Some\Other\\';
        }, 'controllerGetEvent', Priority::NORMAL);

        $this->assertInstanceOf('\Some\Other\TestControllerGetEventInterveneController', $this->controllers->get('Something_Useless'));
    }

}
