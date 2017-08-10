<?php
use Wms\Module\Web\Controller\Action,
    Wms\Module\Enderecamento\Report\MovimentacaoProduto;

class Enderecamento_Relatorio_MovimentacaoProdutoController extends Action
{
    public function indexAction()
    {
        $utilizaGrade = $this->getSystemParameterValue("UTILIZA_GRADE");
        $caractEndEn = $this->_em->getRepository('wms:Deposito\Endereco\Caracteristica')->findAll();

        $arrTiposEnderecos = array();
        /** @var \Wms\Domain\Entity\Deposito\Endereco\Caracteristica $caract */
        foreach ($caractEndEn as $caract) {
            $arrTiposEnderecos[$caract->getId()] = ucwords(mb_strtolower($caract->getDescricao(), 'UTF-8'));
        }
        $form = new \Wms\Module\Armazenagem\Form\MovimentacaoProduto\Filtro();
        $form->init($utilizaGrade, $arrTiposEnderecos);
        $values = $form->getParams();

        if ($values)
        {
             $relatorio = new MovimentacaoProduto();
             $relatorio->init($values);
        }

        $this->view->form = $form;

    }

}