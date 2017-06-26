<?php
/**
 * Created by PhpStorm.
 * User: Rodrigo
 * Date: 20/07/2016
 * Time: 15:16
 */

namespace Wms\Module\Produtividade\Form;

use Wms\Module\Expedicao\Form\ModeloSeparacao;
use Wms\Domain\Entity\Expedicao\ModeloSeparacao as tipoSeparacao;
use Wms\Module\Produtividade\Form\Subform\EtiquetaSeparacao;
use Wms\Module\Produtividade\Form\Subform\MapaSeparacao;
use Wms\Module\Web\Form;

class EquipeSeparacao extends Form
{

    public function init()
    {
        //form's attr
        $this->setAttribs(array('id' => 'equipe-separacao-form', 'class' => 'saveForm'));

        $parametroRepo = $this->getEm()->getRepository('wms:Sistema\Parametro');
        $parametro = $parametroRepo->findOneBy(array('constante' => 'MODELO_SEPARACAO_PADRAO'));
        $idModeloSeparacao = $parametro->getValor();

        $modeloSeparacaoRepo = $this->getEm()->getRepository('wms:Expedicao\ModeloSeparacao');
        $modeloSeparacao = $modeloSeparacaoRepo->findOneBy(array('id' => $idModeloSeparacao));

        if ($modeloSeparacao->getTipoSeparacaoFracionado() === tipoSeparacao::TIPO_SEPARACAO_MAPA || $modeloSeparacao->getTipoSeparacaoNaoFracionado() === tipoSeparacao::TIPO_SEPARACAO_MAPA) {
            $this->addSubFormTab('Vincular Mapas', new MapaSeparacao, 'mapas');
            $this->addSubFormTab('Vincular Etiquetas', new EtiquetaSeparacao, 'etiquetas');
        } elseif ($modeloSeparacao->getTipoSeparacaoFracionado() === tipoSeparacao::TIPO_SEPARACAO_ETIQUETA || $modeloSeparacao->getTipoSeparacaoNaoFracionado() === tipoSeparacao::TIPO_SEPARACAO_ETIQUETA) {
            $this->addSubFormTab('Vincular Etiquetas', new EtiquetaSeparacao, 'etiquetas');
            $this->addSubFormTab('Vincular Mapas', new MapaSeparacao, 'mapas');
        }
    }

}