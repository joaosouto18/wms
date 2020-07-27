<?php


namespace Wms\Domain\Entity\Pessoa\Papel;


interface EmissorInterface
{
    public function getId();
    public function getPessoa();
    public function getCodExterno();
}