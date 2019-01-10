let templates = [];
templates.push({
    name:'default.html',
    template: ''
});

templates.push({
    name: 'dialog-template.html',
    template: '<div id="wms-dialog-msg" style="font-size: 12px;">{{model.msg}}</div>'
});


templates.push({
    name: 'previewer-inventario.html',
    template:
        '<div id="wms-dialog-modal" ng-controller="previewerCtrl" style="font-size: 12px;" class="ui-dialog-content ui-widget-content">' +
        '<form id="mainForm" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" action="" method="post">' +
        '<div>' +
        '<fieldset id="fieldset-Buscar">' +
        '<legend>Novo inventário por: {{lblCriterio}}</legend>' +
        '<div class="field">' +
        '<label for="dscNovoInv" class="field optional">Nome do novo inventário</label>' +
        '<input type="text" name="dscNovoInv" id="dscNovoInv" ng-model="dscInventario" size="40" class="focus" placeholder="Ex.: Inventário da Rua 1">' +
        '</div>' +
        '<div class="field">' +
        '<label for="modelo" class="field optional">Modelo do Inventário</label>' +
        '<select id="modelo" style="min-width: 120px" ng-model="modSel" ng-options="modelo.dscModelo for modelo in modelos"></select>' +
        '</div>' +
        '<div class="field" style="margin: 0; padding-top: 10px">' +
        '<table>' +
        '<thead>' +
        '<tr>' +
        '<th>Conferir Item à item?</th>' +
        '<th>Controlar validade?</th>' +
        '<th>Exigir U.M.A.?</th>' +
        '<th>Número mínimo de contagens iguais?</th>' +
        '<th>Comparar com estoque atual?</th>' +
        '<th>Permitir mesmo usuário em N contagens?</th>' +
        '<th>Forçar contagem de todos os itens no endereço?</th>' +
        '<th>Contar volumes individualmente?</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>' +
        '<tr>' +
        '<td valign="center" align="center"><img ng-if="modSel.itemAItem" alt="Sim" src="/img/icons/tick.png"><img ng-if="!modSel.itemAItem" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center">{{modSel.controlaValidadeLbl}}</td>' +
        '<td valign="center" align="center"><img ng-if="modSel.exigeUMA" alt="Sim" src="/img/icons/tick.png"><img ng-if="!modSel.exigeUMA" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center">{{modSel.numContagens}}</td>' +
        '<td valign="center" align="center"><img ng-if="modSel.comparaEstoque" alt="Sim" src="/img/icons/tick.png"><img ng-if="!modSel.comparaEstoque" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center"><img ng-if="modSel.usuarioNContagens" alt="Sim" src="/img/icons/tick.png"><img ng-if="!modSel.usuarioNContagens" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center"><img ng-if="modSel.contarTudo" alt="Sim" src="/img/icons/tick.png"><img ng-if="!modSel.contarTudo" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center"><img ng-if="modSel.volumesSeparadamente" alt="Sim" src="/img/icons/tick.png"><img ng-if="!modSel.volumesSeparadamente" alt="Não" src="/img/icons/cross.png"></td>' +
        '</tr>' +
        '</tbody>' +
        '</table>' +
        '</div>' +
        '</fieldset>' +
        '</div>' +
        '</form>' +
        '<fieldset id="fs-grid-selected">' +
        '<legend>Selecionados</legend>' +
        '<div class="grid">' +
        '<div class="gMassAction">' +
        '<div class="gSelect">' +
        '<span>Total de {{itens.length}} incluídos</span>' +
        '</div>' +
        '<div class="gAction">' +
        '<button ng-show="modelos.length" type="button" class="btn-grid" ng-click="criarInventario()"><span>Concluir</span></button>' +
        '</div>' +
        '</div>' +
        '<table class="gTable" style="width: 708px">' +
        '<tbody >' +
        '<tr class="gTTitle">' +
        '<td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '<div ng-if="column.type === \'ordenator\'"><a href="" title="" class="sort" ng-click="ordenarPor(column.name)"><span>{{column.label}}</span></a></div>' +
        '<div ng-if="column.type === \'dropAction\'" align="center"><span>{{column.label}}</span></div>' +
        '</td>' +
        '</tr>' +
        '</tbody>' +
        '</table>' +
        '<div style="overflow-y:scroll; max-height: 340px" >' +
        '<table class="gTable">' +
        '<tr class="gTResultSet " ng-repeat="item in itens">' +
        '<td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '<div ng-if="column.type === \'ordenator\'">{{item[column.name]}}</div>' +
        '<div ng-if="column.type === \'dropAction\'" align="center"><img alt="remover" style="cursor: pointer" ng-click="drop(item)" src="/img/icons/cancel.png"></div>' +
        '</td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '</div>' +
        '</fieldset>' +
        '</div>'
});

templates.push({
    name: 'andamento-accordion.html',
    template: '<div class="accordion__item js-accordion-item">' +
        '<div class="accordion-header js-accordion-header">{{title}}</div>' +
    '<div class="accordion-body js-accordion-body">' +
    '<div class="accordion-body__contents">' +
    '{{content}}' +
'</div>' +
'</div><!-- end of accordion body -->' +
'</div><!-- end of accordion item -->'
});

angular.module('wms').run(function ($templateCache) {
    angular.forEach(templates, function (obj) {
        $templateCache.put(obj.name, obj.template)
    });
});