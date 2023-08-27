<?php

namespace Html;

class Help extends Html {

    public function __construct($path) {
        $this->setTitle('Súgó');
        $this->content = '';

        //TODO: validate
        $idT = explode('-', $path[0]);
        foreach ($idT as $id) {
            $help = new \Help($id);
            $this->content .= $help->html;
        }
    }

}
