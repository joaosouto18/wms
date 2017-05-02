<?php

use Wms\Module\Web\Controller\Action,
    Wms\Module\Armazenagem\Printer\EtiquetaEndereco,
    Wms\Domain\Entity\Deposito\Endereco ;

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

            $this->view->tipo = $values['tipoEndereco'];
            $this->view->endereco = $endereco;

        }
        $this->view->form = $form;
    }

    public function imprimirAction()
    {
        try {
            $params = $this->_getAllParams();
            $tipo = $this->_getParam('tipo');

            if (!isset($params['enderecos']) || empty($params['enderecos'])) {
                throw new \Exception("Nenhum endereço foi selecionado");
            }

            $codEndereco = implode(",", $params['enderecos']);

            /** @var \Wms\Domain\Entity\Deposito\EnderecoRepository $EnderecoRepository */
            $EnderecoRepository = $this->getEntityManager()->getRepository('wms:Deposito\Endereco');
            $enderecos = $EnderecoRepository->getImprimirEndereco($codEndereco);

            if ($tipo == Endereco::ENDERECO_PICKING ||
                $tipo == Endereco::ENDERECO_PICKING_DINAMICO ) {

                $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PICKING");
                $pdf = self::gerarEtiquetasPdf($enderecos, $modelo);
                $pdf->Output('Etiquetas-endereco-Picking.pdf', 'D');
            } elseif ($tipo == Endereco::ENDERECO_PULMAO) {
                $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PULMAO");
                $pdf = self::gerarEtiquetasPdf($enderecos, $modelo);
                $pdf->Output('Etiquetas-endereco-Pulmão.pdf', 'D');
            }

            exit;
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/imprimir');
        }
    }

    private function gerarEtiquetasPdf($enderecos, $modelo)
    {
        if (($modelo == 4) || ($modelo == 6)) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(110, 60));
        } elseif ($modelo == 7) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(100, 75));
        } elseif ($modelo == 9) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(85, 30));
        } elseif ($modelo == 10) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(150, 25));
        } else {
            $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
        }
        $etiqueta->imprimir($enderecos, $modelo);
        return $etiqueta;
    }
}