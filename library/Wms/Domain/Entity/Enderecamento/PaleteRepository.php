<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository;
use DoctrineExtensions\Versionable\Exception;
use Wms\Domain\Entity\OrdemServico as OrdemServicoEntity,
    Wms\Domain\Entity\Recebimento as RecebimentoEntity,
    Wms\Domain\Entity\Atividade as AtividadeEntity;
use Wms\Module\Web\Grid\Recebimento;

class PaleteRepository extends EntityRepository
{

    public function getQtdProdutosByRecebimento ($params)
    {
        extract($params);

        $query = "
            SELECT DISTINCT R.COD_RECEBIMENTO,
                   TO_CHAR(R.DTH_INICIO_RECEB,'DD/MM/YYYY') as DTH_INICIO_RECEB,
                   TO_CHAR(R.DTH_FINAL_RECEB, 'DD/MM/YYYY') as DTH_FINAL_RECEB,
                   S.DSC_SIGLA,
                   NF.COD_FORNECEDOR,
                   FORNECEDOR.NOM_PESSOA FORNECEDOR,
                   NVL(QTD.QTD,0) as QTD_TOTAL,
                   NVL(QE.QTD_ENDERECAMENTO,0) as QTD_ENDERECAMENTO,
                   NVL(QF.QTD_ENDERECADO,0) as QTD_ENDERECADO,
                   NVL(QTD.QTD,0) - NVL(QE.QTD_ENDERECAMENTO,0) - NVL(QF.QTD_ENDERECADO,0) as QTD_RECEBIMENTO
              FROM RECEBIMENTO R
              LEFT JOIN NOTA_FISCAL NF ON R.COD_RECEBIMENTO = NF.COD_RECEBIMENTO
              LEFT JOIN FORNECEDOR F ON NF.COD_FORNECEDOR = F.COD_FORNECEDOR
              LEFT JOIN (SELECT PJ.COD_PESSOA, P.NOM_PESSOA
                         FROM PESSOA_JURIDICA PJ
                         LEFT JOIN PESSOA P ON PJ.COD_PESSOA = P.COD_PESSOA) FORNECEDOR ON F.COD_FORNECEDOR = FORNECEDOR.COD_PESSOA
              LEFT JOIN (SELECT SUM(QTD) as QTD,
                                COD_RECEBIMENTO
                           FROM V_QTD_RECEBIMENTO
                          GROUP BY COD_RECEBIMENTO) QTD ON QTD.COD_RECEBIMENTO = R.COD_RECEBIMENTO
              LEFT JOIN (SELECT SUM(QTD) as QTD_ENDERECAMENTO,
                        COD_RECEBIMENTO
                   FROM PALETE
                  WHERE COD_STATUS = 535
                  GROUP BY COD_RECEBIMENTO) QE ON R.COD_RECEBIMENTO = QE.COD_RECEBIMENTO
              LEFT JOIN (SELECT SUM(QTD) as QTD_ENDERECADO,
                        COD_RECEBIMENTO
                   FROM PALETE
                  WHERE COD_STATUS = 536
                  GROUP BY COD_RECEBIMENTO) QF ON R.COD_RECEBIMENTO = QF.COD_RECEBIMENTO
              LEFT JOIN SIGLA S ON R.COD_STATUS = S.COD_SIGLA
              LEFT JOIN PALETE PA ON R.COD_RECEBIMENTO = PA.COD_RECEBIMENTO
        ";

        $queryWhere = " WHERE ";
        $filter = false;

        if (isset($dataInicial1) && (!empty($dataInicial1))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_INICIO_RECEB >= TO_DATE('$dataInicial1 00:00:00','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataInicial2) && (!empty($dataInicial2))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_INICIO_RECEB <= TO_DATE('$dataInicial2 23:59:59','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataFinal1) && (!empty($dataFinal1))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_FINAL_RECEB >= TO_DATE('$dataFinal1 00:00:00','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($dataFinal2) && (!empty($dataFinal2))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.DTH_FINAL_RECEB <= TO_DATE('$dataFinal2 23:59:59','DD/MM/YYYY HH24:MI:SS')";
            $filter = true;
        }

        if (isset($status) && (!empty($status))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.COD_STATUS = $status";
            $filter = true;
        }

        if (isset($idRecebimento) && (!empty($idRecebimento))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " R.COD_RECEBIMENTO = $idRecebimento";
            $filter = true;
        }

        if (isset($uma) && (!empty($uma))) {
            if ($filter == true) {$queryWhere = $queryWhere . " AND ";}
            $queryWhere = $queryWhere . " PA.UMA = $uma";
            $filter = true;
        }

        if ($filter == true) {$query = $query . $queryWhere . " ORDER BY R.COD_RECEBIMENTO";}

        $array = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;

    }

    public function getPaletes ($idRecebimento, $idProduto, $grade) {

        $this->gerarPaletes($idRecebimento,$idProduto,$grade);
        $paletes = $this->findBy(array('recebimento' => $idRecebimento, 'codProduto' => $idProduto, 'grade' => $grade), array('id'=> 'ASC'));

        return $paletes;

    }

    public function getQtdEnderecadaByProduto($idRecebimento, $idProduto, $grade) {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("SUM(p.qtd) as qtd, p.codNormaPaletizacao as idNormaPaletizacao")
            ->from("wms:Enderecamento\Palete", "p")
            ->where("p.codProduto = $idProduto")
            ->andWhere("p.grade = '$grade'")
            ->andWhere("p.recebimento = $idRecebimento")
            ->andWhere("p.codStatus != ". Palete::STATUS_EM_RECEBIMENTO)
            ->groupBy('p.codNormaPaletizacao');
        $result = $query->getQuery()->getArrayResult();
        return $result;
    }

    public function getQtdByProdutoAndStatus($idRecebimento, $idProduto, $grade, $codStatus) {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("SUM(p.qtd) as qtd")
            ->from("wms:Enderecamento\Palete", "p")
            ->where("p.codProduto = $idProduto")
            ->andWhere("p.grade = '$grade'")
            ->andWhere("p.recebimento = $idRecebimento")
            ->andWhere("p.codStatus =  $codStatus");
        $result = $query->getQuery()->getArrayResult();

        $qtd = 0;
        if ($result[0]['qtd'] != NULL) {
            $qtd = $result[0]['qtd'];
        }
        return $qtd;
    }

    public function deletaPaletesEmRecebimento ($idRecebimento, $idProduto, $grade) {
        $paletes = $this->findBy(array('recebimento' => $idRecebimento, 'codProduto' => $idProduto, 'grade' => $grade, 'codStatus' => Palete::STATUS_EM_RECEBIMENTO));
        foreach ($paletes as $key => $palete) {
            $this->getEntityManager()->remove($palete);
        }
        $this->_em->flush();
    }

    public function deletaPaletesRecebidos ($idRecebimento, $idProduto, $grade) {
        $paletes = $this->findBy(array('recebimento' => $idRecebimento, 'codProduto' => $idProduto, 'grade' => $grade, 'codStatus' => Palete::STATUS_RECEBIDO));
        foreach ($paletes as $key => $palete) {
            $this->getEntityManager()->remove($palete);
        }
        $this->_em->flush();
    }


    public function getQtdEmRecebimento ($idRecebimento, $idProduto, $grade) {
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo    = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');

        $qtdTotalReceb = $conferenciaRepo->getQtdByRecebimento($idRecebimento,$idProduto,$grade);
        $qtdEnderecada = $this->getQtdEnderecadaByProduto($idRecebimento,$idProduto,$grade);

        foreach ($qtdTotalReceb as $recebido) {
            foreach ($qtdEnderecada as $key => $enderecado) {
                if ($recebido['idNormaPaletizacao'] == $enderecado['idNormaPaletizacao']) {
                    $qtdTotalReceb[$key]['qtd'] = $recebido['qtd'] - $enderecado['qtd'];
                }
            }
        }

        $qtd = 0;
        foreach ($qtdTotalReceb as $recebido) {
            $qtd = $qtd + $recebido['qtd'];
        }

        return $qtd;
    }

    public function gerarPaletes ($idRecebimento, $idProduto, $grade)
    {
        /** @var \Wms\Domain\Entity\Recebimento\ConferenciaRepository $conferenciaRepo */
        $conferenciaRepo    = $this->getEntityManager()->getRepository('wms:Recebimento\Conferencia');

        /** @var \Wms\Domain\Entity\NotaFiscalRepository $nfRepo */
        $nfRepo    = $this->getEntityManager()->getRepository('wms:NotaFiscal');

        $recebimentoEn = $this->getEntityManager()->getRepository('wms:Recebimento')->find($idRecebimento);
        $produtoEn     = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id'=>$idProduto, 'grade' => $grade));

        if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_FINALIZADO) {
            $codStatus = Palete::STATUS_RECEBIDO;
            $recebimentoFinalizado = true;
        } else if ($recebimentoEn->getStatus()->getId() == RecebimentoEntity::STATUS_DESFEITO){
            $codStatus = Palete::STATUS_CANCELADO;
            $recebimentoFinalizado = true;
        } else {
            $codStatus = Palete::STATUS_EM_RECEBIMENTO;
            $recebimentoFinalizado = false;
        }

        $statusEn      = $this->getEntityManager()->getRepository('wms:Util\Sigla')->find($codStatus);

        $qtdTotalReceb = $conferenciaRepo->getQtdByRecebimento($idRecebimento,$idProduto,$grade);
        $qtdEnderecada = $this->getQtdEnderecadaByProduto($idRecebimento,$idProduto,$grade);

        $totalEnderecado = 0;
        foreach ($qtdEnderecada as $key => $enderecado) {
            $totalEnderecado = $totalEnderecado + $enderecado['qtd'];
            foreach ($qtdTotalReceb as $recebido) {
                if ($recebido['idNormaPaletizacao'] == $enderecado['idNormaPaletizacao']) {
                    $qtdTotalReceb[$key]['qtd'] = $recebido['qtd'] - $enderecado['qtd'];
                }
            }
        }

        if (count($qtdTotalReceb) <= 0) {
            throw new Exception("O recebimento do produto $idProduto não possui unitizador");
        }

        $this->deletaPaletesEmRecebimento($idRecebimento,$idProduto,$grade);

        if ($recebimentoFinalizado == false) {
            $qtdLimite = $nfRepo->getQtdByProduto($idRecebimento,$idProduto,$grade) - $totalEnderecado;
        }

        foreach ($qtdTotalReceb as $unitizador) {
            if ($unitizador['qtd'] > 0) {

                if ($unitizador['numNorma'] == 0) {
                    throw new Exception("O produto $idProduto não possui norma de paletização");
                }

                $qtd = $unitizador['qtd'];

                //TRAVA PARA GERAR NO MAXIMO A QUANTIDADE TOTAL DA NOTA ENQUANTO O RECEBIMENTO NÃO TIVER SIDO FINALIZADO
                if ($recebimentoFinalizado == false) {
                    $qtdLimite = $qtdLimite - $qtd;
                    if ($qtdLimite < 0) {
                        $qtd = $qtd + $qtdLimite;
                    }
                }


                $qtdPaletes         = $qtd / $unitizador['numNorma'];
                $qtdUltimoPalete    = $qtd % $unitizador['numNorma'];
                $unitizadorEn       = $this->getEntityManager()->getRepository('wms:Armazenagem\Unitizador')->find($unitizador['idUnitizador']);

                for ($i = 1; $i <= $qtdPaletes; $i++) {
                    $paleteEn = new Palete();
                    $paleteEn->setRecebimento($recebimentoEn);
                    $paleteEn->setUnitizador($unitizadorEn);
                    $paleteEn->setCodNormaPaletizacao($unitizador['idNormaPaletizacao']);
                    $paleteEn->setProduto($produtoEn);
                    $paleteEn->setStatus($statusEn);
                    $paleteEn->setDepositoEndereco(null);
                    $paleteEn->setQtd($unitizador['numNorma']);
                    $this->_em->persist($paleteEn);
                }

                if ($qtdUltimoPalete > 0) {
                    //TRAVA PARA GERAR O PALETE COM A QUANTIDADE QUEBRADA SOMENTE SE TIVER FINALIZADO
                    if ($recebimentoFinalizado == true) {
                        $paleteEn = new Palete();
                        $paleteEn->setRecebimento($recebimentoEn);
                        $paleteEn->setUnitizador($unitizadorEn);
                        $paleteEn->setCodNormaPaletizacao($unitizador['idNormaPaletizacao']);
                        $paleteEn->setProduto($produtoEn);
                        $paleteEn->setStatus($statusEn);
                        $paleteEn->setDepositoEndereco(null);
                        $paleteEn->setQtd($qtdUltimoPalete);
                        $this->_em->persist($paleteEn);
                    }
                }
            }
        }

        $this->_em->flush();
        $this->_em->clear();
    }

    public function finalizar(array $paletes, $idPessoa, $formaConferencia = OrdemServicoEntity::MANUAL)
    {
        if (count($paletes) <= 0 || empty($idPessoa)) {
            throw new Exception('Usuario ou palete não informados');
        }
        $retorno = array();
        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo    = $this->getEntityManager()->getRepository('wms:Enderecamento\Estoque');
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        $ok = false;
        foreach($paletes as $paleteId) {
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $this->find($paleteId);
            if ($paleteEn->getCodStatus() != Palete::STATUS_ENDERECADO && $paleteEn->getCodStatus() != Palete::STATUS_CANCELADO) {
                if ($formaConferencia == OrdemServicoEntity::COLETOR) {
                    $paleteEn->setCodStatus(Palete::STATUS_ENDERECADO);
                    $this->_em->persist($paleteEn);
                    $retorno = $this->criarOrdemServico($paleteId, $idPessoa, $formaConferencia);
                } else {
                    if ($paleteEn->getCodStatus() == Palete::STATUS_EM_ENDERECAMENTO) {
                        $paleteEn->setCodStatus(Palete::STATUS_ENDERECADO);
                        $this->_em->persist($paleteEn);
                        $retorno = $this->criarOrdemServico($paleteId, $idPessoa, $formaConferencia);
                    }
                }
                if ($retorno['criado']) {
                    $ok = true;
                    if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                        $reservaEstoqueRepo->efetivaReservaEstoque($paleteEn->getDepositoEndereco()->getId(),$paleteEn->getCodProduto(),$paleteEn->getGrade(),$paleteEn->getQtd(),"E","U",$paleteEn->getId(),$idPessoa,$retorno['id'],$paleteEn->getUnitizador()->getId());
                    }
                }
            }
        }

        $this->_em->flush();
        return $ok;
    }

    public function criarOrdemServico($idEnderecamento, $idPessoa, $formaConferencia)
    {
        /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
        $ordemServicoRepo = $this->_em->getRepository('wms:OrdemServico');

        // cria ordem de servico
        $idOrdemServico = $ordemServicoRepo->save(new OrdemServicoEntity, array(
            'identificacao' => array(
                'tipoOrdem' => 'enderecamento',
                'idEnderecamento' => $idEnderecamento,
                'idAtividade' => AtividadeEntity::ENDERECAMENTO,
                'formaConferencia' => $formaConferencia,
                'idPessoa' => $idPessoa
            ),
        ));

        return array(
            'criado' => true,
            'id' => $idOrdemServico,
            'mensagem' => 'Ordem de Serviço Nº ' . $idOrdemServico . ' criada com sucesso.',
        );
    }

    /*
     * EXEMPLO DE USO DA FUNÇÃO ENDERECAPICKING
      $paletesMock = array('116','117');
      $paleteRepo = $this->_em->getRepository('wms:Enderecamento\Palete');
      $paleteRepo->enderecaPicking($paletesMock);
    */
    public function enderecaPicking ($paletes = array())
    {
        if ($paletes == NULL) {
            throw new \Exception("Nenhum Palete Selecionado");
        }

        foreach ($paletes as $palete){
            /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
            $paleteEn = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete")->find($palete);

            if ($paleteEn->getRecebimento()->getStatus()->getId() != \wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                throw new \Exception("Só é permitido endereçar no picking quando o recebimento estiver finalizado");
            }

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
            $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");

            $enderecosPicking = $enderecoRepo->getEnderecoByProduto($paleteEn->getCodProduto(), $paleteEn->getGrade());

            if (count($enderecosPicking) <= 0) {
                throw new \Exception("Não existe endereço de picking para o produto " . $paleteEn->getCodProduto() . " / " . $paleteEn->getGrade());
            }

            $pickingId = $enderecosPicking[0]['id'];
            $this->alocaEnderecoPalete($paleteEn->getId(),$pickingId);
        }
        $this->getEntityManager()->flush();
    }

    public function alocaEnderecoPalete($idPalete, $idEndereco) {

        /** @var \Wms\Domain\Entity\Enderecamento\PaleteRepository $paleteRepo */
        $paleteRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Palete");

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoRepo */
        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");

        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $paleteRepo->find($idPalete);

        if ($paleteEn == NULL) {
            throw new \Exception("Palete $idPalete não encontrado");
        }

        if ($paleteEn->getCodStatus() == $paleteEn::STATUS_ENDERECADO) {
            throw new \Exception("Palete $idPalete já endereçado");
        }

        if ($paleteEn->getCodStatus() == $paleteEn::STATUS_CANCELADO) {
            throw new \Exception("Palete $idPalete cancelado");
        }

        $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();

        /** @var \Wms\Domain\Entity\Deposito\Endereco $enderecoEn */
        $enderecoNovoEn = $enderecoRepo->find($idEndereco);
        $enderecoAntigoEn = $paleteEn->getDepositoEndereco();

        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");

        if ($enderecoAntigoEn != NULL) {
            $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigoEn->getId(),$qtdAdjacente,"LIBERAR");
            $reservaEstoqueRepo->cancelaReservaEstoque($paleteEn->getDepositoEndereco(),$paleteEn->getCodProduto(),$paleteEn->getGrade(),$paleteEn->getQtd(),"E","U",$paleteEn->getId());
            if ($enderecoAntigoEn->getId() != $enderecoNovoEn->getId()) {
                $paleteEn->setImpresso("N");
            }
        } else {
            $paleteEn->setImpresso("N");
        }
        $paleteEn->setDepositoEndereco($enderecoNovoEn);
        $paleteEn->setCodStatus($paleteEn::STATUS_EM_ENDERECAMENTO);
        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoNovoEn->getId(),$qtdAdjacente,"OCUPAR");
        $reservaEstoqueRepo->adicionaReservaEstoque($enderecoNovoEn->getId(),$paleteEn->getCodProduto(),$paleteEn->getGrade(),$paleteEn->getQtd(),"E","U",$paleteEn->getId());

        $this->getEntityManager()->persist($paleteEn);
    }

    public function getPaletesReport($values)
    {
        extract($values);

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa.id coduma,
                      r.id codrecebimento,
                      pa.qtd quantidade,
                      prod.id codproduto,
                      prod.descricao nomeproduto,
                      s.sigla status,
                      dep.descricao endereco"
            )
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin("pa.produto", "prod")
            ->innerJoin("pa.recebimento", "r")
            ->innerJoin("r.status", "s")
            ->leftJoin("pa.depositoEndereco", "dep");

        if (isset($dataInicial1) && (!empty($dataInicial1)) && (!empty($dataInicial2)))
        {
            $dataInicial1 = str_replace("/", "-", $dataInicial1);
            $dataI1 = new \DateTime($dataInicial1);

            $dataInicial2 = str_replace("/", "-", $dataInicial2);
            $dataI2 = new \DateTime($dataInicial2);

            $query->where("((TRUNC(r.dataInicial) >= ?1 AND TRUNC(r.dataInicial) <= ?2) OR r.dataInicial IS NULL)")
                ->setParameter(1, $dataI1)
                ->setParameter(2, $dataI2);
        }

        if (isset($dataFinal1) && (!empty($dataFinal1)) && (!empty($dataFinal2)))
        {
            $DataFinal1 = str_replace("/", "-", $dataFinal1);
            $dataF1 = new \DateTime($DataFinal1);

            $DataFinal2 = str_replace("/", "-", $dataFinal2);
            $dataF2 = new \DateTime($DataFinal2);

            $query->andWhere("((TRUNC(r.dataFinal) >= ?3 AND TRUNC(r.dataFinal) <= ?4) OR r.dataFinal IS NULL")
                ->setParameter(3, $dataF1)
                ->setParameter(4, $dataF2);
        }

        if (isset($status) && (!empty($status))) {
            $query->andWhere("r.status = ?5")
                ->setParameter(5, $status);
        }

        if (isset($idRecebimento) && (!empty($idRecebimento))) {
            $query->andWhere("r.id = ?6")
                ->setParameter(6, $idRecebimento);
        }

        $relatorio_uma = $query->getQuery()->getArrayResult();
        return $relatorio_uma;

    }

    public function cancelaPalete($idUma) {
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->findOneBy(array('id'=> $idUma ));

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");

        $idUsuarioLogado  = \Zend_Auth::getInstance()->getIdentity()->getId();

        if ($paleteEn == NULL) {
            throw new \Exception ("Palete não encontrado");
        }

        try {
            if ($paleteEn->getCodStatus() == \Wms\Domain\Entity\Enderecamento\Palete::STATUS_ENDERECADO) {
                $idEndereco = $paleteEn->getDepositoEndereco()->getId();
                $codProduto = $paleteEn->getCodProduto();
                $grade =$paleteEn->getGrade();
                $qtd = $paleteEn->getQtd();
                $idUma = $paleteEn->getId();
                if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO){
                    $estoqueRepo->movimentaEstoque($codProduto,$grade ,$idEndereco, $qtd* -1, $idUsuarioLogado ,"Mov. ref. cancelamento do Palete ". $idUma);
                }
            }

            $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
            $enderecoAntigo = $paleteEn->getDepositoEndereco();
            if ($enderecoAntigo != NULL) {
                $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
                $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo->getId(),$qtdAdjacente,"LIBERAR");
            }

            $paleteEn->setCodStatus(Palete::STATUS_CANCELADO);

            $this->getEntityManager()->persist($paleteEn);
            $this->getEntityManager()->flush();
        } catch (Exception $e) {
            throw new \Exception ($e->getMessage());
        }
    }

    public function desfazerPalete($idUma) {

        /** @var \Wms\Domain\Entity\Enderecamento\EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->getEntityManager()->getRepository("wms:Enderecamento\Estoque");
        /** @var \Wms\Domain\Entity\Ressuprimento\ReservaEstoqueRepository $reservaEstoqueRepo */
        $reservaEstoqueRepo = $this->getEntityManager()->getRepository("wms:Ressuprimento\ReservaEstoque");
        /** @var \Wms\Domain\Entity\Enderecamento\Palete $paleteEn */
        $paleteEn = $this->findOneBy(array('id'=> $idUma ));
        $idUsuarioLogado  = \Zend_Auth::getInstance()->getIdentity()->getId();

        if ($paleteEn == NULL) {
            throw new \Exception ("Palete $idUma não encontrado");
        }

        $idEndereco = $paleteEn->getDepositoEndereco()->getId();
        $codProduto = $paleteEn->getCodProduto();
        $grade =$paleteEn->getGrade();
        $qtd = $paleteEn->getQtd();
        $idUma = $paleteEn->getId();

        try{
            switch ($paleteEn->getCodStatus()){
                case Palete::STATUS_ENDERECADO:
                    $reservaEstoqueRepo->reabrirReservaEstoque($idEndereco,$codProduto,$grade,$qtd,"E","U",$idUma);
                    $paleteEn->setCodStatus(\Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_ENDERECAMENTO);
                    $this->getEntityManager()->persist($paleteEn);
                    break;
                case Palete::STATUS_EM_ENDERECAMENTO:
                    if ($paleteEn->getRecebimento()->getStatus()->getId() == \Wms\Domain\Entity\Recebimento::STATUS_FINALIZADO) {
                        $codStatus = \Wms\Domain\Entity\Enderecamento\Palete::STATUS_RECEBIDO;
                    } else {
                        $codStatus = \Wms\Domain\Entity\Enderecamento\Palete::STATUS_EM_RECEBIMENTO;
                    }

                    $qtdAdjacente = $paleteEn->getUnitizador()->getQtdOcupacao();
                    $enderecoAntigo = $paleteEn->getDepositoEndereco();
                    if ($enderecoAntigo != NULL) {
                        $enderecoRepo = $this->getEntityManager()->getRepository("wms:Deposito\Endereco");
                        $enderecoRepo->ocuparLiberarEnderecosAdjacentes($enderecoAntigo->getId(),$qtdAdjacente,"LIBERAR");
                        $reservaEstoqueRepo->cancelaReservaEstoque($idEndereco,$codProduto,$grade,$qtd,"E","U",$idUma);
                    }

                    $paleteEn->setDepositoEndereco(NULL);
                    $paleteEn->setImpresso("N");
                    $paleteEn->setCodStatus($codStatus);
                    $this->getEntityManager()->persist($paleteEn);
                    break;
                case Palete::STATUS_RECEBIDO:
                case Palete::STATUS_EM_RECEBIMENTO:
                    $this->getEntityManager()->remove($paleteEn);
                    break;
            }
            $this->getEntityManager()->flush();
        } catch(Exception $e) {
            throw new \Exception ($e->getMessage());
        }
        return true;
    }

    public function getImprimeNorma($idRecebimento, $idProduto, $grade)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("u.descricao")
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin("pa.unitizador", "u")
            ->innerJoin("pa.produto", "prod")
            ->innerJoin("pa.recebimento", "r")
            ->where("r.id = $idRecebimento")
            ->andWhere("prod.id = $idProduto")
            ->andWhere("prod.grade = '$grade'");

        $array = $query->getQuery()->getArrayResult();

        $norma = $array[0]['descricao'];
        return $norma;

    }

    public function getByRecebimentoAndStatus($recebimento, $status = Palete::STATUS_CANCELADO)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa.id, u.descricao unitizador, pa.qtd, sigla.sigla status, de.descricao endereco, pa.impresso")
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin('pa.unitizador', 'u')
            ->innerJoin('pa.status', 'sigla')
            ->leftJoin('pa.depositoEndereco', 'de')
            ->where("pa.status = ".$status);

        if ($recebimento) {
            $query->andWhere('pa.recebimento = :recebimento')
                ->setParameter('recebimento', $recebimento);
        }

        return $query->getQuery()->getArrayResult();
    }

    public function realizaTroca($recebimento, array $umas)
    {
        foreach($umas as $uma)
        {
            $entity = $this->find($uma);
            $entRecebimento = $this->_em->getReference('wms:Recebimento', $recebimento);

            if ($entity->getDepositoEndereco() != null) {
                $entStatus = $this->_em->getReference('wms:Util\Sigla', Palete::STATUS_ENDERECADO);
            } else {
                $entStatus = $this->_em->getReference('wms:Util\Sigla', Palete::STATUS_EM_ENDERECAMENTO);
            }

            $entity->setStatus($entStatus);
            $entity->setRecebimento($entRecebimento);
            $this->_em->persist($entity);
        }
        try {
            $this->_em->flush();
            return true;
        } catch(Exception $e) {
            throw new $e->getMessage();
        }
    }

    public function getPaletesByProdutoAndGrade($params)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select("pa.id, u.descricao unitizador, pa.qtd, sigla.sigla status, de.descricao endereco, pa.impresso")
            ->from("wms:Enderecamento\Palete", "pa")
            ->innerJoin('pa.unitizador', 'u')
            ->innerJoin('pa.recebimento', 'receb')
            ->innerJoin('receb.status', 'sigla')
            ->leftJoin('pa.depositoEndereco', 'de');

        if (isset($params['grade']) && !empty($params['grade']) && isset($params['codigo']) && !empty($params['codigo'])) {
            $query
                ->setParameter('grade', $params['grade'])
                ->andWhere('pa.grade = :grade')
                ->andWhere('pa.codProduto = :produto')
                ->setParameter('produto', $params['codigo']);
        } else {
            $query
                ->andWhere('pa.recebimento = :recebimento')
                ->setParameter('recebimento', $params['filtro-recebimento']);
        }

        return $query->getQuery()->getResult();
    }


}
