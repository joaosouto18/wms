<?php
namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Enderecamento\Andamento;

class AndamentoRepository extends EntityRepository
{
    /**
     * @param bool $observacao
     * @param $idRecebimento
     * @param $codProduto
     * @param $grade
     * @param bool $usuarioId
     */
    public function save($observacao = false, $idRecebimento, $codProduto, $grade, $usuarioId = false)
    {
        $usuarioId = ($usuarioId) ? $usuarioId : \Zend_Auth::getInstance()->getIdentity()->getId();
        $usuario = $this->_em->getReference('wms:Usuario', (int) $usuarioId);

        /** @var \Wms\Domain\Entity\ExpedicaoRepository $recebimentoRepo */
        $recebimentoRepo  = $this->_em->getRepository('wms:Recebimento');
        $recebimentoEntity = $recebimentoRepo->find($idRecebimento);

        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo  = $this->_em->getRepository('wms:Produto');
        $produtoEntity = $produtoRepo->findOneBy(array('id'=>$codProduto, 'grade'=>$grade));

        $andamento = new Andamento();
        $andamento->setUsuario($usuario);
        $andamento->setRecebimento($recebimentoEntity);
        $andamento->setDscObservacao($observacao);
        $andamento->setProduto($produtoEntity);
        $andamento->setDataAndamento(new \DateTime);

        $this->_em->persist($andamento);
        $this->_em->flush();
    }

    public function getAndamento ($idRecebimento, $codProduto, $grade) {

        $source = $this->getEntityManager()->createQueryBuilder()

            ->select("a.dscObservacao,
                      a.dataAndamento,
                      p.nome")
            ->from("wms:Enderecamento\Andamento", "a")
            ->innerJoin("a.usuario", "u")
            ->innerJoin("u.pessoa", "p")

            ->innerJoin("a.recebimento","r")
            ->innerJoin("a.produto","pr")
            ->where('r.id = ' . $idRecebimento)
            ->andWhere("pr.id = " . $codProduto)
            ->andWhere("pr.grade = '".$grade . "'")
            ->orderBy("a.id" , "DESC");

        $result = $source->getQuery()->getResult();
        return $source;
    }


}