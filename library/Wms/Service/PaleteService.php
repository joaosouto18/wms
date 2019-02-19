<?php

namespace Wms\Service;

use Wms\Domain\Entity\Enderecamento\PaleteProduto;
use Wms\Domain\Entity\Produto;
use Wms\Math;

class PaleteService extends AbstractService
{
    /**
     * @param $recebimento int
     * @param $produto Produto
     * @param $volume int
     * @param $lote string
     * @param $saldoRealocado int|float
     */
    public function removerSaldoRealocado ($recebimento, $produto, $volume, $lote, $saldoRealocado)
    {
        /** @var PaleteProduto[] $prodsPaletes */
        $prodsPaletes = $this->getRepository()->getPaletesByStatus($recebimento, $produto->getId(), $produto->getGrade(), $volume, $lote);
        $umaAlteradas = [];

        foreach ($prodsPaletes as $prodPalete) {
            if (Math::compare($saldoRealocado, $prodPalete->getQtd(), ">=")) {
                $saldoRealocado = Math::subtrair($saldoRealocado, $prodPalete->getQtd());

                if (!isset($umaAlteradas[$prodPalete->getUma()->getId()]))
                    $umaAlteradas[$prodPalete->getUma()->getId()] = $prodPalete->getUma();

                $this->em->remove($prodPalete);
            } else {
                $prodPalete->setQtd(Math::subtrair($prodPalete->getQtd(), $saldoRealocado));
                $saldoRealocado = 0;
                $this->em->persist($prodPalete);
            }

            if (empty($saldoRealocado)) break;
        }
    }
}