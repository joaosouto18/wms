<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class ModeloTipoEnderecoRepository extends EntityRepository
{
    public function insert(array $data)
    {
        $modeloTipoEnderecoEn = new ModeloTipoEndereco();

        //obter entidade do modelo
        $modelo = $this->getEntityManager()->getReference("wms:Enderecamento\Modelo", $data['modeloEnderecamento']);
        $modeloTipoEnderecoEn->setModeloEnderecamento($modelo);

        //obter entidade da area de armazenagem
        $tipoEndereco = $this->getEntityManager()->getReference("wms:Deposito\Endereco\Tipo", $data['idTipoEndereco']);
        $modeloTipoEnderecoEn->setTipoEndereco($tipoEndereco);

        $modeloTipoEnderecoEn->setPrioridade($data['prioridadeTipoEndereco']);

        $this->getEntityManager()->persist($modeloTipoEnderecoEn);
        return $modeloTipoEnderecoEn;
    }

    public function delete(array $data)
    {
        $tipoEnderecoEn = $this->getEntityManager()->getRepository("wms:Enderecamento\ModeloTipoEndereco")->findBy(array('modeloEnderecamento' => $data['modeloEnderecamento']));

        foreach ($tipoEnderecoEn as $tipoEndereco) {
            $this->getEntityManager()->remove($tipoEndereco);
        }

        return $tipoEnderecoEn;
    }

}