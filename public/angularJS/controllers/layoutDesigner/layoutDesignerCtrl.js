angular.module("wms").controller("layoutDesingerCtrl", function($scope, $http, $filter, uiDialogService) {

    let convertToPx = function(val, unit) {
        switch (unit) {
            case 'px':
                return parseFloat(val);
            case 'mm':
                return parseFloat(val) * 3.7795275590551;
            case 'cm':
                return (parseFloat(val) * 10) * 3.7795275590551;
        }
    };

    let convertPxTo = function(val, unit) {
        switch (unit) {
            case 'px':
                return parseFloat(val);
            case 'mm':
                return parseFloat(val) / 3.7795275590551;
            case 'cm':
                return (parseFloat(val) / 3.7795275590551) / 10;
        }
    };

    let Component = class Component{
        constructor(dataInit, dpa) {
            this.posX = 0;
            this.realX = 0;
            this.posY = 0;
            this.realY = 0;
            this.posZ = 0;
            this.selected = false;
            this.width = dataInit.minW;
            this.height = dataInit.minH;
            this.realWidth = dataInit.minW;
            this.realHeight = dataInit.minH;
        }

        select() {
            this.selected = true;
        }

        updateUnit(unit, decimals = 3) {
            this.width = convertPxTo(this.realWidth, unit.id).toFixed((decimals) );
            this.height = convertPxTo(this.realHeight, unit.id).toFixed(decimals);
            this.posX = convertPxTo(this.realX, unit.id).toFixed(decimals);
            this.posY = convertPxTo(this.realY, unit.id).toFixed(decimals);
        }


    };

    let verifyPosItensOutArea = function (dpa) {
        let isOut = false;
        $.each($scope.componentAdded, function (k, component) {
            if (verifyPositionOut(dpa, component)) {
                isOut = true;
                return;
            }
        });
        return isOut;
    };

    let verifyPositionOut = function(dpa, component) {
        let minX = parseFloat(component.realX) + (parseFloat(component.realWidth) + 1);
        let minY = parseFloat(component.realY) + (parseFloat(component.realHeight) + 1);
        return (dpa.realWidth < minX || dpa.realHeight < minY);
    };

    $scope.componentsList = [];
    $scope.componentAdded = [];
    $scope.unitList = [
        {id:'px', dsc: "Pixel"},
        {id:'mm', dsc: "Milímetro" },
        {id:'cm', dsc: "Centímetro" }
    ];

    $scope.componentConfig = {};

    $scope.componentForm = {
        list: [],
        selected: null
    };

    $scope.displayArea = {
        width: 400,
        height: 200,
        realWidth: 400,
        realHeight: 200,
        widthBkp: 400,
        heightBkp: 200,
        unit: $scope.unitList[0],
        proportion: 1,
        getCenter: function () {
            return {x: (this.realWidth / 2), y: (this.realHeight / 2)}
        }
    };

    let initComponent = function(dataComp) {
        let centerArea = $scope.displayArea.getCenter();

        component.selected = false;
        component.realWidth = component.minW;
        component.realHeight = component.minH;
        component.posX = (centerArea.x - (component.realWidth / 2));
        component.bkpX = component.realX = component.posX;
        component.posY = (centerArea.y - (component.realHeight / 2));
        component.bkpY = component.realY = component.posY;
        component.posZ = ($scope.componentsList.length + 1);
        component.select = function() { this.selected = true};
        component.updateUnit = function () {
            let unit = $scope.displayArea.unit;
            let rwp = this;
            let rhp = component.realHeight;
            let rxp = component.realX;
            let ryp = component.realY;

            component.width = convertPxTo(rwp, unit.id).toFixed(3);
            component.height = convertPxTo(rhp, unit.id).toFixed(3);
            component.posX = convertPxTo(rxp, unit.id).toFixed(3);
            component.posY = convertPxTo(ryp, unit.id).toFixed(3);
        };

        return component
    };

    let repositionComponent = function (target) {
        let x = parseFloat(target.getAttribute('data-x'));
        let y = parseFloat(target.getAttribute('data-y'));
        let component = $scope.componentConfig;
        if (!isNaN(x)) component.bkpX = component.realX = x;
        if (!isNaN(y)) component.bkpY = component.realY = y;

        $scope.componentConfig = updateUnitPosComponent(component, $scope.displayArea.unit);
        listenerComponentChanged(component);
    };

    $scope.prepareInteract = function(element) {
        interact(element)
            .draggable({
                // enable inertial throwing
                inertia: false,
                // keep the element within the area of it's parent
                modifiers: [
                    interact.modifiers.restrictRect({
                        restriction: 'parent',
                        endOnly: false
                    })
                ],
                // enable autoScroll
                autoScroll: false,

                onstart: function(event) {
                    let target = event.target;
                    $scope.selectComponent($scope.componentAdded.filter((item) => item.$$hashKey === target.getAttribute('ld-component-id'))[0]);

                },
                // call this function on every dragmove event
                onmove: dragMoveListener,
                // call this function on every dragend event
                onend: function (event) {
                    repositionComponent(event.target);
                }
            });

// this is used later in the resizing and gesture demos
        window.dragMoveListener = dragMoveListener;
        dragMoveListener(event)
    };

    $scope.startList = function () {
        $scope.componentForm.list.push({type:'text', name:"codProduto", desc:"Cod. Produto", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'text', name:"grade", desc:"Grade", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'text', name:"codEtiqueta", desc:"Num. Etiqueta", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'text', name:"codPedido", desc:"Pedido", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'img',  name:"logo", desc:"Logomarca", selected:false, src:'/img/layoutDesigner/logo_cliente.png', width:120, minW:120, minH: 60, height: 60});
    };

    $scope.updateUnitArea = function () {
        let rwp = $scope.displayArea.realWidth;
        let rhp = $scope.displayArea.realHeight;
        let unit = $scope.displayArea.unit;

        $scope.displayArea.width = convertPxTo(rwp, unit.id).toFixed(3);
        $scope.displayArea.height = convertPxTo(rhp, unit.id).toFixed(3);

        let componentSelected = $scope.componentConfig;
        if (!isEmpty(componentSelected)) $scope.componentConfig = updateUnitComponent(componentSelected);
    };

    let updateUnitComponent = function (component) {
        let unit = $scope.displayArea.unit;
        let rwp = component.realWidth;
        let rhp = component.realHeight;

        component.width = convertPxTo(rwp, unit.id).toFixed(3);
        component.height = convertPxTo(rhp, unit.id).toFixed(3);

        return updateUnitPosComponent(component, unit);
    };

    let updateUnitPosComponent = function(component, unit){
        let rxp = component.realX;
        let ryp = component.realY;

        component.posX = convertPxTo(rxp, unit.id).toFixed(3);
        component.posY = convertPxTo(ryp, unit.id).toFixed(3);
        return component;
    };

    $scope.addComponent = function () {
        if (isEmpty($scope.componentForm.selected)) {
            uiDialogService.dialogAlert("Selecione o componente que deseja adicionar!");
            return;
        }
        let componentInitialized = initComponent(angular.copy($scope.componentForm.selected));
        $scope.componentAdded.push(componentInitialized);
        $scope.selectComponent(componentInitialized);
    };

    $scope.changeSizeArea = function() {
        let dpa = $scope.displayArea;
        dpa.realWidth = convertToPx(dpa.width, dpa.unit.id);
        dpa.realHeight = convertToPx(dpa.height, dpa.unit.id);
        if (verifyPosItensOutArea(dpa)) {
            dpa.realWidth = dpa.widthBkp;
            dpa.realHeight = dpa.heightBkp;
            dpa.width = convertPxTo(dpa.realWidth, dpa.unit.id);
            dpa.height = convertPxTo(dpa.realHeight, dpa.unit.id);
            uiDialogService.dialogAlert("Existem compontentes posicionados fora da área de impressão! Ajuste antes de prosseguir.");
        } else {
            dpa.widthBkp = dpa.realWidth;
            dpa.heightBkp = dpa.realHeight;
        }
        $scope.displayArea = dpa;
    };

    $scope.changeSizeComponent = function() {

    };

    let listenerComponentChanged = function (component) {
        let index = $scope.componentAdded.findIndex(function (el) { return (el.$$hashKey === component.$$hashKey) });
        $scope.componentAdded[index] = component;
    };

    let dragMoveListener = function(event) {
        var target = event.target;
        // keep the dragged position in the data-x/data-y attributes

        let dirX = parseFloat(target.getAttribute('data-x'));
        let dirY = parseFloat(target.getAttribute('data-y'));

        var x = dirX + event.dx;
        var y = dirY + event.dy;

        //translate the element
        target.style.webkitTransform =
            target.style.transform =
                'translate(' + x + 'px, ' + y + 'px)';

        // update the posiion attributes
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    };

    $scope.selectComponent = function (component) {
        let activeItem = $scope.componentAdded.filter((item) => item.selected === true)[0];
        if (!isEmpty(activeItem)) activeItem.selected = false;
        component.select();
        $scope.componentConfig = updateUnitComponent(component);
    };

    $scope.changePosComponent = function () {
        let component = $scope.componentConfig;
        let unit = $scope.displayArea.unit;
        component.realX = convertToPx(component.posX, unit.id);
        component.realY = convertToPx(component.posY, unit.id);
        if (!verifyPositionOut($scope.displayArea, component)) {
            component.bkpX = component.realX;
            component.bkpY = component.realY;
        } else {
            component.realX = component.bkpX;
            component.realY = component.bkpY;
            component.posX = convertPxTo(component.realX, unit.id);
            component.posY = convertPxTo(component.realY, unit.id);
        }
        listenerComponentChanged(component);
    };

    $scope.checkEnter = function (e) {
        if (e.keyCode === 13) {
            e.target.blur();
        }
    }
});