let templates = [];
templates.push({
    name:'default.html',
    template: ''
});

templates.push({
    name: 'dialog-template.html',
    template: '<div id="wms-dialog-msg" style="font-size: 12px; " ng-bind-html="model.msg"></div>'
});

templates.push({
    name: 'previewer-inventario.html',
    template:
        '<div id="wms-dialog-modal" ng-controller="previewerInventarioCtrl" style="font-size: 12px;" class="ui-dialog-content ui-widget-content">' +
        '<div id="sending" class="sending">' +
        '<p>Criando inventário</p>' +
        '<img height="150%" src="/img/ajax-bar-loader.gif" width="50%">' +
        '</div>' +
        '<div id="div-form">' +
        '<form id="mainForm" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" action="" method="post">' +
        '<div>' +
        '<fieldset id="fieldset-Buscar">' +
        '<legend>Novo inventário por: {{lblCriterio}}</legend>' +
        '<div class="field">' +
        '<label for="dscNovoInv" class="field optional">Nome do novo inventário</label>' +
        '<input type="text" name="dscNovoInv" id="dscNovoInv" ng-model="dscInventario" ng-keypress="enterSubmit($event)" size="40" class="focus" placeholder="Ex.: Inventário da Rua 1">' +
        '</div>' +
        '<div class="field">' +
        '<label for="modelo" class="field optional">Modelo do Inventário</label>' +
        '<select id="modelo" style="min-width: 120px" ng-model="modSel" ng-options="modelo.dscModelo for modelo in modelos"></select>' +
        '</div>' +
        '<div class="field" style="margin: 0; padding-top: 10px">' +
        '<table>' +
        '<thead>' +
        '<tr>' +
        '<th>Controlar validade?</th>' +
        '<th>Número mínimo de contagens iguais?</th>' +
        '<th>Comparar com estoque atual?</th>' +
        '<th>Permitir mesmo usuário em N contagens?</th>' +
        '<th>Forçar contagem de todos os itens no endereço?</th>' +
        '<th>Contar volumes individualmente?</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>' +
        '<tr>' +
        '<td valign="center" align="center">{{modSel.controlaValidadeLbl}}</td>' +
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
        '</div>' +
        '</div>'
});

templates.push({
    name: 'previewer-result-inventario.html',
    template:
        '<div id="wms-dialog-modal" ng-controller="previewerResultInventarioCtrl" ng-init="prepare()" style="font-size: 12px;" class="ui-dialog-content ui-widget-content">' +
        '<form id="mainForm" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" action="">' +
        '<div>' +
        '<fieldset id="fieldset-Buscar">' +
        '<legend>Resultado do inventário {{inventario.id}} ({{inventario.descricao}})</legend>' +
        '<div class="field" style="margin: 0; padding-top: 10px">' +
        '<table>' +
        '<thead>' +
        '<tr>' +
        '<th>Controlar validade?</th>' +
        '<th>Número mínimo de contagens iguais?</th>' +
        '<th>Comparar com estoque atual?</th>' +
        '<th>Permitir mesmo usuário em N contagens?</th>' +
        '<th>Forçar contagem de todos os itens no endereço?</th>' +
        '<th>Contar volumes individualmente?</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>' +
        '<tr>' +
        '<td valign="center" align="center">{{inventario.controlaValidadeLbl}}</td>' +
        '<td valign="center" align="center">{{inventario.numContagens}}</td>' +
        '<td valign="center" align="center"><img ng-if="inventario.comparaEstoque" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.comparaEstoque" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center"><img ng-if="inventario.usuarioNContagens" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.usuarioNContagens" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center"><img ng-if="inventario.contarTudo" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.contarTudo" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td valign="center" align="center"><img ng-if="inventario.volumesSeparadamente" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.volumesSeparadamente" alt="Não" src="/img/icons/cross.png"></td>' +
        '</tr>' +
        '</tbody>' +
        '</table>' +
        '</div>' +
        '</fieldset>' +
        '</div>' +
        '</form>' +
        '<fieldset id="fs-grid-selected">' +
        '<legend>Resultado</legend>' +
        '<div class="grid">' +
        '<div class="gMassAction">' +
        '<div class="gAction">' +
        '<button ng-show="results.length" type="button" class="btn-grid" ng-click="atualizar()"><span>Atualizar o estoque</span></button>' +
        '<button ng-show="noResults" type="button" class="btn-grid" ng-click="cancelar()"><span>Cancelar Inventário</span></button>' +
        '</div>' +
        '</div>' +
        '<table class="gTable" style="width:1007px!important">' +
        '<tbody >' +
        '<tr class="gTTitle">' +
        '<td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '<div><a href="" title="" class="sort" ng-click="ordenarPor(column.name)"><span>{{column.label}}</span></a></div>' +
        '<div align="center" class="field"><input ng-if="column.filter" type="text" ng-model="objectFilter[column.name]" ng-attr-ui-mask="{{column.filter.masked && column.filter.maskFilter || \'\'}}" ng-attr-model-view-value="{{column.filter.masked && true || false}}" size="{{column.filter.size}}" class=""></div>' +
        '</td>' +
        '</tr>' +
        '</tbody>' +
        '</table>' +
        '<div style="overflow-y:scroll; max-height: 340px" >' +
        '<table class="gTable">' +
        '<tr ng-show="showLoading">' +
        '<td colspan="100%" align="center">' +
        '<img height="150%" src="/img/ajax-bar-loader.gif" width="50%">' +
        '</td>' +
        '</tr>' +
        '<tr class="gTResultSet" ng-repeat="result in results | filter:objectFilter | orderBy:tbOrderBy:direction:typeSensitiveComparator">' +
        '<td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '<div>{{result[column.name]}}</div>' +
        '</td>' +
        '</tr>' +
        '<tr>' +
        '<td colspan="100%" align="center" ng-show="noResults" >Nenhum produto/endereço para atualizar estoque</td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '</div>' +
        '</fieldset>' +
        '</div>'
});

templates.push({
    name: 'divergencia-grid.html',
    template:
        '<style>' +
        '.gTTitle a.sort span{ background:none}' +
        '.table-info-grid{' +
        'border-collapse: collapse;' +
        '} ' +
        '.table-info-grid th{' +
        'padding: 5px 10px; ' +
        'width: 16.66%; ' +
        '}' +
        '.table-info-grid td{' +
        'vertical-align: middle;' +
        'text-align: center' +
        '}' +
        '</style>' +
        '<div id="wms-dialog-modal" ng-controller="divergenciaGridCtrl" ng-init="prepare()" style="font-size: 12px;" class="ui-dialog-content ui-widget-content">' +
        '<form id="mainForm" enctype="application/x-www-form-urlencoded" accept-charset="UTF-8" action="">' +
        '<div>' +
        '<fieldset id="fieldset-Buscar">' +
        '<legend>Resultado do inventário {{inventario.id}} ({{inventario.descricao}})</legend>' +
        '<div class="field" style="margin: 0; padding-top: 10px">' +
        '<table class="table-info-grid">' +
        '<thead>' +
        '<tr>' +
        '<th>Controlar validade?</th>' +
        '<th>Número mínimo de contagens iguais?</th>' +
        '<th>Comparar com estoque atual?</th>' +
        '<th>Permitir mesmo usuário em N contagens?</th>' +
        '<th>Forçar contagem de todos os itens no endereço?</th>' +
        '<th>Contar volumes individualmente?</th>' +
        '</tr>' +
        '</thead>' +
        '<tbody>' +
        '<tr>' +
        '<td>{{inventario.controlaValidadeLbl}}</td>' +
        '<td>{{inventario.numContagens}}</td>' +
        '<td><img ng-if="inventario.comparaEstoque" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.comparaEstoque" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td><img ng-if="inventario.usuarioNContagens" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.usuarioNContagens" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td><img ng-if="inventario.contarTudo" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.contarTudo" alt="Não" src="/img/icons/cross.png"></td>' +
        '<td><img ng-if="inventario.volumesSeparadamente" alt="Sim" src="/img/icons/tick.png"><img ng-if="!inventario.volumesSeparadamente" alt="Não" src="/img/icons/cross.png"></td>' +
        '</tr>' +
        '</tbody>' +
        '</table>' +
        '</div>' +
        '</fieldset>' +
        '</div>' +
        '</form>' +
        '<fieldset id="fs-grid-selected">' +
        '<legend>Resultado</legend>' +
        '<div class="grid">' +
        '<div class="gMassAction">' +
        '<div class="gAction">' +
        '<button ng-show="results.length" type="button" class="btn-grid" ng-click="export(\'pdf\')"><span>Exportar PDF</span></button>' +
        '<button ng-show="results.length" type="button" class="btn-grid" ng-click="export(\'csv\')"><span>Exportar CSV</span></button>' +
        '</div>' +
        '</div>' +
        '<table class="gTable" style="width:1127px!important">' +
        '<tbody >' +
        '<tr class="gTTitle">' +
        '<td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '<div><a href="" title="" class="sort" ng-click="ordenarPor(column.name)"><span>{{column.label}}</span></a></div>' +
        '<div align="center" class="field"><input ng-if="column.filter" type="text" ng-model="objectFilter[column.name]" ng-attr-ui-mask="{{column.filter.masked && column.filter.maskFilter || \'\'}}" ng-attr-model-view-value="{{column.filter.masked && true || false}}" size="{{column.filter.size}}" class=""></div>' +
        '</td>' +
        '</tr>' +
        '</tbody>' +
        '</table>' +
        '<div style="overflow-y:scroll; max-height: 340px" >' +
        '<table class="gTable">' +
        '<tr ng-show="showLoading">' +
        '<td colspan="100%" align="center">' +
        '<img height="150%" src="/img/ajax-bar-loader.gif" width="50%">' +
        '</td>' +
        '</tr>' +
        '<tr class="gTResultSet" ng-repeat="result in results | filter:objectFilter | orderBy:tbOrderBy:direction:typeSensitiveComparator">' +
        '<td ng-repeat="column in gridColumns" width="{{column.width}}">' +
        '<div>{{result[column.name]}}</div>' +
        '</td>' +
        '</tr>' +
        '<tr>' +
        '<td colspan="100%" align="center" ng-show="noResults" >Nenhum produto/endereço teve divergência</td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '</div>' +
        '</fieldset>' +
        '</div>'
});

angular.module('wms').run(function ($templateCache) {
    angular.forEach(templates, function (obj) {
        $templateCache.put(obj.name, obj.template)
    });
});