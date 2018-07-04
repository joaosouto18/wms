<?php

namespace Wms\Domain\Entity\Movimentacao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Movimentacao\Veiculo as VeiculoEntity,
    \Doctrine\Common\Persistence\ObjectRepository;

class VeiculoRepository extends EntityRepository implements ObjectRepository
{

    /**
     * Retorna se a placa do veiculo já existe
     * @return boolean
     */
    public function checkPlacaExiste($id)
    {
        $id = $this->findBy(array('id' => mb_strtoupper($id, 'UTF-8')));
        return ($id != null);
    }

    /**
     *
     * @param VeiculoEntity $veiculo
     * @param array $values
     * @throws \Exception 
     */
    public function save(VeiculoEntity $veiculo, array $values)
    {
        extract($values['identificacao']);

        $em = $this->getEntityManager();

        $tipo = $em->getReference('wms:Movimentacao\Veiculo\Tipo', $idTipo);
        $transportadorEntity = $em->getReference('wms:Pessoa\Papel\Transportador', $idTransportador);

        if (strlen($id) != 7)
            throw new \Exception('Formato inválido de placa!');

        //verificar se a placa já existe no banco
        if (($veiculo->getId() == null) || ($veiculo->getId() != $id)) {
            if ($this->checkPlacaExiste($id))
                throw new \Exception('Placa já cadastrada!');
        }

        if (!isset($values['identificacao']['cubagem'])) $cubagem = 0;
        if (!isset($values['identificacao']['altura'])) $altura = 0;
        if (!isset($values['identificacao']['largura'])) $largura = 0;
        if (!isset($values['identificacao']['profundidade'])) $profundidade = 0;
        if (!isset($values['identificacao']['capacidade'])) $capacidade = 0;
        if (!isset($values['identificacao']['descricao'])) $descricao = 'AAA0000';

        // request
        $veiculo->setTipo($tipo)
                ->setTransportador($transportadorEntity)
                ->setDescricao($descricao)
                ->setId($id)
                ->setAltura($altura)
                ->setLargura($largura)
                ->setProfundidade($profundidade)
                ->setCubagem($cubagem)
                ->setCapacidade($capacidade);

        $em->persist($veiculo);
        $em->flush();
    }

    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Movimentacao\Veiculo', $id);
        $em->remove($proxy);
    }

}