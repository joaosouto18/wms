angular.module("wms").controller("layoutDesingerCtrl", function($scope, $http, $filter, uiDialogService) {

    let convertToPx = function(val, unit) {
        switch (unit.id) {
            case 'px':
                return parseFloat(val);
            case 'mm':
                return parseFloat(val) * 3.7795275590551;
            case 'cm':
                return (parseFloat(val) * 10) * 3.7795275590551;
        }
    };

    let convertPxTo = function(val, unit) {
        switch (unit.id) {
            case 'px':
                return parseFloat(val).toFixed(3);
            case 'mm':
                return (parseFloat(val) / 3.7795275590551).toFixed(3);
            case 'cm':
                return ((parseFloat(val) / 3.7795275590551) / 10).toFixed(3);
        }
    };

    class Component{
        constructor(dataInit, dpa, posZ) {
            let centerArea = dpa.getCenter();
            this.realX = this.posX = (centerArea.x - (dataInit.minW / 2));
            this.realY = this.posY = (centerArea.y - (dataInit.minH / 2));
            this.posZ = posZ;
            this.selected = false;
            this.width = dataInit.minW;
            this.height = dataInit.minH;
            this.realWidth = dataInit.minW;
            this.realHeight = dataInit.minH;
            this.label = dataInit.desc;
        }

        isSelected() {
            return this.selected;
        }

        isImage() {
            return (this instanceof ImageComponent);
        }

        isText() {
            return (this instanceof TextComponent)
        }

        select() {
            this.selected = true;
            return this;
        }

        unSelect() {
            this.selected = false;
            return this;
        }

        updateUnit(unit) {
            this.updateUnitSize(unit);
            this.updateUnitPos(unit);
            return this;
        }

        updateUnitSize(unit) {
            this.width = convertPxTo(this.realWidth, unit);
            this.height = convertPxTo(this.realHeight, unit);
            return this;
        }

        updateUnitPos(unit) {
            this.posX = convertPxTo(this.realX, unit);
            this.posY = convertPxTo(this.realY, unit);
            return this;
        }

        verifyPositionOut(dpa) {
            let minX = parseFloat(this.realX) + (parseFloat(this.realWidth) + 1);
            let minY = parseFloat(this.realY) + (parseFloat(this.realHeight) + 1);
            return (dpa.realWidth < minX || dpa.realHeight < minY);
        }
    }

    class ImageComponent extends Component {
        constructor(dataInit, dpa, posZ) {
            super(dataInit, dpa, posZ);
            this.src = dataInit.src;
        }
    }

    class TextComponent extends Component {
        constructor(dataInit, dpa, posZ) {
            super(dataInit, dpa, posZ);
            this.exampleValue = dataInit.exampleValue;
        }
    }

    let verifyPosItensOutArea = function (dpa) {
        let isOut = false;
        $.each($scope.componentAdded, function (k, component) {
            isOut = component.verifyPositionOut(dpa);
            if (isOut) return;
        });
        return isOut;
    };

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

    let repositionComponent = function (x, y) {
        let component = $scope.componentConfig;
        if (!isNaN(x))
            component.realX = x;
        if (!isNaN(y))
            component.realY = y;

        component.updateUnitPos($scope.displayArea.unit, 3);
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
                        endOnly: true
                    })
                ],
                // enable autoScroll
                autoScroll: false,

                onstart: function(event) {
                    $scope.selectComponent($scope.componentAdded.filter((item) => item.$$hashKey === event.target.getAttribute('ld-component-id'))[0]);
                },
                // call this function on every dragmove event
                onmove: dragMoveListener,
                // call this function on every dragend event
                onend: function (event) {
                    let target = event.target;
                    let x = parseFloat(target.getAttribute('data-x'));
                    let y = parseFloat(target.getAttribute('data-y'));
                    if (x < 0 || y < 0) {
                        if (x < 0) {
                            x = 0;
                            target.setAttribute('data-x', x);
                        }
                        if (y < 0) {
                            y = 0;
                            target.setAttribute('data-y', y);
                        }
                        dragMoveListener(event);
                    }
                    repositionComponent(x, y);
                }
            });

// this is used later in the resizing and gesture demos
        window.dragMoveListener = dragMoveListener;
        dragMoveListener(event)
    };

    $scope.startList = function () {
        $scope.componentForm.list.push({type:'text', exampleValue: "102030", name:"codProduto", desc:"Cod. Produto", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'text', exampleValue: "UNICA", name:"grade", desc:"Grade", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'text', exampleValue: "552154", name:"codEtiqueta", desc:"Num. Etiqueta", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'text', exampleValue: "223542", name:"codPedido", desc:"Pedido", selected:false, width:80, minW:80, minH: 12, height: 12});
        $scope.componentForm.list.push({type:'img',  name:"logo", desc:"Logomarca", selected:false, src:'/img/layoutDesigner/logo_cliente.png', width:120, minW:120, minH: 60, height: 60});
    };

    $scope.updateUnitArea = function () {
        let rwp = $scope.displayArea.realWidth;
        let rhp = $scope.displayArea.realHeight;
        let unit = $scope.displayArea.unit;

        $scope.displayArea.width = convertPxTo(rwp, unit);
        $scope.displayArea.height = convertPxTo(rhp, unit);

        if (!isEmpty($scope.componentConfig)) $scope.componentConfig.updateUnit(unit);
    };

    $scope.addComponent = function () {
        if (isEmpty($scope.componentForm.selected)) {
            uiDialogService.dialogAlert("Selecione o componente que deseja adicionar!");
            return;
        }
        let component = null;
        switch ($scope.componentForm.selected.type) {
            case 'text':
                component = new TextComponent($scope.componentForm.selected, $scope.displayArea, ($scope.componentAdded.length + 1));
                break;
            case 'img':
                component = new ImageComponent($scope.componentForm.selected, $scope.displayArea, ($scope.componentAdded.length + 1));
        }
        $scope.componentAdded.push(component);
        $scope.selectComponent(component);
    };

    $scope.changeSizeArea = function() {
        let dpa = $scope.displayArea;
        dpa.realWidth = convertToPx(dpa.width, dpa.unit);
        dpa.realHeight = convertToPx(dpa.height, dpa.unit);
        if (verifyPosItensOutArea(dpa)) {
            dpa.realWidth = dpa.widthBkp;
            dpa.realHeight = dpa.heightBkp;
            dpa.width = convertPxTo(dpa.realWidth, dpa.unit);
            dpa.height = convertPxTo(dpa.realHeight, dpa.unit);
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
        let index = $scope.componentAdded.findIndex(function (el) { return (el.$$hashKey === component.referedId) });
        $scope.componentAdded[index] = angular.copy(component);
    };

    let rollbackComponentChanged = function (component){
        $scope.componentConfig = angular.copy($scope.componentAdded.filter((item) => item.$$hashKey === component.referedId)[0]);
    };

    let dragMoveListener = function(event) {
        let target = event.target;
        // keep the dragged position in the data-x/data-y attributes

        let dirX = parseFloat(target.getAttribute('data-x'));
        let dirY = parseFloat(target.getAttribute('data-y'));

        let x = dirX + event.dx;
        let y = dirY + event.dy;

        //translate the element
        target.style.webkitTransform =
            target.style.transform =
                'translate(' + x + 'px, ' + y + 'px)';

        // update the posiion attributes
        target.setAttribute('data-x', x);
        target.setAttribute('data-y', y);
    };

    $scope.selectComponent = function (component) {
        let activeItem = $scope.componentAdded.filter((item) => item.isSelected())[0];
        if (!isEmpty(activeItem)) activeItem.unSelect();
        component.updateUnit($scope.displayArea.unit, 3);
        component.select();

        $scope.componentConfig = angular.copy(component);
        $scope.componentConfig.referedId = component.$$hashKey;
    };

    $scope.changePosComponent = function (component) {
        let unit = $scope.displayArea.unit;
        component.realX = convertToPx(component.posX, unit);
        component.realY = convertToPx(component.posY, unit);
        if (component.verifyPositionOut($scope.displayArea)) {
            rollbackComponentChanged(component);
        } else {
            listenerComponentChanged(component);
        }
    };

    $scope.checkEnter = function (e) {
        if (e.keyCode === 13) e.target.blur();
    }
});