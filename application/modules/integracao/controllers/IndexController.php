<html>
<script language='JavaScript'>
    function close(){
        //var win = window.open("about:blank", "_self"); win.close();
    }
</script>
<body onLoad="close()"></body>
</html>

<?php
/**
 *
 * @author Lucas Chinelate <lucaschinelate@hotmail.com>
 */
class Integracao_IndexController extends Core\Controller\Action\WebService
{
    public function init(){
        $front = \Zend_Controller_Front::getInstance();
        $front->setParam('noErrorHandler', true);
        $front->setParam('noViewRenderer', true);
        if (null != \Zend_Layout::getMvcInstance()) {
            \Zend_Layout::getMvcInstance()->disableLayout();
        }

        $this->getHelper('viewRenderer')->setNoRender(true);
    }

    public function runAction()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        $idAcao = $this->getRequest()->getParam('id');
        $options = $this->getRequest()->getParam('options',null);
        $idFiltro = $this->_getParam('idFiltro',\Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::DATA_ESPECIFICA);
        if ($options != null) {
            $options = explode(",",$options);
        }

        $acaoEn = $acaoIntRepo->find($idAcao);
        $acaoIntRepo->processaAcao($acaoEn,$options,'E','P',null,$idFiltro);

    }

    public function integracaoErrorAjaxAction()
    {
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoAndamentoRepository $integracaoAndamentoRepository */
        $integracaoAndamentoRepository = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracaoAndamento');
        $integracaoError = $integracaoAndamentoRepository->getStatusAcaoIntegracao();

        $pdf = new \Wms\Module\Web\Report\Generico('L');
        $pdf->init($integracaoError, 'integracao-falha', 'Integrações com falha');
    }
}