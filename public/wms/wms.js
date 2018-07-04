steal.plugins(	
    'jquery/controller',		// a widget factory
    'jquery/controller/subscribe',	// subscribe to OpenAjax.hub
    'jquery/view/ejs',              // client side templates
    'jquery/controller/view',       // lookup views with the controller's name
    'jquery/model',                 // Ajax wrappers
    'jquery/dom/form_params'        // form data helper
    )		
// loads styles
.css(
    '../css/admin/style',
    '../css/admin/form',
    '../css/jquery/menu',
    '../css/admin/grid',
    '../css/jquery/ui',
    '../css/jquery/dynatree/ui.dynatree' 
    )	
// 3rd party script's (like jQueryUI), in resources folder
.resources(
    'ui', 
    'form', 
    'menu', 
    'validate', 
    'cookie', 
    'mask', 
    'metadata',
    'dynatree',
    'priceformat',
    'fn',
    'uiblock',
    'SetCase',
    'jquery/flot.min',
    'jquery/jqplot.barRenderer.min',
    'jquery/jqplot.categoryAxisRenderer.min',
    'jquery/jqplot.pointLabels.min',
    'jquery/jqplot.pieRenderer.min',
    'jquery/jquery.cycle.all.latest',
    'jsapi',
    'admin/default',
    'wms'
    ) 
// loads files in models folder 
.models(
    'perfil_usuario',
    'pessoa_dados_pessoais',
    'pessoa_endereco',
    'pessoa_telefone',
    'produto_embalagem',
    'produto_volume',
    'deposito_endereco',
    'menu_item',
    'norma_paletizacao',
    'produto_dado_logistico',
    'enderecamento'
    ) 
// loads files in controllers folder
.controllers(
    'perfil_usuario',
    'pessoa_dados_pessoais',
    'pessoa_endereco',
    'pessoa_telefone',
    'calculo_medida',
    'box',
    'ajuda',
    'praca',
    'rota',
    'produto_embalagem',
    'produto_volume',
    'produto_dado_logistico',
    'recebimento',
    'auditoria',
    'deposito_endereco',
    'veiculo', 
    'menu_item',
    'produto',
    'filtro_nota_fiscal',
    'expedicao',
    'relatorios_simples',
    'relatorio_pedidos_expedicao',
    'enderecamento'
    )
// adds views to be added to build
.views()
    .then(function($){
        
    });						
