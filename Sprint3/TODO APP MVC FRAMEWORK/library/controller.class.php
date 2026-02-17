<?php

class Controller {

    protected $_model;
    protected $_controller;
    protected $_action;
    protected $_template;
    private $_modelInstances = [];

    function __set($name, $value) {
        $this->_modelInstances[$name] = $value;
    }

    function __get($name) {
        return isset($this->_modelInstances[$name]) ? $this->_modelInstances[$name] : null;
    }

    function __construct($model, $controller, $action) {

        $this->_controller = $controller;
        $this->_action     = $action;
        $this->_model      = $model;

        $this->$model      = new $model;
        $this->_template   = new Template($controller, $action);
    }

    function set($name, $value) {
        $this->_template->set($name, $value);
    }

    function __destruct() {
        $this->_template->render();
    }
}