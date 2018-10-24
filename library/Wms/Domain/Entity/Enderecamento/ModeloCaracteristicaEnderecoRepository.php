<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;

class ModeloCaracteristicaEnderecoRepository extends EntityRepository
{
    public function insert(array $data)
    {
        $modeloCaracteristicaEnderecoEn = new ModeloCaracteristicaEndereco();

        //obter entidade do modelo
        $modelo = $this->getEntityManager()->getReference("wms:Enderecamento\Modelo", $data['modeloEnderecamento']);
        $modeloCaracteristicaEnderecoEn->setModeloEnderecamento($modelo);

        //obter entidade da area de armazenagem
        $caracteristicaEndereco = $this->getEntityManager()->getReference("wms:Deposito\Endereco\Caracteristica", $data['idCaracteristicaEndereco']);
        $modeloCaracteristicaEnderecoEn->setCaracteristicaEndereco($caracteristicaEndereco);

        $modeloCaracteristicaEnderecoEn->setPrioridade($data['prioridadeCaracteristicaEndereco']);

        $this->getEntityManager()->persist($modeloCaracteristicaEnderecoEn);
        return $modeloCaracteristicaEnderecoEn;
    }

    public function delete(array $data)
    {
        $caracteristicaEnderecoEn = $this->getEntityManager()->getRepository("wms:Enderecamento\ModeloCaracteristicaEndereco")->findBy(array('modeloEnderecamento' => $data['modeloEnderecamento']));

        foreach ($caracteristicaEnderecoEn as $caracteristicaEndereco) {
            $this->getEntityManager()->remove($caracteristicaEndereco);
        }

        return $caracteristicaEnderecoEn;
    }

}