<?php

namespace Wms\Domain\Entity\Enderecamento;

use Doctrine\ORM\EntityRepository,
    Core\Util\Produto as ProdutoUtil,
    Wms\Domain\Entity\Deposito\Endereco as EnderecoEntity,
    Wms\Domain\Entity\Enderecamento\EstoqueProprietario as EstoqueProprietarioEntity,
    Wms\Util\Endereco as EnderecoUtil;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Produto\Lote;
use Wms\Domain\Entity\Produto\LoteRepository;
use Wms\Domain\Entity\Expedicao;
use Wms\Math;

class EstoqueRepository extends EntityRepository
{
    /*
     $params = array();
     $params['produto'];      - obrigatorio, entidade de produto - wms:Produto
     $params['endereco'];     - obrigatorio, entidade de produto - wms:Deposito\Endereco
     $params['qtd'];          - obrigatorio, quantidade a movimentar
     $params['volume'];       - entidade do volume a movimentar - wms:Produto\Volume
     $params['embalagem'];    - entidade da embalagem a movimentar - wms:Produto\Embalagem
     $params['tipo']           - tipo de movimentação ('S'=> Sistema, 'M'=> Manual, 'I' => Inventario, 'RC' => Ressuprimento Corretivo
                               'RP' => 'Ressuprimento Preventivo, 'E' => Expedicao )
     $params['observacoes'];  - observações
     $params['unitizador'];   - entidade do unitizador a movimentar - wms:Armazenagem\Unitizador
     $params['os'];           - entidade de OS relacionada a movimentação - wms:OrdemServico
     $params['uma'];          - id da U.M.A
     $params['usuario'];      - entidade de usuario - wms:Usuario
     */
    public function movimentaEstoque($params, $runFlush = true, $saidaProduto = false, $dataValidade = null, $ignorarBloqueio = false)
    {
        $em = $this->getEntityManager();
        $idInventario = null;

        /** @var \Wms\Domain\Entity\NotaFiscalRepository $notaFiscalRepository */
        $notaFiscalRepository = $em->getRepository('wms:NotaFiscal');

        if (!isset($params['produto']) or is_null($params['produto']))
            throw new \Exception("Produto não informado");
        if (!isset($params['endereco']) or is_null($params['endereco']))
            throw new \Exception("Endereço não informado");
        if (!isset($params['qtd']) or is_null($params['qtd']))
            throw new \Exception("Quantidade não informada");

        /** @var Endereco $enderecoEn */
        $enderecoEn = $params['endereco'];
        $produtoEn = $params['produto'];
        $qtd = $params['qtd'];

        $volumeEn = null;
        if (isset($params['volume']) && !empty($params['volume'])) {
            $volumeEn = $params['volume'];
        }
        if (isset($params['idInventario']) && !empty($params['idInventario'])) {
            $idInventario = $params['idInventario'];
        }

        if ($enderecoEn->getAtivo() == 'N') {
            throw new \Exception("Não é permitido fazer movimentações em um endereço inativo - Endereço:" . $enderecoEn->getDescricao());
        }

        if (!$ignorarBloqueio) {
            if (($qtd < 0) && $enderecoEn->isBloqueadaSaida()) throw new \Exception("Este endereço '".$enderecoEn->getDescricao()."' está bloqueado para movimentações de saída!");
            if (($qtd > 0) && $enderecoEn->isBloqueadaEntrada()) throw new \Exception("Este endereço '".$enderecoEn->getDescricao()."' está bloqueado para movimentações de entrada!");
        }

        $codProduto = $produtoEn->getId();
        $grade = $produtoEn->getGrade();
        $endereco = $enderecoEn->getId();
        $controlaLote = $produtoEn->getIndControlaLote();

        $usuarioEn = null;
        if (isset($params['usuario']) and !is_null($params['usuario'])) {
            $usuarioEn = $params['usuario'];
        } else {
            $auth = \Zend_Auth::getInstance();
            $usuarioSessao = $auth->getIdentity();
            $pessoaRepo = $this->getEntityManager()->getRepository("wms:Usuario");
            $usuarioEn = $pessoaRepo->find($usuarioSessao->getId());
        }

        if ($controlaLote == 'S' && ((!isset($params['lote']) || empty($params['lote'])) && empty($idInventario))) {
            throw new \Exception('Informe o Lote.');
        } elseif($controlaLote == 'S' && isset($params['lote']) && !empty($params['lote'])){
            /** @var LoteRepository $loteRepository */
            $loteRepository = $em->getRepository('wms:Produto\Lote');
            if (empty($codProduto))
                throw new \Exception("O código do produto não foi informado!");
            
            $loteEntity = $loteRepository->verificaLote($params['lote'], $codProduto, $grade, $usuarioEn->getId(), (in_array($params['tipo'],[ HistoricoEstoque::TIPO_MOVIMENTACAO, HistoricoEstoque::TIPO_INVENTARIO]) && $qtd > 0));
            if(empty($loteEntity)){
                throw new \Exception('O lote '.$params['lote'].' não pertence ao produto '.$codProduto);
            }
        }


        $qtdReserva = 0;
        if ($saidaProduto == true) {
            $dql = "SELECT SUM(REP.QTD_RESERVADA) QTD_RESERVADA
                        FROM RESERVA_ESTOQUE RE
                        INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                        WHERE RE.IND_ATENDIDA = 'N' AND RE.TIPO_RESERVA = 'S'
                        AND REP.COD_PRODUTO = '$codProduto' AND REP.DSC_GRADE = '$grade' AND RE.COD_DEPOSITO_ENDERECO = $endereco";
                        if (isset($volumeEn) && !empty($volumeEn)) {
                            $idVolume = $volumeEn->getId();
                            $dql .= " AND REP.COD_PRODUTO_VOLUME = $idVolume";
                        }
                        if (isset($params['lote']) && !empty($params['lote'])) {
                            $dql .= " AND REP.DSC_LOTE = '$params[lote]'";
                        }
            $dql .= " GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, RE.COD_DEPOSITO_ENDERECO, NVL(COD_PRODUTO_VOLUME,0)";

            $resultado = $this->getEntityManager()->getConnection()->query($dql)->fetchAll(\PDO::FETCH_ASSOC);

            if (count($resultado) > 0) {
                $qtdReserva = $resultado[0]['QTD_RESERVADA'];
            }
        }

        $argsConsultaEstoque = [
            'codProduto' => $codProduto,
            'grade' => $grade,
            'depositoEndereco' => $enderecoEn,
        ];
        if (!empty($volumeEn))
            $argsConsultaEstoque['produtoVolume'] = $volumeEn;

        if ($controlaLote == "S")
            $argsConsultaEstoque['lote'] = $params['lote'];

        /** @var Estoque $estoqueEn */
        $estoqueEn = $this->findOneBy($argsConsultaEstoque);

        $embalagemEn = null;
        if (isset($params['embalagem']) and !is_null($params['embalagem']) && !empty($params['embalagem'])) {
            $embalagemEn = $params['embalagem'];
        }

        $tipo = "S";
        if (isset($params['tipo']) and !is_null($params['tipo'])){
            $tipo = $params['tipo'];
        }
        $observacoes = "";
        if (isset($params['observacoes']) and !is_null($params['observacoes'])) {
            $observacoes = $params['observacoes'];
        }

        $unitizadorEn = null;
        if (isset($params['unitizador']) and (!is_null($params['unitizador']))) {
            $unitizadorEn = $params['unitizador'];
        }

        $osEn = null;
        if (isset($params['os']) and !is_null($params['os'])) {
            $osEn = $params['os'];
        }

        $idUma = null;
        $notaFiscalDevolucao = null;
        if (isset($params['uma']) and !empty($params['uma'])) {
            $idUma = $params['uma'];
            $notaFiscalDevolucao = $notaFiscalRepository->getTipoNotaByUma($idUma);
        }

        $validade = null;
        $validadeEsttoque = null;
        $validadeParam = null;
        if (isset($estoqueEn) && is_object($estoqueEn)) {
            $validadeEsttoque = $estoqueEn->getValidade();
        }
        if (isset($params['validade']) and !empty($params['validade'])) {
            $validadeParam = new \Zend_Date($params['validade']);
            $validadeParam = $validadeParam->toString('yyyy-MM-dd');
            $validadeParam = new \DateTime($validadeParam);
        } elseif (isset($dataValidade['dataValidade']) and !empty($dataValidade['dataValidade'])) {
            $validadeParam = (is_string($dataValidade['dataValidade'])) ? new \DateTime($dataValidade['dataValidade']) : $dataValidade['dataValidade'];
        }

        if (isset($validadeParam) && !empty($validadeParam)) {
            $validade = $validadeParam;
        } elseif (isset($validadeEsttoque) && !empty($validadeEsttoque)) {
            $validade = $validadeEsttoque;
        }
        if (!empty($notaFiscalDevolucao)) {
            if (($enderecoEn->getCaracteristica()->getId() == Endereco::PICKING) && ($this->getSystemParameterValue('ATUALIZAR_DATA_PICKING') != 'S')) {
                if (isset($validadeEsttoque) && !empty($validadeEsttoque)) {
                    $validade = $validadeEsttoque;
                } elseif (isset($validadeParam) && !empty($validadeParam)) {
                    $validade = $validadeParam;
                }
            }
        }

        //ATUALIZA A TABELA ESTOQUE COM O SALDO DE ESTOQUE
        if ($estoqueEn == NULL) {
            $novaQtd = $qtd;
            $saldoAnterior = 0;
            $estoqueEn = new Estoque();
            $estoqueEn->setDepositoEndereco($enderecoEn);
            $estoqueEn->setProduto($produtoEn);
            $estoqueEn->setDtPrimeiraEntrada($params['dthEntrada']);
            $estoqueEn->setQtd($qtd);
            $estoqueEn->setUma($idUma);
            $estoqueEn->setUnitizador($unitizadorEn);
            $estoqueEn->setProdutoEmbalagem($embalagemEn);
            $estoqueEn->setProdutoVolume($volumeEn);
            $estoqueEn->setValidade($validade);
            $estoqueEn->setLote((isset($params['lote']) && !empty($params['lote'])) ? $params['lote'] : null);

            $dscEndereco = $enderecoEn->getDescricao();
            $dscProduto = $produtoEn->getDescricao();
        } else {
            $saldoAnterior = $estoqueEn->getQtd();
            $idUma = $estoqueEn->getUma();
            $dscEndereco = $estoqueEn->getDepositoEndereco()->getDescricao();
            $dscProduto = $estoqueEn->getProduto()->getDescricao();
            $novaQtd = Math::adicionar($estoqueEn->getQtd(), $qtd);
            if ($novaQtd > 0) {
                $estoqueEn->setQtd($novaQtd);
                $estoqueEn->setValidade($validade);
                if (isset($unitizadorEn)) {
                    $estoqueEn->setUnitizador($unitizadorEn);
                }
            }
        }

        if (($qtd < 0) and ($novaQtd + $qtdReserva < 0)) {
            throw new \Exception("Não é permitido estoque negativo para o endereço $dscEndereco com o produto $codProduto / $grade - $dscProduto");
        } else if ($novaQtd > 0) {
            $em->persist($estoqueEn);
        } else {
            $em->remove($estoqueEn);
        }
        $saldoFinal = $novaQtd;

        if ($runFlush == true) {
            $em->flush();
            $this->removePickingDinamicoProduto($codProduto,$grade);
        }

        //CRIA UM HISTÓRICO DE MOVIMENTAÇÃO DE ESTOQUE
        $historico = new HistoricoEstoque();
        $historico->setQtd($qtd);
        $historico->setSaldoAnterior($saldoAnterior);
        $historico->setSaldoFinal($saldoFinal);
        $historico->setData(new \DateTime());
        $historico->setDepositoEndereco($enderecoEn);
        $historico->setObservacao($observacoes);
        $historico->setOrdemServico($osEn);
        $historico->setTipo($tipo);
        $historico->setUsuario($usuarioEn);
        $historico->setUma($idUma);
        $historico->setProduto($produtoEn);
        $historico->setUnitizador($unitizadorEn);
        $historico->setProdutoEmbalagem($embalagemEn);
        $historico->setProdutoVolume($volumeEn);
        $historico->setValidade($validade);
        if (!empty($params['obsUsuario']))
            $historico->setObsUsuario($params['obsUsuario']);

        if (!empty($params['idMotMov']))
            $historico->setMotivoMovimentacao($this->_em->getReference("wms:Enderecamento\MotivoMovimentacao", $params['idMotMov']));

        if(!empty($idInventario))
            $historico->setOperacao($idInventario);

        $em->persist($historico);
        $controleProprietario = $this->getEntityManager()->getRepository('wms:Sistema\Parametro')->findOneBy(array('constante' => 'CONTROLE_PROPRIETARIO'))->getValor();
        if($controleProprietario == 'S') {
            if (!empty($params['codProprietario']) && in_array($tipo,[HistoricoEstoque::TIPO_MOVIMENTACAO, HistoricoEstoque::TIPO_EXPEDICAO])) {
                $operacao = null;
                $arg = null;
                if ($tipo == HistoricoEstoque::TIPO_MOVIMENTACAO) {
                    $operacao = EstoqueProprietario::MOVIMENTACAO;
                    $arg = $idUma;
                } elseif($tipo == HistoricoEstoque::TIPO_EXPEDICAO){
                    $operacao = EstoqueProprietario::EXPEDICAO;
                    $arg = $params['codPedido'];
                }
                if (!empty($operacao)) {
                    $this->getEntityManager()->getRepository("wms:Enderecamento\EstoqueProprietario")->buildMovimentacaoEstoque($produtoEn->getId(), $produtoEn->getGrade(), $qtd, $operacao, $params['codProprietario'], $arg);
                }
            } elseif (empty($params['codProprietario']) && in_array($tipo,[HistoricoEstoque::TIPO_MOVIMENTACAO, HistoricoEstoque::TIPO_EXPEDICAO])) {
                throw new \Exception('Selecione um proprietário.');
            }
        }
        //VERIFICA SE O ENDERECO VAI ESTAR DISPONIVEL OU NÃO PARA ENDEREÇAMENTO
        if ($novaQtd > 0) {
            if ($enderecoEn->getDisponivel() == "S") {
                $enderecoEn->setDisponivel("N");
                $em->persist($enderecoEn);
            }
        } else {
            if (is_null($idInventario)) {
                $existeReservaEntradaPendente = false;
                $existeOutroEstoque = false;

                $SQL = " SELECT * 
                           FROM RESERVA_ESTOQUE_ENDERECAMENTO REE
                           INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                           WHERE RE.IND_ATENDIDA = 'N'
                             AND RE.COD_DEPOSITO_ENDERECO = '$endereco'";
                $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
                if (count($result) > 0) {
                    $existeReservaEntradaPendente = true;
                }

                $SQL = " SELECT *
                           FROM ESTOQUE E 
                          WHERE E.COD_DEPOSITO_ENDERECO = '$endereco'
                            AND NOT(E.COD_PRODUTO = '$codProduto' AND E.DSC_GRADE = '$grade')";
                $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
                if (count($result) > 0) {
                    $existeOutroEstoque = true;
                }

                if (($existeOutroEstoque == false) && ($existeReservaEntradaPendente == false)) {
                    if ($enderecoEn->getDisponivel() == "N") {
                        $enderecoEn->setDisponivel("S");
                        $em->persist($enderecoEn);
                    }
                }
            }

        }

        if ($runFlush == true)
            $em->flush();

        return true;
    }

    public function movimentaEstoqueInventario($params)
    {
        return $this->movimentaEstoque(
            $params['codProduto'], $params['grade'], $params['codProdutoVolume'], $params['codProdutoEmbalagem'], $params['idEndereco'], $params['qtd'], $params['idPessoa'] , $params['observacoes'],
            $params['tipo'], $params['idOs']);
    }

    /*
     * $params = [
     *  'idProduto' => '32123,
     *  'grade' = > 'UNICA',
     *  'idVolume' => 32312,
     *  'maxResult' => 5, (Optional)
     *  'idEnderecoIgnorar' => 321 (Optionoal)
     *  'idCaracteristigaIgnorar' => 37 (Optional)
     *  'lote' => 'LI32' (Optional)
     * ]
     */
    public function getEstoqueByParams ($params)
    {
        $subSelect = '';
        $subWhere = '';
        $paramJoin = '';
        $groupByLote = '';
        if (isset($params['controlaLote']) && !empty($params['controlaLote']) && $params['controlaLote']) {
            $subSelect = ', REP.DSC_LOTE';
            $paramJoin = ' AND RS.DSC_LOTE = ESTQ.DSC_LOTE';
            $groupByLote = ', ESTQ.DSC_LOTE';

            if (isset($params['lote']) && !empty($params['lote']) && $params['lote'] != Lote::LND) {
                $subWhere = " AND REP.DSC_LOTE = '$params[lote]'";
            }
        }

        $subWhereReserva = "= 'S'";
        if (isset($params['consideraReservaEntrada']) && $params['consideraReservaEntrada']) {
            $subWhereReserva = " IN ('S', 'E')";
        }

        $endPicking = EnderecoEntity::PICKING;
        $Sql = " SELECT
                    ESTQ.COD_DEPOSITO_ENDERECO,
                    DE.DSC_DEPOSITO_ENDERECO, 
                    SUM(ESTQ.QTD) QTD, 
                    SUM(NVL(RS.QTD_RESERVA,0)) as QTD_RESERVA, 
                    SUM(ESTQ.QTD + NVL(RS.QTD_RESERVA,0)) as SALDO, 
                    ESTQ.COD_PRODUTO_VOLUME, 
                    ESTQ.COD_PRODUTO, 
                    ESTQ.DSC_GRADE
                    $groupByLote, 
                    ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                    NVL(NVL(ESTQ.DTH_VALIDADE, PLT.DTH_CRIACAO), TO_DATE(CONCAT(TO_CHAR(ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,'DD/MM/YYYY'),' 00:00'),'DD/MM/YYYY HH24:MI')) as DT_MOVIMENTACAO,
                    TO_DATE(ESTQ.DTH_VALIDADE) as DTH_VALIDADE,
                    CASE WHEN (DE.COD_CARACTERISTICA_ENDERECO = $endPicking) THEN 1
                         ELSE 2 END AS PRIORIDADE_PICKING
                   FROM ( SELECT SUM(QTD) QTD, E.COD_DEPOSITO_ENDERECO, COD_PRODUTO_VOLUME, COD_PRODUTO, DSC_GRADE, DSC_LOTE, DTH_PRIMEIRA_MOVIMENTACAO, DTH_VALIDADE, NVL(E.UMA, 0) UMA
                            FROM ESTOQUE E
                      INNER JOIN DEPOSITO_ENDERECO D on E.COD_DEPOSITO_ENDERECO = D.COD_DEPOSITO_ENDERECO AND D.BLOQUEADA_SAIDA = 0
                        GROUP BY E.COD_DEPOSITO_ENDERECO, COD_PRODUTO_VOLUME, COD_PRODUTO, DSC_GRADE, DSC_LOTE, DTH_PRIMEIRA_MOVIMENTACAO, DTH_VALIDADE, NVL(E.UMA, 0)) ESTQ
                   LEFT JOIN (SELECT RE.COD_DEPOSITO_ENDERECO, SUM(REP.QTD_RESERVADA) QTD_RESERVA, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME $subSelect
                                FROM RESERVA_ESTOQUE RE
                           LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                               WHERE TIPO_RESERVA $subWhereReserva
                                 AND IND_ATENDIDA = 'N'
                                 $subWhere
                               GROUP BY RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) $subSelect) RS
                     ON RS.COD_PRODUTO = ESTQ.COD_PRODUTO
                    AND RS.DSC_GRADE = ESTQ.DSC_GRADE
                    $paramJoin
                    AND RS.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO
                    AND ((RS.VOLUME = ESTQ.COD_PRODUTO_VOLUME) OR (RS.VOLUME = 0 AND ESTQ.COD_PRODUTO_VOLUME IS NULL))
                   LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO
                   LEFT JOIN PALETE PLT ON PLT.UMA = ESTQ.UMA
                  WHERE ((ESTQ.QTD + NVL(RS.QTD_RESERVA,0)) > 0)";

        $SqlOrder = " ORDER BY TO_DATE(ESTQ.DTH_VALIDADE), PRIORIDADE_PICKING, TO_DATE(DT_MOVIMENTACAO), SUM(ESTQ.QTD + NVL(RS.QTD_RESERVA,0))";
        $SqlWhere = "";

        if (isset($params['idProduto']) && $params['idProduto'] != null) {
            $SqlWhere .= " AND ESTQ.COD_PRODUTO = '" . $params['idProduto'] . "'";
        }

        if (isset($params['grade']) && $params['grade'] != null) {
            $SqlWhere .= " AND ESTQ.DSC_GRADE = '" . $params['grade'] . "'";
        }

        if ((isset($params['controlaLote']) && !empty($params['controlaLote']) && $params['controlaLote'] == 'S') &&
            (isset($params['lote']) && !empty($params['lote']) && $params['lote'] != Lote::LND)) {
            $SqlWhere .= " AND ESTQ.DSC_LOTE = '" . $params['lote'] . "'";
        }

        if (isset($params['idVolume']) && ($params['idVolume'] != null)) {
            $idVolume = $params['idVolume'];
            if (is_array($params['idVolume']) == true) {
                $idVolume = implode(",", $params['idVolume']);
            }

            $SqlWhere .= " AND ESTQ.COD_PRODUTO_VOLUME IN (" . $idVolume . ")";
        }

        if (isset($params['idCaracteristicaIgnorar']) && $params['idCaracteristicaIgnorar'] != null) {
            $SqlWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO <> " . $params['idCaracteristicaIgnorar'] . "";
        }

        if (isset($params['idEnderecoIgnorar']) && $params['idEnderecoIgnorar'] != null) {
            $SqlWhere .= " AND DE.COD_DEPOSITO_ENDERECO <> '" . $params['idEnderecoIgnorar'] . "'";
        }

        if (isset($params['idEnderecoEspecifico']) && $params['idEnderecoEspecifico'] != null) {
            $SqlWhere .= " AND DE.COD_DEPOSITO_ENDERECO = '" . $params['idEnderecoEspecifico'] . "' ";
        }

        if (isset($params['isCDK']) && $params['isCDK']) {
            $SqlWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO = ". EnderecoEntity::CROSS_DOCKING;
        }

        $SqlGroupBy = " GROUP BY 
                            ESTQ.COD_DEPOSITO_ENDERECO,
                            DE.DSC_DEPOSITO_ENDERECO,
                            ESTQ.COD_PRODUTO_VOLUME, 
                            ESTQ.COD_PRODUTO, 
                            ESTQ.DSC_GRADE
                            $groupByLote, 
                            ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                            NVL(NVL(ESTQ.DTH_VALIDADE, PLT.DTH_CRIACAO), TO_DATE(CONCAT(TO_CHAR(ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,'DD/MM/YYYY'),' 00:00'),'DD/MM/YYYY HH24:MI')),
                            TO_DATE(ESTQ.DTH_VALIDADE),
                            CASE WHEN (DE.COD_CARACTERISTICA_ENDERECO = $endPicking) THEN 1 ELSE 2 END";

        $query = $Sql . $SqlWhere . $SqlGroupBy . $SqlOrder;
        
        if ((isset($params['maxResult'])) && ($params['maxResult'] != null)) {
            $maxResult = $params['maxResult'];
            $resultado = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);

            $arrayResult = array();
            foreach ($resultado as $key => $line) {
                $arrayResult[] = $line;
                if (($key + 1) >= $maxResult)
                    break;
            }
            $result = $arrayResult;
        } else {
            $result = $this->getEntityManager()->getConnection()->query($query)->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $result;
    }

    public function getEstoqueGroupByVolumns($params) {
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '-1');
        $subQuery = $this->getEstoqueAndVolumeByParams($params, null, true, null, true);
        $SQL = "
            SELECT ESTQ.ENDERECO,
                   ESTQ.TIPO,
                   ESTQ.COD_PRODUTO,
                   ESTQ.DSC_GRADE,
                   ESTQ.RESERVA_SAIDA,
                   ESTQ.RESERVA_ENTRADA,
                   LISTAGG(ESTQ.VOLUME,',') WITHIN GROUP (ORDER BY ESTQ.ENDERECO, ESTQ.TIPO, ESTQ. COD_PRODUTO, ESTQ.DSC_GRADE, ESTQ.RESERVA_SAIDA, ESTQ.RESERVA_ENTRADA,ESTQ.QTD,ESTQ.DTH_PRIMEIRA_MOVIMENTACAO) VOLUME,
                   ESTQ.QTD,
                   ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                   ESTQ.DSC_PRODUTO
              FROM ($subQuery) ESTQ
             GROUP BY ESTQ.ENDERECO, ESTQ.TIPO, ESTQ. COD_PRODUTO, ESTQ.DSC_GRADE, ESTQ.RESERVA_SAIDA, ESTQ.RESERVA_ENTRADA,ESTQ.QTD,ESTQ.DTH_PRIMEIRA_MOVIMENTACAO, ESTQ.DSC_PRODUTO
             ORDER BY COD_PRODUTO, DSC_GRADE, VOLUME, ENDERECO, DTH_PRIMEIRA_MOVIMENTACAO
        ";

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEstoqueAndVolumeByParams($parametros, $maxResult = null, $showPicking = true, $orderBy = null, $returnQuery = false)
    {
        $loteNd = Lote::LND;
        $SQL = "SELECT 
                       DE.DSC_DEPOSITO_ENDERECO ENDERECO,
                       DE.COD_DEPOSITO_ENDERECO COD_ENDERECO,
                       C.DSC_CARACTERISTICA_ENDERECO TIPO,
                       P.COD_PRODUTO,
                       P.DSC_PRODUTO,
                       P.DSC_GRADE as DSC_GRADE,
                       NVL(PV.DSC_VOLUME, 'PRODUTO UNITÁRIO') as VOLUME,
                       NVL(PV.COD_PRODUTO_VOLUME, 0) AS COD_VOLUME,
                       SUM(NVL(RE.QTD_RESERVADA, 0)) as RESERVA_ENTRADA,
                       SUM(NVL(RS.QTD_RESERVADA, 0)) as RESERVA_SAIDA,
                       SUM(NVL(ESTQ.QTD, 0)) as QTD,
                       NVL(PV.COD_NORMA_PALETIZACAO, 0) as NORMA,
                       MAX(TO_CHAR(ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,'dd/mm/yyyy hh:mi:ss')) AS DTH_PRIMEIRA_MOVIMENTACAO,
                       MAX(ESTQ.UMA) AS UMA,
                       MAX(UN.DSC_UNITIZADOR) AS UNITIZADOR,
                       ESTQ.DTH_VALIDADE,
                       NVL(ESTQ.LOTE, NVL(RE.LOTE, RS.LOTE)) AS LOTE,
                       CASE WHEN DE.IND_ATIVO = 'N' THEN 'INATIVO'
                            WHEN (DE.BLOQUEADA_ENTRADA = 1 AND DE.BLOQUEADA_SAIDA = 0) THEN 'BLOQUEADO PARA ENTRADA'
                            WHEN (DE.BLOQUEADA_ENTRADA = 0 AND DE.BLOQUEADA_SAIDA = 1) THEN 'BLOQUEADO PARA SAIDA'
                            WHEN (DE.BLOQUEADA_ENTRADA = 1 OR DE.BLOQUEADA_SAIDA = 1) THEN 'BLOQUEIO TOTAL'
                            ELSE 'DISPONIVEL' 
                       END as SITUACAO
                FROM (SELECT DTH_PRIMEIRA_MOVIMENTACAO, QTD, UMA, COD_UNITIZADOR, DTH_VALIDADE,
                             COD_DEPOSITO_ENDERECO, COD_PRODUTO, DSC_GRADE, COD_PRODUTO_VOLUME as VOLUME, DSC_LOTE AS LOTE FROM ESTOQUE) ESTQ
                LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = ESTQ.COD_UNITIZADOR
                FULL OUTER JOIN (SELECT SUM(R.QTD_RESERVADA) as QTD_RESERVADA, R.COD_DEPOSITO_ENDERECO, R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME, R.LOTE
                                FROM (SELECT REP.QTD_RESERVADA, RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, REP.COD_PRODUTO_VOLUME as VOLUME, REP.DSC_LOTE AS LOTE
                                      FROM RESERVA_ESTOQUE RE
                                             INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                      WHERE IND_ATENDIDA = 'N'
                                        AND TIPO_RESERVA = 'E') R
                                GROUP BY R.COD_DEPOSITO_ENDERECO,R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME, R.LOTE) RE
                                  ON ESTQ.COD_PRODUTO = RE.COD_PRODUTO
                                 AND ESTQ.DSC_GRADE = RE.DSC_GRADE
                                 AND NVL(ESTQ.VOLUME,0) = NVL(RE.VOLUME,0)
                                 AND NVL(ESTQ.LOTE, '$loteNd') = NVL(RE.LOTE, '$loteNd')
                                 AND ESTQ.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                FULL OUTER JOIN (SELECT SUM(R.QTD_RESERVADA) as QTD_RESERVADA, R.COD_DEPOSITO_ENDERECO, R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME, R.LOTE
                                FROM (SELECT REP.QTD_RESERVADA, RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, REP.COD_PRODUTO_VOLUME as VOLUME, REP.DSC_LOTE AS LOTE
                                      FROM RESERVA_ESTOQUE RE
                                             INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                      WHERE IND_ATENDIDA = 'N'
                                        AND TIPO_RESERVA = 'S') R
                                GROUP BY R.COD_DEPOSITO_ENDERECO,R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME, R.LOTE) RS
                               ON ESTQ.COD_PRODUTO = RS.COD_PRODUTO
                                 AND ESTQ.DSC_GRADE = RS.DSC_GRADE
                                 AND NVL(ESTQ.VOLUME,0) = NVL(RS.VOLUME,0)
                                 AND NVL(ESTQ.LOTE, '$loteNd') = NVL(RS.LOTE, '$loteNd')
                                 AND ESTQ.COD_DEPOSITO_ENDERECO = RS.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO_VOLUME PV ON (PV.COD_PRODUTO_VOLUME = ESTQ.VOLUME) OR (PV.COD_PRODUTO_VOLUME = RE.VOLUME) OR (PV.COD_PRODUTO_VOLUME = RS.VOLUME)
                LEFT JOIN PRODUTO P ON
                  (P.COD_PRODUTO = ESTQ.COD_PRODUTO AND P.DSC_GRADE = ESTQ.DSC_GRADE) OR
                  (P.COD_PRODUTO = RE.COD_PRODUTO AND P.DSC_GRADE = RE.DSC_GRADE) OR
                  (P.COD_PRODUTO = RS.COD_PRODUTO AND P.DSC_GRADE = RS.DSC_GRADE)
                LEFT JOIN DEPOSITO_ENDERECO DE ON
                  (DE.COD_DEPOSITO_ENDERECO = ESTQ.COD_DEPOSITO_ENDERECO) OR
                  (DE.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO) OR
                  (DE.COD_DEPOSITO_ENDERECO = RS.COD_DEPOSITO_ENDERECO)
                LEFT JOIN CARACTERISTICA_ENDERECO C ON C.COD_CARACTERISTICA_ENDERECO = DE.COD_CARACTERISTICA_ENDERECO";

        $SQLWhere = " WHERE 1 = 1 ";
        if (isset($parametros['idProduto']) && !empty($parametros['idProduto'])) {
            $parametros['idProduto'] = ProdutoUtil::formatar($parametros['idProduto']);
            $SQLWhere .= " AND P.COD_PRODUTO = '" . $parametros['idProduto'] . "' ";
            if (isset($parametros['grade']) && !empty($parametros['grade'])) {
                $SQLWhere .= " AND P.DSC_GRADE = '" . $parametros['grade'] . "'";
            } else {
                $SQLWhere .= " AND P.DSC_GRADE = 'UNICA'";
            }
        }

        if ($showPicking == false) {
            $caracteristicaPicking = EnderecoEntity::PICKING;
            $SQLWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO <> " . $caracteristicaPicking;
        }
        if (isset($parametros['lote']) && !empty($parametros['lote'])) {
            $SQLWhere .= " AND NVL(ESTQ.LOTE, NVL(RE.LOTE, RS.LOTE)) = '" . $parametros['lote'] ."'";
        }
        if (isset($parametros['rua']) && !empty($parametros['rua'])) {
            $SQLWhere .= " AND DE.NUM_RUA = " . $parametros['rua'];
        }
        if (isset($parametros['predio']) && !empty($parametros['predio'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO = " . $parametros['predio'];
        }
        if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL = " . $parametros['nivel'];
        }
        if (isset($parametros['apto']) && !empty($parametros['apto'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO = " . $parametros['apto'];
        }
        if (isset($parametros['volume']) && !empty($parametros['volume'])) {
            $SQLWhere .= " AND PV.COD_PRODUTO_VOLUME = " . $parametros['volume'];
        }
        if (isset($parametros['tipoEndereco']) && !empty($parametros['tipoEndereco'])) {
            $SQLWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO = " . $parametros['tipoEndereco'];
        }

        if ($orderBy != null) {
            $SQLOrderBy = $orderBy;
        } else {
            $SQLOrderBy = " ORDER BY ESTQ.DTH_VALIDADE, P.COD_PRODUTO, P.DSC_GRADE, NORMA, VOLUME, C.COD_CARACTERISTICA_ENDERECO, DTH_PRIMEIRA_MOVIMENTACAO, LOTE";
        }

        $SQLgroupBy = " GROUP BY DE.DSC_DEPOSITO_ENDERECO, DE.COD_DEPOSITO_ENDERECO, C.DSC_CARACTERISTICA_ENDERECO, P.COD_PRODUTO, P.DSC_PRODUTO, P.DSC_GRADE, PV.DSC_VOLUME, NVL(PV.COD_PRODUTO_VOLUME,0), NVL(PV.COD_NORMA_PALETIZACAO,0), ESTQ.DTH_VALIDADE, NVL(ESTQ.LOTE, NVL(RE.LOTE, RS.LOTE)), C.COD_CARACTERISTICA_ENDERECO, DE.IND_ATIVO, DE.BLOQUEADA_ENTRADA, DE.BLOQUEADA_SAIDA";
        if ($returnQuery == true) {
            return $SQL . $SQLWhere . $SQLgroupBy . $SQLOrderBy;
        }

        $result = $this->getEntityManager()->getConnection()->query("$SQL $SQLWhere $SQLgroupBy $SQLOrderBy")->fetchAll(\PDO::FETCH_ASSOC);

        if (isset($maxResult) && !empty($maxResult)) {
            if ($maxResult != false) {

                $arrayResult = array();
                foreach ($result as $key => $line) {
                    $arrayResult[] = $line;
                    if (($key + 1) >= $maxResult)
                        break;
                }
                $result = $arrayResult;
            }
        }
        if (!empty($result) && is_array($result)) {
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            foreach ($result as $key => $value) {
                $result[$key]['QTD_EMBALAGEM'] = $value['QTD'];
                if(!isset($value['COD_VOLUME'])){
                    $value['COD_VOLUME'] = 0;
                }

                if ($value['QTD'] > 0 && ($value['COD_VOLUME'] == 0 || $value['COD_VOLUME'] == null)) {
                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD']);

                    if(is_array($vetEstoque)) {
                        $result[$key]['QTD_EMBALAGEM'] = implode('<br />', $vetEstoque);
                    }else{
                        $result[$key]['QTD_EMBALAGEM'] = $vetEstoque;
                    }
                }
            }
        }

        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        foreach ($result as $key => $value) {
            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['RESERVA_ENTRADA']);
            if(is_array($vetEstoque)) {
                $result[$key]['RE_EMBALAGEM'] = implode('<br />', $vetEstoque);
            }else{
                $result[$key]['RE_EMBALAGEM'] = $vetEstoque;
            }

            $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['RESERVA_SAIDA']);
            if(is_array($vetEstoque)) {
                $result[$key]['RS_EMBALAGEM'] = implode('<br />', $vetEstoque);
            }else{
                $result[$key]['RS_EMBALAGEM'] = $vetEstoque;
            }
        }

        return $result;
    }

    public function getEstoquePulmao($parametros)
    {
        $tipoPicking = EnderecoEntity::PICKING;

        $and = "";
        $cond = "";
        if (isset($parametros['uma']) && !empty($parametros['uma'])) {
            $cond .= $and . 'E.UMA = \'' . $parametros['uma'] . '\'';
            $and = " and ";
        } else {
            if (isset($parametros['idProduto']) && !empty($parametros['idProduto'])) {
                $cond .= $and . 'P.COD_PRODUTO = ' . $parametros['idProduto'];
                $and = " and ";
                if (isset($parametros['grade']) && !empty($parametros['grade'])) {
                    $cond .= $and . 'P.DSC_GRADE = \'' . $parametros['grade'] . '\'';
                    $and = " and ";
                } else {
                    $cond .= $and . 'P.DSC_GRADE = \'UNICA\'';
                    $and = " and ";
                }
            }

            if (isset($parametros['idNormaPaletizacao']) && !empty($parametros['idNormaPaletizacao'])) {
                $cond .= $and . 'U.COD_UNITIZADOR = ' . $parametros['idNormaPaletizacao'];
                $and = " and ";
            }

            if (isset($parametros['rua']) && !empty($parametros['rua'])) {
                $cond .= $and . 'DE.NUM_RUA = ' . $parametros['rua'];
                $and = " and ";
            }
            if (isset($parametros['predio']) && !empty($parametros['predio'])) {
                $cond .= $and . 'DE.NUM_PREDIO = ' . $parametros['predio'];
                $and = " and ";
            }
            if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
                $cond .= $and . 'DE.NUM_NIVEL = ' . $parametros['nivel'];
                $and = " and ";
            }
            if (isset($parametros['apto']) && !empty($parametros['apto'])) {
                $cond .= $and . 'DE.NUM_APARTAMENTO = ' . $parametros['apto'];
                $and = " and ";
            }
        }

        $condPicking = str_replace("E.", "P.", $cond);

        $SQL = "
            SELECT * FROM
                (
                    SELECT
                      NULL as \"descricao\",
                      U.DSC_UNITIZADOR as \"unitizador\",
                      DE.COD_DEPOSITO_ENDERECO as \"id\",
                      E.QTD as \"qtd\",
                      E.DTH_PRIMEIRA_MOVIMENTACAO as \"dtPrimeiraEntrada\",
                      P.COD_PRODUTO as \"codProduto\",
                      P.DSC_GRADE as \"grade\",
                      P.DSC_PRODUTO as \"produto\",
                      DE.DSC_DEPOSITO_ENDERECO as \"enderecoPicking\"
                    FROM
                      PRODUTO P
                    LEFT JOIN PRODUTO_VOLUME  PV
                      ON P.COD_PRODUTO=PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                    LEFT JOIN PRODUTO_EMBALAGEM  PE
                      ON P.COD_PRODUTO=PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE
                    LEFT JOIN DEPOSITO_ENDERECO  DE
                      ON PV.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                      OR PE.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                    LEFT JOIN ESTOQUE E
                      ON E.COD_PRODUTO = P.COD_PRODUTO AND E.DSC_GRADE = P.DSC_GRADE AND E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                    LEFT JOIN UNITIZADOR  U
                      ON E.COD_UNITIZADOR=U.COD_UNITIZADOR
                    WHERE
                      DE.COD_CARACTERISTICA_ENDERECO=" . $tipoPicking . " and " . $cond . "
                    GROUP BY DE.DSC_DEPOSITO_ENDERECO,
                             U.DSC_UNITIZADOR,
                             DE.COD_DEPOSITO_ENDERECO,
                             E.QTD,
                             E.DTH_PRIMEIRA_MOVIMENTACAO,
                             P.COD_PRODUTO,
                             P.DSC_GRADE,
                             P.DSC_PRODUTO
                    ORDER BY P.COD_PRODUTO,
                             P.DSC_GRADE,
                             E.QTD,
                             E.DTH_PRIMEIRA_MOVIMENTACAO
                )
                 UNION ALL

            SELECT * FROM
                (
                SELECT
                  DE.DSC_DEPOSITO_ENDERECO as \"descricao\",U.DSC_UNITIZADOR as \"unitizador\", E.COD_DEPOSITO_ENDERECO as \"id\", E.QTD as \"qtd\", E.DTH_PRIMEIRA_MOVIMENTACAO as \"dtPrimeiraEntrada\", E.COD_PRODUTO as \"codProduto\", E.DSC_GRADE as \"grade\", P.DSC_PRODUTO as \"produto\", NULL as \"enderecoPicking\"
                FROM
                  ESTOQUE E
                INNER JOIN DEPOSITO_ENDERECO DE
                  ON E.COD_DEPOSITO_ENDERECO=DE.COD_DEPOSITO_ENDERECO
                INNER JOIN PRODUTO  P
                  ON E.COD_PRODUTO=P.COD_PRODUTO AND E.DSC_GRADE=P.DSC_GRADE
                LEFT JOIN UNITIZADOR  U
                  ON E.COD_UNITIZADOR=U.COD_UNITIZADOR
                LEFT JOIN PRODUTO_VOLUME  PV
                  ON P.COD_PRODUTO=PV.COD_PRODUTO
                LEFT JOIN DEPOSITO_ENDERECO  PVE
                  ON PV.COD_DEPOSITO_ENDERECO=PVE.COD_DEPOSITO_ENDERECO
                LEFT JOIN PRODUTO_EMBALAGEM  PE
                  ON P.COD_PRODUTO=PE.COD_PRODUTO
                LEFT JOIN DEPOSITO_ENDERECO  PEE
                  ON PE.COD_DEPOSITO_ENDERECO=PEE.COD_DEPOSITO_ENDERECO
                WHERE
                  DE.COD_CARACTERISTICA_ENDERECO<>" . $tipoPicking . " and " . $cond . "
                GROUP BY
                  DE.DSC_DEPOSITO_ENDERECO,U.DSC_UNITIZADOR, E.COD_DEPOSITO_ENDERECO, E.QTD, E.DTH_PRIMEIRA_MOVIMENTACAO, E.COD_PRODUTO, E.DSC_GRADE, P.DSC_PRODUTO
                ORDER BY
                  E.COD_PRODUTO,E.DSC_GRADE,E.QTD,E.DTH_PRIMEIRA_MOVIMENTACAO
                )
        ";

        $resultado = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        $groupByProduto = array();
        foreach ($resultado as $chv => $data) {

            $id = $data['codProduto'] . $data['grade'];
            $data['dtPrimeiraEntrada'] = new \DateTime($data['dtPrimeiraEntrada']);
            if (isset($groupByProduto[$id])) {
                $groupByProduto[$id][] = $data;
            } else {
                $groupByProduto[$id] = array($data);
            }
        }
        return $groupByProduto;
    }

    public function getEstoqueByRua($inicioRua, $fimRua, $grandeza = null,$exibePicking = 1, $exibePulmao = 1)
    {
        $tipoPicking = EnderecoEntity::PICKING;

        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("e.descricao, estq.codProduto, estq.grade, p.descricao nomeProduto")
                ->from("wms:Enderecamento\Estoque", 'estq')
                ->innerJoin("estq.depositoEndereco", "e")
                ->innerJoin("estq.produto", "p")
                ->orderBy("e.descricao, p.id, p.grade, estq.dtPrimeiraEntrada");

        if ($inicioRua) {
            $query->andWhere('e.rua >= :inicioRua');
            $query->setParameter('inicioRua', $inicioRua);
        }

        if ($fimRua) {
            $query->andWhere('e.rua <= :fimRua');
            $query->setParameter('fimRua', $fimRua);
        }

        if (!empty($grandeza)) {
            $grandeza = implode(',', $grandeza);
            $query->andWhere("p.linhaSeparacao in ($grandeza)");
        }

        if (($exibePulmao == 1) && ($exibePicking == 0)) {
            $query->andWhere("e.nivel != '" . $tipoPicking . "'");
        }

        if (($exibePulmao == 0) && ($exibePicking == 1)) {
            $query->andWhere("e.idCaracteristica = '" . $tipoPicking . "'");
        }

        return $query->getQuery()->getResult();
    }

    public function saldo($params)
    {
        $tipoPicking = EnderecoEntity::PICKING;
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select('estq.codProduto, estq.grade, ls.descricao, sum(estq.qtd) qtdestoque, NVL(depv.descricao, depe.descricao) enderecoPicking')
                ->from("wms:Enderecamento\Estoque", 'estq')
                ->innerJoin("estq.produto", "p")
                ->leftJoin("p.volumes", 'pv')
                ->leftJoin("pv.endereco", 'depv')
                ->leftJoin("p.embalagens", 'pe')
                ->leftJoin("pe.endereco", 'depe')
                ->innerJoin("p.linhaSeparacao", "ls")
                ->innerJoin("estq.depositoEndereco", "e")
                ->groupBy('estq.codProduto, estq.grade, ls.descricao, depv.descricao, depe.descricao');

        if (!empty($params['grandeza'])) {
            $grandeza = $params['grandeza'];
            $grandeza = implode(',', $grandeza);
            $query->andWhere("p.linhaSeparacao in ($grandeza)");
        }

        if (!empty($params['inicioRua'])) {
            $query->andWhere('e.rua >= :inicioRua');
            $query->setParameter('inicioRua', $params['inicioRua']);
        }

        if (!empty($params['fimRua'])) {
            $query->andWhere('e.rua <= :fimRua');
            $query->setParameter('fimRua', $params['fimRua']);
        }

        if (($params['pulmao'] == 1) && ($params['picking'] == 0)) {
            $query->andWhere("e.nivel !=  '" . $tipoPicking . "'");
        }

        if (($params['pulmao'] == 0) && ($params['picking'] == 1)) {
            $query->andWhere("e.idCaracteristica = '" . $tipoPicking . "'");
        }

        return $query->getQuery()->getResult();
    }

    public function getExisteEnderecoPulmao ($codProduto, $grade)
    {
        $tipoPicking = EnderecoEntity::PICKING;
        $query = $this->getEntityManager()->createQueryBuilder()
                ->select('estq.codProduto, estq.grade')
                ->from("wms:Enderecamento\Estoque", 'estq')
                ->innerJoin("estq.depositoEndereco", "dep")
                ->where("dep.idCaracteristica != '$tipoPicking'")
                ->andWhere("estq.codProduto = '$codProduto'")
                ->andWhere("estq.grade = '$grade'");

        $estoque = $query->getQuery()->getResult();

        if (count($estoque) == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function imprimeMovimentacaoAvulsa($codProduto, $grade, $quantidade, $endereco)
    {
        $dadosPalete = array();
        $dadosRelatorio = array();
        $paletes = array();

        $dadosPalete['idUma'] = 0;
        $dadosPalete['endereco'] = $endereco;
        $dadosPalete['qtd'] = $quantidade;

        $paletes[] = $dadosPalete;

        $dadosRelatorio['idRecebimento'] = 0;
        $dadosRelatorio['codProduto'] = $codProduto;
        $dadosRelatorio['grade'] = $grade;
        $dadosRelatorio['paletes'] = $paletes;

        $Uma = new \Wms\Module\Enderecamento\Printer\UMA('L');
        $Uma->imprimir($dadosRelatorio, $this->getSystemParameterValue("MODELO_RELATORIOS"));
    }

    public function getEstoqueConsolidado($params)
    {
        $SQL = 'SELECT LS.DSC_LINHA_SEPARACAO as "Linha de separação",
                       E.COD_PRODUTO as "Codigo",
                       E.DSC_GRADE as "Grade",
                       SubSTR(P.DSC_PRODUTO,0,60) as "Descrição",
                       F.NOM_FABRICANTE as "Fabricante",
                       MIN(E.QTD) as "Quantidade"
                  FROM (SELECT PROD.COD_PRODUTO,
                               PROD.DSC_GRADE,
                               NVL(QTD.QTD,0) as QTD
                          FROM (SELECT DISTINCT E.COD_PRODUTO, E.DSC_GRADE, NVL(PV.COD_PRODUTO_VOLUME,0) as VOLUME
                                  FROM ESTOQUE E
                                  LEFT JOIN PRODUTO_VOLUME PV ON E.COD_PRODUTO = PV.COD_PRODUTO AND E.DSC_GRADE = PV.DSC_GRADE) PROD
                          LEFT JOIN (SELECT SUM(E.QTD) as QTD, E.COD_PRODUTO, E.DSC_GRADE,
                                            NVL(E.COD_PRODUTO_VOLUME,0) as VOLUME
                                       FROM ESTOQUE E
                                      GROUP BY E.COD_PRODUTO, E.DSC_GRADE, NVL(E.COD_PRODUTO_VOLUME,0)) QTD
                            ON QTD.COD_PRODUTO = PROD.COD_PRODUTO
                           AND QTD.DSC_GRADE = PROD.DSC_GRADE
                           AND QTD.VOLUME = PROD.VOLUME) E
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE
                  LEFT JOIN LINHA_SEPARACAO LS ON LS.COD_LINHA_SEPARACAO = P.COD_LINHA_SEPARACAO
                  INNER JOIN FABRICANTE F ON F.COD_FABRICANTE = P.COD_FABRICANTE
        ';
        $SQLGroup = " GROUP BY E.COD_PRODUTO,
                            E.DSC_GRADE,
                            P.DSC_PRODUTO,
                            F.NOM_FABRICANTE,
                            LS.DSC_LINHA_SEPARACAO";

        $SQLOrder = " ORDER BY LS.DSC_LINHA_SEPARACAO, P.DSC_PRODUTO";

        $SQLWhere = "WHERE E.QTD > 0";
        if (isset($params['grandeza'])) {
            $grandeza = $params['grandeza'];
            if (!empty($grandeza)) {
                $grandeza = implode(',', $grandeza);
                $SQLWhere .= " AND P.COD_LINHA_SEPARACAO IN ($grandeza) ";
            }
        }
        if (isset($params['fabricante'])) {
            $fabricante = $params['fabricante'];
            if (!empty($fabricante)) {
                $fabricante = implode(',', $fabricante);
                $SQLWhere .= " AND F.COD_FABRICANTE IN ($fabricante) ";
            }
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLGroup . $SQLOrder)->fetchAll(\PDO::FETCH_ASSOC);

        return $result;
    }

    public function getSituacaoEstoque($params) {

        $tipoPicking = EnderecoEntity::PICKING;

        $query = $this->getEntityManager()->createQueryBuilder()
                ->select("de.descricao,
                 NVL(NVL(NVL(e.codProduto, pp.codProduto),pv.codProduto),pe.codProduto) as codProduto,
                 NVL(NVL(NVL(e.grade, pp.grade),pv.grade),pe.grade) as grade,
                 NVL(e.qtd,pp.qtd) as qtd,
                 p.id as uma,
                 r.id as idRecebimento,
                 s.sigla as sigla
                 ")
                ->from("wms:Deposito\Endereco", 'de')
                ->leftJoin("wms:Enderecamento\Estoque", "e", "WITH", "e.depositoEndereco = de.id")
                ->leftJoin("wms:Enderecamento\Palete", "p", "WITH", "p.depositoEndereco = de.id  AND p.codStatus !=" . Palete::STATUS_ENDERECADO . " AND p.codStatus !=" . Palete::STATUS_CANCELADO)
                ->leftJoin("p.produtos", "pp")
                ->leftJoin("wms:Produto\Volume", "pv", "WITH", "de.id = pv.endereco")
                ->leftJoin("wms:Produto\Embalagem", "pe", "WITH", "de.id = pe.endereco")
                ->leftJoin("p.recebimento", "r")
                ->leftJoin("p.status", "s")
                ->andWhere("de.bloqueadaEntrada = 0")
                ->andWhere("de.bloqueadaSaida = 0")
                ->distinct(true)
                ->orderBy("de.descricao");

        $query->andWhere('(pv.id is  null and pe.id is  null)');

        if (!empty($params['mostrarPicking']) && $params['mostrarPicking'] == 1) {
            $query->andWhere('de.idCaracteristica = :idCaracteristica');
            $query->setParameter('idCaracteristica', $tipoPicking);
        } else {
            $query->andWhere('de.idCaracteristica <> :idCaracteristica');
            $query->setParameter('idCaracteristica', $tipoPicking);
        }

        if (!empty($params['rua'])) {
            $query->andWhere('de.rua = :rua');
            $query->setParameter('rua', $params['rua']);
        }

        if (!empty($params['predio'])) {
            $query->andWhere('de.predio = :predio');
            $query->setParameter('predio', $params['predio']);
        }

        if (!empty($params['nivel'])) {
            $query->andWhere('de.nivel = :nivel');
            $query->setParameter('nivel', $params['nivel']);
        }

        if (!empty($params['apartamento'])) {
            $query->andWhere('de.apartamento = :apartamento');
            $query->setParameter('apartamento', $params['apartamento']);
        }

        if (($params['mostraOcupado']) == 0) {
            $query->andWhere('((e.codProduto IS NULL) AND (pp.codProduto IS NULL))');
        }

        $result = $query->getQuery()->getResult();

        foreach ($result as $key => $endereco) {
            if ($endereco['codProduto'] == NULL) {
                $endereco['statusEndereco'] = "Endereço não utilizado";
                $endereco['tipo'] = "V";
            } else {
                if ($endereco['uma'] == NULL) {
                    $endereco['tipo'] = "E";
                    $endereco['statusEndereco'] = "Endereçado no estoque";
                } else {
                    $endereco['statusEndereco'] = "Reservado para o palete $endereco[uma] ($endereco[sigla]) no recebimento $endereco[idRecebimento]";
                    $endereco['tipo'] = "P";
                }
            }
            $result[$key] = $endereco;
        }


        return $result;
    }

    public function getProdutoByNivel($dscEndereco, $nivel) {

        if (is_null($nivel) || $nivel == '') {
            throw new \Exception('Nivel não foi informado');
        }

        $em = $this->getEntityManager();

        $endereco = EnderecoUtil::formatar($dscEndereco, null, null, $nivel);

        $dql = $em->createQueryBuilder()
                ->select('dep.rua, dep.nivel, dep.predio, dep.apartamento, e.uma, e.id, dep.id as idEndereco')
                ->from("wms:Enderecamento\Estoque", "e")
                ->InnerJoin("e.depositoEndereco", "dep")
                ->where("dep.descricao = '$endereco'");

        return $dql->getQuery()->getArrayResult();
    }

    public function getProdutoByUMA($codigoBarrasUMA, $idEndereco)
    {
        $em = $this->getEntityManager();
        $sql = "SELECT p0_.DSC_PRODUTO AS descricao, p0_.COD_PRODUTO AS id, p0_.DSC_GRADE AS grade, e1_.DSC_LOTE AS lote, NVL(e1_.QTD / NVL(p2_.QTD_EMBALAGEM, 1),'1') AS qtd, d3_.DSC_DEPOSITO_ENDERECO AS endereco, NVL(p2_.DSC_EMBALAGEM,'VOLUMES') as DSC_EMBALAGEM, NVL(p2_.COD_PRODUTO_EMBALAGEM,0) as COD_PRODUTO_EMBALAGEM, p2_.QTD_EMBALAGEM, e1_.QTD as QTD_UNIT
                FROM ESTOQUE e1_
                INNER JOIN DEPOSITO_ENDERECO d3_ ON e1_.COD_DEPOSITO_ENDERECO = d3_.COD_DEPOSITO_ENDERECO
                INNER JOIN PRODUTO p0_ ON e1_.COD_PRODUTO = p0_.COD_PRODUTO AND e1_.DSC_GRADE = p0_.DSC_GRADE
                LEFT JOIN PRODUTO_EMBALAGEM p2_ ON (p2_.COD_PRODUTO = p0_.COD_PRODUTO AND p2_.DSC_GRADE = p0_.DSC_GRADE AND p2_.DTH_INATIVACAO is null)
                WHERE e1_.UMA = $codigoBarrasUMA AND d3_.COD_DEPOSITO_ENDERECO = $idEndereco
                ORDER BY p2_.QTD_EMBALAGEM";

        return $em->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getProdutoByCodBarrasAndEstoque($etiquetaProduto, $idEndereco)
    {
        $em = $this->getEntityManager();
        $dql = "SELECT p0_.DSC_PRODUTO AS descricao, p0_.COD_PRODUTO AS id, p0_.DSC_GRADE AS grade, e1_.DSC_LOTE AS lote, e1_.QTD / NVL(p3_.QTD_EMBALAGEM,1) AS qtd, NVL(p3_.DSC_EMBALAGEM,'') DSC_EMBALAGEM, p3_.COD_PRODUTO_EMBALAGEM, d2_.DSC_DEPOSITO_ENDERECO AS ENDERECO,
                    DE.DSC_DEPOSITO_ENDERECO PICKING, e1_.QTD as QTD_UNIT
                    FROM ESTOQUE e1_ INNER JOIN PRODUTO p0_ ON e1_.COD_PRODUTO = p0_.COD_PRODUTO AND e1_.DSC_GRADE = p0_.DSC_GRADE
                    LEFT JOIN DEPOSITO_ENDERECO d2_ ON e1_.COD_DEPOSITO_ENDERECO = d2_.COD_DEPOSITO_ENDERECO
                    LEFT JOIN PRODUTO_EMBALAGEM p3_ ON (p3_.COD_PRODUTO = e1_.COD_PRODUTO AND p3_.DSC_GRADE = e1_.DSC_GRADE AND p3_.DTH_INATIVACAO is null)
                    LEFT JOIN PRODUTO_VOLUME p4_ ON p0_.COD_PRODUTO = p4_.COD_PRODUTO AND p0_.DSC_GRADE = p4_.DSC_GRADE
                    LEFT JOIN DEPOSITO_ENDERECO DE ON p3_.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO OR p4_.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                    WHERE ((p3_.COD_BARRAS = '$etiquetaProduto' OR p4_.COD_BARRAS = '$etiquetaProduto')) AND d2_.COD_DEPOSITO_ENDERECO = $idEndereco";

        return $em->getConnection()->query($dql)->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQtdProdutoByVolumesOrProduct($codProduto, $grade, $idEndereco, $volumes) {
        if (count($volumes) == 0) {
            $SQL = "SELECT CASE WHEN SUM(QTD) IS NULL THEN 0 ELSE SUM (QTD) END AS QTD
                      FROM ESTOQUE
                     WHERE COD_PRODUTO = '$codProduto'
                       AND DSC_GRADE = '$grade'
                       AND COD_DEPOSITO_ENDERECO = '$idEndereco'";
            $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
            return $result[0]['QTD'];
        } else {
            $menorQtd = null;
            foreach ($volumes as $volume) {
                $SQL = "SELECT CASE WHEN SUM(QTD) IS NULL THEN 0 ELSE SUM (QTD) END AS QTD
                          FROM ESTOQUE
                         WHERE COD_PRODUTO = '$codProduto'
                           AND DSC_GRADE = '$grade'
                           AND COD_DEPOSITO_ENDERECO = '$idEndereco'
                           AND COD_PRODUTO_VOLUME = '$volume'";
                $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
                $qtd = $result[0]['QTD'];
                if (is_null($menorQtd) || $qtd < $menorQtd) {
                    $menorQtd = $qtd;
                }
            }
            if (is_null($menorQtd)) {
                return 0;
            } else {
                return $menorQtd;
            }
        }
    }

    public function getEstoqueProdutosSemPicking($params) {

        $SQL = "
                SELECT P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO,  SUM(E.QTD) as QTD FROM
        (SELECT DISTINCT P.COD_PRODUTO,
               P.DSC_GRADE,
               NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO) AS COD_DEPOSITO_ENDERECO
          FROM PRODUTO P
          LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO = P.COD_PRODUTO AND PE.DSC_GRADE = P.DSC_GRADE
          LEFT JOIN PRODUTO_VOLUME    PV ON PV.COD_PRODUTO = P.COD_PRODUTO AND PV.DSC_GRADE = P.DSC_GRADE
          WHERE PE.COD_DEPOSITO_ENDERECO IS NULL AND PV.COD_DEPOSITO_ENDERECO IS NULL)PR
        LEFT JOIN ESTOQUE E ON E.COD_PRODUTO = PR.COD_PRODUTO AND E.DSC_GRADE = PR.DSC_GRADE
        LEFT JOIN PRODUTO P ON P.COD_PRODUTO = PR.COD_PRODUTO AND P.DSC_GRADE = PR.DSC_GRADE";

        if (isset($params['grandeza'])) {
            $grandeza = implode(',', $params['grandeza']);
            $SQL = $SQL . " WHERE P.COD_LINHA_SEPARACAO IN ($grandeza)";
        }
        $SQL = $SQL . " GROUP BY P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO";
        $SQL = $SQL . " ORDER BY P.DSC_PRODUTO";

        $array = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $array;
    }

    public function getProdutosArmazenadosPickingErrado($params) {
        $SQLWhere = " WHERE ";
        $SQLOrder = " ORDER BY DE.DSC_DEPOSITO_ENDERECO ";
        $SQL = "SELECT DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                       PK.COD_PRODUTO as PRODUTO_PICKING,
                       PK.DSC_GRADE as GRADE_PICKING,
                       PK.VOLUMES as VOLUME_PICKING,
                       E.COD_PRODUTO as PRODUTO_ESTOQUE,
                       E.DSC_GRADE as GRADE_ESTOQUE,
                       E.VOLUMES as VOLUMES_ESTOQUE,
                       E.QTD,
                       E.PK_CORRETO as PICKING_CORRETO
                  FROM (SELECT E.COD_PRODUTO, E.DSC_GRADE, E.COD_DEPOSITO_ENDERECO, E.QTD, E.PK_CORRETO,
                               LISTAGG(E.VOLUME,',') WITHIN GROUP (ORDER BY E.VOLUME) VOLUMES
                          FROM (SELECT E.QTD,
                                       NVL(PE.COD_PRODUTO, PV.COD_PRODUTO) as COD_PRODUTO,
                                       NVL(PE.DSC_GRADE, PV.DSC_GRADE) as DSC_GRADE,
                                       NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME,
                                       E.COD_DEPOSITO_ENDERECO,
                                       DE2.DSC_DEPOSITO_ENDERECO AS PK_CORRETO
                                  FROM ESTOQUE E
                                  LEFT JOIN PRODUTO_VOLUME PV ON PV.COD_PRODUTO_VOLUME = E.COD_PRODUTO_VOLUME
                                  LEFT JOIN PRODUTO_EMBALAGEM PE ON PE.COD_PRODUTO_EMBALAGEM = E.COD_PRODUTO_EMBALAGEM
                                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                                  LEFT JOIN DEPOSITO_ENDERECO DE2 ON (DE2.COD_DEPOSITO_ENDERECO = PV.COD_DEPOSITO_ENDERECO OR DE2.COD_DEPOSITO_ENDERECO = PE.COD_DEPOSITO_ENDERECO)
                                 WHERE DE.COD_CARACTERISTICA_ENDERECO = 37
                                   AND (E.COD_DEPOSITO_ENDERECO <> PE.COD_DEPOSITO_ENDERECO
                                     OR E.COD_DEPOSITO_ENDERECO <> PV.COD_DEPOSITO_ENDERECO))E
                         GROUP BY E.QTD, E.COD_PRODUTO, E.DSC_GRADE, E.COD_DEPOSITO_ENDERECO, E.PK_CORRETO) E
                  LEFT JOIN (SELECT COD_PRODUTO,
                                    DSC_GRADE,
                                    COD_DEPOSITO_ENDERECO,
                                    LISTAGG (VOLUME,';') WITHIN GROUP (ORDER BY VOLUME) VOLUMES
                               FROM (SELECT P.COD_PRODUTO, P.DSC_GRADE, NVL(PE.DSC_EMBALAGEM, PV.DSC_VOLUME) as VOLUME,NVL(PE.COD_DEPOSITO_ENDERECO, PV.COD_DEPOSITO_ENDERECO) as COD_DEPOSITO_ENDERECO
                                       FROM PRODUTO P
                                       LEFT JOIN PRODUTO_VOLUME PV ON P.COD_PRODUTO = PV.COD_PRODUTO AND P.DSC_GRADE = PV.DSC_GRADE
                                       LEFT JOIN PRODUTO_EMBALAGEM PE ON P.COD_PRODUTO = PE.COD_PRODUTO AND P.DSC_GRADE = PE.DSC_GRADE)
                              GROUP BY COD_PRODUTO, DSC_GRADE, COD_DEPOSITO_ENDERECO) PK
                         ON PK.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  LEFT JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                  LEFT JOIN PRODUTO PROD ON PROD.COD_PRODUTO = E.COD_PRODUTO AND PROD.DSC_GRADE = E.DSC_GRADE
                   ";

        if (isset($params['inicioRua']) && ($params['inicioRua'] != "")) {
            if ($SQLWhere != " WHERE ")
                $SQLWhere .= " AND ";
            $SQLWhere .= " DE.NUM_RUA >= " . $params['inicioRua'];
        }

        if (isset($params['fimRua']) && ($params['fimRua'] != "")) {
            if ($SQLWhere != " WHERE ")
                $SQLWhere .= " AND ";
            $SQLWhere .= " DE.NUM_RUA <= " . $params['fimRua'];
        }

        if (isset($params['grandeza']) && (count($params['grandeza']) > 0)) {
            if ($SQLWhere != " WHERE ")
                $SQLWhere .= " AND ";
            $grandezas = implode(",", $params['grandeza']);
            $SQLWhere .= " PROD.COD_LINHA_SEPARACAO IN ($grandezas)";
        }

        $array = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLOrder)->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($array as $key => $value){
            $array[$key]['VOLUME_PICKING'] =  substr($value['VOLUME_PICKING'], 0, 20);
            $array[$key]['VOLUMES_ESTOQUE'] = substr($value['VOLUMES_ESTOQUE'], 0, 20);
        }
        return $array;
    }

    public function getProdutosVolumesDivergentes()
    {
        $dql = $this->getEntityManager()->createQueryBuilder()
                ->select('e.codProduto as Codigo, e.grade as Grade, p.descricao as Produto', 'v.descricao as Volume, SUM(e.qtd) as Qtd')
                ->from("wms:Enderecamento\Estoque", "e")
                ->innerJoin("e.produto", 'p')
                ->innerJoin("e.produtoVolume", 'v')
                ->where('e.produtoVolume IS NOT NULL')
                ->groupBy('e.codProduto ', 'e.grade', 'p.descricao', 'v.id', 'v.descricao')
                ->orderBy('e.codProduto, e.grade', 'ASC');

        $result = $dql->getQuery()->getArrayResult();

        $prodAnterior = "";
        $prodAtual = "";
        $qtdVolumes = 1;

        $produtosDivergentes = array();

        foreach ($result as $produto) {
            $prodAtual = $produto;

            if ($prodAnterior == "") {
                $prodAnterior = $produto;
            } else {
                if (($prodAnterior['Codigo'] == $produto['Codigo']) && ($prodAnterior['Grade'] == $produto['Grade'])) {
                    $qtdVolumes = $qtdVolumes + 1;
                    if ($prodAnterior['Qtd'] != $produto['Qtd']) {
                        array_push($produtosDivergentes, $produto);
                    }
                } else {
                    $produtoEn = $this->getEntityManager()->getRepository('wms:Produto')->findOneBy(array('id' => $prodAnterior['Codigo'], 'grade' => $prodAnterior['Grade']));
                    if ($produtoEn->getNumVolumes() != $qtdVolumes) {
                        $produtoFaltante = $prodAnterior;
                        $produtoFaltante['Volume'] = 'Faltando Volume';
                        $produtoFaltante['Qtd'] = "-";
                        array_push($produtosDivergentes, $produtoFaltante);
                    }

                    $qtdVolumes = 1;
                }
            }

            $prodAnterior = $prodAtual;
        }

        return $produtosDivergentes;
    }

    public function getEstoqueByProduto($produtos = null)
    {
        $SQL = "SELECT P.COD_PRODUTO,
                       P.DSC_GRADE,
                       NVL(E.QTD_ESTOQUE,0) as QTD_ESTOQUE_TOTAL,
                       NVL(E.QTD_ESTOQUE,0) + NVL(R.QTD_RESERVADA,0) as QTD_ESTOQUE_DISPONIVEL
                  FROM PRODUTO P
                  LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD) as QTD_ESTOQUE
                               FROM ESTOQUE E
                              GROUP BY COD_PRODUTO, DSC_GRADE) E
                    ON E.COD_PRODUTO = P.COD_PRODUTO AND E.DSC_GRADE = P.DSC_GRADE
                  LEFT JOIN (SELECT COD_PRODUTO, DSC_GRADE, SUM(QTD_RESERVADA) as QTD_RESERVADA
                               FROM (SELECT DISTINCT REP.COD_PRODUTO, REP.DSC_GRADE, REP.COD_RESERVA_ESTOQUE, REP.QTD_RESERVADA
                                       FROM RESERVA_ESTOQUE_EXPEDICAO REE
                                      INNER JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                                       LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                                      WHERE RE.IND_ATENDIDA = 'N')
                              GROUP BY COD_PRODUTO, DSC_GRADE) R
                   ON R.COD_PRODUTO = P.COD_PRODUTO AND R.DSC_GRADE = P.DSC_GRADE";

        if ($produtos != null) {
            $SQL = $SQL . " WHERE P.COD_PRODUTO IN (" . $produtos . ")";
        }

        $result = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public function getEstoquePreventivoByParams($parametros, $maxResult = null, $showPicking = true, $orderBy = null, $returnQuery = false) {
        $SQL = "SELECT DE.DSC_DEPOSITO_ENDERECO as ENDERECO,
                       DE.COD_DEPOSITO_ENDERECO as COD_ENDERECO,
                       C.DSC_CARACTERISTICA_ENDERECO as TIPO,
                       E.COD_PRODUTO,
                       E.DSC_GRADE,
                       E.NORMA,
                       E.COD_VOLUME,
                       E.VOLUME,
                       E.RESERVA_ENTRADA,
                       E.RESERVA_SAIDA,
                       E.QTD,
                       TO_CHAR(E.DTH_PRIMEIRA_MOVIMENTACAO,'dd/mm/yyyy hh:mi:ss') AS DTH_PRIMEIRA_MOVIMENTACAO,
                       P.DSC_PRODUTO,
                       E.UMA,
                       E.UNITIZADOR,
                       E.DTH_VALIDADE
                  FROM (SELECT NVL(NVL(RE.COD_DEPOSITO_ENDERECO, RS.COD_DEPOSITO_ENDERECO),ESTQ.COD_DEPOSITO_ENDERECO) as COD_DEPOSITO_ENDERECO,
                               NVL(NVL(RE.COD_PRODUTO, RS.COD_PRODUTO),ESTQ.COD_PRODUTO) as COD_PRODUTO,
                               NVL(NVL(RE.DSC_GRADE,RS.DSC_GRADE),ESTQ.DSC_GRADE) as DSC_GRADE,
                               CASE WHEN (ESTQ.VOLUME = '0' OR RE.VOLUME = '0' OR RS.VOLUME = '0') THEN 'PRODUTO UNITÁRIO'
                                    ELSE PV.DSC_VOLUME
                               END as VOLUME,
                               NVL(NVL(RS.VOLUME, RE.VOLUME),ESTQ.VOLUME) as COD_VOLUME,
                               NVL(RE.QTD_RESERVADA,0) as RESERVA_ENTRADA,
                               NVL(RS.QTD_RESERVADA,0) as RESERVA_SAIDA,
                               NVL(ESTQ.QTD,0) as QTD,
                               NVL(PV.COD_NORMA_PALETIZACAO,0) as NORMA,
                               ESTQ.DTH_PRIMEIRA_MOVIMENTACAO,
                               ESTQ.UMA,
                               UN.DSC_UNITIZADOR AS UNITIZADOR,
                               ESTQ.DTH_VALIDADE
                          FROM (SELECT DTH_PRIMEIRA_MOVIMENTACAO, QTD, UMA, COD_UNITIZADOR, DTH_VALIDADE,
                                       COD_DEPOSITO_ENDERECO, COD_PRODUTO, DSC_GRADE, NVL(COD_PRODUTO_VOLUME,'0') as VOLUME FROM ESTOQUE) ESTQ
                          LEFT JOIN UNITIZADOR UN ON UN.COD_UNITIZADOR = ESTQ.COD_UNITIZADOR
                          FULL OUTER JOIN (SELECT SUM(R.QTD_RESERVADA) as QTD_RESERVADA, R.COD_DEPOSITO_ENDERECO, R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME
                                             FROM (SELECT REP.QTD_RESERVADA, RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                                                     FROM RESERVA_ESTOQUE RE
                                                    INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                                    WHERE IND_ATENDIDA = 'N'
                                                      AND TIPO_RESERVA = 'E') R
                                            GROUP BY R.COD_DEPOSITO_ENDERECO,R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME) RE
                                  ON ESTQ.COD_PRODUTO = RE.COD_PRODUTO
                                 AND ESTQ.DSC_GRADE = RE.DSC_GRADE
                                 AND ESTQ.VOLUME = RE.VOLUME
                                 AND ESTQ.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                          FULL OUTER JOIN (SELECT SUM(R.QTD_RESERVADA) as QTD_RESERVADA, R.COD_DEPOSITO_ENDERECO, R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME
                                             FROM (SELECT REP.QTD_RESERVADA, RE.COD_DEPOSITO_ENDERECO, REP.COD_PRODUTO, REP.DSC_GRADE, NVL(REP.COD_PRODUTO_VOLUME,0) as VOLUME
                                                     FROM RESERVA_ESTOQUE RE
                                                    INNER JOIN RESERVA_ESTOQUE_PRODUTO REP ON RE.COD_RESERVA_ESTOQUE = REP.COD_RESERVA_ESTOQUE
                                                    WHERE IND_ATENDIDA = 'N'
                                                      AND TIPO_RESERVA = 'S') R
                                            GROUP BY R.COD_DEPOSITO_ENDERECO,R.COD_PRODUTO, R.DSC_GRADE, R.VOLUME) RS
                                  ON ESTQ.COD_PRODUTO = RS.COD_PRODUTO
                                 AND ESTQ.DSC_GRADE = RS.DSC_GRADE
                                 AND ESTQ.VOLUME = RS.VOLUME
                                 AND ESTQ.COD_DEPOSITO_ENDERECO = RS.COD_DEPOSITO_ENDERECO
                          LEFT JOIN PRODUTO_VOLUME PV ON (PV.COD_PRODUTO_VOLUME = ESTQ.VOLUME) OR (PV.COD_PRODUTO_VOLUME = RE.VOLUME) OR (PV.COD_PRODUTO_VOLUME = RS.VOLUME)) E
                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = E.COD_DEPOSITO_ENDERECO
                  LEFT JOIN CARACTERISTICA_ENDERECO C ON C.COD_CARACTERISTICA_ENDERECO = DE.COD_CARACTERISTICA_ENDERECO
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = E.COD_PRODUTO AND P.DSC_GRADE = E.DSC_GRADE";

        $SQLWhere = " WHERE DE.COD_CARACTERSITICA_ENDERECO != " . Endereco::CROSS_DOCKING;

        if (isset($parametros['tipoEndereco']) && !empty($parametros['tipoEndereco'])) {
            $SQLWhere .= " AND DE.COD_CARACTERISTICA_ENDERECO = " . $parametros['tipoEndereco'];
        }
        if (isset($parametros['linhaSeparacao']) && !empty($parametros['linhaSeparacao'])) {
            $SQLWhere .= " AND P.COD_LINHA_SEPARACAO = " . $parametros['linhaSeparacao'];
        }
        if (isset($parametros['rua']) && !empty($parametros['rua'])) {
            $SQLWhere .= " AND DE.NUM_RUA >= " . $parametros['rua'];
        }
        if (isset($parametros['predio']) && !empty($parametros['predio'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO >= " . $parametros['predio'];
        }
        if (isset($parametros['nivel']) && !empty($parametros['nivel'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL >= " . $parametros['nivel'];
        }
        if (isset($parametros['apto']) && !empty($parametros['apto'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO >= " . $parametros['apto'];
        }

        if (isset($parametros['ruaFinal']) && !empty($parametros['ruaFinal'])) {
            $SQLWhere .= " AND DE.NUM_RUA <= " . $parametros['ruaFinal'];
        }
        if (isset($parametros['predioFinal']) && !empty($parametros['predioFinal'])) {
            $SQLWhere .= " AND DE.NUM_PREDIO <= " . $parametros['predioFinal'];
        }
        if (isset($parametros['nivelFinal']) && !empty($parametros['nivelFinal'])) {
            $SQLWhere .= " AND DE.NUM_NIVEL <= " . $parametros['nivelFinal'];
        }
        if (isset($parametros['aptoFinal']) && !empty($parametros['aptoFinal'])) {
            $SQLWhere .= " AND DE.NUM_APARTAMENTO <= " . $parametros['aptoFinal'];
        }

        if ($orderBy != null) {
            $SQLOrderBy = $orderBy;
        } else {
            $SQLOrderBy = " ORDER BY DE.DSC_DEPOSITO_ENDERECO,E.DTH_VALIDADE, E.COD_PRODUTO, E.DSC_GRADE, E.NORMA, E.VOLUME, C.COD_CARACTERISTICA_ENDERECO, E.DTH_PRIMEIRA_MOVIMENTACAO";
        }
        $result = $this->getEntityManager()->getConnection()->query($SQL . $SQLWhere . $SQLOrderBy)->fetchAll(\PDO::FETCH_ASSOC);

        if ($returnQuery == true) {
            return $SQL . $SQLWhere . $SQLOrderBy;
        }

        if (isset($maxResult) && !empty($maxResult)) {
            if ($maxResult != false) {
                $arrayResult = array();
                foreach ($result as $key => $line) {
                    $arrayResult[] = $line;
                    if (($key + 1) >= $maxResult)
                        break;
                }
                $result = $arrayResult;
            }
        }
        if (!empty($result) && is_array($result)) {
            $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
            foreach ($result as $key => $value) {
                $result[$key]['QTD_EMBALAGEM'] = $value['QTD'];
                if ($value['QTD'] > 0) {
                    $vetEstoque = $embalagemRepo->getQtdEmbalagensProduto($value['COD_PRODUTO'], $value['DSC_GRADE'], $value['QTD']);
                    $result[$key]['QTD_EMBALAGEM'] = implode('<br />', $vetEstoque);
                }
            }
        }
        return $result;
    }

    public function getMenorValidadePulmao($codProduto, $grade){
        $tipoPulmao = EnderecoEntity::PULMAO;
        $SQL = "SELECT MIN(DTH_VALIDADE) AS DATA 
                FROM ESTOQUE  E 
                INNER JOIN DEPOSITO_ENDERECO DE ON E.COD_DEPOSITO_ENDERECO = DE.COD_DEPOSITO_ENDERECO
                WHERE COD_PRODUTO = '$codProduto'
                AND DSC_GRADE = '$grade'
                AND DE.COD_CARACTERISTICA_ENDERECO = $tipoPulmao";
        return $this->getEntityManager()->getConnection()->query($SQL)->fetch(\PDO::FETCH_ASSOC);
    }

    public function removePickingDinamicoProduto ($codProduto, $grade) {
        $estoques = $this->findBy(array('codProduto'=>$codProduto, 'grade' =>$grade));
        if (count($estoques) >0 ) return;

        $caracteristicaPickingRotativo = $this->getSystemParameterValue('ID_CARACTERISTICA_PICKING_ROTATIVO');

        $embalagemRepo = $this->getEntityManager()->getRepository("wms:Produto\Embalagem");
        $volumeRepo = $this->getEntityManager()->getRepository("wms:Produto\Volume");

        $embalagens = $embalagemRepo->findBy(array('codProduto'=>$codProduto, 'grade'=> $grade));
        $volumes = $volumeRepo->findBy(array('codProduto'=>$codProduto, 'grade'=> $grade));

        foreach ($embalagens as $embalagemEn) {
            if ($embalagemEn->getEndereco() != null) {
                $enderecoEn = $embalagemEn->getEndereco();
                if ($enderecoEn->getIdCaracteristica() == $caracteristicaPickingRotativo) {
                    $embalagemEn->setEndereco(null);
                    $this->getEntityManager()->persist($embalagemEn);
                }
            }
        }

        foreach ($volumes as $volumeEn) {
            if ($volumeEn->getEndereco() != null) {
                $enderecoEn = $volumeEn->getEndereco();
                if ($enderecoEn->getIdCaracteristica() == $caracteristicaPickingRotativo) {
                    $volumeEn->setEndereco(null);
                    $this->getEntityManager()->persist($volumeEn);
                }
            }
        }
    }

    public function validaMovimentaçãoExpedicaoFinalizada ($codDepositoEndereco, $codProduto, $grade) {

        $idStatusEmFinalizacao = Expedicao::STATUS_EM_FINALIZACAO;

        $sql = "SELECT REP.COD_PRODUTO, 
                       REP.DSC_GRADE, 
                       P.DSC_PRODUTO,
                       DE.DSC_DEPOSITO_ENDERECO,
                       LISTAGG(E.COD_EXPEDICAO,',') WITHIN GROUP (ORDER BY E.COD_EXPEDICAO) EXPEDICAO
                  FROM EXPEDICAO E
                  LEFT JOIN RESERVA_ESTOQUE_EXPEDICAO REE ON REE.COD_EXPEDICAO = E.COD_EXPEDICAO
                  LEFT JOIN RESERVA_ESTOQUE RE ON RE.COD_RESERVA_ESTOQUE = REE.COD_RESERVA_ESTOQUE
                  LEFT JOIN RESERVA_ESTOQUE_PRODUTO REP ON REP.COD_RESERVA_ESTOQUE = RE.COD_RESERVA_ESTOQUE
                  LEFT JOIN PRODUTO P ON P.COD_PRODUTO = REP.COD_PRODUTO AND P.DSC_GRADE = REP.DSC_GRADE
                  LEFT JOIN DEPOSITO_ENDERECO DE ON DE.COD_DEPOSITO_ENDERECO = RE.COD_DEPOSITO_ENDERECO
                 WHERE E.COD_STATUS = $idStatusEmFinalizacao
                   AND RE.IND_ATENDIDA = 'N'
                   AND RE.COD_DEPOSITO_ENDERECO = $codDepositoEndereco
                   AND REP.COD_PRODUTO = '$codProduto'
                   AND REP.DSC_GRADE = '$grade'
                 GROUP BY REP.COD_PRODUTO, REP.DSC_GRADE, P.DSC_PRODUTO, DE.DSC_DEPOSITO_ENDERECO";

        $result = $this->getEntityManager()->getConnection()->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        if (count($result) >0) {
            $endereco = $result[0]['DSC_DEPOSITO_ENDERECO'];
            $expedicao = $result[0]['EXPEDICAO'];
            $dscProduto = $result[0]['DSC_PRODUTO'];
            $codProduto = $result[0]['COD_PRODUTO'];
            $dscGrade = $result[0]['DSC_GRADE'];

            $msg = "O Endereço $endereco com o produto $codProduto/$dscGrade - $dscProduto, está em uso por um processo de finalização das expedições $expedicao. Aguarde alguns seguntos e tente novamente";

            throw new \Exception($msg);
        }

        return true;
    }

    public function getEstoqueToInventario($idEndereco, $idInventario = null)
    {
        $dql = $this->_em->createQueryBuilder();
        $dql->select("e")
            ->from("wms:Enderecamento\Estoque", "e")
            ->where("e.depositoEndereco = $idEndereco");

        if (!empty($idInventario)) {
            $dql->innerJoin("wms:InventarioNovo\InventarioEndProd", "iep", "WITH", "iep.codProduto = e.codProduto and iep.grade = e.grade and iep.ativo = 'S'")
                ->innerJoin("iep.inventarioEndereco", "ien", "WITH", "ien.depositoEndereco = $idEndereco")
                ->andWhere("ien.inventario = $idInventario");
        }

        return $dql->getQuery()->getResult();
    }

}
