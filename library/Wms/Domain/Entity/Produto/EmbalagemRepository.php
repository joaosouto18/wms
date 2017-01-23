<?php

namespace Wms\Domain\Entity\Produto;

use Doctrine\ORM\EntityRepository;

class EmbalagemRepository extends EntityRepository
{

    /**
     * @param $novaEmbalagem \Wms\Domain\Entity\Produto\Embalagem
     * @return bool|\Exception
     */
    public function checkEmbalagemDefault($novaEmbalagem)
    {
        try{
            if (!empty($novaEmbalagem) && is_a($novaEmbalagem,'\Wms\Domain\Entity\Produto\Embalagem')){
                $criterio = array(
                    'codProduto' => $novaEmbalagem->getProduto()->getId(),
                    'grade' => $novaEmbalagem->getProduto()->getGrade(),
                    'isPadrao' => 'S'
                );

                $result = $this->findBy($criterio);

                if (count($result) > 1) {
                    if (($key = array_search($novaEmbalagem, $result)) !== false) {
                        unset($result[$key]);
                    }

                    /** @var \Wms\Domain\Entity\Produto\Embalagem $obj */
                    foreach ($result as $key => $obj) {
                        $obj->setIsPadrao('N');
                        $this->_em->persist($obj);
                    }

                    $this->_em->flush();
                }

                return true;
            } else {
                throw new \Exception("A variavel passada não é válida");
            }
        }catch (\Exception $e){
            return $e;
        }
    }

    public function updateEmbalagem($codBarras, $enderecoEn, $capacidadePicking)
    {
        $embalagemRepo = $this->getEntityManager()->getRepository('wms:Produto\Embalagem');
        $embalagemEn = $embalagemRepo->findOneBy(array('codigoBarras' => $codBarras));
        $embalagemEntities = $embalagemRepo->findBy(array('codProduto' => $embalagemEn->getCodProduto(), 'grade' => $embalagemEn->getGrade()));

        if (!isset($embalagemEn) || empty($embalagemEn)) {
            throw new \Exception('Produto não encontrado');
        }
        foreach ($embalagemEntities as $embalagem) {
            $embalagem->setEndereco($enderecoEn);
            $this->getEntityManager()->persist($embalagemEn);
        }

        $embalagemEn->setCapacidadePicking($capacidadePicking);
        $this->getEntityManager()->persist($embalagemEn);
        $this->getEntityManager()->flush();
    }

}
