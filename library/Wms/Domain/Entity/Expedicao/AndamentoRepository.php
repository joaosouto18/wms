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
    public function save($observacao = false, $idExpedicao, $usuarioId = false, $flush = true, $codigoBarras = null, $codBarrasProdutos = null, $integracao = false, $idMapa = null)
    {
        $usuario = null;
        if ($integracao == false) {
            $usuarioId = ($usuarioId) ? $usuarioId : \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);
        }
        $expedicaoEntity  = $this->_em->getReference('wms:Expedicao', $idExpedicao);

        $andamento = new Andamento();
        $andamento->setUsuario($usuario);
        $andamento->setExpedicao($expedicaoEntity);
        $andamento->setDscObservacao($observacao);
        $andamento->setDataAndamento(new \DateTime);
        $andamento->setCodBarras($codigoBarras);
        $andamento->setCodMapa($idMapa);
        $andamento->setCodBarrasProduto($codBarrasProdutos);

        $this->_em->persist($andamento);

        if ($flush == true) {
            $this->_em->flush();
        }
    }

}