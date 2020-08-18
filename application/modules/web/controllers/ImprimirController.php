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

            if ($tipo == Endereco::PICKING || $tipo == Endereco::PICKING_DINAMICO || $tipo == Endereco::CROSS_DOCKING) {

                $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PICKING");
                $pdf = self::gerarEtiquetasPdf($enderecos, $modelo);
                $pdf->Output('Etiquetas-endereco-Picking.pdf', 'D');
            } elseif ($tipo == Endereco::PULMAO) {
                $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PULMAO");
                $pdf = self::gerarEtiquetasPdf($enderecos, $modelo);
                $pdf->Output('Etiquetas-endereco-Pulmão.pdf', 'D');
            }

//            exit;
        } catch (Exception $e) {
            $this->addFlashMessage('error', $e->getMessage());
            $this->_redirect('/imprimir');
        }
    }

    private function gerarEtiquetasPdf($enderecos, $modelo)
    {
        $quantidadeByPage = null;
        $unico = false;
        if ($modelo == 14) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(115, 55));
        } else if ($modelo == 16) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(120, 60));
        } else if (($modelo == 4) || ($modelo == 6) || $modelo == 15) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(110, 60));
        } elseif ($modelo == 7) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(100, 75));
        } elseif ($modelo == 9) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(85, 30));
        } elseif ($modelo == 10) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(150, 91));
        } elseif ($modelo == 11) {
            $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
        } elseif ($modelo == 1) {
            $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
            $quantidadeByPage = 7;
        } elseif($modelo == 12){
            $modelo = 9;
            $unico = true;
            /*
             * Usa o modelo 9 na Campo Bom
             * porem precisa imprimir apenas uma etiqueta
             */
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(85, 30));
        } elseif ($modelo == 13) {
            $etiqueta = new EtiquetaEndereco("L", 'mm',  array(100, 27));
        } else if ($modelo == 17) {
            $etiqueta = new EtiquetaEndereco("L", 'mm', array(100, 35));
        }
        else {
            $etiqueta = new EtiquetaEndereco("P", 'mm', "A4");
        }
        $etiqueta->imprimir($enderecos, $modelo, $unico, $quantidadeByPage);
        return $etiqueta;
    }

    public function printEtiquetasAjaxAction()
    {
        $modelo = $this->getSystemParameterValue("MODELO_ETIQUETA_PICKING");
        $txt = str_replace("\r","",str_replace("\n","",file_get_contents('enderecos.txt')));
        $array = explode(";", $txt);

        $enderecos = [];
        foreach ($array as $ends){
            $enderecos[] = ['DESCRICAO' => $ends];
        }

        $pdf = self::gerarEtiquetasPdf($enderecos, $modelo);
        $pdf->Output('Etiquetas-enderecos-txt.pdf', 'D');
    }
}