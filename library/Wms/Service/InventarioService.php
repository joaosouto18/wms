<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:16
 */

namespace Wms\Service;


use Bisna\Base\Domain\Entity\EntityService;
use Doctrine\Common\Collections\Criteria;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Deposito\Endereco;
use Wms\Domain\Entity\Deposito\EnderecoRepository;
use Wms\Domain\Entity\Enderecamento\EstoqueProprietarioRepository;
use Wms\Domain\Entity\Enderecamento\EstoqueRepository;
use Wms\Domain\Entity\Enderecamento\HistoricoEstoque;
use Wms\Domain\Entity\Inventario;
use Wms\Domain\Entity\InventarioNovo;
use Wms\Domain\Entity\InventarioNovoRepository;
use Wms\Domain\Entity\OrdemServico;
use Wms\Domain\Entity\OrdemServicoRepository;
use Wms\Domain\Entity\Pessoa;
use Wms\Domain\Entity\Produto;
use Wms\Domain\Entity\Usuario;
use Wms\Math;

class InventarioService extends AbstractService
{
    /**
     * @param $params array
     * @return InventarioNovo|null
     * @throws \Exception
     */
    public function registrarNovoInventario($params) {

        $this->em->beginTransaction();

        try {
            $args = [
                'descricao' => (isset($params['descricao']) && !empty($params['descricao'])) ? $params['descricao'] : null,
                'modeloInventario' => $this->em->getReference('wms:InventarioNovo\ModeloInventario', $params['modelo']['id']),
                'criterio' => $params['criterio']
            ];
            unset($params['modelo']['id']);
            unset($params['modelo']['dscModelo']);
            unset($params['modelo']['dthCriacao']);
            unset($params['modelo']['ativo']);
            unset($params['modelo']['isDefault']);

            /** @var InventarioNovo $inventarioEn */
            $inventarioEn = self::save( array_merge($args, $params['modelo']), false);

            /** @var InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:InventarioNovo\InventarioEnderecoNovo');

            if ($inventarioEn->isPorProduto()) {
                /** @var InventarioNovo\InventarioEndProdRepository $invEndProdRepod */
                $invEndProdRepod = $this->em->getRepository('wms:InventarioNovo\InventarioEndProd');
            }

            foreach ($params['selecionados'] as $item) {
                $inventarioEnderecoEn = $inventarioEnderecoRepo->findOneBy(['inventario' => $inventarioEn, 'depositoEndereco' => $item['id']]);

                if (empty($inventarioEnderecoEn))
                    $inventarioEnderecoEn = $inventarioEnderecoRepo->save([
                        'inventario' => $inventarioEn,
                        'depositoEndereco' => $this->em->getReference('wms:Deposito\Endereco', $item['id']),
                        'ativo' => 'S',
                        'contagem' => 0
                    ]);

                if ($inventarioEn->isPorProduto()) {
                    $invEndProdRepod->save([
                        'inventarioEndereco' => $inventarioEnderecoEn,
                        'ativo' => 'S',
                        'produto' => $this->em->getReference('wms:Produto', ['id' => $item['codProduto'], 'grade' => $item['grade']])
                    ]);
                }
            }

            self::newLog($inventarioEn,InventarioNovo\InventarioAndamento::STATUS_GERADO);
            $this->em->flush();
            $this->em->commit();
            return $inventarioEn;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $id
     * @return bool|array
     * @throws \Exception
     */
    public function liberarInventario($id)
    {
        $this->em->beginTransaction();
        try {
            /** @var InventarioNovo $inventarioEn */
            $inventarioEn = $this->find($id);
            if (!$inventarioEn->isGerado()) {
                throw new \Exception("O inventário $id está " . $inventarioEn->getDscStatus());
            }

            $impedimentos = $this->getRepository()->findImpedimentosLiberacao($id);
            if (!empty($impedimentos)) {
                return [$impedimentos, $inventarioEn];
            } else {
                $inventarioEn->liberar();

                /** @var InventarioNovo\InventarioEnderecoNovo[] $invEnds */
                $invEnds = $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->findBy(["inventario" => $inventarioEn]);

                foreach ($invEnds as $invEnd) {
                    $this->addNovaContagem($invEnd);
                    $depEndEn = $invEnd->getDepositoEndereco();
                    $depEndEn->setInventarioBloqueado("S");
                    $this->em->persist($depEndEn);
                }

                $this->em->persist($inventarioEn);
                self::newLog($inventarioEn,InventarioNovo\InventarioAndamento::STATUS_LIBERADO);
                $this->em->flush();
                $this->em->commit();
                return true;
            }
        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $id
     * @return InventarioNovo
     * @throws \Exception
     */
    public function removerProduto($id){
        $this->em->beginTransaction();

        try {
            //exclusao logica do produto
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository $inventarioEndProdRepo */
            $inventarioEndProdRepo = $this->em->getRepository('wms:InventarioNovo\InventarioEndProd');
            /** @var InventarioNovo\InventarioEndProd $produto */
            $produto = $inventarioEndProdRepo->find($id);

            /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
            $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');
            $ordemServicoRepo->buscaOsProdutoExcluidoDoInventario($produto->getInventarioEndereco()->getId(), $produto->getCodProduto(), $produto->getGrade());

            //exclusão lógica

            $produto->setAtivo(false);
            $this->em->persist($produto);
            self::newLog($produto->getInventarioEndereco()->getInventario(),InventarioNovo\InventarioAndamento::REMOVER_PRODUTO, null, $produto->getCodProduto() . " - " . $produto->getGrade());
            $this->em->flush();

            // se nao existir mais produtos no endereço, cancela o endereço
            $produtoAtivo = $inventarioEndProdRepo->findOneBy(['inventarioEndereco' => $produto->getInventarioEndereco()->getId(), 'ativo' => 'S']);

            if( empty($produtoAtivo) )
                $this->removerEndereco($produto->getInventarioEndereco()->getId());

            $this->em->commit();
            return $produto->getInventarioEndereco()->getInventario();
        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $id
     * @return InventarioNovo
     * @throws \Exception
     */
    public function removerEndereco($id)
    {
        $this->em->beginTransaction();

        try {
            //exclusao logica do endereço
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:InventarioNovo\InventarioEnderecoNovo');
            /** @var InventarioNovo\InventarioEnderecoNovo $endereco */
            $endereco = $inventarioEnderecoRepo->find($id);

            /** @var \Wms\Domain\Entity\OrdemServicoRepository $ordemServicoRepo */
            $ordemServicoRepo = $this->em->getRepository('wms:OrdemServico');
            $ordemServicoRepo->buscaOsEnderecoExcluidoDoInventario($id);

            $endereco->setAtivo(false);
            $this->em->persist($endereco);

            $depEndEn = $endereco->getDepositoEndereco();
            $depEndEn->setInventarioBloqueado("N");
            $this->em->persist($depEndEn);

            self::newLog($endereco->getInventario(),InventarioNovo\InventarioAndamento::REMOVER_ENDERECO, null, $endereco->getDepositoEndereco()->getDescricao());
            $this->em->flush();

            // se nao existir mais endereços ativos nesse inventario, cancela o mesmo
            $enderecoAtivo = $inventarioEnderecoRepo->findOneBy(['inventario' => $endereco->getInventario()->getId(), 'ativo' => 'S']);

            if( empty($enderecoAtivo) )
            {
                /** @var \Wms\Domain\Entity\InventarioNovo $inventarioEn */
                $inventarioEn = $endereco->getInventario();
                self::cancelarInventario($inventarioEn->getId(), $inventarioEn);
            }

            $this->em->commit();

            return $endereco->getInventario();
        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param InventarioNovo\InventarioEnderecoNovo $invEndEn
     * @param int $proximaSequencia
     * @param int $contagem
     * @param bool $divergencia
     * @return InventarioNovo\InventarioContEnd
     * @throws \Exception
     */
    public function addNovaContagem(InventarioNovo\InventarioEnderecoNovo $invEndEn, $proximaSequencia = 1, $contagem = 1, $divergencia = false)
    {
        try {
            /** @var InventarioNovo\InventarioContEndRepository $inventContEndRepo */
            $inventContEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEnd");

            $invContEndEn = $inventContEndRepo->save([
                "inventarioEndereco" => $invEndEn,
                "sequencia" => $proximaSequencia,
                "contagem" => $contagem,
                "contagemDivergencia" => $divergencia
            ], false);

            if($invEndEn->getStatus() == InventarioNovo\InventarioEnderecoNovo::STATUS_PENDENTE){
                $invEndEn->setConferencia();
            }
            elseif($divergencia && $invEndEn->getStatus() == InventarioNovo\InventarioEnderecoNovo::STATUS_CONFERENCIA){
                $invEndEn->setDivergencia();
            }

            $invEndEn->setContagem($proximaSequencia);

            $this->em->persist($invEndEn);

            return $invContEndEn;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario
     * @param $contEnd
     * @param $produto
     * @param $conferencia
     * @param $tipoConferencia
     * @throws \Exception
     */
    public function novaConferencia($inventario, $contEnd, $produto, $conferencia, $tipoConferencia)
    {
        /** @var InventarioNovo\InventarioContEndProdRepository $contEndProdRepo */
        $contEndProdRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd");

        $this->em->beginTransaction();
        try {
            $volSeparadamente = json_decode($inventario['volumesSeparadamente']);

            /** @var InventarioNovo\InventarioContEndProdRepository $inventContEndProdRepo */
            $inventContEndProdRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd");
            $resultado = $inventContEndProdRepo->getContagemFinalizada($contEnd, $produto, $volSeparadamente);

            if(empty($resultado)) {

                $elements = [];
                $isEmb = false;
                if (isset($produto['idVolume']) && !empty(json_decode($produto['idVolume']))) {
                    $isEmb = false;
                    if ($volSeparadamente)
                        $elements[] = $this->em->getReference("wms:Produto\Volume", $produto['idVolume']);
                    else {
                        /** @var Produto\Volume[] $elements */
                        $elements = $this->em->getRepository("wms:Produto\Volume")->getProdutosVolumesByNorma($produto['norma'], $produto['idProduto'], $produto['grade'], null, true);
                        $finalizados = $contEndProdRepo->getProdutosContagemFinalizada($contEnd['idInvEnd'], $contEnd['sequencia']);
                        if (!empty($elements) && !empty($finalizados)) {
                            $loteConf = json_decode($conferencia['lote']);
                            foreach ($elements as $k => $vol) {
                                foreach ($finalizados as $elem) {
                                    if ($vol->getCodProduto() == $elem['COD_PRODUTO'] &&
                                        $vol->getGrade() == $elem['DSC_GRADE'] &&
                                        $loteConf == $elem['DSC_LOTE'] &&
                                        $vol->getId() == $elem['COD_PRODUTO_VOLUME']) {
                                            unset($elements[$k]);
                                            break;

                                    }
                                }
                            }
                        }
                    }
                }
                elseif (isset($produto['idEmbalagem']) && !empty(json_decode($produto['idEmbalagem']))) {
                    $isEmb = true;
                    $elements[] = $this->em->getReference("wms:Produto\Embalagem", $produto['idEmbalagem']);
                }
                $conferencia["validade"] = (!empty($conferencia['validade'])) ? date_create_from_format("d/m/Y", $conferencia['validade']) : null;

                $this->registrarConferencia(
                    $elements,
                    $this->getOsUsuarioContagem($contEnd, $inventario, $tipoConferencia, true),
                    $conferencia,
                    $this->em->getReference("wms:Produto", ["id" => $produto['idProduto'], "grade" => $produto['grade']]),
                    $isEmb,
                    $produto["quantidadeEmbalagem"],
                    $produto["codigoBarras"]);

                $this->em->flush();
                $this->em->commit();
            }
            else
               throw new \Exception("A contagem desse produto já foi finalizada.");

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param array $elements
     * @param InventarioNovo\InventarioContEndOs $invContEndOs
     * @param array $conferencia
     * @param Produto $produto
     * @param bool $isEmb
     * @param int $qtdElem
     * @param null $codBarras
     * @param null $divergente
     * @throws \Exception
     */
    private function registrarConferencia($elements, $invContEndOs, $conferencia, $produto, $isEmb, $qtdElem = 1, $codBarras = null, $divergente = null)
    {
        try {
            foreach ($elements as $element) {
                $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd")->save([
                    "invContEndOs" => $invContEndOs,
                    "produto" => $produto,
                    "lote" => $conferencia['lote'],
                    "qtdContada" => $conferencia['qtd'],
                    "produtoEmbalagem" => ($isEmb) ? $element : null,
                    "qtdEmbalagem" => $qtdElem,
                    "codBarras" => $codBarras,
                    "produtoVolume" => (!$isEmb) ? $element : null,
                    "validade" => $conferencia['validade'],
                    "divergente" => $divergente
                ], false);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contEnd
     * @param $inventario
     * @param $tipoConferencia
     * @param $createIfNoExist
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    public function getOsUsuarioContagem($contEnd, $inventario = [], $tipoConferencia = null, $createIfNoExist = false)
    {
        try {
            /** @var Usuario $usuario */
            $usuario = $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

            /** @var InventarioNovo\InventarioContEndOsRepository $contagemEndOsRepo */
            $contagemEndOsRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs");

            /** @var InventarioNovo\InventarioContEndOs $invContEndOs */
            $invContEndOs = $contagemEndOsRepo->getOsContUsuario( $contEnd["idContEnd"],  $usuario->getId());

            if (!empty($invContEndOs) && !empty($invContEndOs->getOrdemServico()->getDataFinal()))
                throw new \Exception("Sua ordem de serviço já foi finalizada em: ". $invContEndOs->getOrdemServico()->getDataFinal());

            if (empty($invContEndOs)) {
                if (!$createIfNoExist)
                    throw new \Exception("Não existe O.S aberta para esse usuário");

                $osContagensAnteriores = $contagemEndOsRepo->getContagensUsuario( $usuario->getId(), $contEnd["idInvEnd"]);

                if (!empty($osContagensAnteriores) && !json_decode($inventario['usuarioNContagens']))
                    throw new \Exception("Este usuário não tem permissão para iniciar uma nova contagem neste endereço");

                $invContEndOs = $this->addNewOsContagem($contEnd["idContEnd"], $usuario, $tipoConferencia);
            }

            return $invContEndOs;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $idContEnd
     * @param $usuario Usuario
     * @param $tipoConferencia
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    private function addNewOsContagem($idContEnd, $usuario, $tipoConferencia)
    {
        try {
            /** @var OrdemServico $newOsEn */
            $newOsEn = $this->em->getRepository("wms:OrdemServico")->addNewOs([
                "dataInicial" => new \DateTime(),
                "pessoa" => $usuario->getPessoa(),
                "atividade" => $this->em->getReference('wms:Atividade', Atividade::INVENTARIO),
                "formaConferencia" => $tipoConferencia,
                "dscObservacao" => "Inclusão de novo usuário na contagem"
            ], false);

            return $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs")->save([
                "invContEnd" => $this->em->getReference("wms:InventarioNovo\InventarioContEnd", $idContEnd),
                "indAtivo" => true,
                "ordemServico" => $newOsEn
            ], false);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario
     * @param $contEnd
     * @param $tipoConferencia
     * @return array
     * @throws \Exception
     */
    public function finalizarOs($inventario, $contEnd, $tipoConferencia)
    {
        $this->em->beginTransaction();
        try {
            /** @var OrdemServicoRepository $osRepo */
            $osRepo = $this->em->getRepository("wms:OrdemServico");

            $outrasOs = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs")
                ->getOutrasOsAbertasContagem(\Zend_Auth::getInstance()->getIdentity()->getId(), $contEnd['idContEnd']);

            $result = ["code" => 1, "msg" => "Ordem de serviço finalizada com sucesso"];

            $osUsuarioCont = $this->getOsUsuarioContagem($contEnd, $inventario, $tipoConferencia,  empty($outrasOs));

            if (empty($outrasOs)) {
                $result = $this->compararContagens( $osUsuarioCont, $inventario);
            }

            $osRepo->finalizar(null, "Contagem finalizada", $osUsuarioCont->getOrdemServico(), false);


            $this->em->flush();
            $this->em->commit();

            return $result;
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $invContEndOs InventarioNovo\InventarioContEndOs
     * @param $inventario
     * @return array
     * @throws \Exception
     */
    private function compararContagens($invContEndOs, $inventario)
    {
        try {
            /** @var InventarioNovo\InventarioContEndProdRepository $contEndProdRepo */
            $contEndProdRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd");

            $invContEnd = $invContEndOs->getInvContEnd();

            $countQtdsIguais = [];

            $validaValidade = ($inventario['controlaValidade'] === InventarioNovo\ModeloInventario::VALIDADE_VALIDA);
            $invPorProduto = ($inventario['criterio'] === InventarioNovo::CRITERIO_PRODUTO);

            $strConcat = "+=+";

            /** @var \Wms\Domain\Entity\Enderecamento\Estoque[] $estoques */
            $estoques = [];
            if (json_decode($inventario['comparaEstoque'])) {
                $estoques = $this->em->getRepository("wms:Enderecamento\Estoque")->getEstoqueToInventario(
                    $invContEnd->getInventarioEndereco()->getDepositoEndereco()->getId(),
                    ($invPorProduto) ? $inventario['id'] : null
                );
            }

            $finalizados = $contEndProdRepo->getProdutosContagemFinalizada($invContEnd->getInventarioEndereco()->getId(), $invContEnd->getSequencia());
            if (!empty($estoques) && !empty($finalizados)) {
                foreach ($estoques as $k => $prod) {
                    foreach ($finalizados as $elem) {
                        if ($prod->getCodProduto() == $elem['COD_PRODUTO'] && $prod->getGrade() == $elem['DSC_GRADE'] && $prod->getLote() == $elem['DSC_LOTE']) {
                            if (empty($prod->getProdutoVolume()) || (!empty($prod->getProdutoVolume() && $prod->getProdutoVolume()->getId() == $elem['COD_PRODUTO_VOLUME']))) {
                                unset($estoques[$k]);
                                break;
                            }
                        }
                    }
                }
            }

            $estoques = array_values($estoques);

            $contagemAnterior = $contEndProdRepo->getProdutosContagemAnterior($invContEnd->getInventarioEndereco()->getId(), $invContEnd->getSequencia());

            $contados = $contEndProdRepo->getContagensProdutos($invContEnd->getId());

            $volSeparados = json_decode($inventario["volumesSeparadamente"]);

            foreach ($contados as $contagem) {
                $estoque = null;
                if (!empty($estoques)) {
                    for ($i = 0; $i < count($estoques); $i++) {
                        $idVolEstoque = (!empty($estoques[$i]->getProdutoVolume())) ? $estoques[$i]->getProdutoVolume()->getId() : null;
                        if ($contagem["COD_PRODUTO"] != $estoques[$i]->getCodProduto() ||
                            $contagem["DSC_GRADE"] != $estoques[$i]->getGrade() ||
                            $contagem["DSC_LOTE"] != $estoques[$i]->getLote() ||
                            ($volSeparados && !empty($contagem["COD_PRODUTO_VOLUME"]) && $contagem["COD_PRODUTO_VOLUME"] != $idVolEstoque)
                        ) {
                            continue;
                        }

                        $estoque = $estoques[$i];
                        unset($estoques[$i]);

                        $estoques = array_values($estoques);
                        break;
                    }
                }

                foreach($contagemAnterior as $key => $prodContAnt) {
                    if (
                        $prodContAnt['COD_PRODUTO'] == $contagem["COD_PRODUTO"] &&
                        $prodContAnt['DSC_GRADE'] == $contagem["DSC_GRADE"] &&
                        $prodContAnt['COD_PRODUTO_VOLUME'] == $contagem["COD_PRODUTO_VOLUME"] &&
                        $prodContAnt['DSC_LOTE'] == $contagem["DSC_LOTE"]
                    ) {
                        unset($contagemAnterior[$key]);
                    }
                }

                if (!empty($estoque)) {
                    $prod = [
                        $estoque->getCodProduto(),
                        $estoque->getGrade(),
                        $estoque->getLote(),
                        (!empty($estoque->getProdutoVolume()))? $estoque->getProdutoVolume()->getId() : null
                    ];
                    $elemCount = [
                        $estoque->getQtd(),
                        (!empty($estoque->getValidade()) && $validaValidade) ? $estoque->getValidade()->format("d/m/Y") : null
                    ];
                    $countQtdsIguais[implode($strConcat, $prod)][implode($strConcat, $elemCount)][] = "estoque";
                }

                $strProd = implode($strConcat, [
                    $contagem['COD_PRODUTO'],
                    $contagem['DSC_GRADE'],
                    $contagem['DSC_LOTE'],
                    $contagem['COD_PRODUTO_VOLUME']
                ]);
                $elemCount = [
                    $contagem['QTD_CONTAGEM'],
                    ($validaValidade) ? $contagem['VALIDADE'] : null
                ];
                $countQtdsIguais[$strProd][implode($strConcat, $elemCount)][] = $contagem['NUM_SEQUENCIA'];

                foreach ($contEndProdRepo->getContagensAnteriores($invContEnd->getInventarioEndereco()->getId(), $invContEnd->getSequencia(),
                    $contagem['COD_PRODUTO'], $contagem['DSC_GRADE'], $contagem['DSC_LOTE'], $contagem['COD_PRODUTO_VOLUME']) as $contAnterior) {
                    $elemCount = [
                        $contAnterior['QTD_CONTAGEM'],
                        ($validaValidade) ? $contAnterior['VALIDADE'] : null
                    ];
                    $countQtdsIguais[$strProd][implode($strConcat, $elemCount)][] = $contAnterior['NUM_SEQUENCIA'];
                }
            }

            if ($invContEnd->getContagemDivergencia() == 'N' && json_decode($inventario['contarTudo']) && !empty($estoques)) {

                foreach ($estoques as $estoque) {
                    $prod = [
                        "idProduto" => $estoque->getCodProduto(),
                        "grade" => $estoque->getGrade(),
                        "lote" => $estoque->getLote(),
                        "idVolume" => (!empty($estoque->getProdutoVolume()))? $estoque->getProdutoVolume()->getId() : null
                    ];
                    $elemCount = [
                        0,
                        (!empty($estoque->getValidade()) && $validaValidade) ? $estoque->getValidade()->format("d/m/Y") : null
                    ];
                    $countQtdsIguais[implode($strConcat, $prod)][implode($strConcat, $elemCount)][] = $invContEnd->getSequencia();

                    $this->zerarProduto($invContEndOs, $prod,true);
                }
            }

            $itensRecontPendente = [];
            foreach ($contagemAnterior as $produto) {
                $prod = [
                    "idProduto" => $produto['COD_PRODUTO'],
                    "grade" => $produto['DSC_GRADE'],
                    "lote" => $produto['DSC_LOTE'],
                    "idVolume" => $produto['COD_PRODUTO_VOLUME']
                ];
                $elemCount = [
                    0,
                    null
                ];
                $strProd = implode($strConcat, $prod);
                $countQtdsIguais[$strProd][implode($strConcat, $elemCount)][] = $invContEnd->getSequencia();
                $itensRecontPendente[] = $strProd;
                $this->zerarProduto($invContEndOs, $prod,true);
            }

            $count = [];
            foreach ($countQtdsIguais as $strProd => $arrCount) {
                foreach ($arrCount as $seqs) {
                    if (!isset($count[$strProd]) || $count[$strProd] < count($seqs))
                        $count[$strProd] = count($seqs);
                }
            }

            $nContagensNecessarias = (json_decode($inventario['comparaEstoque'])) ? $inventario['numContagens'] + 1 : $inventario['numContagens'] ;

            $temDivergencia = false;
            foreach ($count as $strProd => $contsIguais) {
                $prodX = explode($strConcat, $strProd);
                $divergente = false;
                if ($contsIguais < $nContagensNecessarias || in_array($strProd, $itensRecontPendente)) {
                    $temDivergencia = $divergente = true;
                }
                $this->updateFlagContagensProdutos($invContEndOs, $prodX[0], $prodX[1], $prodX[2], $prodX[3], $divergente);
            }

            if ($temDivergencia || (empty($count) && $invContEnd->getSequencia() < $inventario['numContagens'])) {
                $contDiverg = ($invContEnd->getSequencia() >= $inventario['numContagens']);
                $this->addNovaContagem(
                    $invContEnd->getInventarioEndereco(),
                    $invContEnd->getSequencia() + 1,
                    (!$invContEnd->isContagemDivergencia() && ($invContEnd->getSequencia() >= $inventario['numContagens'])) ? 1 : $invContEnd->getContagem() + 1,
                    $contDiverg
                );
                $situacao = ($contDiverg) ? "divergência" : "sucesso";
                return ["code" => 2, "msg" => "Contagem finalizada com $situacao"];
            } else {
                return $this->finalizarEndereco($invContEnd->getInventarioEndereco());
            }

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $invContEndOs InventarioNovo\InventarioContEndOs
     * @param $produto
     * @param $divergente
     * @throws \Exception
     */
    private function zerarProduto($invContEndOs, $produto, $divergente)
    {
        try {

            if (isset($produto["idVolume"]) && ($produto["idVolume"] != 'null') ) {
                $isEmb = false;
                $produtoVolumeRepo = $this->getEntityManager()->getRepository('wms:Produto\Volume');
                $volumeEn = $produtoVolumeRepo->find($produto["idVolume"]);
                $elements[] = $volumeEn;
            } else {
                $isEmb = true;
                $elements[] = null;
            }

            $produto["qtd"] = 0;
            $produto["validade"] = null;
            $produto['lote'] = null;

            $this->registrarConferencia(
                $elements,
                $invContEndOs,
                $produto,
                $this->em->getReference("wms:Produto", ["id" => $produto['idProduto'], "grade" => $produto['grade']]),
                $isEmb,
                0,
                null,
                $divergente
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contEndOs InventarioNovo\InventarioContEndOs
     * @param $produto
     * @param $grade
     * @param $lote
     * @param $vol
     * @param $isDiverg bool
     * @throws \Exception
     */
    private function updateFlagContagensProdutos($contEndOs, $produto, $grade, $lote, $vol, $isDiverg)
    {
        try {
            /** @var InventarioNovo\InventarioContEndOsRepository $invContEndOsRepo */
            $invContEndOsRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOS");
            /** @var InventarioNovo\InventarioContEndProdRepository $invContEndProdRepo */
            $invContEndProdRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd");

            foreach ($invContEndOsRepo->findBy(['invContEnd' => $contEndOs->getInvContEnd()]) as $osContEnd) {

                $arg = [
                    "invContEndOs" => $osContEnd,
                    "codProduto" => $produto,
                    "grade" => $grade,
                    "lote" => (!empty($lote)) ? $lote : null,
                    "produtoVolume" => (!empty($vol)) ? $vol : null
                ];

                /** @var InventarioNovo\InventarioContEndProd $contProd */
                foreach ($invContEndProdRepo->findBy($arg) as $contProd) {
                    $contProd->setDivergente($isDiverg);
                    $this->em->persist($contProd);
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventarioEnd InventarioNovo\InventarioEnderecoNovo
     * @return array
     * @throws \Exception
     */
    private function finalizarEndereco($inventarioEnd)
    {
        try {
            if (!$inventarioEnd->getInventario()->isLiberado()) {
                throw new \Exception("Este endereço " . $inventarioEnd->getDepositoEndereco()->getDescricao() .
                    " não pode ser finalizado pois seu inventário está " . $inventarioEnd->getInventario()->getDscStatus());
            }

            if (!$inventarioEnd->isAtivo()) {
                throw new \Exception("Este endereço " . $inventarioEnd->getDepositoEndereco()->getDescricao() . " foi removido do inventário e não pode ser finalizado!");
            }

            $inventarioEnd->setFinalizado();
            $this->em->persist($inventarioEnd);

            if (empty($this->getRepository()->getEnderecosPendentes($inventarioEnd))) {
                return $this->concluirInventario($inventarioEnd->getInventario());
            }

            return ["code" => 3, "msg" => "Endereço finalizado com sucesso"];

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario InventarioNovo
     * @return array
     * @throws \Exception
     */
    private function concluirInventario($inventario)
    {
        try {
            $inventario->concluir();
            $this->em->persist($inventario);
            self::newLog($inventario,InventarioNovo\InventarioAndamento::STATUS_CONCLUIDO);
            return ["code" => 4, "msg" => "Inventário concluído com sucesso"];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $idInventario
     * @param $sequencia
     * @param $isDiverg
     * @param $endereco
     * @param $isPicking
     * @return array
     * @throws \Exception
     */
    public function getInfoEndereco($idInventario, $sequencia, $isDiverg, $endereco, $isPicking)
    {
        try {
            /** @var InventarioNovo\InventarioEnderecoNovoRepository $invEndRepo */
            $invEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo");
            $check = $invEndRepo->checkContEndOsFinalizada($idInventario, $sequencia, $endereco, \Zend_Auth::getInstance()->getIdentity()->getId());
            if (!empty($check)) throw new \Exception("Este usuário já finalizou essa contagem neste endereço");

            if ($isDiverg == "S") {
                $result = $invEndRepo->getItensDiverg($idInventario, $sequencia, $endereco);
            } else {
                $result = $invEndRepo->getInfoEndereco($idInventario, $sequencia, $endereco);
            }

            $pickingsAssoc = [];
            if (json_decode($isPicking)) {
                /** @var EnderecoRepository $depEndRepo */
                $depEndRepo = $this->em->getRepository("wms:Deposito\Endereco");

                foreach ($depEndRepo->getProdutosPicking($endereco) as $val) {
                    $pickingsAssoc['uniKey'][] = "$val[COD_PRODUTO]--$val[DSC_GRADE]--$val[ID_NORMA]";
                    $pickingsAssoc['itens'][] = [
                        'idProduto' => $val['COD_PRODUTO'],
                        'dscProduto' => $val['DSC_PRODUTO'],
                        'grade' => $val['DSC_GRADE']
                    ];
                }
            }

            $agroup = [];
            foreach ($result as $item) {
                $strConcat = "$item[codProduto]--$item[grade]--$item[idVol]--$item[lote]";
                if (!isset($agroup[$strConcat])) {
                    $agroup[$strConcat] = [
                        "idProduto" => $item['codProduto'],
                        "grade" => $item['grade'],
                        "descricao" => $item['descricao'],
                        "codBarras" => [$item["codBarras"]],
                        "lote" => (isset($item['lote'])) ? $item['lote'] : null,
                        "idVolume" => (isset($item['idVol'])) ? $item['idVol'] : null,
                        "dscVolume" => (isset($item['dscVol'])) ? $item['dscVol'] : null,
                        "zerado" => (isset($item['qtdContada']) && empty($item['qtdContada']))
                    ];
                } else {
                    if (!in_array($item["codBarras"], $agroup[$strConcat]["codBarras"]))
                        $agroup[$strConcat]["codBarras"][] = $item["codBarras"];
                }
            }

            return ["pickingOf" => $pickingsAssoc, "listItens" => $agroup];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contEnd
     * @param $produto
     * @throws \Exception
     */
    public function confirmarProdutoZerado($inventario, $contEnd, $produto, $tipoConferencia)
    {
        $this->em->beginTransaction();
        try{
            $produto["idVolume"] = json_decode($produto["idVolume"]);
            $this->zerarProduto(
                $this->getOsUsuarioContagem($contEnd, $inventario, $tipoConferencia, true),
                $produto,
                null
            );

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
        return;
    }

    public function getResultadoInventario($id)
    {
        $results = $this->getRepository()->getResultInventario($id, false, true);
        $return = [];
        foreach ($results as $result) {
            $obj = new \stdClass;
            $obj->endereco         = $result["DSC_DEPOSITO_ENDERECO"];
            $obj->rua              = $result["NUM_RUA"];
            $obj->predio           = $result["NUM_PREDIO"];
            $obj->nivel            = $result["NUM_NIVEL"];
            $obj->apto             = $result["NUM_APARTAMENTO"];
            $obj->codProduto       = $result["COD_PRODUTO"];
            $obj->dscProduto       = $result["DSC_PRODUTO"];
            $obj->grade            = $result["DSC_GRADE"];
            $obj->elemento         = $result["ELEMENTO"];
            $obj->lote             = (empty($result["DSC_LOTE"])) ? "--" : $result["DSC_LOTE"];
            $obj->qtdInventariada  = $result["QTD_INVENTARIADA"];
            $obj->qtdDiff          = $result["QTD"];
            $obj->qtdEstoque       = $result["POSSUI_SALDO"];

            if (!empty($result["DTH_VALIDADE"])) {
                $data = new \DateTime($result["DTH_VALIDADE"]);
                $obj->validade = $data->format("d/m/Y");
            }

            $return[] = $obj;
        }

        return $return;

    }

    public function getDivergenciasInventario($id)
    {
        $results = $this->getRepository()->getListDivergencias($id);
        $return = [];
        foreach ($results as $result) {
            $obj = new \stdClass;
            $obj->endereco         = $result["DSC_DEPOSITO_ENDERECO"];
            $obj->contagem         = $result["NUM_CONTAGEM"];
            $obj->codProduto       = $result["COD_PRODUTO"];
            $obj->dscProduto       = $result["DSC_PRODUTO"];
            $obj->grade            = $result["DSC_GRADE"];
            $obj->loteEstq         = (empty($result["DSC_LOTE"])) ? "--" : $result["DSC_LOTE"];
            $obj->loteConf         = (empty($result["DSC_LOTE"])) ? "--" : $result["DSC_LOTE"];
            $obj->qtdEstq          = $result["QTD_ESTQ"];
            $obj->qtdConf          = $result["QTD_CONF"];
            $obj->validadeEstq     = $result["VALIDADE_ESTQ"];
            $obj->validadeConf     = $result["VALIDADE_CONF"];

            $return[] = $obj;
        }

        return $return;

    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function finalizarInventario($id)
    {
        $this->em->beginTransaction();
        try {
            /** @var InventarioNovo $invEn */
            $invEn = $this->find($id);

            if (!($invEn->isConcluido() || $invEn->isInterrompido())) throw new \Exception("Impossível atualizar o estoque com este inventário $id pois está: " . $invEn->getDscStatus());

            $resultInv = $this->getRepository()->getResultInventario($id);

            $controlaProprietario = ($this->getRepository()->getSystemParameterValue("CONTROLE_PROPRIETARIO") == "S");

            if ($controlaProprietario) {
                /** @var EstoqueProprietarioRepository $estoqueProprietarioRepo */
                $estoqueProprietarioRepo = $this->em->getRepository("wms:Enderecamento\EstoqueProprietario");
                $produtos = [];
            }

            foreach ($resultInv as $item) {
                if ($controlaProprietario) {
                    if (!isset($produtos[$item['COD_PRODUTO']])) {
                        $produtos[ $item['COD_PRODUTO'] ] = [ "codProduto" => $item["COD_PRODUTO"] ];
                    }
                }

                if ($item["QTD"] != 0 || !empty($item["DTH_VALIDADE"])) {
                    /** @var Produto $produtoEn */
                    $produtoEn = $this->em->getRepository("wms:Produto")->find(["id"=> $item["COD_PRODUTO"], "grade" => $item["DSC_GRADE"]]);
                    if (empty($produtoEn)) throw new \Exception("O produto $item[COD_PRODUTO] - $item[DSC_GRADE] não encontrado");

                    $elem = null;
                    if ($produtoEn->getTipoComercializacao()->getId() === Produto::TIPO_UNITARIO) {
                        $embs = $produtoEn->getEmbalagens()->filter(
                            function ($e) {
                                return empty($e->getDataInativacao());
                            }
                        )->toArray();

                        if (empty($embs)) throw new \Exception("O produto $item[COD_PRODUTO] - $item[DSC_GRADE] não tem embalagens ativas");

                        usort($embs, function ($a, $b) {
                            return $a->getQuantidade() > $b->getQuantidade();
                        });

                        $elem = $embs[0];
                    }
                    elseif ($produtoEn->getTipoComercializacao()->getId() === Produto::TIPO_COMPOSTO && !empty($item["COD_PRODUTO_VOLUME"])) {
                        $elem = $this->em->getReference("wms:Produto\Volume", $item["COD_PRODUTO_VOLUME"]);
                    }

                    $this->atualizarEstoque(
                        $id,
                        $item["COD_DEPOSITO_ENDERECO"],
                        $produtoEn,
                        $item["DSC_LOTE"],
                        $produtoEn->getTipoComercializacao()->getId(),
                        $elem,
                        $item["QTD"],
                        ["dataValidade" => $item["DTH_VALIDADE"]],
                        (empty($item["POSSUI_SALDO"])) ? new \DateTime() : null
                    );
                }
            }

            $this->em->flush();

            if ($controlaProprietario) {
                foreach ($produtos as $produto) {
                    $estoqueProprietarioRepo->updateSaldoByInventario($produto["codProduto"], $id);
                }
            }

            $invEn->finalizar();
            $this->em->persist($invEn);
            self::newLog($invEn,InventarioNovo\InventarioAndamento::STATUS_FINALIZADO);

            $this->em->flush();
            $this->em->commit();

            $this->em->getRepository("wms:Deposito\Endereco")->desbloquearByInventario($id);

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $idInventario
     * @param $endereco
     * @param Produto $produtoEn
     * @param $lote
     * @param $tipo
     * @param $elem
     * @param $qtd
     * @param $validade
     * @param $dthEntrada
     * @throws \Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function atualizarEstoque($idInventario, $endereco, $produtoEn, $lote, $tipo, $elem, $qtd, $validade, $dthEntrada)
    {
        /** @var EstoqueRepository $estoqueRepo */
        $estoqueRepo = $this->em->getRepository("wms:Enderecamento\Estoque");
        /** @var Produto\LoteRepository $loteRepo */
        $loteRepo = $this->em->getRepository("wms:Produto\Lote");

        $idUsuario = \Zend_Auth::getInstance()->getIdentity()->getId();

        if ($produtoEn->getIndControlaLote() == "S" and !empty($lote) and !empty($dthEntrada)) {
            if (empty($loteRepo->verificaLote($lote, $produtoEn->getId(), $produtoEn->getGrade(), $idUsuario, true)))
                $loteRepo->save($produtoEn->getId(), $produtoEn->getGrade(), $lote, $idUsuario, Produto\Lote::INTERNO);
        }

        $elemType = ($tipo == Produto::TIPO_UNITARIO) ? "embalagem" : "volume";

        $estoqueRepo->movimentaEstoque([
            "idInventario" => $idInventario,
            "endereco" => $this->em->find("wms:Deposito\Endereco", $endereco),
            "produto" => $produtoEn,
            "lote" => $lote,
            $elemType => $elem,
            "qtd" => $qtd,
            "observacoes" => "Mov. correção inventário $idInventario",
            "usuario" => $this->em->getReference('wms:Usuario', $idUsuario),
            "tipo" => HistoricoEstoque::TIPO_INVENTARIO,
            "dthEntrada" => $dthEntrada
        ],false,false, $validade, true);
    }

    /**
     * @param $id
     * @throws \Exception
     */
    public function interromperInventario($id)
    {
        $this->em->beginTransaction();
        try{
            /** @var InventarioNovo $invEn */
            $invEn = $this->find($id);

            if (!$invEn->isLiberado()) throw new \Exception("Este inventário $id não pode ser interrompido pois está: " . $invEn->getDscStatus());

            $this->em->getRepository(InventarioNovo\InventarioContEndOs::class)->cancelarContOs($id, true);

            $invEn->interromper();
            $this->em->persist($invEn);
            self::newLog($invEn,InventarioNovo\InventarioAndamento::STATUS_INTERROMPIDO);

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $id
     * @param $invEn InventarioNovo
     * @throws \Exception
     */
    public function cancelarInventario($id, $invEn = null)
    {
        $this->em->beginTransaction();
        try{
            if (empty($inventario) && !empty($id)) {
                /** @var InventarioNovo $invEn */
                $invEn = $this->find($id);
            }

            if ($invEn->isCancelado()) throw new \Exception("Este inventário $id já está cancelado");
            if ($invEn->isFinalizado()) throw new \Exception("Este inventário $id não pode mais ser cancelado, pois já foi aplicado ao estoque");

            $this->em->getRepository(InventarioNovo\InventarioContEndOs::class)->cancelarContOs($id);

            $invEn->cancelar();
            $this->em->persist($invEn);
            self::newLog($invEn,InventarioNovo\InventarioAndamento::STATUS_CANCELADO);

            $this->em->flush();
            $this->em->commit();

            $this->em->getRepository("wms:Deposito\Endereco")->desbloquearByInventario($id);
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function getMovimentacaoByInventario($idInventario)
    {
        /** @var InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->getRepository();
        $result = [];
        $ends = [];

        foreach ($inventarioRepo->getSumarioByRua($idInventario) as $conf) {
            if (!in_array($conf['DSC_DEPOSITO_ENDERECO'], $ends)) {
                if (!isset($result[$conf['NUM_RUA']])) {
                    $result[$conf['NUM_RUA']]['pendentes'] = 0;
                    $result[$conf['NUM_RUA']]['conferencia'] = 0;
                    $result[$conf['NUM_RUA']]['divergentes'] = 0;
                    $result[$conf['NUM_RUA']]['finalizados'] = 0;
                }
                if ($conf['COD_STATUS'] == InventarioNovo\InventarioEnderecoNovo::STATUS_PENDENTE) {
                    $result[$conf['NUM_RUA']]['pendentes']++;
                }
                elseif ($conf['COD_STATUS'] == InventarioNovo\InventarioEnderecoNovo::STATUS_CONFERENCIA) {
                    $result[$conf['NUM_RUA']]['conferencia']++;
                }
                elseif ($conf['COD_STATUS'] == InventarioNovo\InventarioEnderecoNovo::STATUS_DIVERGENCIA) {
                    $result[$conf['NUM_RUA']]['divergentes']++;
                }
                elseif ($conf['COD_STATUS'] == InventarioNovo\InventarioEnderecoNovo::STATUS_FINALIZADO) {
                    $result[$conf['NUM_RUA']]['finalizados']++;
                }
                $ends[] = $conf['DSC_DEPOSITO_ENDERECO'];
            }

            $result[$conf['NUM_RUA']]['enderecos'][$conf['DSC_DEPOSITO_ENDERECO']]['status'] = InventarioNovo\InventarioEnderecoNovo::$tipoStatus[$conf['COD_STATUS']];
            $result[$conf['NUM_RUA']]['enderecos'][$conf['DSC_DEPOSITO_ENDERECO']]['conferencias'][] = [
                "contagem" => "$conf[NUM_CONTAGEM]ª Cont." . (($conf['IND_CONTAGEM_DIVERGENCIA'] == 'S') ? ' Divergência' : ''),
                "conferente" => $conf['NOM_PESSOA'],
                "codProduto" => $conf['COD_PRODUTO'],
                "dscProd" => $conf['DSC_PRODUTO'],
                "grade" => $conf['DSC_GRADE'],
                "lote" => $conf['DSC_LOTE'],
                "dscEmbVol" => $conf['UNID'],
                "qtdContada" => $conf['QTD_CONTADA'],
                "dthValidade" => $conf['DTH_VALIDADE'],
                "dthConferencia" => $conf['DTH_CONFERENCIA']
            ];
        }

        return $result;
    }

    /*
     * Layout de exportação definido para o Winthor
     */
    public function exportarInventarioModelo1($id, $produtosERP = null)
    {

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->em->getRepository('wms:Produto\Embalagem');

        $codInvErp = $this->find($id)->getCodErp();

        if (empty($codInvErp)){
            throw new \Exception("Este inventário não tem o código do inventário respectivo do ERP");
        }

        $inventariosByErp = $this->findBy(array('codErp' => $codInvErp));
        foreach ($inventariosByErp as $inventario) {
            $inventarios[] = $inventario->getId();
        }

        $filename = "Exp_Inventario($codInvErp).txt";
        $file = fopen($filename, 'w');

        $contagens = $this->em->getRepository("wms:InventarioNovo")->getResultInventario(implode(',', $inventarios), true);
        $inventario = array();

        if ($produtosERP != null) {
            $result = array();
            foreach ($contagens as $cont) {
                foreach ($produtosERP as $prodERP) {
                    if (($prodERP['COD_PRODUTO'] == $cont['COD_PRODUTO']) && ($prodERP['DSC_GRADE'] == $cont['DSC_GRADE'])) {
                        $result[] = $cont;
                        break;
                    }
                }
            }
            $contagens = $result;
        }

        foreach ($contagens as $contagem) {
            $embs = $embalagemRepo->findBy(array('codProduto' => $contagem['COD_PRODUTO'], 'grade' => $contagem['DSC_GRADE']), array('quantidade' => 'ASC'));
            if (empty($embs)) continue;
            $embalagemEntity = reset($embs);

            if (isset($inventario[$contagem['COD_PRODUTO']])) {
                $inventario[$contagem['COD_PRODUTO']]['QUANTIDADE'] = Math::adicionar($inventario[$contagem['COD_PRODUTO']]['QUANTIDADE'], $contagem['QTD']);
            } else {
                $inventario[$contagem['COD_PRODUTO']]['QUANTIDADE'] = $contagem['QTD'];
                $inventario[$contagem['COD_PRODUTO']]['COD_BARRAS'] = $embalagemEntity->getCodigoBarras();
                $inventario[$contagem['COD_PRODUTO']]['FATOR'] = $embalagemEntity->getQuantidade();
            };
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
    public function exportarInventarioModelo2($idInventario = null, $caminho = null) {
        /*
         * Nome do arquivo solicitado pela sonoshow como aammddhh.min
         */

        $nomeArquivo = date("ymdH.0i");
        $arquivo = $caminho . DIRECTORY_SEPARATOR . $nomeArquivo;

        $statusFinalizado = InventarioNovo::STATUS_FINALIZADO;

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
            $SQL .= " INNER JOIN (SELECT DISTINCT ICEP.COD_PRODUTO,
                                    ICEP.DSC_GRADE
                               FROM INVENTARIO_ENDERECO_NOVO IEN
                               INNER JOIN INVENTARIO_NOVO INVN ON INVN.COD_INVENTARIO = IEN.COD_INVENTARIO
                               INNER JOIN INVENTARIO_CONT_END ICE ON ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO
                               LEFT JOIN INVENTARIO_CONT_END_OS ICEO ON ICEO.COD_INV_CONT_END = ICE.COD_INV_CONT_END
                               LEFT JOIN INVENTARIO_CONT_END_PROD ICEP ON ICEO.COD_INV_CONT_END_OS = ICEP.COD_INV_CONT_END_OS
                              WHERE IEN.COD_INVENTARIO = $idInventario AND INVN.COD_STATUS = $statusFinalizado AND ICEP.IND_DIVERGENTE = 'N') I
                    ON (I.COD_PRODUTO = P.COD_PRODUTO)
                   AND (I.DSC_GRADE = P.DSC_GRADE)";
        }

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

    public function exportarInventarioModelo3($id, $produtosERP)
    {
        /** @var \Wms\Domain\Entity\InventarioNovo $inventarioEn */
        $inventarioEn = $this->find($id);
        $codInvErp = $inventarioEn->getCodErp();


        if (empty($codInvErp)) {
            throw new \Exception("Este inventário não tem o código do inventário respectivo no ERP");
        }

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->em->getRepository('wms:Produto\Embalagem');

        $inventariosByErp = $this->findBy(array('codErp' => $codInvErp));
        foreach ($inventariosByErp as $inventario) {
            $inventarios[] = $inventario->getId();
        }

        $filename = "Exp_Inventario($codInvErp).txt";
        $file = fopen($filename, 'w');
        $contagens = $this->em->getRepository("wms:InventarioNovo")->getResultInventario(implode(',', $inventarios), true);
        $inventario = array();

        if ($produtosERP != null) {
            $result = array();
            foreach ($contagens as $cont) {
                foreach ($produtosERP as $prodERP) {
                    if (($prodERP['COD_PRODUTO'] == $cont['COD_PRODUTO']) && ($prodERP['DSC_GRADE'] == $cont['DSC_GRADE'])) {
                        $result[] = $cont;
                        break;
                    }
                }
            }
            $contagens = $result;
        }

        foreach ($contagens as $contagem) {
            $embs = $embalagemRepo->findBy(array('codProduto' => $contagem['COD_PRODUTO'], 'grade' => $contagem['DSC_GRADE']), array('quantidade' => 'ASC'));
            if (empty($embs)) continue;
            $embalagemEntity = reset($embs);

            if (isset($inventario[$contagem['COD_PRODUTO']])) {
                $inventario[$contagem['COD_PRODUTO']]['QUANTIDADE'] = Math::adicionar($inventario[$contagem['COD_PRODUTO']]['QUANTIDADE'], $contagem['QTD']);
            } else {
                $inventario[$contagem['COD_PRODUTO']]['QUANTIDADE'] = $contagem['QTD'];
                $inventario[$contagem['COD_PRODUTO']]['COD_BARRAS'] = $embalagemEntity->getCodigoBarras();
                $inventario[$contagem['COD_PRODUTO']]['FATOR'] = $embalagemEntity->getQuantidade();
            };
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

    public function exportarInventarioModelo4($id, $produtosERP)
    {
        /** @var \Wms\Domain\Entity\InventarioNovo $inventarioEn */
        $inventarioEn = $this->find($id);
        $codInvErp = $inventarioEn->getCodErp();

        if (empty($codInvErp)) {
            throw new \Exception("Este inventário não tem o código do inventário respectivo no ERP");
        }

        /** @var \Wms\Domain\Entity\Produto\EmbalagemRepository $embalagemRepo */
        $embalagemRepo = $this->em->getRepository('wms:Produto\Embalagem');

        $inventariosByErp = $this->findBy(array('codErp' => $codInvErp));
        foreach ($inventariosByErp as $inventario) {
            $inventarios[] = $inventario->getId();
        }

        $strInventarios = implode(", ", $inventarios);

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
                                            GROUP BY E.COD_PRODUTO, E.DSC_GRADE, NVL(E.COD_PRODUTO_VOLUME,0)) E
                              GROUP BY COD_PRODUTO, DSC_GRADE) ESTQ
                    ON ESTQ.COD_PRODUTO = P.COD_PRODUTO
                   AND ESTQ.DSC_GRADE = P.DSC_GRADE " ;

        if (!empty($strInventarios)) {
            $statusFinalizado = InventarioNovo::STATUS_FINALIZADO;
            $SQL .= " INNER JOIN (SELECT DISTINCT ICEP.COD_PRODUTO,
                                    ICEP.DSC_GRADE
                               FROM INVENTARIO_ENDERECO_NOVO IEN
                               INNER JOIN INVENTARIO_NOVO INVN ON INVN.COD_INVENTARIO = IEN.COD_INVENTARIO
                               INNER JOIN INVENTARIO_CONT_END ICE ON ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO
                               LEFT JOIN INVENTARIO_CONT_END_OS ICEO ON ICEO.COD_INV_CONT_END = ICE.COD_INV_CONT_END
                               LEFT JOIN INVENTARIO_CONT_END_PROD ICEP ON ICEO.COD_INV_CONT_END_OS = ICEP.COD_INV_CONT_END_OS
                              WHERE IEN.COD_INVENTARIO IN ($strInventarios) AND INVN.COD_STATUS = $statusFinalizado AND ICEP.IND_DIVERGENTE = 'N') I
                    ON (I.COD_PRODUTO = P.COD_PRODUTO)
                   AND (I.DSC_GRADE = P.DSC_GRADE)";
        }

        $produtos = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        if ($produtosERP != null) {
            $result = array();
            foreach ($produtos as $cont) {
                foreach ($produtosERP as $prodERP) {
                    if (($prodERP['COD_PRODUTO'] == $cont['COD_PRODUTO']) && ($prodERP['DSC_GRADE'] == $cont['DSC_GRADE'])) {
                        $result[] = $cont;
                        break;
                    }
                }
            }
            $produtos = $result;
        }

        foreach ($produtos as $produto) {
            $txtCodInventario = str_pad($codInvErp, 4, '0', STR_PAD_LEFT);
            $txtContagem = '001';
            $txtLocal = '001';

            /** @var Produto\Embalagem[] $embs */
            $embs = $embalagemRepo->findBy(array('codProduto' => $produto['COD_PRODUTO'], 'grade' => $produto['DSC_GRADE']), array('quantidade' => 'ASC'));
            if (empty($embs)) continue;
            $embalagemEntity = reset($embs);

            $txtCodBarras = str_pad($embalagemEntity->getCodigoBarras(), 14, '0', STR_PAD_LEFT);

            $txtQtd = str_pad($produto["QTD"] / $embalagemEntity->getQuantidade(), 9, '0', STR_PAD_LEFT);
            $txtCodProduto = str_pad($produto['COD_PRODUTO'], 6, '0', STR_PAD_LEFT);

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

    public function setCodInventarioERP($idInventario, $codInventarioERP) {
        /** @var InventarioNovo $inventarioEn */
        $inventarioEn = $this->find($idInventario);
        if (!empty($inventarioEn)) {
            $inventarioEn->setCodErp($codInventarioERP);
            $this->em->flush($inventarioEn);
        } else {
            throw new \Exception("Nenhum inventário encontrado com o código $idInventario!");
        }
    }

    private function newLog($inventario, $acao, $usuario = null, $arg = null)
    {
        if (empty($usuario)) {
            /** @var Usuario $usuario */
            $usuario = $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());
        }

        /** @var InventarioNovo\InventarioAndamentoRepository $logRepo */
        $logRepo = $this->em->getRepository("wms:InventarioNovo\InventarioAndamento");

        $dscAcao = "";
        switch ($acao){
            case InventarioNovo\InventarioAndamento::STATUS_GERADO:
                $dscAcao = "Inventario gerado";
                break;
            case InventarioNovo\InventarioAndamento::STATUS_LIBERADO:
                $dscAcao = "Inventario liberado";
                break;
            case InventarioNovo\InventarioAndamento::STATUS_CONCLUIDO:
                $dscAcao = "Inventario concluido";
                break;
            case InventarioNovo\InventarioAndamento::STATUS_FINALIZADO:
                $dscAcao = "Inventario finalizado";
                break;
            case InventarioNovo\InventarioAndamento::STATUS_INTERROMPIDO:
                $dscAcao = "Inventario interrompido";
                break;
            case InventarioNovo\InventarioAndamento::STATUS_CANCELADO:
                $dscAcao = "Inventario cancelado";
                break;
            case InventarioNovo\InventarioAndamento::REMOVER_ENDERECO:
                $dscAcao = "Endereço $arg removido";
                break;
            case InventarioNovo\InventarioAndamento::REMOVER_PRODUTO:
                $dscAcao = "Produto $arg removido";
                break;
        }

        $logRepo->save([
            "inventario" => $inventario,
            "usuario" => $usuario,
            "codAcao" => $acao,
            "descricao" => $dscAcao
        ]);
    }

    /**
     * @param $idInventario
     * @param null $idEndereco
     * @param null $idProduto
     * @param null $dscGrade
     * @return bool
     * @throws \Exception
     */
    public function verificarRequisicaoColetor($idInventario, $idEndereco = null, $idProduto = null, $dscGrade = null)
    {
        /** @var InventarioNovo $inventarioEn */
        $inventarioEn = $this->getRepository()->find($idInventario);

        if (empty($inventarioEn)) throw new \Exception("Ação negada. Este inventário $idInventario não foi encontrao!", 4000);

        if (!$inventarioEn->isLiberado()) throw new \Exception("Ação negada. Este inventário $idInventario está " . $inventarioEn->getDscStatus(), 4000);

        if (!empty($idEndereco)) {
            /** @var InventarioNovo\InventarioEnderecoNovoRepository $invEndRepo */
            $invEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo");

            /** @var InventarioNovo\InventarioEnderecoNovo $invEndEn */
            $invEndEn = $invEndRepo->findOneBy(["inventario" => $idInventario, "depositoEndereco" => $idEndereco]);

            if (!$invEndEn->isAtivo()) throw new \Exception("Ação negada. Este endereço '".$invEndEn->getDepositoEndereco()->getDescricao()."' foi removido do inventário!", 4001);

            if ($invEndEn->isFinalizado()) throw new \Exception("Ação negada. Este endereço '".$invEndEn->getDepositoEndereco()->getDescricao()."' já está finalizado!", 4001);

            if ($inventarioEn->isPorProduto() && !empty($idProduto) && !empty($dscGrade)) {
                /** @var InventarioNovo\InventarioEndProdRepository $invEndProdRepo */
                $invEndProdRepo = $this->em->getRepository("wms:InventarioNovo\InventarioEndProd");
                /** @var InventarioNovo\InventarioEndProd $invEndProdEn */
                $invEndProdEn = $invEndProdRepo->findOneBy(["inventarioEndereco" => $invEndEn, "codProduto" => $idProduto, "grade" => $dscGrade]);

                if(empty($invEndProdEn))
                    throw new \Exception("Ação negada. Este produto $idProduto grade $dscGrade não está relacionado para inventário neste endereço", 4002);

                if (!$invEndProdEn->isAtivo())
                    throw new \Exception("Ação negada. Este produto $idProduto grade $dscGrade foi removido do inventário neste endereço ".$invEndProdEn->getInventarioEndereco()->getDepositoEndereco()->getDescricao() ."!", 4002);
            }
        }

        return true;
    }

    public function getResultadoInventarioComparativo ($modelo, $codInvErp) {

        $contagens = array();

        if (($modelo == 1) || ($modelo == 3)) {

            $inventariosByErp = $this->findBy(array('codErp' => $codInvErp));
            foreach ($inventariosByErp as $inventario) {
                $inventarios[] = $inventario->getId();
            }
            $contagens = $this->em->getRepository("wms:InventarioNovo")->getResultInventario(implode(',', $inventarios), true, true);

            $result = array();
            foreach ($contagens as $c) {
                $k = null;
                foreach ($result as $key => $r) {
                    if (($c['COD_PRODUTO'] == $r['COD_PRODUTO']) && ($c['DSC_GRADE'] == $r['DSC_GRADE'])) {
                        $k = $key;
                    }
                }

                if ($k != null) {
                    $resut[$k]['QTD'] = $result[$k]['QTD'] + $c['QTD'];
                } else {
                    $result[] = array(
                        'COD_PRODUTO' => $c['COD_PRODUTO'],
                        'DSC_GRADE' => $c['DSC_GRADE'],
                        'DSC_PRODUTO' => $c['DSC_PRODUTO'],
                        'QTD' => $c['QTD'],
                    );
                }
            }

            $contagens = $result;
        } else if ($modelo == 4) {
            $statusFinalizado = InventarioNovo::STATUS_FINALIZADO;
            $SQL = "SELECT P.COD_PRODUTO, P.DSC_GRADE, P.DSC_PRODUTO, NVL(ESTQ.QTD,0) as QTD
                      FROM PRODUTO P
                      LEFT JOIN (SELECT E.COD_PRODUTO,
                                        E.DSC_GRADE, 
                                        MIN(QTD) as QTD
                                   FROM (SELECT E.COD_PRODUTO,
                                                E.DSC_GRADE,
                                                SUM(E.QTD) as QTD,
                                                NVL(E.COD_PRODUTO_VOLUME,0) as ID_VOLUME
                                           FROM ESTOQUE E
                                                GROUP BY E.COD_PRODUTO, E.DSC_GRADE, NVL(E.COD_PRODUTO_VOLUME,0)) E
                                  GROUP BY COD_PRODUTO, DSC_GRADE) ESTQ
                        ON ESTQ.COD_PRODUTO = P.COD_PRODUTO
                       AND ESTQ.DSC_GRADE = P.DSC_GRADE 
                      INNER JOIN (SELECT DISTINCT ICEP.COD_PRODUTO,
                                         ICEP.DSC_GRADE
                                    FROM INVENTARIO_ENDERECO_NOVO IEN
                                   INNER JOIN INVENTARIO_NOVO INVN ON INVN.COD_INVENTARIO = IEN.COD_INVENTARIO
                                   INNER JOIN INVENTARIO_CONT_END ICE ON ICE.COD_INVENTARIO_ENDERECO = IEN.COD_INVENTARIO_ENDERECO
                                    LEFT JOIN INVENTARIO_CONT_END_OS ICEO ON ICEO.COD_INV_CONT_END = ICE.COD_INV_CONT_END
                                    LEFT JOIN INVENTARIO_CONT_END_PROD ICEP ON ICEO.COD_INV_CONT_END_OS = ICEP.COD_INV_CONT_END_OS
                                   WHERE INVN.COD_INVENTARIO_ERP = $codInvErp AND INVN.COD_STATUS = $statusFinalizado AND ICEP.IND_DIVERGENTE = 'N') I
            ON (I.COD_PRODUTO = P.COD_PRODUTO)
           AND (I.DSC_GRADE = P.DSC_GRADE)" ;

            $contagens = $this->getEntityManager()->getConnection()->query($SQL)->fetchAll(\PDO::FETCH_ASSOC);

        }
        return $contagens;
    }

    public function comparataInventarioWMSxERP($WMS, $ERP) {

        $invWMSERP = array();
        $invApenasWMS = array();
        $invApenasERP = array();

        foreach ($WMS as $w) {
            $find = false;
            foreach ($ERP as $e) {
                if (($w['COD_PRODUTO'] == $e['COD_PRODUTO']) && ($w['DSC_GRADE']) == $e['DSC_GRADE']) {
                    $find = true;
                }
            }

            $produto = array(
                'COD_PRODUTO' => $w['COD_PRODUTO'],
                'DSC_GRADE' => $w['DSC_GRADE'],
                'DSC_PRODUTO' => $w['DSC_PRODUTO'],
                'QTD' => $w['QTD'],
            );
            if ($find == true) {
                $invWMSERP[] = $produto;
            } else {
                $invApenasWMS[] = $produto;
            }
        }
        foreach ($ERP as $e) {
            $find = false;
            foreach ($WMS as $w) {
                if (($w['COD_PRODUTO'] == $e['COD_PRODUTO']) && ($w['DSC_GRADE']) == $e['DSC_GRADE']) {
                    $find = true;
                }
            }

            if ($find == false) {
                $produto = array(
                    'COD_PRODUTO' => $e['COD_PRODUTO'],
                    'DSC_GRADE' => $e['DSC_GRADE'],
                    'DSC_PRODUTO' => $e['DSC_PRODUTO'],
                );
                $invApenasERP[] = $produto;
            }
        }

        $result = array(
            'resultado-inventario' => $WMS,
            'inventario-erp' => $ERP,
            'inventario-erp-wms' => $invWMSERP,
            'apenas-wms' => $invApenasWMS,
            'apenas-erp' => $invApenasERP
        );
        return $result;
    }

}