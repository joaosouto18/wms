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

    }
    public function runAction()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', '-1');

        /** @var \Wms\Domain\Entity\Integracao\AcaoIntegracaoRepository $acaoIntRepo */
        $acaoIntRepo = $this->getEntityManager()->getRepository('wms:Integracao\AcaoIntegracao');

        $idAcao = $this->getRequest()->getParam('id');

        $acaoEn = $acaoIntRepo->find($idAcao);
        $acaoIntRepo->processaAcao($acaoEn);
    }
}