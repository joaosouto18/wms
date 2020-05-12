angular.module("wms", ['ngSanitize', 'uiDialogService', 'ui.mask', 'ngMask'])
    .filter("interval", function () {
    return function (input, interval) {
        if (input.length > 0) {
            let start = Number(interval.start);
            let end = Number(interval.end);
            return input.slice(start, (end + 1));
        }
    }
}).filter('contains', function() {
    return function (array, needle, notContains, like) {
        if (Number.isInteger(needle)) needle = parseInt(needle);
        else if (!isNaN(needle)) needle = parseFloat(needle);

        if (!notContains) {
            return (array.indexOf(needle) >= 0);
        } else {
            return !(array.indexOf(needle) >= 0);
        }
    };
}).filter('queryFilter', function($filter){
    return function (array, needle, strict) {
        for(let prop in needle) {
            if (isEmpty(needle[prop])) delete needle[prop];
        }
        return $filter('filter')(array, needle, strict);
    }
}).directive('uppercase', function() {
    return {
        restrict: 'A',
        require: 'ngModel',
        link: function(scope, element, attrs, modelCtrl) {
            let uppercase = function(inputValue) {
                if (inputValue == undefined) inputValue = '';
                let upped = inputValue.toUpperCase();
                if (upped !== inputValue) {
                    // see where the cursor is before the update so that we can set it back
                    let selection = element[0].selectionStart;
                    modelCtrl.$setViewValue(upped);
                    modelCtrl.$render();
                    // set back the cursor after rendering
                    element[0].selectionStart = selection;
                    element[0].selectionEnd = selection;
                }
                return upped;
            }
            if (!isEmpty(attrs.uppercase) && attrs.uppercase != 'false' && attrs.uppercase != '' && attrs.uppercase != '0') {
                modelCtrl.$parsers.push(uppercase);
                uppercase(scope[attrs.ngModel]); // capitalize initial value
            }
        }
    };
});

function typeSensitiveComparatorFn () {
    return function(v1, v2) {
        // If we don't get strings, just compare by index
        if (angular.isNumber(Number(v1.value)) && angular.isNumber(Number(v2.value))) {
            return (Number(v1.value) < Number(v2.value)) ? -1 : 1;
        } else if (v1.type !== 'string' || v2.type !== 'string') {
            return (v1.index < v2.index) ? -1 : 1;
        }

        // Compare strings alphabetically, taking locale into account
        return v1.value.localeCompare(v2.value);
    }
}

function extractFile(file, fileName) {
    let link = document.createElement('a');
    // create a blobURI pointing to our Blob
    link.href = URL.createObjectURL(file);
    link.download = fileName;
    link.style = "display: none";
    // some browser needs the anchor to be in the doc
    document.body.append(link);
    link.click();
    link.remove();
    // in case the Blob uses a lot of memory
    URL.revokeObjectURL(link.href);
}

function isEmpty( val ) {

    // test results
    //---------------
    // []        true, empty array
    // {}        true, empty object
    // null      true
    // undefined true
    // ""        true, empty string
    // ''        true, empty string
    // 0         false, number
    // true      false, boolean
    // false     false, boolean
    // Date      false
    // function  false

    if (val === undefined)
        return true;

    if (typeof (val) == 'function' || typeof (val) == 'number' || typeof (val) == 'boolean' || Object.prototype.toString.call(val) === '[object Date]')
        return false;

    if (val == null || val.length === 0)        // null or 0 length array
        return true;

    if (typeof (val) == "object") {
        // empty object

        var r = true;

        for (var f in val)
            r = false;

        return r;
    }

    return false;
}