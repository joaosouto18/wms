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
            $inventarioEn = self::save([
                'descricao' => $params['descricao'],
                'dthIicio' => new \DateTime(),
                'status' => InventarioNovo::STATUS_GERADO,
//                'modelo' => $params['descricao']
            ], false);

            /** @var InventarioNovo\InventarioEnderecoNovoRepository $inventarioEnderecoRepo */
            $inventarioEnderecoRepo = $this->em->getRepository('wms:InventarioNovo\InventarioEnderecoNovo');

            if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
                /** @var InventarioNovo\InventarioContEndProdRepository $invEndProdRepod */
                $invEndProdRepod = $this->em->getRepository('wms:InventarioNovo\InventarioEndProd');
            }

            foreach ($params['selecionados'] as $item) {
                $inventarioEnderecoEn = $inventarioEnderecoRepo->save([
                    'inventario' => $inventarioEn,
                    'depositoEndereco' => $this->em->getReference('wms:Deposito\Endereco', $item['id']),
                    'contagem' => 1,
                    'finalizado' => 'N'
                ]);
                if ($params['criterio'] === InventarioNovo::CRITERIO_PRODUTO) {
                    $invEndProdRepod->save([
                        'inventarioEndereco' => $inventarioEnderecoEn,
                        'produto' => $this->em->getReference('wms:Produto', ['id' => $item['codProduto'], 'grade' => $item['grade']])
                    ]);
                }
            }

            $this->em->flush();
            $this->em->commit();
            return $invEn;

        } catch (\Exception $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}