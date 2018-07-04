<?php

use Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Sistema\Configuracao as ConfiguracaoForm,
    Wms\Domain\Entity\Sistema\Parametro\Valor as ValorEntity,
    Wms\Domain\Repository\Sistema\Parametro\Valor as ValorRepository;

/**
 * Description of Web_ConfiguracaoController
 *
 * @author Renato Medina <medinadato@gmail.com>
 */
class Web_ConfiguracaoController extends Action
{

    public function indexAction()
    {

        $form = new ConfiguracaoForm;

        //botao save
        Page::configure(array(
            'buttons' => array(
                array(
                    'label' => 'Atualizar',
                    'cssClass' => 'btnSave'
                )
            )
        ));

        try {
            $params = $this->getRequest()->getParams();

            if ($this->getRequest()->isPost() && $form->isValid($_POST)) {
                $repository = $this->em->getRepository('wms:Sistema\Parametro');
                $repository->update($params);
                $this->em->flush();
                $this->_helper->messenger('success', 'Valores atualizados com sucesso');
                return $this->redirect('index');
            }
        } catch (\Exception $e) {
            $this->_helper->messenger('error', $e->getMessage());
        }

        $this->view->form = $form;
    }


    public function getMaskEnderecoAjaxAction()
    {
        $arrQtdDigitos = \Wms\Util\Endereco::getQtdDigitos();
        $mascara = \Wms\Util\Endereco::mascara($arrQtdDigitos,'9');
        $arrMasc = \Wms\Util\Endereco::separar($mascara, $arrQtdDigitos);

        $mask = implode('.', array_reverse($arrMasc,true));

        $result = array(
            'mask' => $mask,
            'enderecoRua' => $arrMasc['rua'],
            'enderecoPredio' => $arrMasc['predio'],
            'enderecoNivel' => $arrMasc['nivel'],
            'enderecoApartamento' => $arrMasc['apartamento']
        );

        $this->_helper->json($result);
    }

}
