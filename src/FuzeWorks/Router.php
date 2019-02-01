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

namespace FuzeWorks;


use FuzeWorks\Event\RouterLoadViewAndControllerEvent;
use FuzeWorks\Exception\ConfigException;
use FuzeWorks\Exception\ControllerException;
use FuzeWorks\Exception\EventException;
use FuzeWorks\Exception\HaltException;
use FuzeWorks\Exception\NotFoundException;
use FuzeWorks\Exception\RouterException;
use FuzeWorks\Exception\ViewException;

class Router
{
    /**
     * The routes loaded into the Router
     *
     * @var array
     */
    protected $routes = [];

    /**
     * The current callable used
     *
     * @var callable|null
     */
    protected $callable = null;

    /**
     * The current matches used
     *
     * @var array|null
     */
    protected $matches = null;

    /**
     * The current route used
     *
     * @var string|null
     */
    protected $route = null;

    /**
     * The current View
     *
     * @var View|null
     */
    protected $view = null;

    /**
     * The current Controller
     *
     * @var Controller|null
     */
    protected $controller = null;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Controllers
     */
    private $controllers;

    /**
     * @var Views
     */
    private $views;

    /**
     * Router constructor.
     *
     * @codeCoverageIgnore
     */
    public function __construct()
    {
        // Prepare
        $factory = Factory::getInstance();
        $this->config = $factory->config;
        $this->controllers = $factory->controllers;
        $this->views = $factory->views;
    }

    /**
     * Route Parser
     *
     * This method parses all the routes in the routes table config file
     * and adds them to the Router. It converts some routes which use wildcards
     *
     * @return void
     * @throws RouterException
     */
    public function init()
    {
        // Get routing routes
        try {
            $routes = $this->config->getConfig('routes');
            // @codeCoverageIgnoreStart
        } catch (ConfigException $e) {
            throw new RouterException("Could not parse routing. Error in config 'routes'");
            // @codeCoverageIgnoreEnd
        }

        // Cycle through all provided routes
        foreach ($routes as $route => $routeConfig)
        {
            // Check if only a string is provided
            // e.g: 0 => '.*$'
            if (is_int($route))
            {
                $route = $routeConfig;
                $routeConfig = ['callable' => [$this, 'defaultCallable']];
            }

            // Finally add the route
            $this->addRoute($route, $routeConfig);
        }
    }

    public function addRoute(string $route, $routeConfig = null, bool $prepend = true)
    {
        // Set defaultCallable if no value provided
        if (is_null($routeConfig))
            $routeConfig = ['callable' => [$this, 'defaultCallable']];

        // Convert wildcards to Regex
        $route = str_replace([':any',':num'], ['[^/]+', '[0-9]+'], $route);

        if ($prepend)
            $this->routes = [$route => $routeConfig] + $this->routes;
        else
            $this->routes[$route] = $routeConfig;

        Logger::log('Route added at '.($prepend ? 'top' : 'bottom').': "'.$route.'"');
    }

    /**
     * Removes a route from the array based on the given route.
     *
     * @param $route string The route to remove
     */
    public function removeRoute(string $route)
    {
        unset($this->routes[$route]);

        Logger::log('Route removed: '.$route);
    }

    /**
     * @param string $path
     * @return mixed
     * @throws NotFoundException
     */
    public function route(string $path)
    {
        // Check all the provided custom paths
        foreach ($this->routes as $route => $routeConfig)
        {
            // Match the path against the routes
            if (!preg_match('#^'.$route.'$#', $path, $matches))
                continue;

            // Save the matches
            Logger::log('Route matched: '.$route);
            $this->matches = $matches;
            $this->route = $route;

            // Call callable if routeConfig is callable, so routeConfig can be replaced
            // e.g: '.*$' => callable
            if (is_callable($routeConfig))
                $routeConfig = call_user_func_array($routeConfig, [$matches]);

            // If routeConfig is an array, multiple things might be at hand
            if (is_array($routeConfig))
            {
                // Replace defaultCallable if a custom callable is provided
                // e.g: '.*$' => ['callable' => [$object, 'method']]
                if (isset($routeConfig['callable']) && is_callable($routeConfig['callable']))
                    $this->callable = $routeConfig['callable'];

                // If the route provides a configuration, use that
                // e.g: '.*$' => ['viewName' => 'custom', 'viewType' => 'cli', 'function' => 'index']
                else
                    $this->matches = array_merge($this->matches, $routeConfig);
            }

            // If no custom callable is provided, use default
            if (is_null($this->callable))
                $this->callable = [$this, 'defaultCallable'];

            // Attempt and load callable. If false, continue
            $output = $this->loadCallable($this->callable, $this->matches, $route);
            if (is_bool($output) && $output === FALSE)
            {
                Logger::log('Callable not satisfied, skipping to next callable');
                continue;
            }

            return $output;
        }

        throw new NotFoundException("Could not load view. Router could not find matching route.");
    }

    /**
     * @param callable $callable
     * @param array $matches
     * @param string $route
     * @return mixed
     */
    protected function loadCallable(callable $callable, array $matches, string $route)
    {
        // Log the input to the logger
        Logger::newLevel('Loading callable with matches:');
        foreach ($matches as $key => $value) {
            if (!is_int($key))
                Logger::log($key.': '.var_export($value, true).'');
        }
        Logger::stopLevel();

        // Invoke callable
        return call_user_func_array($callable, [$matches, $route]);
    }

    /**
     * @param array $matches
     * @param string $route
     * @return mixed
     * @throws HaltException
     * @throws RouterException
     * @todo Use $route and send it to the view
     */
    public function defaultCallable(array $matches, string $route)
    {
        Logger::log('defaultCallable called');

        // Prepare variables
        $viewName = isset($matches['viewName']) ? $matches['viewName'] : null;
        $viewType = isset($matches['viewType']) ? $matches['viewType'] : $this->config->routing->default_viewType;
        $viewMethod = isset($matches['viewMethod']) ? $matches['viewMethod'] : $this->config->routing->default_viewMethod;
        $viewParameters = isset($matches['viewParameters']) ? $matches['viewParameters'] : '';

        // If nothing is provided, cancel loading
        if (is_null($viewName))
            return false;

        try {
            /** @var RouterLoadViewAndControllerEvent $event */
            $event = Events::fireEvent('routerLoadViewAndControllerEvent',
                $viewName,
                $viewType,
                $viewMethod,
                $viewParameters,
                $route
            );
        } catch (EventException $e) {
            throw new RouterException("Could not load view. routerLoadViewAndControllerEvent threw exception: '".$e->getMessage()."'");
        }

        // Cancel if requested to do so
        if ($event->isCancelled())
            throw new HaltException("Will not load view. Cancelled by routerLoadViewAndControllerEvent.");

        // First receive the controller
        try {
            $this->controller = (!is_null($event->controller) ? $event->controller : $this->controllers->get($event->viewName));
        } catch (ControllerException $e) {
            throw new RouterException("Could not load view. Controllers::get threw ControllerException: '".$e->getMessage()."'");
        } catch (NotFoundException $e) {
            return false;
        }

        // Then try and receive the view
        try {
            $this->view = $this->views->get($event->viewName, $this->controller, $event->viewType);
        } catch (ViewException $e) {
            throw new RouterException("Could not load view. Views::get threw ViewException: '".$e->getMessage()."'");
        } catch (NotFoundException $e) {
            return false;
        }

        // If the view does not want a function to be loaded, provide a halt parameter
        if (isset($this->view->halt))
            throw new HaltException("Will not load view. Cancelled by 'halt' attribute in view.");

        // Check if requested function or magic method exists in view
        if (method_exists($this->view, $event->viewMethod) || method_exists($this->view, '__call'))
        {
            // Run viewCallMethodEvent.
            try {
                $methodEvent = Events::fireEvent('viewCallMethodEvent');
            } catch (EventException $e) {
                throw new RouterException("Could not load view. viewCallMethodEvent threw exception: '".$e->getMessage()."'");
            }

            // If cancelled, halt
            if ($methodEvent->isCancelled())
                throw new HaltException("Will not load view. Cancelled by viewCallMethodEvent");

            // Execute the function on the view
            return $this->view->{$event->viewMethod}($event->viewParameters);
        }

        // View could not be found
        return false;
    }

    /**
     * Returns an array with all the routes.
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Returns the current route
     *
     * @return string|null
     * @codeCoverageIgnore
     */
    public function getCurrentRoute()
    {
        return $this->route;
    }

    /**
     * Returns all the matches with the RegEx route.
     *
     * @return null|array
     * @codeCoverageIgnore
     */
    public function getCurrentMatches()
    {
        return $this->matches;
    }

    /**
     * Returns the current View
     *
     * @return View|null
     * @codeCoverageIgnore
     */
    public function getCurrentView()
    {
        return $this->view;
    }

    /**
     * Returns the current Controller
     *
     * @return Controller|null
     * @codeCoverageIgnore
     */
    public function getCurrentController()
    {
        return $this->controller;
    }
}