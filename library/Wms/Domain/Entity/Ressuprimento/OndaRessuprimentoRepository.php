<?php

namespace Wms\Domain\Entity\Ressuprimento;

use Doctrine\ORM\EntityRepository;

class OndaRessuprimentoRepository extends EntityRepository
{
    public function getOndasEmAberto(){
            $query = $this->getEntityManager()->createQueryBuilder()
                ->select("os.id as OS,
                          w.id as Onda,
                          wos.id as OndaOsId")
                ->from("wms:Ressuprimento\OndaRessuprimentoOs",'wos')
                ->leftJoin("wos.os","os")
                ->leftJoin("wos.ondaRessuprimento","w")
                ->where("wos.status = ". \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_ONDA_GERADA)
                ->orderBy("wos.id");
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

    /** @var \Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs $ondaOs */
    public function finalizaOnda($ondaOs)
    {
        try {
            $this->getEntityManager()->beginTransaction();
            /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
            $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Pessoa");

            /** @var \Wms\Domain\Entity\OrdemServico $osEn */
            $osEn = $ondaOs->getOs();
            $produtoEn = $ondaOs->getProduto();
            $codProduto = $produtoEn->getId();
            $grade = $produtoEn->getGrade();
            $idOs = $osEn->getId();
            $idUsuario  = \Zend_Auth::getInstance()->getIdentity()->getId();
            $usuarioEn = $pessoaRepo->find($idUsuario);

            $reservaEstoqueRepo->efetivaReservaEstoque(NULL,$codProduto,$grade,0,"E","O",$ondaOs->getId(),$idUsuario,$idOs);
            $reservaEstoqueRepo->efetivaReservaEstoque(NULL,$codProduto,$grade,0,"S","O",$ondaOs->getId(),$idUsuario,$idOs);

            $statusEn = $this->getEntityManager()->getRepository("wms:Util\Sigla")->findOneBy(array('id'=>\Wms\Domain\Entity\Ressuprimento\OndaRessuprimentoOs::STATUS_FINALIZADO));
            $ondaOs->setStatus($statusEn);
            $this->getEntityManager()->persist($ondaOs);

            $osEn->setDataFinal(new \DateTime());
            $osEn->setPessoa($usuarioEn);

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }

}
