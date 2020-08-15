<?php


namespace Wms\Domain\Entity\Pessoa\Papel;


interface EmissorInterface
{
    const EMISSOR_CLIENTE = 'C';
    const EMISSOR_FORNECEDOR = 'F';

    public function getId();
    public function getPessoa();
    public function getCodExterno();
    public function getCpfCnpj();
    public function getNome();
}