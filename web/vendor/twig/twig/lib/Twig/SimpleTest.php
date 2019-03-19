<?php

use Twig\TwigTest;

class_exists('Twig\TwigTest');

if (\false) {
    class Twig_SimpleTest extends TwigTest
    {
    }

    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments()
    {
        return $this->arguments;
    }
}
