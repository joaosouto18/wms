<?php

namespace Enderecamento\Service;

class Movimentacao
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

} 