<?php

namespace Mobile\Service;

class Enderecamento
{
    protected $em;

    public function __construct($em)
    {
        $this->em = $em;
    }

} 