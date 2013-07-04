<?php

namespace Laelaps\Twig\Naturaltime\Tests;

use Laelaps\Twig\Naturaltime\Extension\Naturaltime;
use PHPUnit_Framework_TestCase;
use Twig_Environment;
use Twig_Loader_String;

class NaturaltimeTest extends PHPUnit_Framework_TestCase
{
    /**
     * Returns twig environment with loaded extension.
     *
     * @return \Twig_Environment
     */
    private function getTwig()
    {
        $twig = new Twig_Environment(new Twig_Loader_String);
        $twig->addExtension(new Naturaltime);

        return $twig;
    }

    public function testThatNaturaltimeIsRenderedWithoutErrors()
    {
        $this->getTwig()->render('{{ 0|naturaltime }}');
    }
}
