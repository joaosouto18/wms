angular.module('wms').run(function ($templateCache) {
    angular.forEach(templates, function (obj) {
        $templateCache.put(obj.name, obj.template)
    });
});

let templates = [];

templates.push({
    name:'uiPreviewer-inventario.html',
    template: '' +
        '<div class="ui-dialog ui-widget ui-widget-content ui-corner-all ui-draggable ui-resizable" tabindex="-1" role="dialog" aria-labelledby="ui-dialog-title-wms-dialog-modal" style="display: block; z-index: 1002; outline: 0px; position: absolute; height: 211.171px; width: 778.171px; top: 345px; left: 111px;">' +
            '<div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix">' +
                '<span class="ui-dialog-title" id="ui-dialog-title-wms-dialog-modal">Teste</span>' +
                '<a href="#" class="ui-dialog-titlebar-close ui-corner-all" role="button">' +
                    '<span class="ui-icon ui-icon-closethick">close</span>' +
                '</a>' +
            '</div>' +
            '<div id="wms-dialog-modal" style="font-size: 12px; width: 753px; min-height: 109.829px; height: 173px;" class="ui-dialog-content ui-widget-content">' +
                '<div id="tpl-content" ng-include="" src="/tpl.html"></div>' +
            '</div>' +
            '<div class="ui-resizable-handle ui-resizable-n"></div>' +
            '<div class="ui-resizable-handle ui-resizable-e"></div>' +
            '<div class="ui-resizable-handle ui-resizable-s"></div>' +
            '<div class="ui-resizable-handle ui-resizable-w"></div>' +
            '<div class="ui-resizable-handle ui-resizable-se ui-icon ui-icon-gripsmall-diagonal-se ui-icon-grip-diagonal-se" style="z-index: 1001;"></div>' +
            '<div class="ui-resizable-handle ui-resizable-sw" style="z-index: 1002;"></div>' +
            '<div class="ui-resizable-handle ui-resizable-ne" style="z-index: 1003;"></div>' +
            '<div class="ui-resizable-handle ui-resizable-nw" style="z-index: 1004;"></div>' +
        '</div>' +
        '<div class="ui-widget-overlay" style="width: 1052px; height: 853px; z-index: 1001;"></div>'
});