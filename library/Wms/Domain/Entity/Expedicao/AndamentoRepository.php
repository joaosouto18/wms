<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\Andamento;

class AndamentoRepository extends EntityRepository
{
    /**
     * @param bool $observacao
     * @param $idExpedicao
     * @param bool $usuarioId
     */
    public function save($observacao = false, $idExpedicao, $usuarioId = false, $flush = true, $codigoBarras)
    {
        $usuarioId = ($usuarioId) ? $usuarioId : \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $expedicaoRepo */
        $expedicaoRepo  = $this->_em->getRepository('wms:Expedicao');
        $expedicaoEntity = $expedicaoRepo->find($idExpedicao);

        $andamento = new Andamento();
        $andamento->setUsuario($usuario)
            ->setExpedicao($expedicaoEntity)
            ->setDscObservacao($observacao)
            ->setDataAndamento(new \DateTime)
            ->setCodBarras($codigoBarras);

        $this->_em->persist($andamento);

        if ($flush == true) {
            $this->_em->flush();
        }
    }

}