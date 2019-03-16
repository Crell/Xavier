<?php
declare(strict_types=1);

namespace Crell\Xavier;

class State
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var array
     */
    public $next = [];

    /**
     * @var callable
     */
    public $process;
}
