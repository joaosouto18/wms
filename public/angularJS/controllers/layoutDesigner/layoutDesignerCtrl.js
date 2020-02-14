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
            this.displayArea = dpa;
            let centerArea = this.displayArea.getCenter();
            this.realX = this.posX = (centerArea.x - (dataInit.minW / 2));
            this.realY = this.posY = (centerArea.y - (dataInit.minH / 2));
            this.posZ = posZ;
            this.selected = false;
            this.realWidth = this.width = dataInit.minW;
            this.realHeight = this.height = dataInit.minH;
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

        updateUnit() {
            this.updateUnitSize();
            this.updateUnitPos();
            return this;
        }

        updateUnitSize() {
            let unit = this.displayArea.unit;
            this.width = convertPxTo(this.realWidth, unit);
            this.height = convertPxTo(this.realHeight, unit);
            return this;
        }

        updateUnitPos() {
            let unit = this.displayArea.unit;
            this.posX = convertPxTo(this.realX, unit);
            this.posY = convertPxTo(this.realY, unit);
            return this;
        }

        verifyPositionOut() {
            let unit = this.displayArea.unit;

            let posX = convertToPx(this.posX, unit);
            let compWidth = convertToPx(this.width, unit);
            let minWidth = posX + (compWidth+ 1);

            let posY = convertToPx(this.posY, unit);
            let compHeight = convertToPx(this.height, unit);
            let minHeight = posY + (compHeight+ 1);

            let dpaW = convertToPx(this.displayArea.width, this.displayArea.unit);
            let dpaH = convertToPx(this.displayArea.height, this.displayArea.unit);
            return (dpaW < minWidth || dpaH < minHeight);
        }

        updatePos() {
            let unit = this.displayArea.unit;
            this.realX = convertToPx(this.posX, unit);
            this.realY = convertToPx(this.posY, unit);
            return this;
        }

        rollbackPos() {
            let unit = this.displayArea.unit;
            this.posX = convertPxTo(this.realX, unit);
            this.posY = convertPxTo(this.realY, unit);
            return this;
        }

        updateSize() {
            let unit = this.displayArea.unit;
            this.realWidth = convertToPx(this.width, unit);
            this.realHeight = convertToPx(this.height, unit);
            return this;
        }

        rollbackSize() {
            let unit = this.displayArea.unit;
            this.width = convertPxTo(this.realWidth, unit);
            this.height = convertPxTo(this.realHeight, unit);
            return this;
        }
    }

    class ImageComponent extends Component {
        constructor(dataInit, dpa, posZ) {
            super(dataInit, dpa, posZ);
            this.src = dataInit.src;
            this.file = {name: "default.png"};
        }

        loadNewImage() {
            this.src = loadImage(this.file, true);
        }
    }

    class TextComponent extends Component {
        constructor(dataInit, dpa, posZ) {
            super(dataInit, dpa, posZ);
            this.exampleValue = dataInit.exampleValue;
            this.textDesc = dataInit.descFieldText
        }
    }

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
        unit: $scope.unitList[0],
        proportion: 1,
        getCenter: function () {
            return {x: (this.realWidth / 2), y: (this.realHeight / 2)}
        },
        updateArea: function () {
            this.realWidth = convertToPx(this.width, this.unit);
            this.realHeight = convertToPx(this.height, this.unit);
        },
        rollbackArea: function () {
            this.width = convertPxTo(this.realWidth, this.unit);
            this.height = convertPxTo(this.realHeight, this.unit);
        },
        verifyHasItensOutArea: function () {
            let isOut = false;
            $.each($scope.componentAdded, function (k, component) {
                isOut = component.verifyPositionOut();
                if (isOut) return;
            });
            return isOut;
        }
    };

    let repositionComponent = function (x, y) {
        let component = $scope.componentConfig;
        if (!isNaN(x))
            component.realX = x;
        if (!isNaN(y))
            component.realY = y;

        component.updateUnitPos();
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
        let unit = $scope.displayArea.unit;

        $scope.displayArea.width = convertPxTo($scope.displayArea.realWidth, unit);
        $scope.displayArea.height = convertPxTo($scope.displayArea.realHeight, unit);

        if (!isEmpty($scope.componentConfig)) $scope.componentConfig.updateUnit();
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
        if (dpa.verifyHasItensOutArea()) {
            dpa.rollbackArea();
            uiDialogService.dialogAlert("Existem compontentes que ficarão fora da área de impressão! Ajuste antes de prosseguir.");
        } else {
            dpa.updateArea();
        }
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
        component.updateUnit();
        component.select();
        $scope.componentConfig = component;
    };

    $scope.changePosComponent = function (component) {
        if (component.verifyPositionOut()) {
            component.rollbackPos();
            uiDialogService.dialogAlert("Valor inválido! O componente ficará fora da área de impressão!");
        } else {
            component.updatePos();
        }
    };

    $scope.changeSizeComponent = function(component) {
        if (component.verifyPositionOut()) {
            component.rollbackSize();
            uiDialogService.dialogAlert("Valor inválido! O componente ficará fora da área de impressão!");
        } else {
            component.updateSize();
        }
    };

    $scope.checkEnter = function (e) {
        if (e.keyCode === 13) e.target.blur();
    };
});