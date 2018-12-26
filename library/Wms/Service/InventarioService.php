<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:16
 */

namespace Wms\Service;


use Bisna\Base\Domain\Entity\EntityService;
use Wms\Domain\Entity\Atividade;
use Wms\Domain\Entity\Enderecamento\EstoqueRepository;
use Wms\Domain\Entity\InventarioNovo;
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
                'descricao' => $params['descricao'],
                'dthCriacao' => new \DateTime(),
                'status' => InventarioNovo::STATUS_GERADO,
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
                $inventarioEnderecoEn = $inventarioEnderecoRepo->save([
                    'inventario' => $inventarioEn,
                    'depositoEndereco' => $this->em->getReference('wms:Deposito\Endereco', $item['id']),
                    'contagem' => 1,
                    'finalizado' => 'N',
                    'ativo' => 'S'
                ]);
                if ($inventarioEn->isPorProduto()) {
                    $invEndProdRepod->save([
                        'inventarioEndereco' => $inventarioEnderecoEn,
                        'ativo' => 'S',
                        'produto' => $this->em->getReference('wms:Produto', ['id' => $item['codProduto'], 'grade' => $item['grade']])
                    ]);
                }
            }

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
     * @return bool
     * @throws \Exception
     */
    public function liberarInventario($id)
    {
        $this->em->beginTransaction();
        try {
            /** @var InventarioNovo $inventarioEn */
            $inventarioEn = $this->find($id);
            if (!$inventarioEn->isGerado()) {
                throw new \Exception("O inventário $id está " . $inventarioEn->getDscStatus(), 500);
            }

            $impedimentos = $this->getRepository()->findImpedimentosLiberacao($id);
            if (!empty($impedimentos)) {
                return $impedimentos;
            } else {
                $inventarioEn->liberar();
                $inventarioEn->setDthInicio(new \DateTime());

                /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $enderecoRepo */
                $enderecoRepo = $this->em->getRepository('wms:Deposito\Endereco');

                /** @var InventarioNovo\InventarioEnderecoNovo[] $enderecos */
                $enderecos = $this->em->getRepository("wms:InventarioNovo\InventarioEnderecoNovo")->findBy(["inventario" => $inventarioEn]);

                foreach ($enderecos as $endereco) {
                    $this->addNovaContagem($endereco);
                    $enderecoRepo->bloqueiaOuDesbloqueiaInventario($endereco->getDepositoEndereco(), 'S', false);
                }

                $this->em->persist($inventarioEn);
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
     * @param $id_inventario
     * @param $id_item
     * @param $tipo
     * @param $grade
     * @param $lote
     * @throws \Exception
     */

    public function removerProduto($idInventario, $idInventarioEndereco, $idProduto, $grade){
        $this->em->beginTransaction();

        try {
            //exclusao logica do produto
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository $inventarioEndProdRepo */
            $inventarioEndProdRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEndProd');
            $produto = $inventarioEndProdRepo->findOneBy(['inventarioEndereco' => $idInventarioEndereco, 'codProduto' => $idProduto, 'grade' => $grade]);

            //exclusão lógica
            $produto->setAtivo(false);

            $this->em->persist($produto);
            $this->em->flush();

            // se nao existir mais produtos no endereço, cancela o endereço
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository $inventarioEndProdRepo2 */
            $inventarioEndProdRepo2 = $this->em->getRepository('wms:inventarioNovo\InventarioEndProd');
            $produtoAtivo = $inventarioEndProdRepo2->findOneBy(['inventarioEndereco' => $idInventarioEndereco, 'ativo' => 'S']);

            if( count($produtoAtivo) == 0 )
                $this->removerEndereco($idInventario, $idInventarioEndereco);

            $this->em->commit();

        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function removerEndereco($idInventario, $idEndereco)
    {
        $this->em->beginTransaction();

        try {
            //exclusao logica do endereço
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEnderecoNovo');
            $endereco = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'depositoEndereco' => $idEndereco]);

            $endereco->setAtivo(false);

            $this->em->persist($endereco);
            $this->em->flush();
            //$this->em->commit();

            // se nao existir mais endereços ativos nesse inventario, cancela o mesmo
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo2 */
            $inventarioEnderecoRepo2 = $this->em->getRepository('wms:inventarioNovo\InventarioEnderecoNovo');
            $enderecoAtivo = $inventarioEnderecoRepo2->findOneBy(['inventario' => $idInventario, 'ativo' => 'S']);

            if( (count($enderecoAtivo)) == 0)
            {
                /** @var \Wms\Domain\Entity\InventarioNovoRepository $inventarioRepo */
                $inventarioRepo = $this->find($idInventario);
                $inventarioRepo->setStatus(InventarioNovo::STATUS_CANCELADO);

                $this->em->persist($inventarioRepo);
                $this->em->flush();

                throw new \Exception("O inventário $idInventario foi cancelado pois está vazio");
            }

            $this->em->commit();
        }catch (\Exception $e) {
            //$this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn
     * @param int $ultimaSequencia
     * @param int $contagem
     * @param bool $divergencia
     * @return InventarioNovo\InventarioContEnd
     * @throws \Exception
     */
    public function addNovaContagem(InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn, $ultimaSequencia = 1, $contagem = 1, $divergencia = false)
    {
        try {
            /** @var InventarioNovo\InventarioContEndRepository $inventContEndRepo */
            $inventContEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEnd");

            return $inventContEndRepo->save([
                "inventarioEndereco" => $inventarioEnderecoEn,
                "sequencia" => ($ultimaSequencia + 1),
                "contagem" => ($contagem + 1),
                "contagemDivergencia" => $divergencia,
                "finalizada" => false
            ], false);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario
     * @param array | InventarioNovo\InventarioContEnd $contagem
     * @param array | Produto $produto
     * @param array $conferencia
     * @param $tipoConferencia
     * @param Produto\Embalagem | null $embEn
     * @param Produto\Volume | null $volEn
     * @param bool $getOs
     * @throws \Exception
     */
    public function registrarContagem($inventario, $contagem, $produto, $conferencia, $tipoConferencia, $embEn = null, $volEn = null, $getOs = true)
    {
        $this->em->beginTransaction();
        try {

            if ($getOs) {
                $osUserCont = $this->getOsUsuarioContagem( $contagem, $inventario, $tipoConferencia, true);
            }

            $elements = [];
            $isEmb = null;

            if (json_decode($inventario['volumesSeparadamente']) && (!empty($volEn) ||
                    (is_array($produto) && (isset($produto['idVolume']) && !empty($produto['idVolume']))))) {
                if (empty($volEn)) {
                    $elements[] = $this->em->getReference("wms:Produto\Volume", $produto['idVolume']);
                } else {
                    $elements[] = $volEn;
                }
                $isEmb = false;
            } elseif (!json_decode($inventario['volumesSeparadamente']) && (!empty($volEn) ||
                    (is_array($produto) && isset($produto['idVolume']) && !empty($produto['idVolume'])))) {
                if (empty($volEn)) {
                    $elements = $this->em->getRepository("wms:Produto\Volume")->findBy(["id" => $produto['idProduto'], "grade" => $produto['grade'], "dataInativacao" => null]);
                } else {
                    $elements = $produto->getVolumes()->filter(function ($vol) { return (empty($vol->getDataInativacao())); })->toArray();
                }
                $isEmb = false;
            } elseif (!empty($embEn) || (!is_a($produto, "wms:Produto") && isset($produto['idEmbalagem']) && !empty($produto['idEmbalagem']))) {
                if (empty($embEn)) {
                    $elements[] = $this->em->getReference("wms:Produto\Embalagem", $produto['idEmbalagem']);
                } else {
                    $elements[] = $embEn;
                }
                $isEmb = true;
            }

            $produto = (!is_array($produto)) ? $produto : $this->em->getReference("wms:Produto", ["id" => $produto['idProduto'], "grade" => $produto['grade']]);
            $invContEnd = (!is_array($contagem)) ? $contagem : $osUserCont->getInvContEnd();

            foreach ($elements as $element) {
                $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd")->save([
                    "inventarioContEnd" => $invContEnd,
                    "produto" => $produto,
                    "lote" => $conferencia['lote'],
                    "qtdContada" => $conferencia['qtd'],
                    "produtoEmbalagem" => (!empty($isEmb)) ? $element : null,
                    "qtdEmbalagem" => (is_array($produto)) ? $produto['quantidadeEmbalagem'] : null,
                    "codBarras" => (is_array($produto)) ? $produto['codigoBarras'] : null,
                    "produtoVolume" => (empty($isEmb)) ? $element : null,
                    "validade" => (!empty($conferencia['validade'])) ? date_create_from_format("d/m/y", $conferencia['validade']) : null
                ], false);
            }

            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    /**
     * @param $contagem
     * @param $inventario
     * @param $tipoConferencia
     * @param $createIfNoExist
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    public function getOsUsuarioContagem($contagem, $inventario = [], $tipoConferencia = [], $createIfNoExist = false)
    {
        try {
            /** @var Usuario $usuario */
            $usuario = $this->em->getReference('wms:Usuario', \Zend_Auth::getInstance()->getIdentity()->getId());

            /** @var InventarioNovo\InventarioContEndOsRepository $contagemEndOsRepo */
            $contagemEndOsRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs");

            /** @var InventarioNovo\InventarioContEndOs $usrContOs */
            $usrContOs = $contagemEndOsRepo->getOsContUsuario( $contagem['id'], $usuario->getId());

            if (empty($usrContOs) && $createIfNoExist) {
                $osContagensAnteriores = $contagemEndOsRepo->getContagensUsuario( $usuario->getId(), $inventario['id']);
                if (!empty($osContagensAnteriores) && json_decode($inventario['usuarioNContagens']))
                    throw new \Exception("Este usuário não tem permissão para iniciar uma nova contagem neste endereço");

                $usrContOs = $this->addNewOsContagem($contagem, $usuario, $tipoConferencia);
            }

            return $usrContOs;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contagemEndereco
     * @param $usuario Usuario
     * @param $tipoConferencia
     * @return InventarioNovo\InventarioContEndOs
     * @throws \Exception
     */
    private function addNewOsContagem($contagemEndereco, $usuario, $tipoConferencia)
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
                "invContEnd" => $this->em->getReference("wms:InventarioNovo\InventarioContEnd", $contagemEndereco['id']),
                "ordemServico" => $newOsEn
            ], false);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario
     * @param $contagem
     * @throws \Exception
     */
    public function finalizarOs($inventario, $contagem)
    {
        try {
            /** @var OrdemServicoRepository $osRepo */
            $osRepo = $this->em->getRepository("wms:OrdemServico");

            $osUsuarioCont = $this->getOsUsuarioContagem($contagem);

            $osRepo->finalizar($osUsuarioCont->getOrdemServico()->getId(), "Contagem finalizada", $osUsuarioCont->getOrdemServico(), false);

            $outrasOs = $this->em->getRepository("wms:InventarioNovo\InventarioContEndOs")
                ->getOutrasOsAbertasContagem($inventario['id'], $osUsuarioCont->getOrdemServico()->getPessoa()->getId(), $osUsuarioCont->getId());

            if (empty($outrasOs)) {
                $contMaiorAcerto = $this->compararContagens($osUsuarioCont->getInvContEnd(), $inventario);
                $nContagensNecessarias = (json_decode($inventario['comparaEstoque'])) ? $inventario['numContagens'] + 1 : $inventario['numContagens'] ;
                $isDiverg = ($contagem['sequencia'] > $nContagensNecessarias);
                $this->updateFlagContagensProdutos($osUsuarioCont->getInvContEnd(), $isDiverg);

                if (count($contMaiorAcerto['seq']) < $nContagensNecessarias) {
                    $this->addNovaContagem(
                        $osUsuarioCont->getInvContEnd()->getInventarioEndereco(),
                        $contagem['sequencia'],
                        (!$contagem['divergencia'] && $isDiverg) ? 0 : $contagem['contagem'],
                        $isDiverg
                    );
                    return "Contagem finalizada com divergência";
                } else {
                    return $this->finalizarEndereco($osUsuarioCont->getInvContEnd()->getInventarioEndereco());
                }
            }
            return "Ordem de serviço finalizada com sucesso";
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $contEnd InventarioNovo\InventarioContEnd
     * @param $inventario
     * @return array
     */
    private function compararContagens($contEnd, $inventario)
    {
        try {
            $countQtdsIguais = [];

            $validaValidade = ($inventario['controlaValidade'] === InventarioNovo\ModeloInventario::VALIDADE_VALIDA);
            $invPorProduto = ($inventario['criterio'] === InventarioNovo::CRITERIO_PRODUTO);

            $strConcat = "+=+";

            /** @var \Wms\Domain\Entity\Enderecamento\Estoque[] $estoques */
            $estoques = [];
            if (json_decode($inventario['comparaEstoque'])) {
                $estoques = $this->em->getRepository("wms:Enderecamento\Estoque")->findBy([
                    "depositoEndereco" => $contEnd->getInventarioEndereco()->getDepositoEndereco()
                ]);
            }

            $contados = $this->em->getRepository("wms:InventarioNovo\InventarioContEndProd")->getContagensProdutos($contEnd->getInventarioEndereco()->getId());

            foreach ($contados as $contagem) {
                $estoque = null;
                if (!empty($estoques)) {
                    for ($i = 0; $i < count($estoques); $i++) {
                        if ($invPorProduto || json_decode(!$contagem['contarTudo'])) {
                            if ($contagem["codProduto"] != $estoques[$i]->getCodProduto() || $contagem["grade"] != $estoques[$i]->getGrade())
                                continue;
                        }
                        if (json_decode($inventario["volumesSeparadamente"]) && !empty($contagem["idVol"])) {
                            if ($contagem["idVol"] != $estoques[$i]->getProdutoVolume()->getId())
                                continue;
                        }
                        $estoque = $estoques[$i];
                        unset($estoques[$i]);
                        break;
                    }
                }

                if (!empty($estoque)) {
                    $arg = [
                        $estoque->getCodProduto(),
                        $estoque->getGrade(),
                        $estoque->getLote(),
                        (!empty($estoque->getProdutoVolume())) ? $estoque->getProdutoVolume()->getId() : 1,
                        $estoque->getQtd(),
                        (!empty($estoque->getValidade()) && $validaValidade) ? $estoque->getValidade()->format("d/m/Y") : ""
                    ];
                    $countQtdsIguais[implode($strConcat, $arg)][] = "estoque";
                }

                $arg = [
                    $contagem['codProduto'],
                    $contagem['grade'],
                    $contagem['lote'],
                    (!empty($contagem['idVol'])) ? $contagem['idVol'] : 1,
                    $contagem['qtdContagem'],
                    $contagem['validade'],
                ];
                $countQtdsIguais[implode($strConcat, $arg)][] = $contagem['sequencia'];
            }

            if (!empty($estoques) && (!$invPorProduto || json_decode($inventario['contarTudo']))) {
                foreach ($estoques as $estoque)
                    $this->zerarProdutoNaoContado($inventario, $contEnd, $estoque);
            }

            $result = [];
            foreach ($countQtdsIguais as $k => $v) {
                $exploded = explode($strConcat, $k);
                $result[count($v)] = [
                    "seq" => $v,
                    "codProduto" => $exploded[0],
                    "grade" => $exploded[1],
                    "lote" => $exploded[2],
                    "idElem" => $exploded[3],
                    "qtdContagem" => $exploded[4],
                    "validade" => $exploded[5]
                ];
            }

            ksort($result);
            return array_reverse($result)[0];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $inventario array
     * @param $contEnd InventarioNovo\InventarioContEnd
     * @param $prodEstoque \Wms\Domain\Entity\Enderecamento\Estoque
     */
    private function zerarProdutoNaoContado($inventario, $contEnd, $prodEstoque)
    {
        try {
            $this->registrarContagem(
                $inventario,
                $contEnd,
                $prodEstoque->getProduto(),
                [
                    'qtd' => 0,
                    'lote' => $prodEstoque->getLote(),
                    'dataValidade' => (!empty($prodEstoque->getValidade())) ? $prodEstoque->getValidade()->format("d/m/Y") : null
                ],
                null,
                $prodEstoque->getProdutoEmbalagem(),
                $prodEstoque->getProdutoVolume(),
                false
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param InventarioNovo\InventarioContEnd $contEnd
     * @param bool $isDiverg
     */
    private function updateFlagContagensProdutos($contEnd, $isDiverg)
    {
        /** @var InventarioNovo\InventarioContEndProd $contProd */
        foreach ($this->em->getRepository("wms:InventairoNovo\InventarioContEndProd")->findBy(["inventarioContEnd" => $contEnd]) as $contProd) {
            $contEnd->setContagemDivergencia($isDiverg);
            $this->em->persist($contEnd);
        }
    }

    /**
     * @param InventarioNovo\InventarioEnderecoNovo $inventarioEnd
     */
    private function finalizarEndereco($inventarioEnd)
    {
        try {
            if (!$inventarioEnd->getInventario()->isLiberado()) {
                throw new \Exception("Este endereço " . $inventarioEnd->getDepositoEndereco()->getDescricao() .
                    " não pode ser finalizado pois seu inventário está " . $inventarioEnd->getInventario()->getDscStatus());
            }

            if (!$inventarioEnd->isAtivo()) {
                throw new Exception("Este endereço " . $inventarioEnd->getDepositoEndereco()->getDescricao() . " foi removido do inventário e não pode ser finalizado!");
            }

            $inventarioEnd->setFinalizado(true);
            $this->em->persist($inventarioEnd);

            if (empty($this->getRepository()->getEnderecosPendentes($inventarioEnd))) {
                return $this->finalizarInventario($inventarioEnd->getInventario());
            }

            return "Endereço finalizado com sucesso";

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param InventarioNovo $inventario
     */
    private function finalizarInventario($inventario)
    {
        try {
            $inventario->concluir();
            $this->em->persist($inventario);
            return "Inventário concluído com sucesso";
        } catch (\Exception $e) {
            throw $e;
        }
    }
}