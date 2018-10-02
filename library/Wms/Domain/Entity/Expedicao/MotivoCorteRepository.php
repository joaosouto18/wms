<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Expedicao\Andamento;

class MotivoCorteRepository extends EntityRepository
{
    /**
     * Salva o registro no banco
     * @param MotivoCorte $motivo
     * @param array $values valores vindo de um formulÃƒÂ¡rio
     */
    public function save(MotivoCorte $motivo, array $values)
    {
        $motivo->setDscMotivo($values['identificacao']['dscMotivo']);
        $motivo->setCodExterno($values['identificacao']['codExterno']);
        $this->getEntityManager()->persist($motivo);
    }

    /**
     * Remove o registro no banco atravÃƒÂ©s do seu id
     * @param integer $id
     */
    public function remove($id)
    {
        $em = $this->getEntityManager();
        $proxy = $em->getReference('wms:Expedicao\MotivoCorte', $id);
        $numErros = 0;

        /*
            $dqlProduto = $em->createQueryBuilder()
                ->select('count(p) qtty')
                ->from('wms:Expedicao\PedidoProduto', 'p')
                ->where('p.motivoCorte = ?1')
                ->setParameter(1, $id);
            $resultSetProduto = $dqlProduto->getQuery()->execute();
            $countProduto = (integer) $resultSetProduto[0]['qtty'];
            if ($countProduto > 0) {
                $msg .= "{$countProduto} pedidos(s) ";
                $numErros++;
            }

            if($numErros > 0 ){
                throw new \Exception("Não é possível remover o Motivo de Corte {$proxy->getDescricao()},
                       há {$msg} vinculado(s).");
            }
        */

        // remove
        $em->remove($proxy);
    }

    public function getMotivos()
    {
        $result = $this->findAll();
        foreach ($result as $row)
            $rows[$row->getId()] = $row->getDscMotivo();

        return $rows;
    }

}