<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;
use Wms\Service\Estoque;
use Wms\Service\Mobile\Inventario as InventarioService;


class InventarioRepository extends EntityRepository
{

    public function adicionaEstoqueContagemInicial($inventarioEn)
    {

        /* @ToDo Parametro
         * Pode virar parametro de acordo com o Ricardo
         * Gera Posição do Estoque como primeira contagem?
         */
        return;
        
        if ($this->getSystemParameterValue('VALIDA_ESTOQUE_ATUAL') != "S") {
            return;
        }
        
        try {

            $SQL = "SELECT IE.COD_INVENTARIO_ENDERECO,
                           E.COD_PRODUTO,
                           E.DSC_GRADE,
                           E.COD_DEPOSITO_ENDERECO,
                           NVL(E.QTD,0) as QTD,
                           E.COD_PRODUTO_VOLUME,
                           E.COD_PRODUTO_EMBALAGEM
                      FROM INVENTARIO_ENDERECO IE
                      LEFT JOIN ESTOQUE E ON E.COD_DEPOSITO_ENDERECO = IE.COD_DEPOSITO_ENDERECO
                     WHERE COD_INVENTARIO = " . $inventarioEn->getId();
            $records =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

            $contagemEndRepo = $this->getEntityManager()->getRepository("wms:Inventario\ContagemEndereco");
            $inventarioEndRepo = $this->getEntityManager()->getRepository("wms:Inventario\Endereco");

            $idContagemOs = $this->criarOS($inventarioEn->getId());

            $this->getEntityManager()->beginTransaction();
            foreach ($records as $row){

                $idVolume = $row['COD_PRODUTO_VOLUME'];

                $idEmbalagem = null;
                if ( $row['COD_PRODUTO_EMBALAGEM'] != null) {
                    $idEmbalagem = 0;
                }

                $contagemEndEn = $contagemEndRepo->save(array(
                    'qtd' => $row['QTD'],
                    'idContagemOs' => $idContagemOs,
                    'idInventarioEnd' => $row['COD_INVENTARIO_ENDERECO'],
                    'qtdAvaria' => 0,
                    'codProduto' => $row['COD_PRODUTO'],
                    'grade' => $row['DSC_GRADE'],
                    'codProdutoEmbalagem' => $idEmbalagem,
                    'codProdutoVolume' => $idVolume,
                    'numContagem' => 1
                ),
                false);
                $inventarioEndEn = $inventarioEndRepo->find($row['COD_INVENTARIO_ENDERECO']);
                $inventarioEndEn->setDivergencia(1);
                $contagemEndEn->setQtdDivergencia($row['QTD']);
                $contagemEndEn->setDivergencia(1);
                $this->getEntityManager()->persist($inventarioEndEn);
                $this->getEntityManager()->persist($contagemEndEn);

            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch(\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }

    }

    public function getInventarios($criterio = null){
        $SQL = "SELECT COD_INVENTARIO,
                       STATUS,
                       QTD_END_TOTAL,
                       QTD_DIV_TOTAL,
                       QTD_INV_TOTAL,
                       DTH_INICIO,
                       DTH_FINALIZACAO,
                       CASE WHEN STATUS = 'GERADO' THEN 1
                            WHEN STATUS = 'LIBERADO' THEN 2
                            WHEN STATUS = 'CONCLUIDO' THEN 3 
                            WHEN STATUS = 'FINALIZADO' THEN 4
                            WHEN STATUS = 'CANCELADO' THEN 5
                            ELSE 6
                       END AS SEQUENCIA
                  FROM (
               SELECT I.COD_INVENTARIO,
                       CASE WHEN (S.DSC_SIGLA = 'LIBERADO') AND (NVL(QTD_IE.QTD,0) = NVL(QTD_INV.QTD,0)) THEN 'CONCLUIDO'
                            ELSE S.DSC_SIGLA 
                       END as STATUS,
                       NVL(QTD_IE.QTD,0) as QTD_END_TOTAL,
                       NVL(QTD_DIV.QTD,0) as QTD_DIV_TOTAL,
                       NVL(QTD_INV.QTD,0) as QTD_INV_TOTAL,
                       TO_CHAR(I.DTH_INICIO,'DD-MM-YYYY-HH24-MI') as DTH_INICIO ,
                       TO_CHAR(I.DTH_FINALIZACAO,'DD/MM/YYYY HH24:MI') as DTH_FINALIZACAO
                  FROM INVENTARIO I
                  LEFT JOIN SIGLA S ON S.COD_SIGLA = I.COD_STATUS
                  LEFT JOIN (SELECT COUNT(*) as QTD,
                                    COD_INVENTARIO
                               FROM INVENTARIO_ENDERECO
                              GROUP BY COD_INVENTARIO) QTD_IE ON QTD_IE.COD_INVENTARIO = I.COD_INVENTARIO
                  LEFT JOIN (SELECT COUNT(*) as QTD,
                                    COD_INVENTARIO
                               FROM INVENTARIO_ENDERECO
                              WHERE DIVERGENCIA = 1
                              GROUP BY COD_INVENTARIO) QTD_DIV ON QTD_DIV.COD_INVENTARIO = I.COD_INVENTARIO
                  LEFT JOIN (SELECT COUNT(*) as QTD,
                                    COD_INVENTARIO
                               FROM INVENTARIO_ENDERECO
                              WHERE INVENTARIADO = 1
                              GROUP BY COD_INVENTARIO) QTD_INV ON QTD_INV.COD_INVENTARIO = I.COD_INVENTARIO
          $criterio) I
          ORDER BY SEQUENCIA, COD_INVENTARIO DESC
                              ";

        $records =  $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($records as $row){

            $andamento = 0;
            if ($row['QTD_END_TOTAL'] > 0) {
                $andamento = ($row['QTD_INV_TOTAL']/$row['QTD_END_TOTAL']);
            }
            if  ($row['STATUS'] == 'FINALIZADO') $andamento = 1;
            $dataInicioBanco = explode('-',$row['DTH_INICIO']);
            $dataInicio = new \DateTime();
            $dataInicio->setDate($dataInicioBanco[2],$dataInicioBanco[1],$dataInicioBanco[0]);
            $dataInicio->setTime($dataInicioBanco[3],$dataInicioBanco[4]);

            $dataFinal = null;
            if ($row['DTH_FINALIZACAO'] != "") {
                $dataFinalBanco = explode('-',$row['DTH_FINALIZACAO']);
                $dataFinal = new \DateTime();
                $dataFinal->setDate($dataFinalBanco[2],$dataFinalBanco[1],$dataFinalBanco[0]);
                $dataFinal->setTime($dataFinalBanco[3],$dataFinalBanco[4]);
            }

            $andamento = number_format($andamento,2)*100;
            $values = array(
                'id' => $row['COD_INVENTARIO'],
                'qtdEndereco' => $row['QTD_END_TOTAL'],
                'qtdDivergencia' => $row['QTD_DIV_TOTAL'],
                'qtdInvetariado' => $row['QTD_INV_TOTAL'],
                'andamento' =>  $andamento,
                'dataInicio' => $dataInicio,
                'dataFinalizacao' => $dataFinal,
                'status' => $row['STATUS']);
            $result[] = $values;
        }

        return $result;
    }

    /**
     * @return Inventario
     * @throws \Exception
     */
    public function save()
    {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventario = new Inventario();

            $statusEntity = $em->getReference('wms:Util\Sigla',Inventario::STATUS_GERADO);
            $enInventario->setStatus($statusEntity);
            $enInventario->setDataInicio(new \DateTime);

            $em->persist($enInventario);
            $em->flush();
            $em->commit();

        } catch(\Exception $e) {
            $em->rollback();
            throw new \Exception();
        }

        return $enInventario;
    }


    public function vinculaEnderecos($codEnderecos, $codInventario)
    {
        /** @var \Wms\Domain\Entity\Deposito\Endereco $depositoEnderecoRepo */
        $depositoEnderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $enderecosSalvos = array();
        foreach($codEnderecos as $codEndereco) {
            $enDepositoEnd = $depositoEnderecoRepo->find($codEndereco);
            if (!is_null($enDepositoEnd)) {
                $enderecoEn = $enderecoRepo->findBy(array('inventario' => $codInventario, 'depositoEndereco' => $enDepositoEnd->getId()));
                //não adiciona 2x o mesmo endereço
                if (count($enderecoEn) == 0 && !in_array($codEndereco, $enderecosSalvos)) {
                    $enderecoRepo->save(array('codInventario' => $codInventario, 'codDepositoEndereco' => $codEndereco));
                    $enderecosSalvos[] = $codEndereco;
                }
            }
        }

        if (!is_null($enDepositoEnd)) {
            $this->_em->flush();
        }

    }

    /**
     * @param null $status
     * @return array
     */
    public function getByStatus($status = null)
    {
        $source = $this->getEntityManager()->createQueryBuilder()
            ->select('i.id, i.dataInicio, i.codStatus')
            ->from('wms:Inventario', 'i')
            ->orderBy("i.id" , "DESC");

        if (is_array($status)) {
            $status = implode(',',$status);
            $source->andWhere("i.status in ($status)");
        }else if ($status) {
            $source->andWhere("i.status = :status")
                ->setParameter('status', $status);
        }

        return $source->getQuery()->getArrayResult();
    }

    public function criarOS($idInventario)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->getEntityManager()->getRepository('wms:OrdemServico');

        $contagemOsEn = $this->verificaOSUsuario($idInventario);

        if ($contagemOsEn == null) {

            // cria ordem de servico
            $idOrdemServico = $ordemServicoRepo->save(new OrdemServicoEntity, array(
                'identificacao' => array(
                    'tipoOrdem' => 'inventario',
                    'idAtividade' => AtividadeEntity::INVENTARIO,
                    'formaConferencia' => OrdemServicoEntity::COLETOR,
                ),
            ));

            /** @var \Wms\Domain\Entity\Inventario\ContagemOsRepository $contagemOsRepo */
            $contagemOsRepo = $this->getEntityManager()->getRepository('wms:Inventario\ContagemOs');
            $contagemOsEn = $contagemOsRepo->save(array('codInventario' => $idInventario, 'codOs' => $idOrdemServico));
            $idContagemOs = $contagemOsEn->getId();

        } else {
            $idContagemOs = $contagemOsEn[0]['id'];
        }
        return $idContagemOs;
    }

    /**
     * @param $idInventario
     * @return array
     */
    public function verificaOSUsuario($idInventario)
    {
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $source = $this->_em->createQueryBuilder()
            ->select('ios.id')
            ->from('wms:Inventario\ContagemOs', 'ios')
            ->innerJoin('ios.os','os')
            ->where('ios.inventario = :idInventario')
            ->andWhere('os.pessoa = :pessoa')
            ->setParameter('idInventario', $idInventario)
            ->setParameter('pessoa', $idPessoa);

        return $source->getQuery()->getResult();
    }
    /**
     * @param $idInventario
     * @return array
     */
    public function getAvariados($idInventario)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('de.descricao Endereco, ce.codProduto Produto, ce.grade Grade, ce.qtdAvaria Qtde_Avaria')
            ->from('wms:Inventario', 'i')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'i.id = ie.inventario')
            ->innerJoin('wms:Inventario\ContagemEndereco', 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->innerJoin('ie.depositoEndereco','de')
            ->where('i.id = :idInventario')
            ->andWhere('ce.qtdAvaria is not null')
            ->setParameter('idInventario', $idInventario);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $idInventario
     * @return array
     */
    public function getDivergencias($idInventario)
    {
        $source = $this->_em->createQueryBuilder()
            ->select('de.descricao Endereco, ce.codProduto Produto, ce.grade Grade, ce.qtdContada Qtde_Contada, ce.qtdDivergencia Qtde_Divergencia, ce.numContagem, p.descricao')
            ->from('wms:Inventario', 'i')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'i.id = ie.inventario')
            ->innerJoin('wms:Inventario\ContagemEndereco', 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
            ->innerJoin('ce.produto', 'p')
            ->innerJoin('ie.depositoEndereco','de')
            ->where('i.id = :idInventario')
            ->andWhere('ce.divergencia is not null')
            ->orderBy('de.descricao')
            ->setParameter('idInventario', $idInventario);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $inventarioEntity
     * @param $status
     * @return bool
     */
    public function alteraStatus($inventarioEntity, $status)
    {
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);
        $inventarioEntity->setStatus($statusEntity);
        $this->_em->persist($inventarioEntity);
        $this->_em->flush();
        return true;
    }

    public function cancelar($inventarioEntity)
    {
        $this->alteraStatus($inventarioEntity, Inventario::STATUS_CANCELADO);
    }

    public function atualizarEstoque($inventarioEntity)
    {
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->_em->getRepository('wms:Enderecamento\Estoque');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $idUsuarioLogado  = \Zend_Auth::getInstance()->getIdentity()->getId();
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $usuarioRepo = $this->_em->getRepository('wms:Usuario');
        $usuarioEn   = $usuarioRepo->find($idUsuarioLogado);

        $serviceInventario = new InventarioService();

        $invEnderecosEn = $enderecoRepo->getComContagem($inventarioEntity->getId());
        foreach($invEnderecosEn as $invEnderecoEn) {

            //ultima contagem
            $contagemEndEnds = $enderecoRepo->getUltimaContagem($invEnderecoEn);

            $enderecoEn         = $invEnderecoEn->getDepositoEndereco();
            $idDepositoEndereco = $enderecoEn->getId();

            foreach($contagemEndEnds as $contagemEndEn) {
                //Endereco tem estoque?

                $osEn           = $contagemEndEn->getContagemOs()->getOs();
                $enderecoVazio  = false;

                if ($contagemEndEn->getCodProdutoVolume() != null) {
                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'produtoVolume' => $contagemEndEn->getCodProdutoVolume()));
                } elseif($contagemEndEn->getCodProdutoEmbalagem() != null) {
                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco, 'codProduto' => $contagemEndEn->getCodProduto(), 'grade' => $contagemEndEn->getGrade()));
                } else {
                    $estoqueEn = $estoqueRepo->findOneBy(array('depositoEndereco' => $idDepositoEndereco));
                    $enderecoVazio = true;
                }

                $qtdContagem = ($contagemEndEn->getQtdContada()+$contagemEndEn->getQtdAvaria());
                if ($estoqueEn && $invEnderecoEn->getAtualizaEstoque() == 1) {
                    //mesmo produto?
                    if ($serviceInventario->compareProduto($estoqueEn,$contagemEndEn) == true) {
                        $qtd = $qtdContagem - $estoqueEn->getQtd();
                        if ($qtd != 0) {
                            $this->entradaEstoque($contagemEndEn,$invEnderecoEn,$qtd, $osEn, $usuarioEn, $estoqueRepo);
                        }
                    } else {
                        if ($enderecoVazio) {
                            $qtdRetirar = $estoqueEn->getQtd();
                            $this->retiraEstoque($estoqueEn, $invEnderecoEn, -$qtdRetirar, $osEn, $usuarioEn, $estoqueRepo);
                        } else {
                            $this->retiraEstoque($estoqueEn, $invEnderecoEn, -$qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                            $this->entradaEstoque($contagemEndEn,$invEnderecoEn, $qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                        }
                    }
                } elseif($estoqueEn == null) {
                    if ($qtdContagem != 0) {
                        $this->entradaEstoque($contagemEndEn,$invEnderecoEn,$qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                    }
                }

            }

        }
        $inventarioEntity->setDataFinalizacao(new \DateTime());
        $this->alteraStatus($inventarioEntity,Inventario::STATUS_FINALIZADO);
        $this->_em->persist($inventarioEntity);
        $this->_em->flush();
    }

    public function entradaEstoque($contagemEndEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo)
    {
        $params['contagemEndEn'] = $contagemEndEn;
        $params['produto']       = $contagemEndEn->getProduto();
        $params['endereco']      = $invEnderecoEn->getDepositoEndereco();
        $params['qtd']           = $qtd;
        $params['volume']        = $contagemEndEn->getProdutoVolume();
        $params['embalagem']     = $contagemEndEn->getCodProdutoEmbalagem();
        $params['tipo']          = 'I';
        $params['observacoes']   = 'Mov. correção inventário';
        $params['os']            = $osEn;
        $params['usuario']       = $usuarioEn;
        $params['estoqueRepo']   = $estoqueRepo;

        $serviceEstoque = new Estoque($this->getEntityManager(), $params);
        return $serviceEstoque->movimentaEstoque();
    }

    public function retiraEstoque($estoqueEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo)
    {
        $params['produto']      = $estoqueEn->getProduto();
        $params['endereco']     = $invEnderecoEn->getDepositoEndereco();
        $params['qtd']          = $qtd;
        $params['volume']       = $estoqueEn->getProdutoVolume();
        $params['embalagem']    = 0;
        $params['tipo']         = 'I';
        $params['observacoes']  = 'Mov. correção inventário';
        $params['os']           = $osEn;
        $params['usuario']      = $usuarioEn;
        $params['estoqueRepo']  = $estoqueRepo;

        $serviceEstoque = new Estoque($this->getEntityManager(), $params);
        return $serviceEstoque->movimentaEstoque();
    }

    public function getSumarioByRua($params)
    {
        $idInventario   = $params['id'];

        $sql = "
        SELECT
              F.NUM_RUA RUA,
              COUNT(G.COD_INVENTARIO) QTD_ENDERECOS,
              COUNT(G.DIVERGENCIA) QTD_DIVERGENTE,
              COUNT(G.INVENTARIADO) QTD_INVENTARIADO,
              COUNT(PENDENTES.CONT) as QTD_PENDENTE,
              round( (COUNT(G.INVENTARIADO) * 100) / COUNT(G.COD_INVENTARIO) ) CONCLUIDO
            FROM INVENTARIO_ENDERECO G
            INNER JOIN  DEPOSITO_ENDERECO F  ON F.COD_DEPOSITO_ENDERECO = G.COD_DEPOSITO_ENDERECO
            LEFT JOIN (SELECT IE.COD_INVENTARIO_ENDERECO as CONT, IE.COD_INVENTARIO_ENDERECO FROM INVENTARIO_ENDERECO IE
                  INNER JOIN DEPOSITO_ENDERECO DE ON IE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                  WHERE INVENTARIADO IS NULL AND DIVERGENCIA IS NULL
                  GROUP BY IE.COD_INVENTARIO_ENDERECO) PENDENTES
              ON PENDENTES.COD_INVENTARIO_ENDERECO = G.COD_INVENTARIO_ENDERECO
            WHERE
             G.COD_INVENTARIO = ".$idInventario."
            GROUP BY F.NUM_RUA
            ORDER BY F.NUM_RUA
        ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaReservas($idInventario)
    {
        $source = $this->_em->createQueryBuilder()
            ->select("d.id, prod.id as produto, prod.grade as grade, re.dataReserva, d.descricao,
            CONCAT(
                CASE WHEN exp.id IS NOT NULL THEN 'Expedição Código:'
                     WHEN ressup.id IS NOT NULL THEN 'Ressuprimento OS:'
                     WHEN palete.id IS NOT NULL THEN 'Palete :'
                     ELSE 'Não foi possível identificar a operação'
                END
            ,
                NVL(exp.id,NVL(ressup.id,NVL(palete.id,'')))
            ) as origemReserva,
            CASE WHEN re.tipoReserva = 'S' then 'Saída' ELSE 'Entrada' END as tipoReserva
            ")
            ->from("wms:Ressuprimento\ReservaEstoque","re")
            ->innerJoin('re.endereco', 'd')
            ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'ie.depositoEndereco = d.id')
            ->leftJoin('wms:Ressuprimento\ReservaEstoqueExpedicao','reexp','WITH','reexp.reservaEstoque = re.id')
            ->leftJoin('wms:Ressuprimento\ReservaEstoqueEnderecamento','reend','WITH','reend.reservaEstoque = re.id')
            ->leftJoin('wms:Ressuprimento\ReservaEstoqueOnda','reond','WITH','reond.reservaEstoque = re.id')
            ->leftJoin('reexp.expedicao','exp')
            ->leftJoin('reond.ondaRessuprimentoOs','ressup')
            ->leftJoin('reend.palete','palete')
            ->leftJoin('re.produtos','rep')
            ->leftJoin('rep.produto','prod')
            ->andWhere("re.atendida = 'N'")
            ->andWhere("ie.inventario = $idInventario")
            ->distinct(true);
        return $source->getQuery()->getResult();
    }

    public function removeEnderecos(array $enderecos, $id)
    {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $inventarioContagemEnderecoRepo */
        $inventarioContagemEnderecoRepo = $this->_em->getRepository('wms:Inventario\ContagemEndereco');
        foreach($enderecos as $endereco) {
            $inventarioEndEn = $inventarioEndRepo->findOneBy(array('depositoEndereco' => $endereco, 'inventario' => $id));
            if ($inventarioEndEn) {
                $inventarioContagemEnderecoEn = $inventarioContagemEnderecoRepo->findBy(array('inventarioEndereco' => $inventarioEndEn));
                foreach ($inventarioContagemEnderecoEn as $inventarioContEnd) {
                    $this->_em->remove($inventarioContEnd);
                }
                $this->_em->remove($inventarioEndEn);
            }
        }
        $this->_em->flush();
    }

    public function bloqueiaEnderecos($id)
    {
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $inventarioEndsEn  = $inventarioEndRepo->findBy(array('inventario' => $id));
        foreach($inventarioEndsEn as $invEndEn) {
            $enderecoRepo->bloqueiaOuDesbloqueiaInventario($invEndEn->getDepositoEndereco()->getID(),'S');
        }
        $this->_em->flush();
    }

    public function desbloqueiaEnderecos($id)
    {
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $inventarioEndsEn  = $inventarioEndRepo->findBy(array('inventario' => $id));
        foreach($inventarioEndsEn as $invEndEn) {
            $enderecoRepo->bloqueiaOuDesbloqueiaInventario($invEndEn->getDepositoEndereco()->getID(),'N');
        }
        $this->_em->flush();
    }

    public function impressaoInventarioByEndereco($params, $idInventario)
    {
        $sql = "SELECT DSC_DEPOSITO_ENDERECO AS ENDERECO, NVL(ICE.COD_PRODUTO,'') AS PRODUTO, NVL(ICE.DSC_GRADE,'') AS GRADE, NVL(ICE.QTD_CONTADA,'') AS QUANTIDADE
                FROM INVENTARIO I
                INNER JOIN INVENTARIO_ENDERECO IE ON IE.COD_INVENTARIO = I.COD_INVENTARIO
                INNER JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = IE.COD_DEPOSITO_ENDERECO
                LEFT JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON ICE.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                WHERE I.COD_INVENTARIO = $idInventario";

        if (!empty($params['inicialRua'])) {
            $sql .= " AND DE.NUM_RUA >= $params[inicialRua]";
        }
        if (!empty($params['finalRua'])) {
            $sql .= " AND DE.NUM_RUA <= $params[finalRua]";
        }
        if (!empty($params['inicialPredio'])) {
            $sql .= " AND DE.NUM_PREDIO >= $params[inicialPredio]";
        }
        if (!empty($params['finalPredio'])) {
            $sql .= " AND DE.NUM_PREDIO <= $params[finalPredio]";
        }
        if (!empty($params['inicialNivel'])) {
            $sql .= " AND DE.NUM_NIVEL <= $params[inicialNivel]";
        }
        if (!empty($params['finalNivel'])) {
            $sql .= " AND DE.NUM_NIVEL >= $params[finalNivel]";
        }
        if (!empty($params['inicialApartamento'])) {
            $sql .= " AND DE.NUM_APARTAMENTO >= $params[inicialApartamento]";
        }
        if (!empty($params['finalApartamento'])) {
            $sql .= " AND DE.NUM_APARTAMENTO <= $params[finalApartamento]";
        }
        if (!empty($params['lado'])) {
            if ($params['lado'] == "P")
                $sql .= " AND MOD(DE.NUM_PREDIO,2) = 0";
            if ($params['lado'] == "I")
                $sql .= " AND MOD(DE.NUM_PREDIO,2) = 1";
        }
        if ($params['status'] == 2) {
            $sql .= " AND ICE.COD_INV_CONT_END IS NOT NULL";
        } else {
            $sql .= " AND ICE.COD_INV_CONT_END IS NULL";
        }
        $sql .= " ORDER BY DSC_DEPOSITO_ENDERECO ASC";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

}