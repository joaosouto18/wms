<?php

use Wms\Domain\Entity\Produto\Tipo,
    Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Produto\Imprimir as ImprimirForm,
    Wms\Module\Web\Grid\Produto\Imprimir,
    Wms\Module\Armazenagem\Printer\EtiquetaEndereco,
    Wms\Module\Web\Page;

/**
 * Description of Web_TipoProdutoController
 *
 * @author Jéssica Mayrink <jmayrinkfonseca@gmail.com>
 */
class Web_ImprimirController extends Action
{
    public function indexAction()
    {
        $form = new Wms\Module\Web\Form\Produto\Imprimir();
        $values = $form->getParams();
        if ($values) {
            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $EnderecoRepository */
            $EnderecoRepository   = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
            $endereco = $EnderecoRepository->getEnderecosByParam($values);

            $this->view->endereco = $endereco;

        }
        $this->view->form = $form;
    }

    public function imprimirAction()
    {
        $params = $this->_getAllParams();
        $enderecos = $params['enderecos'];

        $codEndereco = implode(",", $enderecos);

        /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $EnderecoRepository */
        $EnderecoRepository   = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
        $endereco = $EnderecoRepository->getImprimirEndereco($codEndereco);

        $modelo =  $this->getSystemParameterValue("MODELO_ETIQUETA_PICKING");
            if ($modelo == 4) {
                $etiqueta = new EtiquetaEndereco("L", 'mm', array(110, 60));
            } else {
                $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
            }
        $etiqueta->imprimir($endereco, $modelo);
    }
}