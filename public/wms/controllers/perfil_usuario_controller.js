/**
 * @tag controllers, home
 * Displays a table of perfil_usuarios.	 Lets the user 
 * ["Wms.Controllers.PerfilUsuario.prototype.form submit" create], 
 * ["Wms.Controllers.PerfilUsuario.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.PerfilUsuario.prototype.&#46;destroy click" destroy] perfil_usuarios.
 */
$.Controller.extend('Wms.Controllers.PerfilUsuario',
/* @Static */
{
    pluginName: 'perfilUsuario'
},
/* @Prototype */
{
    /**
     * When the page loads, gets all bars to be displayed.
     */
    '{window} load': function(){
        
        $("#tree").dynatree({
            checkbox: true,
            selectMode: 3,
            keyboard: true,
            autoFocus: true,
            clickFolderMode: 3,
            strings: {
                loading: "Carregando…",
                loadError: "Erro ao Carregar!"
            },
            initAjax: {
                url: URL_MODULO + "/perfil-usuario/permissoes-json",
                data: {
                    codPerfil : $('#identificacao-id').val()
                }
            },
            onPostInit: function(isReloading, isError) {
                this.redraw();
            }  
        });
        
        $("#btnContrairAll").click(function(){
            $("#tree").dynatree("getRoot").visit(function(node){
                node.expand(false);
            });
            return false;
        });

        $("#btnExpandirAll").click(function(){
            $("#tree").dynatree("getRoot").visit(function(node){
                node.expand(true);
            });
            return false;
        });

        $("#btnDeselectAll").click(function(){
            $("#tree").dynatree("getRoot").visit(function(node){
                node.select(false);
            });
            return false;
        });
        
        $("#btnSelectAll").click(function(){
            $("#tree").dynatree("getRoot").visit(function(node){
                node.select(true);
            });
            return false;
        });
    },
    /**
     * Responds to the create form being submitted by creating a new Wms.Models.PerfilUsuario.
     * @param {jQuery} el A jQuery wrapped element.
     * @param {Event} ev A jQuery event whose default action is prevented.
     */
    'submit': function( el, ev ){
        ev.preventDefault();
        
        el.unbind('submit');
        
        aBetterEventObject = ev;
        // Now you can do what you want: (Cross-browser)
        aBetterEventObject.preventDefault()
        aBetterEventObject.isDefaultPrevented()
        aBetterEventObject.stopPropagation()
        aBetterEventObject.isPropagationStopped()
        aBetterEventObject.stopImmediatePropagation()
        aBetterEventObject.isImmediatePropagationStopped()
        
        if (el.valid()) {
            // then append Dynatree selected 'checkboxes':
            var tree = $("#tree").dynatree("getTree");
            var acoes = [];
            
            acoesSelecionadas = tree.serializeArray();
            for (i = 0; i < acoesSelecionadas.length; i++) 
                if (acoesSelecionadas[i].value.toString().indexOf('folder') == -1) 
                    acoes.push(acoesSelecionadas[i].value);
            
            valores = el.formParams();
            valores.acoes = acoes;
            
            success = function(){
                
            };
            
            complete = function(){
                window.location = URL_MODULO + '/perfil-usuario';
            };
            
            if ($('#identificacao-id').val() == '') {
                valores.action = 'add';
                new Wms.Models.PerfilUsuario.create(valores, success, complete);
            } else {
                valores.action = 'edit';
                new Wms.Models.PerfilUsuario.update($('#identificacao-id').val(), valores, success, complete);
            }
        }
        
        return;
    }
});