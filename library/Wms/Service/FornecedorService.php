<?php

namespace Wms\Service;

use Core\Util\String;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Pessoa\Papel\Fornecedor;

class FornecedorService extends AbstractService
{

    public function save($data, $runFlush = false)
    {
        try {
            /** @var Fornecedor $entity */
            $entity = $this->findOneBy(['codExterno' => $data['codExterno']]);
            $cpf = String::retirarMaskCpfCnpj($data['cpf']);
            $cnpj = String::retirarMaskCpfCnpj($data['cnpj']);

            if (empty($entity)) {
                $entity = new Fornecedor();
                /** @var Pessoa $pessoa */
                $pessoa = null;
                if (!empty($cnpj)) {
                    $pessoa = $this->em->getRepository('wms:Pessoa\Juridica')
                        ->findOneBy(['cnpj' => $cnpj]);
                } elseif ($cpf) {
                    $pessoa = $this->em->getRepository('wms:Pessoa\Fisica')
                        ->findOneBy(['cpf' => $cpf]);
                } else {
                    throw new \Exception("CPF nem CNPJ não identificados");
                }

                if (empty($pessoa)) {
                    $fornecedor = [];
                    if (!empty($cnpj)) {
                        $fornecedor['pessoa']['tipo'] = 'J';
                        $fornecedor['pessoa']['juridica']['dataAbertura'] = null;
                        $fornecedor['pessoa']['juridica']['cnpj'] = $cnpj;
                        $fornecedor['pessoa']['juridica']['idTipoOrganizacao'] = null;
                        $fornecedor['pessoa']['juridica']['idRamoAtividade'] = null;
                        $fornecedor['pessoa']['juridica']['inscricaoEstadual'] = $data['insc'];
                        $fornecedor['pessoa']['juridica']['nome'] = $data['nome'];
                    } else if (!empty($cpf)) {
                        $fornecedor['pessoa']['tipo'] = 'F';
                        $fornecedor['pessoa']['fisica']['cpf'] = $cpf;
                        $fornecedor['pessoa']['fisica']['nome'] = $data['nome'];
                    }
                    $pessoa = $this->getRepository()->persistirAtor($entity, $fornecedor);
                }

                $entity->setPessoa($pessoa);
                $entity->setId($pessoa->getId());
                $entity->setCodExterno($data['codExterno']);
                $this->em->persist($entity);
            } else {
                if (!in_array($entity->getCpfCnpj(false), [$cpf, $cnpj])) {
                    $cpfCnpj = (!empty($cpf)) ? $cpf : $cnpj;
                    $nome = $entity->getNome();
                    throw new \Exception("O CPF/CNPJ: '$cpfCnpj' já está cadastrado no código '$data[codExterno]' para o fornecedor '$nome'");
                }
                $this->em->getRepository(Fornecedor::class)->tryUpdate($entity->getPessoa(), $data);
            }
            if ($runFlush) $this->em->flush();
        } catch (\Exception $e ) {
            throw $e;
        }
    }
}