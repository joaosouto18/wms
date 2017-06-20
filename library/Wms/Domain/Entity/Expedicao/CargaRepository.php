<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Wms\Domain\Entity\Util\Sigla;

class CargaRepository extends EntityRepository
{

    public function save($carga, $runFlush = true)
    {

        $em = $this->getEntityManager();

        $entityCarga = $this->findBy(array('expedicao' => $carga['idExpedicao']));
        $numeroDeCargas = count($entityCarga)+1;

        try {
            $tipoCarga = $em->getRepository('wms:Util\Sigla')->findOneBy(array('tipo' => 69,'referencia'=> $carga['codTipoCarga']));

            $enCarga = new Carga;
            $enCarga->setPlacaExpedicao($carga['placaExpedicao']);
            $enCarga->setCentralEntrega($carga['centralEntrega']);
            $enCarga->setCodCargaExterno(trim($carga['codCargaExterno']));
            $enCarga->setExpedicao($carga['idExpedicao']);
            $enCarga->setPlacaCarga($carga['placaCarga']);
            $enCarga->setTipoCarga($tipoCarga);
            $enCarga->setSequencia($numeroDeCargas);

            if ($this->getSystemParameterValue('VALIDA_FECHAMENTO_CARGA') == 'N') {
                $enCarga->setDataFechamento(new \DateTime());
            }

            $em->persist($enCarga);

            if ($runFlush == true) {
                $em->flush();
            }

        } catch(\Exception $e) {
            throw new \Exception($e->getMessage() . ' - ' .$e->getTraceAsString());
        }

        return $enCarga;
    }

    /**
     * @param int $idCargaExterno
     * @param Sigla $siglaTipoCarga
     * @throws \Exception
     */
    public function cancelar($idCargaExterno, Sigla $siglaTipoCarga)
    {
        try {
            $cargaEntity = $this->findOneBy(array('codCargaExterno' => $idCargaExterno, 'tipoCarga' => $siglaTipoCarga->getId()));
            $idCarga = $cargaEntity->getId();
            $pedidos = $this->getPedidos($idCarga);

            /** @var \Wms\Domain\Entity\Expedicao\PedidoRepository $PedidoRepo */
            $PedidoRepo = $this->_em->getRepository('wms:Expedicao\Pedido');

            foreach ($pedidos as $pedido) {
                $PedidoRepo->cancelar($pedido->getId());
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public function getPedidos($idCarga)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('wms:Expedicao\Pedido', 'p')
            ->innerJoin('p.carga', 'c')
            ->where('p.carga = :IdCarga')
            ->setParameter('IdCarga', $idCarga);

        return $queryBuilder->getQuery()->getResult();
    }

    public function getSequenciaUltimaCarga($idExpedicao)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('MAX(c.sequencia) as sequencia')
            ->from('wms:Expedicao\Carga', 'c')
            ->where('c.codExpedicao = :expedicao')
            ->setParameter('expedicao', $idExpedicao);

        return $queryBuilder->getQuery()->getResult();
    }

    public function removeCarga($idCarga)
    {
        $cargaEntity = $this->find($idCarga);
        $this->getEntityManager()->remove($cargaEntity);
        $this->getEntityManager()->flush();
        return true;
    }

    public function getDetalhesPeso($codCarga) {
        $sql = "SELECT PROD.COD_PRODUTO,
                       PROD.DSC_GRADE,
                       PROD.DSC_PRODUTO,
                       SUM(PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0)) as QUANTIDADE,
                       PESO.NUM_CUBAGEM as CUBAGEM_UNITARIA,
                       SUM((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0))) * NVL(PESO.NUM_CUBAGEM,0) as CUBAGEM_TOTAL,
                       PESO.NUM_PESO as PESO_UNITARIO,
                       SUM((PP.QUANTIDADE - NVL(PP.QTD_CORTADA,0))) * NVL(PESO.NUM_PESO,0) as PESO_TOTAL
                  FROM PEDIDO P
                  LEFT JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO = P.COD_PEDIDO
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = PP.COD_PRODUTO AND PROD.DSC_GRADE = PP.DSC_GRADE
                  LEFT JOIN PRODUTO_PESO PESO ON PESO.COD_PRODUTO = PP.COD_PRODUTO AND PESO.DSC_GRADE = PP.DSC_GRADE
                 WHERE P.COD_CARGA = $codCarga
                 GROUP BY PROD.COD_PRODUTO,
                          PROD.DSC_GRADE,
                          PROD.DSC_PRODUTO,
                          PESO.NUM_CUBAGEM,
                          PESO.NUM_PESO
                ORDER BY PROD.COD_PRODUTO,
                          PROD.DSC_GRADE
                    ";

        $result=$this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($result as $key => $line){
            $result[$key]['CUBAGEM_UNITARIA'] = number_format($line['CUBAGEM_UNITARIA'],3);
            $result[$key]['PESO_UNITARIO']    = number_format($line['PESO_UNITARIO'],3);
            $result[$key]['CUBAGEM_TOTAL']    = number_format($line['CUBAGEM_TOTAL'],3);
            $result[$key]['PESO_TOTAL']       = number_format($line['PESO_TOTAL'],3);
        }


        return $result;

    }

}