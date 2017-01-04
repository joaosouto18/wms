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
        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        $idAcao = $this->getRequest()->getParam('id');

        $acaoEn = $acaoIntRepo->find($idAcao);
        $acaoIntRepo->processaAcao($acaoEn);
    }
}