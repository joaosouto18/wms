let templates = [];

templates.push({
    name:'uiPreviewer-inventario.html',
    template: '    <div ng-show="display">' +
        '        <div class="ui-dialog ui-widget ui-widget-content ui-corner-all" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-wms-dialog-modal"' +
        '             style="display: block; z-index: 1002; outline: 0px; position: absolute; height: 600px; width: 780px; top: 100px; left: 120px;">' +
        '            <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">' +
        '                <span class="ui-dialog-title" id="ui-dialog-title-wms-dialog-modal">{{description}}</span>' +
        '                <a href="#" class="ui-dialog-titlebar-close ui-corner-all" role="button" ng-click="close()">' +
        '                    <span class="ui-icon ui-icon-closethick">close</span>' +
        '                </a>' +
        '            </div>' +
        '            <div id="wms-dialog-modal" style="font-size: 12px;" class="ui-dialog-content ui-widget-content">' +
        '                <form id="mainForm" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" action="" method="post">' +
        '                    <div>' +
        '                        <fieldset id="fieldset-Buscar"><legend>Novo inventário por: {{criterio}}</legend>' +
        '                            <div class="field">' +
        '                                <label for="dscNovoInv" class="field optional">Nome do novo inventário</label>' +
        '                                <input type="text" name="dscNovoInv" id="dscNovoInv" ng-model="dscNomeInventario" size="40" class="focus" placeholder="Ex.: Inventário da Rua 1">' +
        '                            </div>' +
        '                            <div class="field">' +
        '                               <label for="status" class="field optional">Status</label>' +
        '                               <select ng-model="criterioForm.status" id="status" ng-model="modeloSelecionado" ng-options="modelo.dscModelo for modelo in modelos track by modelo.id">' +
        '                               </select>' +
        '                           </div>' +
        '                        </fieldset>' +
        '                    </div>' +
        '                </form>' +
        '                <fieldset id="fs-grid-selected">' +
        '                    <legend>Selecionados</legend>' +
        '                    <div class="grid">' +
        '                        <div class="gMassAction">' +
        '                            <div class="gSelect">' +
        '                                <span>Total de {{itens.length}} incluídos</span>' +
        '                            </div>' +
        '                            <div class="gAction">' +
        '                                <button type="button" class="btn-grid" ng-click="criarInventario(criterio)"><span>Criar Inventario</span></button>' +
        '                            </div>' +
        '                        </div>' +
        '                        <table class="gTable" style="width: 708px">' +
        '                            <tbody >' +
        '                            <tr class="gTTitle">' +
        '                               <td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '                                   <div ng-if="column.type === \'ordenator\'"><a href="" title="" class="sort" ng-click="ordenarPor(column.name)"><span>{{column.label}}</span></a></div>' +
        '                                   <div ng-if="column.type === \'dropAction\'" align="center"><span>Ação</span></div>' +
        '                               </td>' +
        '                            </tr>' +
        '                            </tbody>' +
        '                        </table>' +
        '                        <div ng-show="itens.length" style="overflow-y:scroll; max-height: 310px" >' +
        '                            <table class="gTable">' +
        '                                <tr class="gTResultSet " ng-repeat="item in itens">' +
        '                                   <td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '                                       <div ng-if="column.type === \'ordenator\'">{{item[column.name]}}</div>' +
        '                                       <div ng-if="column.type === \'dropAction\'" align="center"><img alt="remover" style="cursor: pointer" ng-click="drop(item)" src="/img/icons/cross.png"></div>' +
        '                                   </td>' +
        '                                </tr>' +
        '                            </table>' +
        '                        </div>' +
        '                    </div>' +
        '                </fieldset>' +
        '            </div>' +
        '        </div>' +
        '        <div class="ui-widget-overlay" style="float:  left ; margin width: 100%; height: 100%; z-index: 1001;"></div>' +
        '    </div>'
});

angular.module('wms').run(function ($templateCache) {
    angular.forEach(templates, function (obj) {
        $templateCache.put(obj.name, obj.template)
    });
});