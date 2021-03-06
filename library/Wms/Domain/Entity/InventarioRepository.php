<?php

namespace Wms\Domain\Entity;

use Doctrine\ORM\EntityRepository,
    Wms\Domain\Entity\Atividade as AtividadeEntity,
    Wms\Domain\Entity\OrdemServico as OrdemServicoEntity;
use Wms\Domain\Entity\Enderecamento\HistoricoEstoque;
use Wms\Domain\Entity\Inventario\EnderecoProduto;
use Wms\Service\Estoque;
use Wms\Service\Mobile\Inventario as InventarioService;

class InventarioRepository extends EntityRepository {

    public function adicionaEstoqueContagemInicial($inventarioEn) {

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
            $records = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

            $contagemEndRepo = $this->getEntityManager()->getRepository("wms:Inventario\ContagemEndereco");
            $inventarioEndRepo = $this->getEntityManager()->getRepository("wms:Inventario\Endereco");

            $idContagemOs = $this->criarOS($inventarioEn->getId());

            $this->getEntityManager()->beginTransaction();
            foreach ($records as $row) {

                $idVolume = $row['COD_PRODUTO_VOLUME'];

                $idEmbalagem = null;
                if ($row['COD_PRODUTO_EMBALAGEM'] != null) {
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
                        ), false);
                $inventarioEndEn = $inventarioEndRepo->find($row['COD_INVENTARIO_ENDERECO']);
                $inventarioEndEn->setDivergencia(1);
                $contagemEndEn->setQtdDivergencia($row['QTD']);
                $contagemEndEn->setDivergencia(1);
                $this->getEntityManager()->persist($inventarioEndEn);
                $this->getEntityManager()->persist($contagemEndEn);
            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw new \Exception($e->getMessage());
        }
    }

    public function getInventarios($criterio = null, $dados = array()) {
        $where = "WHERE 1=1 ";
        if (!empty($dados['inventario'])) {
            $where .= " AND I.COD_INVENTARIO = " . $dados['inventario'];
        }
        if (!empty($dados['status'])) {
            $where .= " AND I.COD_STATUS = " . $dados['status'];
        }
        if (isset($dados['dataInicial1']) && (!empty($dados['dataInicial1']))) {
            $where .= " AND I.DTH_INICIO >= TO_DATE('" . $dados['dataInicial1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }
        if (isset($dados['dataInicial2']) && (!empty($dados['dataInicial2']))) {
            $where .= " AND I.DTH_INICIO <= TO_DATE('" . $dados['dataInicial2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($dados['dataFinal1']) && (!empty($dados['dataFinal1']))) {
            $where .= " AND I.DTH_FINALIZACAO >= TO_DATE('" . $dados['dataFinal1'] . " 00:00', 'DD-MM-YYYY HH24:MI')";
        }

        if (isset($dados['dataFinal2']) && (!empty($dados['dataFinal2']))) {
            $where .= " AND I.DTH_FINALIZACAO <= TO_DATE('" . $dados['dataFinal2'] . " 23:59', 'DD-MM-YYYY HH24:MI')";
        }
        $subQuery = " ";
        $SQLWhere = "";

        if (!empty($dados['produto'])) {
            $SQLWhere .= " AND ICE.COD_PRODUTO = " . $dados['produto'];
        }
        if (isset($dados['rua']) && !empty($dados['rua'])) {
            $SQLWhere .= " AND DE.NUM_RUA >= " . $dados['rua'];
        }
        if (isset($dados['predio']) && !empty($dados['predio'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO >= " . $dados['predio'];
        }
        if (isset($dados['nivel']) && !empty($dados['nivel'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL >= " . $dados['nivel'];
        }
        if (isset($dados['apto']) && !empty($dados['apto'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO >= " . $dados['apto'];
        }
        if (isset($dados['ruaFinal']) && !empty($dados['ruaFinal'])) {
            $SQLWhere .= " AND DE.NUM_RUA <= " . $dados['ruaFinal'];
        }
        if (isset($dados['predioFinal']) && !empty($dados['predioFinal'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO <= " . $dados['predioFinal'];
        }
        if (isset($dados['nivelFinal']) && !empty($dados['nivelFinal'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL <= " . $dados['nivelFinal'];
        }
        if (isset($dados['aptoFinal']) && !empty($dados['aptoFinal'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO <= " . $dados['aptoFinal'];
        }
        if (!empty($SQLWhere)) {
            $subQuery .= "INNER JOIN (SELECT COD_INVENTARIO
                               FROM INVENTARIO_ENDERECO IE 
                               INNER JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON (IE.COD_INVENTARIO_ENDERECO = ICE.COD_INVENTARIO_ENDERECO)
                               INNER JOIN DEPOSITO_ENDERECO DE ON (IE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO)
                              WHERE 1 = 1 $SQLWhere                             
                              GROUP BY COD_INVENTARIO
                  ) PRODUTO ON PRODUTO.COD_INVENTARIO = I.COD_INVENTARIO";
        }
        if($criterio != null ){
            $where = $criterio;
        }
        $SQL = "SELECT COD_INVENTARIO,
                       STATUS,
                       QTD_END_TOTAL,
                       QTD_DIV_TOTAL,
                       QTD_INV_TOTAL,
                       DTH_INICIO,
                       COD_INVENTARIO_ERP,
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
                                TO_CHAR(I.DTH_INICIO,'DD/MM/YYYY HH24:MI') as DTH_INICIO ,
                                TO_CHAR(I.DTH_FINALIZACAO,'DD/MM/YYYY HH24:MI') as DTH_FINALIZACAO,
                                I.COD_INVENTARIO_ERP
                            FROM 
                                INVENTARIO I
                                LEFT JOIN SIGLA S ON S.COD_SIGLA = I.COD_STATUS
                                LEFT JOIN 
                                        (
                                            SELECT COUNT(*) as QTD,
                                                COD_INVENTARIO
                                            FROM 
                                                INVENTARIO_ENDERECO
                                            GROUP BY 
                                                COD_INVENTARIO
                                        ) QTD_IE ON QTD_IE.COD_INVENTARIO = I.COD_INVENTARIO
                                LEFT JOIN 
                                        (
                                            SELECT 
                                                COUNT(*) as QTD,
                                                COD_INVENTARIO
                                            FROM 
                                                INVENTARIO_ENDERECO
                                            WHERE 
                                                DIVERGENCIA = 1
                                            GROUP BY 
                                                COD_INVENTARIO
                                        ) QTD_DIV ON QTD_DIV.COD_INVENTARIO = I.COD_INVENTARIO
                                LEFT JOIN 
                                        (
                                            SELECT 
                                                COUNT(*) as QTD,
                                                COD_INVENTARIO
                                            FROM 
                                                INVENTARIO_ENDERECO
                                            WHERE 
                                                INVENTARIADO = 1
                                            GROUP BY 
                                                COD_INVENTARIO
                                        ) QTD_INV ON QTD_INV.COD_INVENTARIO = I.COD_INVENTARIO
                                $subQuery
                             
                                $where
                        ) I
          ORDER BY 
            COD_INVENTARIO DESC, 
            SEQUENCIA DESC";
        $records = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $result = array();
        foreach ($records as $row) {

            $andamento = 0;
            if ($row['QTD_END_TOTAL'] > 0) {
                $andamento = ($row['QTD_INV_TOTAL'] / $row['QTD_END_TOTAL']);
            }
            if ($row['STATUS'] == 'FINALIZADO')
                $andamento = 1;

            $andamento = number_format($andamento, 2) * 100;
            $values = array(
                'id' => $row['COD_INVENTARIO'],
                'qtdEndereco' => $row['QTD_END_TOTAL'],
                'qtdDivergencia' => $row['QTD_DIV_TOTAL'],
                'qtdInventariado' => $row['QTD_INV_TOTAL'],
                'andamento' => $andamento,
                'dataInicio' => $row['DTH_INICIO'],
                'dataFinalizacao' => $row['DTH_FINALIZACAO'],
                'status' => $row['STATUS'],
                'codInvERP' => $row['COD_INVENTARIO_ERP']);
            $result[] = $values;
        }
        return $result;
    }

    /**
     * @return Inventario
     * @throws \Exception
     */
    public function save() {
        $em = $this->getEntityManager();

        $em->beginTransaction();
        try {

            $enInventario = new Inventario();

            $statusEntity = $em->getReference('wms:Util\Sigla', Inventario::STATUS_GERADO);
            $enInventario->setStatus($statusEntity);
            $enInventario->setDataInicio(new \DateTime);

            $em->persist($enInventario);
            $em->flush();
            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();
            throw new \Exception($e->getMessage());
        }

        return $enInventario;
    }

    public function vinculaEnderecos($codEnderecos, $codInventario) {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        /** @var \Wms\Domain\Entity\Inventario\EnderecoProdutoRepository $enderecoProdutoRepo */
        $enderecoProdutoRepo = $this->_em->getRepository('wms:Inventario\EnderecoProduto');

        $enderecosSalvos = array();
        foreach ($codEnderecos as $chave) {

            //list ($codEndereco, $codProduto, $grade) = explode("%#%",$chave);

            $dados = explode("%#%", $chave);
            $codEndereco = null;
            $codProduto = null;
            $grade = null;
            if (isset($dados[0])) {
                $codEndereco = $dados[0];
            }
            if (isset($dados[1])) {
                $codProduto = $dados[1];
            }
            if (isset($dados[2])) {
                $grade = $dados[2];
            }

            $enderecoEn = $enderecoRepo->findOneBy(array('inventario' => $codInventario, 'depositoEndereco' => $codEndereco));
            //não adiciona 2x o mesmo endereço
            if (count($enderecoEn) == 0 && !in_array($codEndereco, $enderecosSalvos) && empty($enderecoEn)) {
                $enderecoEn = $enderecoRepo->save(array('codInventario' => $codInventario, 'codDepositoEndereco' => $codEndereco));
                $enderecosSalvos[] = $codEndereco;
            }

            if (!isset($enderecoEn) || empty($enderecoEn)) {
                continue;
            }

            if (isset($codProduto) && ($codProduto != null)) {
                $enderecoProdutoRepo->save($codProduto, $grade, $enderecoEn);
            }
        }

        $this->_em->flush();
    }

    /**
     * @param null $status
     * @return array
     */
    public function getByStatus($status = null) {
        $source = $this->getEntityManager()->createQueryBuilder()
                ->select('i.id, i.dataInicio, i.codStatus')
                ->from('wms:Inventario', 'i')
                ->orderBy("i.id", "DESC");

        if (is_array($status)) {
            $status = implode(',', $status);
            $source->andWhere("i.status in ($status)");
        } else if ($status) {
            $source->andWhere("i.status = :status")
                    ->setParameter('status', $status);
        }

        return $source->getQuery()->getArrayResult();
    }

    public function criarOS($idInventario) {
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
    public function verificaOSUsuario($idInventario) {
        $idPessoa = \Zend_Auth::getInstance()->getIdentity()->getId();
        $source = $this->_em->createQueryBuilder()
                ->select('ios.id')
                ->from('wms:Inventario\ContagemOs', 'ios')
                ->innerJoin('ios.os', 'os')
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
    public function getAvariados($idInventario) {
        $source = $this->_em->createQueryBuilder()
                ->select('de.descricao Endereco, ce.codProduto Produto, ce.grade Grade, ce.qtdAvaria Qtde_Avaria')
                ->from('wms:Inventario', 'i')
                ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'i.id = ie.inventario')
                ->innerJoin('wms:Inventario\ContagemEndereco', 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
                ->innerJoin('ie.depositoEndereco', 'de')
                ->where('i.id = :idInventario')
                ->andWhere('ce.qtdAvaria is not null')
                ->setParameter('idInventario', $idInventario);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $idInventario
     * @return array
     */
    public function getDivergencias($idInventario) {
        $source = $this->_em->createQueryBuilder()
                ->select('de.descricao Endereco, ce.codProduto Produto, ce.grade Grade, NVL(pe.codigoBarras, pv.codigoBarras) Codigo_Barras, ce.qtdContada Qtde_Contada, ce.qtdDivergencia Qtde_Divergencia, ce.numContagem, p.descricao')
                ->from('wms:Inventario', 'i')
                ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'i.id = ie.inventario')
                ->innerJoin('wms:Inventario\ContagemEndereco', 'ce', 'WITH', 'ie.id = ce.inventarioEndereco')
                ->innerJoin('ce.produto', 'p')
                ->leftJoin('wms:Produto\Embalagem', 'pe', 'WITH', "pe.codProduto = p.id AND pe.grade = p.grade AND pe.isPadrao = 'S' and pe.dataInativacao IS NULL and pe.id = ce.codProdutoEmbalagem")
                ->leftJoin('wms:Produto\Volume', 'pv', 'WITH', 'pv.codProduto = p.id AND pv.grade = p.grade and pv.dataInativacao IS NULL and pv.id = ce.codProdutoVolume')
                ->innerJoin('ie.depositoEndereco', 'de')
                ->where('i.id = :idInventario')
                ->andWhere('ce.divergencia is not null')
                ->groupBy('de.descricao, ce.codProduto, ce.grade, pe.codigoBarras, pv.codigoBarras, ce.qtdContada, ce.qtdDivergencia, ce.numContagem, p.descricao')
                ->orderBy('de.descricao')
                ->setParameter('idInventario', $idInventario);

        return $source->getQuery()->getArrayResult();
    }

    /**
     * @param $inventarioEntity
     * @param $status
     * @return bool
     */
    public function alteraStatus($inventarioEntity, $status) {
        $statusEntity = $this->_em->getReference('wms:Util\Sigla', $status);
        $inventarioEntity->setStatus($statusEntity);
        $this->_em->persist($inventarioEntity);
        $this->_em->flush();
        return true;
    }

    public function cancelar($inventarioEntity) {
        $inventarioEntity->setCodInventarioERP(null);
        $this->alteraStatus($inventarioEntity, Inventario::STATUS_CANCELADO);
    }

    public function atualizarEstoque($inventarioEntity) {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->_em->getRepository('wms:Enderecamento\Estoque');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $idUsuarioLogado = \Zend_Auth::getInstance()->getIdentity()->getId();
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $usuarioRepo = $this->_em->getRepository('wms:Usuario');
        $usuarioEn = $usuarioRepo->find($idUsuarioLogado);

        $serviceInventario = new InventarioService();

        $invEnderecosEn = $enderecoRepo->getComContagem($inventarioEntity->getId());
        foreach ($invEnderecosEn as $invEnderecoEn) {

            //ultima contagem
            $contagemEndEnds = $enderecoRepo->getUltimaContagem($invEnderecoEn);

            $enderecoEn = $invEnderecoEn->getDepositoEndereco();
            $idDepositoEndereco = $enderecoEn->getId();

            foreach ($contagemEndEnds as $contagemEndEn) {
                //Endereco tem estoque?

                $osEn = $contagemEndEn->getContagemOs()->getOs();
                $enderecoVazio = false;

                if ($contagemEndEn->getCodProdutoVolume() != null) {
                    $estoqueEntities = $estoqueRepo->findBy(array('depositoEndereco' => $idDepositoEndereco, 'produtoVolume' => $contagemEndEn->getCodProdutoVolume()));
                } elseif ($contagemEndEn->getCodProdutoEmbalagem() != null) {
                    $estoqueEntities = $estoqueRepo->findBy(array('depositoEndereco' => $idDepositoEndereco, 'codProduto' => $contagemEndEn->getCodProduto(), 'grade' => $contagemEndEn->getGrade()));
                } else {
                    $estoqueEntities = $estoqueRepo->findBy(array('depositoEndereco' => $idDepositoEndereco));
                    $enderecoVazio = true;
                }

                $qtdContagem = ($contagemEndEn->getQtdContada() + $contagemEndEn->getQtdAvaria());
                if (count($estoqueEntities) > 0) {
                    //mesmo produto?
                    foreach ($estoqueEntities as $estoqueEn) {
                        $result = $serviceInventario->compareProduto($estoqueEn, $contagemEndEn);
                        if ($result == true) {
                            $qtd = $qtdContagem - $estoqueEn->getQtd();
                            $validadeContagem = $contagemEndEn->getValidade();
                            $validadeEstoque = $estoqueEn->getValidade();
                            if (!empty($validadeContagem)) {
                                $validadeContagem = strtotime($contagemEndEn->getValidade());
                            }
                            if (!empty($validadeEstoque)) {
                                $validadeEstoque = strtotime($estoqueEn->getValidade()->format('Y-m-d 00:00:00'));
                            }
                            if ($qtd != 0 || $validadeContagem != $validadeEstoque) {
                                $this->entradaEstoque($contagemEndEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo);
                            }
                        } else {
                            if ($enderecoVazio) {
                                $qtdRetirar = $estoqueEn->getQtd();
                                $this->retiraEstoque($estoqueEn, $invEnderecoEn, -$qtdRetirar, $osEn, $usuarioEn, $estoqueRepo);
                            } else {
                                $this->retiraEstoque($estoqueEn, $invEnderecoEn, -$qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                                $this->entradaEstoque($contagemEndEn, $invEnderecoEn, $qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                            }
                        }
                    }
                } else {
                    if ($qtdContagem != 0) {
                        $this->entradaEstoque($contagemEndEn, $invEnderecoEn, $qtdContagem, $osEn, $usuarioEn, $estoqueRepo);
                    }
                }
            }
        }
        $inventarioEntity->setDataFinalizacao(new \DateTime());
        $this->alteraStatus($inventarioEntity, Inventario::STATUS_FINALIZADO);
        $this->_em->persist($inventarioEntity);
        $this->_em->flush();
    }

    public function entradaEstoque($contagemEndEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo) {
        $params['contagemEndEn'] = $contagemEndEn;
        $params['produto'] = $contagemEndEn->getProduto();
        $params['endereco'] = $invEnderecoEn->getDepositoEndereco();
        $params['qtd'] = $qtd;
        $params['volume'] = $contagemEndEn->getProdutoVolume();
        $params['embalagem'] = $contagemEndEn->getCodProdutoEmbalagem();
        $params['validade'] = $contagemEndEn->getValidade();
        $params['tipo'] = HistoricoEstoque::TIPO_INVENTARIO;
        ;
        $params['observacoes'] = 'Mov. correção inventário ' . $invEnderecoEn->getInventario()->getId();
        $params['os'] = $osEn;
        $params['usuario'] = $usuarioEn;
        $params['estoqueRepo'] = $estoqueRepo;

        $serviceEstoque = new Estoque($this->getEntityManager(), $params);
        return $serviceEstoque->movimentaEstoque();
    }

    public function retiraEstoque($estoqueEn, $invEnderecoEn, $qtd, $osEn, $usuarioEn, $estoqueRepo) {
        $params['produto'] = $estoqueEn->getProduto();
        $params['endereco'] = $invEnderecoEn->getDepositoEndereco();
        $params['qtd'] = $qtd;
        $params['volume'] = $estoqueEn->getProdutoVolume();
        $params['embalagem'] = 0;
        $params['validade'] = null;
        $params['tipo'] = HistoricoEstoque::TIPO_INVENTARIO;
        $params['observacoes'] = 'Mov. correção inventário ' . $invEnderecoEn->getInventario()->getId();
        $params['os'] = $osEn;
        $params['usuario'] = $usuarioEn;
        $params['estoqueRepo'] = $estoqueRepo;

        $serviceEstoque = new Estoque($this->getEntityManager(), $params);
        return $serviceEstoque->movimentaEstoque();
    }

    public function getSumarioByRua($params) {
        $idInventario = $params['id'];

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
             G.COD_INVENTARIO = " . $idInventario . "
            GROUP BY F.NUM_RUA
            ORDER BY F.NUM_RUA
        ";

        return $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function verificaReservas($idInventario) {
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
            CASE WHEN re.tipoReserva = 'S' then 'Saída' ELSE 'Entrada' END as tipoReserva,
            NVL(ped.id,'') as pedido
            ")
                ->from("wms:Ressuprimento\ReservaEstoque", "re")
                ->innerJoin('re.endereco', 'd')
                ->innerJoin('wms:Inventario\Endereco', 'ie', 'WITH', 'ie.depositoEndereco = d.id')
                ->leftJoin('wms:Ressuprimento\ReservaEstoqueExpedicao', 'reexp', 'WITH', 'reexp.reservaEstoque = re.id')
                ->leftJoin('wms:Ressuprimento\ReservaEstoqueEnderecamento', 'reend', 'WITH', 'reend.reservaEstoque = re.id')
                ->leftJoin('wms:Ressuprimento\ReservaEstoqueOnda', 'reond', 'WITH', 'reond.reservaEstoque = re.id')
                ->leftJoin('reexp.pedido', 'ped')
                ->leftJoin('reexp.expedicao', 'exp')
                ->leftJoin('reond.ondaRessuprimentoOs', 'ressup')
                ->leftJoin('reend.palete', 'palete')
                ->leftJoin('re.produtos', 'rep')
                ->leftJoin('rep.produto', 'prod')
                ->andWhere("re.atendida = 'N'")
                ->andWhere("ie.inventario = $idInventario")
                ->distinct(true);

        return $source->getQuery()->getResult();
    }

    public function removeEnderecos(array $enderecos, $id) {
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\ContagemEnderecoRepository $inventarioContagemEnderecoRepo */
        $inventarioContagemEnderecoRepo = $this->_em->getRepository('wms:Inventario\ContagemEndereco');
        foreach ($enderecos as $endereco) {
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

    public function bloqueiaEnderecos($id) {
        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');

        $inventarioEndsEn = $inventarioEndRepo->findBy(array('inventario' => $id));
        foreach ($inventarioEndsEn as $invEndEn) {
            $enderecoRepo->bloqueiaOuDesbloqueiaInventario($invEndEn->getDepositoEndereco(), 'S');
        }
        $this->_em->flush();
    }

    public function desbloqueiaEnderecos($id) {
//        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
//        $enderecoRepo = $this->_em->getRepository('wms:Deposito\Endereco');
//        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $inventarioEndRepo */
//        $inventarioEndRepo = $this->_em->getRepository('wms:Inventario\Endereco');
//
//        $inventarioEndsEn = $inventarioEndRepo->findBy(array('inventario' => $id));
//        foreach ($inventarioEndsEn as $invEndEn) {
//            $enderecoRepo->bloqueiaOuDesbloqueiaInventario($invEndEn->getDepositoEndereco(), 'N', false);
//        }
//        $this->_em->flush();

        $sql = "UPDATE DEPOSITO_ENDERECO SET IND_DISPONIVEL = 'S'
                 WHERE COD_DEPOSITO_ENDERECO IN (
                SELECT COD_DEPOSITO_ENDERECO 
                  FROM INVENTARIO_ENDERECO 
                 WHERE COD_INVENTARIO = $id
                   AND COD_DEPOSITO_ENDERECO NOT IN (SELECT COD_DEPOSITO_ENDERECO 
                                                       FROM ESTOQUE))";

        $procedure = $this->_em->getConnection()->prepare($sql);
        $procedure->execute();
        $this->_em->flush();

    }

    public function impressaoInventarioByEndereco($params, $idInventario) {
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

    public function getMovimentacaoEstoqueByInventario($idInventario) {
        $SQL = "SELECT ESTQ.COD_INVENTARIO as \"Inventário\",
                       DE.DSC_DEPOSITO_ENDERECO as \"Endereço\",
                       ESTQ.COD_PRODUTO as \"Cod.Produto\",
                       P.DSC_PRODUTO as \"Produto\",
                       INV.QTD_CONFERIDA - ESTQ.QTD_MOVIMENTADA AS \"Estq.Inicial\",
                       INV.QTD_CONFERIDA as \"Qtd.Conf.\",
                       CASE WHEN ESTQ.QTD_MOVIMENTADA >0 THEN 'Entrada' ELSE 'Saída' END as \"Tipo Mov.\",
                       ESTQ.QTD_MOVIMENTADA as \"Qtd.Mov.\"
                  FROM (SELECT COD_INVENTARIO, COD_DEPOSITO_ENDERECO, COD_PRODUTO, DSC_GRADE, QTD_MOVIMENTADA
                          FROM (SELECT OS.COD_INVENTARIO, HE.COD_DEPOSITO_ENDERECO, HE.COD_PRODUTO, HE.DSC_GRADE, HE.COD_PRODUTO_VOLUME,
                                       CASE WHEN COD_PRODUTO_EMBALAGEM IS NOT NULL THEN 0 ELSE NULL END AS COD_PRODUTO_EMBALAGEM,
                                       SUM(HE.QTD) as QTD_MOVIMENTADA
                                  FROM HISTORICO_ESTOQUE HE
                                 INNER JOIN INVENTARIO_CONTAGEM_OS OS ON OS.COD_OS = HE.COD_OS
                                 GROUP BY OS.COD_INVENTARIO, HE.COD_DEPOSITO_ENDERECO, HE.COD_PRODUTO, HE.DSC_GRADE, HE.COD_PRODUTO_VOLUME, HE.COD_PRODUTO_EMBALAGEM) HE
                         GROUP BY COD_INVENTARIO, COD_DEPOSITO_ENDERECO, COD_PRODUTO, DSC_GRADE, QTD_MOVIMENTADA) ESTQ
             LEFT JOIN (SELECT COD_INVENTARIO, COD_DEPOSITO_ENDERECO, ICE.COD_PRODUTO, ICE.DSC_GRADE,
                               NVL(ICE.QTD_AVARIA,0) + NVL(ICE.QTD_CONTADA,0) as QTD_CONFERIDA
                          FROM INVENTARIO_ENDERECO IE 
                         INNER JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON ICE.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                         INNER JOIN (SELECT MAX(COD_INV_CONT_END) ID, COD_PRODUTO, DSC_GRADE, COD_PRODUTO_EMBALAGEM, COD_PRODUTO_VOLUME, COD_INVENTARIO_ENDERECO
                                       FROM INVENTARIO_CONTAGEM_ENDERECO              
                                      GROUP BY COD_PRODUTO, DSC_GRADE, COD_PRODUTO_EMBALAGEM, COD_PRODUTO_VOLUME, COD_INVENTARIO_ENDERECO) MID 
                                 ON MID.ID = ICE.COD_INV_CONT_END
                         WHERE IE.INVENTARIADO = 1
                           AND IE.ATUALIZA_ESTOQUE = 1
                         GROUP BY COD_INVENTARIO, COD_DEPOSITO_ENDERECO, ICE.COD_PRODUTO, ICE.DSC_GRADE, ICE.QTD_AVARIA, ICE.QTD_CONTADA) INV
                    ON INV.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO
                   AND INV.COD_INVENTARIO = ESTQ.COD_INVENTARIO
                   AND INV.COD_PRODUTO = ESTQ.COD_PRODUTO AND INV.DSC_GRADE = ESTQ.DSC_GRADE
                   AND ESTQ.QTD_MOVIMENTADA <> 0 AND INV.QTD_CONFERIDA > 0
                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = ESTQ.COD_PRODUTO AND P.DSC_GRADE = ESTQ.DSC_GRADE
                 WHERE NOT(ESTQ.QTD_MOVIMENTADA = 0 AND INV.QTD_CONFERIDA IS NULL AND ESTQ.COD_PRODUTO IS NOT NULL)
                   AND ESTQ.COD_INVENTARIO IN ($idInventario)
                 ORDER BY DE.DSC_DEPOSITO_ENDERECO,
                          P.COD_PRODUTO,
                          P.DSC_GRADE,
                          ESTQ.COD_INVENTARIO";
        return $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function setCodInventarioERP($idInventario, $codInventarioERP) {
        if (!empty($idInventario) and ! empty($codInventarioERP)) {
            /** @var Inventario $inventarioEn */
            $inventarioEn = $this->find($idInventario);
            if (!empty($inventarioEn)) {
                $inventarioEn->setCodInventarioERP($codInventarioERP);
                $this->_em->flush($inventarioEn);
            } else {
                throw new \Exception("Nenhum inventário encontrado com o código $idInventario!");
            }
        } else {
            throw new \Exception("O número de inventário ou do código no ERP não foi informado!");
        }
    }

    /*
     * Layout de exportação definido para o Winthor
     */
    public function exportaInventarioModelo01($id) {

        /** @var \Wms\Domain\Entity\Inventario\EnderecoRepository $enderecoRepo */
        $enderecoRepo = $this->_em->getRepository('wms:Inventario\Endereco');
        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');

        $codInvErp = $this->_em->find('wms:Inventario', $id)->getCodInventarioERP();

        if (empty($codInvErp)){
            throw new \Exception("Este inventário não tem o código do inventário respectivo no ERP");
        }

        $inventariosByErp = $this->_em->getRepository('wms:Inventario')->findBy(array('codInventarioERP' => $codInvErp));

        foreach ($inventariosByErp as $inventario) {
            $inventarios[] = $inventario->getId();
        }

        $filename = "Exp_Inventario($codInvErp).txt";
        $file = fopen($filename, 'w');

        $invEnderecosEn = $enderecoRepo->getComContagem(implode(',', $inventarios));
        $qtdTotal = 0;
        $produtoAnterior = null;
        $inventario = array();
        foreach ($invEnderecosEn as $invEnderecoEn) {
            $codInventarioEnderecos[] = $invEnderecoEn->getId();
        }

        $contagemEndEnds = $enderecoRepo->getUltimaContagem(implode(',',$codInventarioEnderecos));
        foreach ($contagemEndEnds as $contagemEndEn) {
            $embalagemEntity = $embalagemRepo->findBy(array('codProduto' => $contagemEndEn->getCodProduto(), 'grade' => $contagemEndEn->getGrade()), array('quantidade' => 'ASC'));
            if (!$embalagemEntity) continue;
            if ($produtoAnterior != $contagemEndEn->getCodProduto()) $qtdTotal = 0;

            $qtdContagem = ($contagemEndEn->getQtdContada() + $contagemEndEn->getQtdAvaria());
            $qtdTotal = $qtdTotal + $qtdContagem;
            $inventario[$contagemEndEn->getCodProduto()]['QUANTIDADE'] = $qtdTotal;
            $inventario[$contagemEndEn->getCodProduto()]['NUM_CONTAGEM'] = $contagemEndEn->getNumContagem();
            $inventario[$contagemEndEn->getCodProduto()]['COD_BARRAS'] = reset($embalagemEntity)->getCodigoBarras();
            $inventario[$contagemEndEn->getCodProduto()]['FATOR'] = reset($embalagemEntity)->getQuantidade();
            $produtoAnterior = $contagemEndEn->getCodProduto();
        }

        foreach ($inventario as $key => $produto) {
            $txtCodInventario = str_pad($codInvErp, 4, '0', STR_PAD_LEFT);
            $txtContagem = '001';
            $txtLocal = '001';
            $txtCodBarras = str_pad($produto['COD_BARRAS'], 14, '0', STR_PAD_LEFT);
            $txtQtd = str_pad(number_format($produto["QUANTIDADE"] / $produto["FATOR"], 3, '', ''), 10, '0', STR_PAD_LEFT);
            $txtCodProduto = str_pad($key, 6, '0', STR_PAD_LEFT);
            $linha = $txtCodInventario.$txtContagem.$txtLocal.$txtCodBarras.$txtQtd.$txtCodProduto."\r\n";
            fwrite($file, $linha, strlen($linha));
        }

        fclose($file);

        header("Content-Type: application/force-download");
        header("Content-type: application/octet-stream;");
        header("Content-disposition: attachment; filename=" . $filename);
        header("Expires: 0");
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");

        readfile($filename);
        flush();

        unlink($filename);
        exit;
    }

    /*
     * Layout de exportação definido para a SonosShow
     */
    public function exportaInventarioModelo02($idInventario = null) {
        /*
         * Nome do arquivo solicitado pela sonoshow como aammddhh.min
         */
        $nomeArquivo = date("ymdH.0i");
        $arquivo = $this->getSystemParameterValue("DIRETORIO_IMPORTACAO") . DIRECTORY_SEPARATOR. $nomeArquivo;

        $SQL = "SELECT P.COD_PRODUTO, NVL(ESTQ.QTD,0) as QTD
                  FROM PRODUTO P
                  LEFT JOIN (SELECT E.COD_PRODUTO,
                                    E.DSC_GRADE, 
                                    MIN(QTD) as QTD
                               FROM (SELECT E.COD_PRODUTO,
                                            E.DSC_GRADE,
                                            SUM(E.QTD) as QTD,
                                            NVL(E.COD_PRODUTO_VOLUME,0) as ID_VOLUME
                                       FROM ESTOQUE E
                                            GROUP BY E.COD_PRODUTO, E.DSC_GRADE,NVL(E.COD_PRODUTO_VOLUME,0)) E
                              GROUP BY COD_PRODUTO, DSC_GRADE) ESTQ
                    ON ESTQ.COD_PRODUTO = P.COD_PRODUTO
                   AND ESTQ.DSC_GRADE = P.DSC_GRADE " ;

        if ($idInventario != null) {
            $SQL .= " INNER JOIN (SELECT ICE.COD_PRODUTO,
                                    ICE.DSC_GRADE
                               FROM INVENTARIO_ENDERECO IE
                               LEFT JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON ICE.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                              WHERE COD_INVENTARIO = $idInventario AND ICE.CONTAGEM_INVENTARIADA = 1 AND ICE.DIVERGENCIA IS NULL
                              GROUP BY ICE.COD_PRODUTO,
                                       ICE.DSC_GRADE) I
                    ON (I.COD_PRODUTO = P.COD_PRODUTO)
                   AND (I.DSC_GRADE = P.DSC_GRADE)";
        }
;
        $produtos = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $file = fopen($arquivo, "w");

        $i = 0;
        foreach ($produtos  as $produto) {
            $i ++;

            $result = fwrite($file,$produto['COD_PRODUTO'] . ";");
            $result = fwrite($file,$produto['QTD'] . ";");

            if (count($produtos) != $i) {
                $result = fwrite($file,"\r\n");
            }

        }

        $result = fwrite($file,"\r\n");
        fclose($file);
    }

    public function exportaInventarioModelo03($id)
    {
        /** @var \Wms\Domain\Entity\Inventario $inventarioEn */
        $inventarioEn = $this->_em->find('wms:Inventario', $id);
        $codInvErp = $inventarioEn->getCodInventarioERP();


        if (empty($codInvErp)) {
            throw new \Exception("Este inventário não tem o código do inventário respectivo no ERP");
        }

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->_em->getRepository('wms:Produto\Embalagem');


        $filename = "Exp_Inventario($codInvErp).txt";
        $file = fopen($filename, 'w');


        $SQL = "SELECT P.COD_PRODUTO, P.DSC_GRADE, NVL(ESTQ.QTD,0) as QTD
                  FROM PRODUTO P
                  LEFT JOIN (SELECT E.COD_PRODUTO,
                                    E.DSC_GRADE, 
                                    MIN(QTD) as QTD
                               FROM (SELECT E.COD_PRODUTO,
                                            E.DSC_GRADE,
                                            SUM(E.QTD) as QTD,
                                            NVL(E.COD_PRODUTO_VOLUME,0) as ID_VOLUME
                                       FROM ESTOQUE E
                                            GROUP BY E.COD_PRODUTO, E.DSC_GRADE,NVL(E.COD_PRODUTO_VOLUME,0)) E
                              GROUP BY COD_PRODUTO, DSC_GRADE) ESTQ
                    ON ESTQ.COD_PRODUTO = P.COD_PRODUTO
                   AND ESTQ.DSC_GRADE = P.DSC_GRADE 
                 INNER JOIN (SELECT DISTINCT 
                                    ICE.COD_PRODUTO,
                                    ICE.DSC_GRADE
                               FROM INVENTARIO_ENDERECO IE
                               LEFT JOIN INVENTARIO I ON I.COD_INVENTARIO = IE.COD_INVENTARIO
                               LEFT JOIN INVENTARIO_CONTAGEM_ENDERECO ICE ON ICE.COD_INVENTARIO_ENDERECO = IE.COD_INVENTARIO_ENDERECO
                              WHERE I.COD_INVENTARIO_ERP = $codInvErp AND ICE.CONTAGEM_INVENTARIADA = 1 AND ICE.DIVERGENCIA IS NULL
                              GROUP BY ICE.COD_PRODUTO,
                                       ICE.DSC_GRADE) I
                    ON I.COD_PRODUTO = P.COD_PRODUTO
                   AND I.DSC_GRADE = P.DSC_GRADE";
        $produtos = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $inventario = array();
        foreach ($produtos as $produto) {
            $embalagemEntity = $embalagemRepo->findBy(array('codProduto' => $produto['COD_PRODUTO'],
                'grade' => $produto['DSC_GRADE'],
                'dataInativacao' => null),
                array('quantidade' => 'ASC'));

            if (!$embalagemEntity) continue;

            $inventario[$produto['COD_PRODUTO']]['QUANTIDADE'] = $produto['QTD'];
            $inventario[$produto['COD_PRODUTO']]['NUM_CONTAGEM'] = 1;
            $inventario[$produto['COD_PRODUTO']]['COD_BARRAS'] = reset($embalagemEntity)->getCodigoBarras();
            $inventario[$produto['COD_PRODUTO']]['FATOR'] = reset($embalagemEntity)->getQuantidade();
        }
        foreach ($inventario as $key => $produto) {
            $txtCodInventario = str_pad($codInvErp, 4, '0', STR_PAD_LEFT);
            $txtContagem = '001';
            $txtLocal = '001';
            $txtCodBarras = str_pad($produto['COD_BARRAS'], 14, '0', STR_PAD_LEFT);

            if ($produto["FATOR"] == 0) {
                $produto["FATOR"] = 1;
            }

            /*
            $txtQtd = str_pad(number_format($produto["QUANTIDADE"] / $produto["FATOR"], 3, '', ''), 9, '0', STR_PAD_LEFT);
            $txtCodProduto = str_pad($key, 6, '0', STR_PAD_LEFT);
            $linha = "$txtCodInventario;" . "$txtContagem;" . "$txtCodProduto;" . "$txtCodBarras;" . "$txtQtd" . "\r\n";
            */
            $txtQtd = $produto["QUANTIDADE"] / $produto["FATOR"];

            //$txtQtd = str_pad(number_format($produto["QUANTIDADE"] / $produto["FATOR"], 3, ',', ''), 9, '0', STR_PAD_LEFT);
            $txtCodProduto = str_pad($key, 6, '0', STR_PAD_LEFT);
            $txtFator = $produto["FATOR"];
            $linha = "$txtCodInventario;" . "$txtContagem;" . "$txtCodProduto;" . "$txtCodBarras;" . "$txtQtd;" . $txtFator . "\r\n";

            fwrite($file, $linha, strlen($linha));
        }

        fclose($file);

        header("Content-Type: application/force-download");
        header("Content-type: application/octet-stream;");
        header("Content-disposition: attachment; filename=" . $filename);
        header("Expires: 0");
        header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");
        header("Pragma: no-cache");

        readfile($filename);
        flush();

        unlink($filename);
        exit;

    }
}
