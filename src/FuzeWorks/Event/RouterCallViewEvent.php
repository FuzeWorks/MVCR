<?php
/**
 * FuzeWorks Framework MVCR Component.
 *
 * The FuzeWorks PHP FrameWork
 *
 * Copyright (C) 2013-2019 TechFuze
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
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 * @license   https://opensource.org/licenses/MIT MIT License
 *
 * @link  http://techfuze.net/fuzeworks
 * @since Version 1.2.0
 *
 * @version Version 1.2.0
 */

namespace FuzeWorks\Event;

use FuzeWorks\Controller;
use FuzeWorks\Event;
use FuzeWorks\View;

/**
 * Event that gets fired when a view is about to be called by the defaultCallable() in the Router.
 *
 * Use this to cancel the calling of a view method.
 *
 * Currently only used by Router::defaultCallable();
 *
 * This event is currently used in the WebComponent project. It allows the component to stop loading when
 * a CSRFException is thrown, and the view has no method of handling this request
 *
 * @author    Abel Hoogeveen <abel@techfuze.net>
 * @copyright Copyright (c) 2013 - 2019, TechFuze. (http://techfuze.net)
 */
class RouterCallViewEvent extends Event
{
    /**
     * The function that will be loaded in the view
     *
     * @var string
     */
    public $viewMethod;

    /**
     * The parameters that will be provided to the function in the view
     *
     * @var string
     */
    public $viewParameters;

    /**
     * The route that resulted in this controller and view
     *
     * @var string
     */
    public $route;

    /**
     * The view the method will be called on
     *
     * @var View
     */
    public $view;

    /**
     * The controller that's associated with this View
     *
     * @var Controller
     */
    public $controller;

    public function init(View $view, Controller $controller, string $viewMethod, string $viewParameters, string $route)
    {
        $this->view = $view;
        $this->controller = $controller;
        $this->viewMethod = $viewMethod;
        $this->viewParameters = $viewParameters;
        $this->route = $route;
    }
}