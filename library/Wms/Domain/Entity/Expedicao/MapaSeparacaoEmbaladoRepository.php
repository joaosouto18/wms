<?php
namespace Wms\Domain\Entity\Expedicao;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Id\SequenceGenerator;
use Doctrine\ORM\Query;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Expedicao;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Usuario;
use Wms\Domain\Entity\UsuarioRepository;

class MapaSeparacaoEmbaladoRepository extends EntityRepository
{

    public function save($idMapa, $codPessoa, $os, $mapaSeparacaoEmbalado = null, $flush = true)
    {
        $pessoaEn = $this->getEntityManager()->getReference('wms:Pessoa',$codPessoa);
        $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$idMapa);
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO);
        $sequencia = 1;
        if (!empty($mapaSeparacaoEmbalado)) {
            $sequencia = $mapaSeparacaoEmbalado->getSequencia() + 1;
        }

        $mapaSeparacaoEmbalado = new MapaSeparacaoEmbalado();
        $mapaSeparacaoEmbalado->generateId($this->_em);
        $mapaSeparacaoEmbalado->setMapaSeparacao($mapaSeparacaoEn);
        $mapaSeparacaoEmbalado->setPessoa($pessoaEn);
        $mapaSeparacaoEmbalado->setSequencia($sequencia);
        $mapaSeparacaoEmbalado->setStatus($siglaEn);
        $mapaSeparacaoEmbalado->setOs($os);
        $mapaSeparacaoEmbalado->setUltimoVolume('N');
        $this->getEntityManager()->persist($mapaSeparacaoEmbalado);
        if ($flush == true) {
            $this->getEntityManager()->flush();
        }

        return $mapaSeparacaoEmbalado;
    }

    /** ocorre quando o conferente bipou os produtos do mapa e lacrou aquele determinado volume embalado */
    /**
     * @param $mapaSeparacaoEmbaladoEn MapaSeparacaoEmbalado
     * @param int|null $posVolume
     * @param int|null $posEntrega
     * @param int|null $totalEntrega
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fecharMapaSeparacaoEmbalado($mapaSeparacaoEmbaladoEn, $posVolume = null, $posEntrega = null, $totalEntrega= null)
    {
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO);
        $mapaSeparacaoEmbaladoEn->setStatus($siglaEn);

        if (!empty($posVolume)) $mapaSeparacaoEmbaladoEn->setPosVolume($posVolume);
        if (!empty($posEntrega)) $mapaSeparacaoEmbaladoEn->setPosEntrega($posEntrega);
        if (!empty($totalEntrega)) $mapaSeparacaoEmbaladoEn->setTotalEntrega($totalEntrega);

        $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
        $this->getEntityManager()->flush();

        return $mapaSeparacaoEmbaladoEn;
    }

    /** ocorre quando o conferente está bipando nos volumes ja lacrados */
    public function conferirVolumeEmbalado($idEmbalado,$idExpedicao,$idMapa)
    {
//        $mapaSeparacaoEmbaladoEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado')->findOneBy(array('id' => $idEmbalado));

        $sql = $this->getEntityManager()->createQueryBuilder()
            ->select('mse')
            ->from('wms:Expedicao\MapaSeparacaoEmbalado','mse')
            ->innerJoin('mse.mapaSeparacao', 'ms')
            ->innerJoin('ms.expedicao', 'e')
            ->where("mse.id = $idEmbalado")
            ->andWhere("e.id = $idExpedicao");

        $mapaSeparacaoEmbaladoEntities = $sql->getQuery()->getResult();

        if (count($mapaSeparacaoEmbaladoEntities) <= 0) {
            throw new \Exception(utf8_encode('Volume Embalado nao encontrado ou nao pertencente a expedicao '.$idExpedicao));
        }
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO);

        foreach ($mapaSeparacaoEmbaladoEntities as $mapaSeparacaoEmbaladoEntity) {
            $mapaSeparacaoEmbaladoEntity->setStatus($siglaEn);
            $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEntity);
        }
        $this->getEntityManager()->flush();

        return true;
    }

    /**
     * @param $mapaSeparacaoEmbaladoEn MapaSeparacaoEmbalado
     * @param $idPessoa
     * @param $fechaEmbaladosNoFinal bool
     * @param $checkSetLast bool
     * @param $isLast bool
     * @throws \Exception
     */
    public function imprimirVolumeEmbalado($mapaSeparacaoEmbaladoEn, $idPessoa, $fechaEmbaladosNoFinal = false, $checkSetLast = true, $isLast = false)
    {

        /** @var \Wms\Domain\Entity\Expedicao\MapaSeparacaoEmbaladoRepository $mapaSeparacaoEmbaladoRepo */
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        if (!$fechaEmbaladosNoFinal) {
            $etiqueta = $this->getDadosEmbalado($mapaSeparacaoEmbaladoEn->getId());
        } else {
            $etiqueta = $this->getDadosEmbalado(null, $mapaSeparacaoEmbaladoEn->getMapaSeparacao()->getExpedicao()->getId(), $mapaSeparacaoEmbaladoEn->getPessoa()->getId());
        }
        if (!isset($etiqueta) || empty($etiqueta) || count($etiqueta) <= 0) {
            throw new \Exception(utf8_encode('Não existe produtos conferidos para esse volume embalado!'));
        }

        $setLastVol = function () use ($mapaSeparacaoEmbaladoEn){
            $mapaSeparacaoEmbaladoEn->setUltimoVolume('S');
            $this->getEntityManager()->persist($mapaSeparacaoEmbaladoEn);
            $this->getEntityManager()->flush($mapaSeparacaoEmbaladoEn);
        };

        if ($checkSetLast) {
            $qtdPendenteConferencia = $this->getProdutosConferidosByCliente($mapaSeparacaoEmbaladoEn->getMapaSeparacao()->getId(), $idPessoa);
            if (empty($qtdPendenteConferencia)) {
                $setLastVol();
            }
        } elseif ($isLast) {
            $setLastVol();
        }

        $modeloEtiqueta = $this->getSystemParameterValue('MODELO_VOLUME_EMBALADO');
        $xy = explode(",",$this->getSystemParameterValue('TAMANHO_ETIQUETA_VOLUME_EMBALADO'));
        switch ($modeloEtiqueta) {
            case 1:
                //LAYOUT CASA DO CONFEITEIRO
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                break;
            case 2:
                //LAYOUT WILSO
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                break;
            case 3:
                //LAYOUT ABRAFER
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(105,75));
                break;
            case 4:
                //LAYOUT HIDRAU
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                break;
            case 5:
                //LAYOUT ETIQUETAS AGRUPADAS BASEADO MODELO 1
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                break;
            case 6:
                //LAYOUT PLANETA
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', $xy);
                break;
            case 7:
                //LAYOUT MBLED
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(100,75));
                break;
            default:
                $gerarEtiqueta = new \Wms\Module\Expedicao\Report\EtiquetaEmbalados("P", 'mm', array(75,45));
                break;

        }
        $gerarEtiqueta->imprimirExpedicaoModelo($etiqueta, $mapaSeparacaoEmbaladoRepo, $modeloEtiqueta, $fechaEmbaladosNoFinal);

    }

    public function validaVolumesEmbaladoConferidos($idExpedicao)
    {
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEn = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacao')->findBy(array('codExpedicao' => $idExpedicao));
        foreach ($mapaSeparacaoEn as $mapaSeparacao) {
            $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findBy(array('mapaSeparacao' => $mapaSeparacao));
            foreach ($mapaSeparacaoEmbaladoEn as $mapaSeparacaoEmbalado) {
                $statusMapaEmbalado = $mapaSeparacaoEmbalado->getStatus()->getId();
                if ($statusMapaEmbalado != MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FECHADO_FINALIZADO) {
                    return 'Existem volumes embalados pendentes de CONFERENCIA!';
                }
            }
        }
        return true;
    }

    public function validaVolumesEmbaladoConferidosByMapa($idMapa)
    {
        $mapaSeparacaoEmbaladoRepo = $this->getEntityManager()->getRepository('wms:Expedicao\MapaSeparacaoEmbalado');
        $mapaSeparacaoEn = $this->getEntityManager()->getReference('wms:Expedicao\MapaSeparacao',$idMapa);
        $siglaEn = $this->getEntityManager()->getReference('wms:Util\Sigla',MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_FINALIZADO);
        $mapaSeparacaoEmbaladoEn = $mapaSeparacaoEmbaladoRepo->findBy(array('mapaSeparacao' => $mapaSeparacaoEn));

        foreach ($mapaSeparacaoEmbaladoEn as $mapaSeparacaoEmbalado) {
            $statusMapaEmbalado = $mapaSeparacaoEmbalado->getStatus()->getId();
            if ($statusMapaEmbalado == MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO) {
                $mapaSeparacaoEmbalado->setStatus($siglaEn);
                $mapaSeparacaoEmbalado->setUltimoVolume('S');
            }
        }
        return true;
    }

    public function getDadosEmbalado($idMapaSeparacaoEmabalado = null, $idExpedicao = null, $idPessoa = null)
    {
        $whereArgs = ["1 = 1"];

        if (!empty($idMapaSeparacaoEmabalado)) {
            $whereArgs[] = "MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = $idMapaSeparacaoEmabalado";
        }
        if (!empty($idExpedicao)) {
            $whereArgs[] = "MS.COD_EXPEDICAO = $idExpedicao";
            $whereArgs[] = "MSE.COD_STATUS <> " . MapaSeparacaoEmbalado::CONFERENCIA_EMBALADO_INICIADO;
        }
        if (!empty($idPessoa)){
            $whereArgs[] = "MSE.COD_PESSOA = $idPessoa";
        }

        $where = implode(" AND ", $whereArgs);
        $sql = "SELECT 
                      E.COD_EXPEDICAO, MAX(C.COD_CARGA_EXTERNO) COD_CARGA_EXTERNO, 
                      I.DSC_ITINERARIO,  MAX(C.DSC_PLACA_CARGA) DSC_PLACA_CARGA, 
                      P.NOM_PESSOA, MSE.NUM_SEQUENCIA,  MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE,
                      PE.DSC_ENDERECO, MAX(PE.NUM_ENDERECO) NUM_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, 
                      SIGLA.COD_REFERENCIA_SIGLA, MIN(PED.COD_EXTERNO) AS COD_PEDIDO,
                      MSE.POS_VOLUME, E.COUNT_VOLUMES, MSE.POS_ENTREGA, MSE.TOTAL_ENTREGA,
                      NVL(R.NUM_SEQ, 0) SEQ_ROTA, NVL(PR.NUM_SEQ, 0) SEQ_PRACA,
                      NVL(R.NOME_ROTA, '') NOME_ROTA, NVL(PR.NOME_PRACA, '') NOME_PRACA,
                      TO_CHAR(OS.DTH_FINAL_ATIVIDADE, 'DD/MM/YYYY HH24:MI:SS') DTH_FECHAMENTO,
                      OP.NOM_PESSOA AS CONFERENTE, B.DSC_BOX
                 FROM MAPA_SEPARACAO MS
           INNER JOIN MAPA_SEPARACAO_EMB_CLIENTE MSE ON MSE.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
           INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
           INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
           INNER JOIN PEDIDO PED ON PED.COD_CARGA = C.COD_CARGA
           INNER JOIN PESSOA P ON P.COD_PESSOA = MSE.COD_PESSOA AND P.COD_PESSOA = PED.COD_PESSOA
           INNER JOIN CLIENTE CL ON CL.COD_PESSOA = PED.COD_PESSOA
           INNER JOIN ORDEM_SERVICO OS ON OS.COD_OS = MSE.COD_OS
           INNER JOIN PESSOA OP ON OP.COD_PESSOA = OS.COD_PESSOA
            LEFT JOIN ROTA R ON R.COD_ROTA = CL.COD_ROTA
            LEFT JOIN PRACA PR ON CL.COD_PRACA = PR.COD_PRACA
            LEFT JOIN BOX B ON B.COD_BOX = E.COD_BOX
            LEFT JOIN PEDIDO_ENDERECO PE ON PE.COD_PEDIDO = PED.COD_PEDIDO
            LEFT JOIN SIGLA ON SIGLA.COD_SIGLA = PE.COD_UF
            LEFT JOIN ITINERARIO I ON PED.COD_ITINERARIO = I.COD_ITINERARIO
            LEFT JOIN MAPA_SEPARACAO_CONFERENCIA MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE = MSC.COD_MAPA_SEPARACAO_EMBALADO
                WHERE $where
             GROUP BY E.COD_EXPEDICAO, I.DSC_ITINERARIO, P.NOM_PESSOA, MSE.NUM_SEQUENCIA, MSE.COD_MAPA_SEPARACAO_EMB_CLIENTE, MSE.POS_ENTREGA, MSE.TOTAL_ENTREGA,
                      PE.DSC_ENDERECO, PE.NOM_BAIRRO, PE.NOM_LOCALIDADE, SIGLA.COD_REFERENCIA_SIGLA, MSE.POS_VOLUME, R.NUM_SEQ, PR.NUM_SEQ, E.COUNT_VOLUMES,
                      NVL(R.NOME_ROTA, ''), NVL(PR.NOME_PRACA, ''), OS.DTH_FINAL_ATIVIDADE, OP.NOM_PESSOA, B.DSC_BOX
             ORDER BY TO_NUMBER(MSE.NUM_SEQUENCIA), TO_NUMBER(NVL(MSE.POS_VOLUME, 0))";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutosConferidosByCliente($idMapa, $idPessoa)
    {
        $sql = "SELECT SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) QTD_SEPARAR, NVL(MSC.QTD_CONFERIDA,0) QTD_CONFERIDA, MSP.COD_PRODUTO,
                MSP.DSC_GRADE, PESSOA.COD_PESSOA, PESSOA.NOM_PESSOA
                FROM MAPA_SEPARACAO_PRODUTO MSP
                INNER JOIN PEDIDO_PRODUTO PP ON PP.COD_PEDIDO_PRODUTO = MSP.COD_PEDIDO_PRODUTO
                INNER JOIN PEDIDO P ON PP.COD_PEDIDO = P.COD_PEDIDO AND P.COD_PESSOA = $idPessoa
                INNER JOIN MAPA_SEPARACAO MS ON MSP.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                LEFT JOIN (
                  SELECT SUM(MSC.QTD_EMBALAGEM * MSC.QTD_CONFERIDA) QTD_CONFERIDA, MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO
                  FROM MAPA_SEPARACAO_CONFERENCIA MSC
                  INNER JOIN MAPA_SEPARACAO MS ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                  WHERE MS.COD_MAPA_SEPARACAO = $idMapa
                  GROUP BY MSC.COD_PRODUTO, MSC.DSC_GRADE, MS.COD_MAPA_SEPARACAO ) MSC ON MSC.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO AND MSC.COD_PRODUTO = MSP.COD_PRODUTO AND MSC.DSC_GRADE = MSP.DSC_GRADE
                LEFT JOIN (
                  SELECT MS.COD_MAPA_SEPARACAO, P.COD_PESSOA, P.NOM_PESSOA
                  FROM MAPA_SEPARACAO MS
                  INNER JOIN EXPEDICAO E ON MS.COD_EXPEDICAO = E.COD_EXPEDICAO
                  INNER JOIN CARGA C ON E.COD_EXPEDICAO = C.COD_EXPEDICAO
                  INNER JOIN PEDIDO PED ON PED.COD_CARGA = C.COD_CARGA
                  INNER JOIN PESSOA P ON P.COD_PESSOA = PED.COD_PESSOA WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND P.COD_PESSOA = $idPessoa ) PESSOA ON PESSOA.COD_MAPA_SEPARACAO = MS.COD_MAPA_SEPARACAO
                WHERE MS.COD_MAPA_SEPARACAO = $idMapa AND PESSOA.COD_PESSOA = $idPessoa
                GROUP BY MSP.COD_PRODUTO, MSP.DSC_GRADE, MSC.QTD_CONFERIDA, PESSOA.COD_PESSOA, PESSOA.NOM_PESSOA
                  HAVING SUM(DISTINCT MSP.QTD_EMBALAGEM * MSP.QTD_SEPARAR - NVL(MSP.QTD_CORTADO,0)) - NVL(MSC.QTD_CONFERIDA,0) > 0";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * @param $cpfEmbalador
     * @param $idExpedicao
     * @param bool $cine (Create If Not Exist)
     * @return OrdemServico
     * @throws \Exception
     */
    public function getOsEmbalagem($cpfEmbalador, $idExpedicao, $cine = false)
    {
        /** @var UsuarioRepository $usuarioRepo */
        $pessoa = $this->_em->getRepository("wms:Usuario")->getPessoaByCpf($cpfEmbalador);
        if (empty($pessoa)) throw new \Exception("Nenhum usuário encontrado com esse CPF: $cpfEmbalador");

        $idPessoa = $pessoa[0]['COD_PESSOA'];
        /** @var OrdemServico[] $arrOs */
        $arrOs = $this->_em->getRepository("wms:OrdemServico")->findBy([
            "pessoa" => $idPessoa,
            "atividade" => Atividade::EMBALAGEM_EXPEDICAO,
            "idExpedicao" => $idExpedicao], ['dataFinal'=> 'DESC']);

        if (!empty($arrOs)) {
            $lastOsFinalizacao = $arrOs[0]->getDataFinal();
            if (empty($lastOsFinalizacao)) return $arrOs[0];
        }

        if ($cine) {
            return self::addNewOsEmbalagem($idPessoa, $idExpedicao);
        }

        throw new \Exception("Nenhuma Ordem de Serviço aberta para embalamento de checkout foi encontrada para essa pessoa nessa expedição");
    }

    /**
     * @param $idPessoa
     * @param $idExpedicao
     * @return OrdemServico
     * @throws \Doctrine\ORM\ORMException
     */
    public function addNewOsEmbalagem($idPessoa, $idExpedicao)
    {
        /** @var OrdemServico $newOsEn */
        $newOsEn = $this->_em->getRepository("wms:OrdemServico")->addNewOs([
            "dataInicial" => new \DateTime(),
            "pessoa" => $this->_em->getReference('wms:Pessoa', $idPessoa),
            "atividade" => $this->_em->getReference('wms:Atividade', Atividade::EMBALAGEM_EXPEDICAO),
            "formaConferencia" => OrdemServico::MANUAL,
            "dscObservacao" => "Embalamento no Checkout",
            "expedicao" => $this->_em->getReference('wms:Expedicao', $idExpedicao)
        ], false);

        return $newOsEn;
    }
}

