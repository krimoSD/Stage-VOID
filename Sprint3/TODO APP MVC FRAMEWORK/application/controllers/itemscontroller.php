<?php

class ItemsController extends Controller {

    /**
     * View a single todo item by id
     * URL: /items/view/{id}/{slug}
     */
    function view($id = null, $name = null) {
        $this->set('title', $name . ' - My Todo List App');
        $this->set('todo', $this->Item->select($id));
    }

    /**
     * View all todo items
     * URL: /items/viewall
     */
    function viewall() {
        $this->set('title', 'All Items - My Todo List App');
        $this->set('todo', $this->Item->selectAll());
    }

    /**
     * Add a new todo item (POST)
     * URL: /items/add
     */
    function add() {
        $todo = isset($_POST['todo']) ? $_POST['todo'] : '';
        $this->set('title', 'Success - My Todo List App');
        $this->set('todo', $this->Item->query(
            'INSERT INTO items (item_name) VALUES (\'' . $this->Item->escape($todo) . '\')'
        ));
    }

    /**
     * Delete a todo item by id
     * URL: /items/delete/{id}
     */
    function delete($id = null) {
        $this->set('title', 'Success - My Todo List App');
        $this->set('todo', $this->Item->query(
            'DELETE FROM items WHERE id = \'' . (int)$id . '\''
        ));
    }
}