<?php

namespace Laelaps\Twig\Naturaltime\Extension;

use Absolvent\ObjectRepository\NameGeneratorTrait;
use Laelaps\Twig\Naturaltime\Naturaltime as NaturaltimeHelper;
use Twig_Extension;
use Twig_SimpleFilter;

class Naturaltime extends Twig_Extension
{
    use NameGeneratorTrait
    {
        underscoredVendorNamespacedClassName as public getName;
    }

    /**
     * @var \Laelaps\Twig\Naturaltime
     */
    private $naturaltime;

    public function __construct()
    {
        $this->naturaltime = new NaturaltimeHelper;
    }

    /**
     * @param mixed $then
     * @return string
     */
    public function filterNaturaltime($then)
    {
        return $this->naturaltime->renderTimestamp($then, $now = time());
    }

    /**
     * {@inheritDoc}
     */
    public function getFilters()
    {
        return [
            'naturaltime' => new Twig_SimpleFilter('naturaltime', [$this, 'filterNaturaltime']),
        ];
    }
}
