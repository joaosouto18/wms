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
        /** @var \Wms\Domain\Entity\Util\SiglaRepository $siglaRepo */
        $siglaRepo = $this->getEntityManager()->getRepository('wms:Util\Sigla');

        $idAcao = $this->getRequest()->getParam('id');
        $options = $this->getRequest()->getParam('options',null);
        $idFiltro = $this->_getParam('idFiltro',\Wms\Domain\Entity\Integracao\AcaoIntegracaoFiltro::DATA_ESPECIFICA);
        if ($options != null) {
            $options = explode(",",$options);
        }

        $acaoEn = $acaoIntRepo->find($idAcao);
        $result = $acaoIntRepo->processaAcao($acaoEn,$options,'E','P',null,$idFiltro);

        /*
        if ($result === true) {
            $msg = 'Integração realizada com sucesso';
        } else {
            $msg = $result;
        }


        $filtroEn = $siglaRepo->find($idFiltro);

        if (is_array($msg)) {
            var_dump($msg);
        } else {
            echo utf8_decode($msg);
        }

        echo utf8_decode("<html> <br><br> Id.Ação: </html>") . $idAcao;
        echo utf8_decode("<html> <br><br> Id.Filtro: </html>" . $idFiltro . ' - ' . $filtroEn->getSigla() );

        if ($options != null) {
            echo "<html> <br><br> Options: <br></html>";
            foreach ($options as $key => $value) {
                echo ":?" . $key +1 . ' => ' . $value;
                echo "<html> <br> </html>";
            }
        }
        */
    }
}