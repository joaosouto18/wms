<?php

namespace Wms\Service;

use Wms\Domain\Entity\Enderecamento\Palete;
use Wms\Domain\Entity\Enderecamento\PaleteProduto;
use Wms\Math;

class PaleteService extends AbstractService
{
    /**
     * @param $recebimento int
     * @param $palete Palete
     */
    public function removerSaldoRealocado ($recebimento, $palete)
    {
        /** @var Palete[] $umaAlteradas */
        $umaAlteradas = [];

        /** @var PaleteProduto $prodUma */
        foreach ($palete->getProdutos() as $prodUma) {
            $prod = $prodUma->getProduto();
            $saldoRealocado = $prodUma->getQtd();

            /** @var PaleteProduto[] $prodsPaletes */
            $prodsPaletes = $this->getRepository()->getPaletesByStatus($recebimento, $prod->getId(), $prod->getGrade(), $prodUma->getCodProdutoVolume(), $prodUma->getLote());

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

        foreach ($umaAlteradas as $uma) {
            $prodsUma = $uma->getProdutos();
            if (empty($prodsUma)) $this->em->remove($uma);
        }

    }
}