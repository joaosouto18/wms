<?php

namespace Wms\Module\Web\Form\Subform\Produto;

use Core\Grid;
use Wms\Domain\Entity\Produto,
    Core\Form\SubForm;

/**
 * Description of DadosLogisticos
 *
 * @author medina
 */
class Enderecamento extends SubForm
{

    public function init()
    {
        $this->addElement('hidden', 'idProduto');
        $this->addElement('hidden', 'grade');

        $this->addElement('text', 'enderecoReferencia', array(
            'label' => 'Endereço de Referencia',
            'alt' => 'endereco',
            'size' => 20,
            'placeholder' => '00.000.00.00',
        ));

        $this->addElement('hidden', 'area');

        $this->addElement('hidden', 'estrutura');

        $this->addElement('hidden', 'tpEndereco');

        $this->addElement('hidden', 'caracEndereco');

        $this->addDisplayGroup(array('id','area','estrutura','tpEndereco','caracEndereco'), 'identificacao', array('legend' => 'Filtros de Busca'));

    }

    /**
     * Popula os dados de um form a partir de um objeto
     * @param Produto $produto
     */
    public function setDefaultsFromEntity(Produto $produto)
    {
        /** @var \Wms\Domain\Entity\ProdutoRepository $produtoRepo */
        $produtoRepo = $this->getEm()->getRepository('wms:Produto');

        $areaArmaz = $produtoRepo->getSequenciaEndAutomaticoAreaArmazenagem($produto->getId(),$produto->getGrade());
        $estArmaz = $produtoRepo->getSequenciaEndAutomaticoTpEstrutura($produto->getId(),$produto->getGrade());
        $tipoEndereco = $produtoRepo->getSequenciaEndAutomaticoTpEndereco ($produto->getId(),$produto->getGrade());
        $caracteristicaEndereco = $produtoRepo->getSequenciaEndAutomaticoCaracEndereco ($produto->getId(),$produto->getGrade());
        $embalagemRepo = $this->getEm()->getRepository('wms:Produto\Embalagem');
        $volumeRepo = $this->getEm()->getRepository('wms:Produto\Volume');
        $embalagemEn = $embalagemRepo->findOneBy(array('codProduto' => $produto->getId(), 'grade' => $produto->getGrade()));
        $volumeEn = $volumeRepo->findOneBy(array('codProduto' => $produto->getId(), 'grade' => $produto->getGrade()));

        $enderecoReferencia = $produto->getEnderecoReferencia();
        if ($enderecoReferencia != null) {
            $enderecoReferencia = $enderecoReferencia->getDescricao();
        } else if (isset($embalagemEn) && !empty($embalagemEn)) {
            $embalagemEn = $embalagemEn->getEndereco();
            if (isset($embalagemEn) && !empty($embalagemEn)) {
                $enderecoReferencia = $embalagemEn->getDescricao();
            }
        } else if (isset($volumeEn) && !empty($volumeEn)) {
            $volumeEn = $volumeEn->getEndereco();
            if (isset($volumeEn) && !empty($volumeEn)) {
                $enderecoReferencia = $volumeEn->getDescricao();
            }
        }

        $values = array(
            'idProduto' => $produto->getId(),
            'grade' => $produto->getGrade(),
            'enderecoReferencia' => $enderecoReferencia,
            'area' => $areaArmaz,
            'estrutura' => $estArmaz,
            'tpEndereco' => $tipoEndereco,
            'caracEndereco' => $caracteristicaEndereco
        );
        $this->setDefaults($values);
    }

}