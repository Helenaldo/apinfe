<?php
namespace app\core;

use app\core\RotaBase;

class Rota extends RotaBase {
    public $rotas;

    public function get($endpoint, $trigger) {
        $this->rotas['get'][$endpoint] = $trigger;
    }

    public function post($endpoint, $trigger) {
        $this->rotas['post'][$endpoint] = $trigger;
    }

    public function put($endpoint, $trigger) {
        $this->rotas['put'][$endpoint] = $trigger;
    }

    public function delete($endpoint, $trigger) {
        $this->rotas['delete'][$endpoint] = $trigger;
    }

}