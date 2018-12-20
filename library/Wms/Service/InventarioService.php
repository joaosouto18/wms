<?php
/**
 * Created by PhpStorm.
 * User: Tarcísio César
 * Date: 14/11/2018
 * Time: 16:16
 */

namespace Wms\Service;


use Bisna\Base\Domain\Entity\EntityService;
use Wms\Domain\Entity\InventarioNovo;

class InventarioService extends AbstractService
{
    /**
     * @param $invData
     * @return InventarioNovo
     */
    public function registrarNovoInventario($params) {

        $this->em->beginTransaction();

        try {
            $args = [
                'descricao' => $params['descricao'],
                'dthCriacao' => new \DateTime(),
                'status' => InventarioNovo::STATUS_GERADO,
                'modeloInventario' => $this->em->getReference('wms:InventarioNovo\ModeloInventario', $params['modelo']['id'])
            ];
            unset($params['modelo']['id']);
            unset($params['modelo']['dscModelo']);
            unset($params['modelo']['dthCriacao']);
            unset($params['modelo']['ativo']);
            unset($params['modelo']['isDefault']);

            $inventarioEn = self::save( array_merge($args, $params['modelo']), false);

            /** @var InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:InventarioNovo\InventarioEnderecoNovo');

            if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
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
                if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
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

    public function liberarInventario($id)
    {
        $this->em->beginTransaction();
        try {
            /** @var InventarioNovo $inventarioEn */
            $inventarioEn = $this->find($id);
            if ($inventarioEn->getStatus() != InventarioNovo::STATUS_GERADO) {
                throw new \Exception("O inventário $id está " . InventarioNovo::$tipoStatus[$inventarioEn->getStatus()], 500);
            }

            $impedimentos = $this->getRepository()->findImpedimentosLiberacao($id);
            if (!empty($impedimentos)) {
                return $impedimentos;
            } else {
                $inventarioEn->setStatus(InventarioNovo::STATUS_LIBERADO);
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

    public function removerEndereco($idInventario, $idEndereco){
        $this->em->beginTransaction();

        try {
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEnderecoNovo');
            $endereco = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'depositoEndereco' => $idEndereco]);

            //exclusão lógica
            $endereco->setAtivo('N');

            $this->em->persist($endereco);
            $this->em->flush();
            $this->em->commit();

            // verifica se existe algum endereço ativo ainda no inventario
            $enderecoAtivo = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'ativo' => 'S']);

            if(empty($enderecoAtivo))
                $this->cancelarInventario($idInventario);

        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    public function removerProduto($idInventario, $idProduto, $grade, $lote){
        $this->em->beginTransaction();

        try {
            /** @var \Wms\Domain\Entity\InventarioNovo\InventarioEndProdRepository $inventarioEndProdRepo */
            $inventarioEndProdRepo = $this->em->getRepository('wms:inventarioNovo\InventarioEndProd');
            $produto = $inventarioEndProdRepo->findOneBy(['COD_INVENTARIO' => $idInventario, 'COD_PRODUTO' => $idProduto, 'GRADE' => $grade, 'LOTE' => $lote]);

            //exclusão lógica
            $produto->setAtivo(false);

            $this->em->persist($produto);
            $this->em->flush();
            $this->em->commit();

            // verifica se existe algum produto ativo ainda no endereço
            $enderecoAtivo = $inventarioEnderecoRepo->findOneBy(['inventario' => $idInventario, 'ativo' => 'S']);

            if(empty($enderecoAtivo))
                $this->cancelarInventario($idInventario);

        }catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }


    public function cancelarEndereco($idInventario, $idEndereco){

        // se o produto for o ultimo, cancela o endereço

    }

    public function cancelarInventario($idInventario){

        // se o endereço for o ultimo, cancela o inventário
        /** @var \Wms\Domain\Entity\InventarioNovoRepository $inventarioRepo */
        $inventarioRepo = $this->em->getRepository("wms:Inventario");
        $inventarioEn   = $inventarioRepo->find($idInventario);

        if ($inventarioEn) {
            $inventarioRepo->cancelar($inventarioEn);
            $inventarioRepo->desbloqueiaEnderecos($idInventario);
            //return $this->redirect('index');
        }
    }

    /**
     * @param InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn
     * @param bool $divergencia
     * @throws \Exception
     */
    public function addNovaContagem(InventarioNovo\InventarioEnderecoNovo $inventarioEnderecoEn, $divergencia = false)
    {
        try {
            /** @var InventarioNovo\InventarioContEndRepository $inventContEndRepo */
            $inventContEndRepo = $this->em->getRepository("wms:InventarioNovo\InventarioContEnd");

            $ultimaContagem = $inventContEndRepo->findBy(["inventarioEndereco" => $inventarioEnderecoEn]);

            return $inventContEndRepo->save([
                "inventarioEndereco" => $inventarioEnderecoEn,
                "sequencia" => (count($ultimaContagem) + 1),
                "contagem" => $inventarioEnderecoEn->getContagem(),
                "contagemDivergencia" => $divergencia
            ], false);

        } catch (\Exception $e) {
            throw $e;
        }
    }

}