<?php

use Wms\Module\Web\Page,
    Wms\Module\Web\Controller\Action,
    Wms\Module\Web\Form\Sistema\Configuracao as ConfiguracaoForm,
    \Wms\Util\Endereco as EnderecoUtil;

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
        try {
            $arrQtdDigitos = EnderecoUtil::getQtdDigitos();
            $mascara = EnderecoUtil::mascara($arrQtdDigitos, '9', EnderecoUtil::FORMATO_MATRIZ_ASSOC);
            $this->_helper->json([
                'mask' => implode('.', $mascara),
                'enderecoRua' => $mascara['rua'],
                'enderecoPredio' => $mascara['predio'],
                'enderecoNivel' => $mascara['nivel'],
                'enderecoApartamento' => $mascara['apartamento']
            ]);
        } catch (Exception $e){
            $this->_helper->messenger('error', "Problema na formatação da mascara de endereço: '". $e->getMessage(). "'. Um valor padrão foi atribuído. Notifique ao suporte da Imperium");
            $this->_helper->json([
                'mask' => '99.999.99.99',
                'enderecoRua' => '99',
                'enderecoPredio' => '999',
                'enderecoNivel' => '99',
                'enderecoApartamento' => '99'
            ]);
        }
    }

}
