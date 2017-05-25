<?php
namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Produto\Andamento;

class AndamentoRepository extends EntityRepository
{
    /**
     * @param bool $observacao
     * @param $idProduto
     * @param bool $usuarioId
     */
    public function save($idProduto, $grade, $usuarioId = false, $observacao = false, $flush = true, $integracao = false)
    {
        $usuario = null;
        if ($integracao == false) {
            $usuarioId = ($usuarioId) ? $usuarioId : \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);
        }

        $andamento = new Andamento();
        $produto = $this->_em->getReference('wms:Produto', array('id' => $idProduto, 'grade' => $grade));
        $andamento->setProduto($produto);
        $andamento->setUsuario($usuario);
        $andamento->setCodProduto($idProduto);
        $andamento->setGrade($grade);
        $andamento->setDscObservacao($observacao);
        $andamento->setDataAndamento(new \DateTime);

        $this->_em->persist($andamento);

        if ($flush == true) {
            $this->_em->flush();
        }
    }

}