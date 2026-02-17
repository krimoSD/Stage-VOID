<?php

class Template {

    protected $variables  = array();
    protected $_controller;
    protected $_action;

    function __construct($controller, $action) {
        $this->_controller = $controller;
        $this->_action     = $action;
    }

    /** Set a variable to be available inside the view **/

    function set($name, $value) {
        $this->variables[$name] = $value;
    }

    /** Render header + action view + footer **/

    function render() {
        extract($this->variables);

        // Header: prefer controller-specific, fall back to global
        if (file_exists(ROOT . DS . 'application' . DS . 'views' . DS . $this->_controller . DS . 'header.php')) {
            include(ROOT . DS . 'application' . DS . 'views' . DS . $this->_controller . DS . 'header.php');
        } else {
            include(ROOT . DS . 'application' . DS . 'views' . DS . 'header.php');
        }

        // Action view (required)
        include(ROOT . DS . 'application' . DS . 'views' . DS . $this->_controller . DS . $this->_action . '.php');

        // Footer: prefer controller-specific, fall back to global
        if (file_exists(ROOT . DS . 'application' . DS . 'views' . DS . $this->_controller . DS . 'footer.php')) {
            include(ROOT . DS . 'application' . DS . 'views' . DS . $this->_controller . DS . 'footer.php');
        } else {
            include(ROOT . DS . 'application' . DS . 'views' . DS . 'footer.php');
        }
    }
}
