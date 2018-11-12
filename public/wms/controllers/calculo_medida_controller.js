/**
 * @tag controllers, home
 * Displays a table of unitizadors.	 Lets the user 
 * ["Wms.Controllers.Unitizador.prototype.form submit" create], 
 * ["Wms.Controllers.Unitizador.prototype.&#46;edit click" edit],
 * or ["Wms.Controllers.Unitizador.prototype.&#46;destroy click" destroy] unitizadors.
 */


$.Controller.extend('Wms.Controllers.CalculoMedida',
/* @Static */
{
    pluginName: 'calculaMedidas'
},
/* @Prototype */
{
    formatMoney : function(n, c, d, t){
        c = isNaN(c = Math.abs(c)) ? 2 : c, d = d == undefined ? "," : d, t = t == undefined ? "." : t, s = n < 0 ? "-" : "", i = parseInt(n = Math.abs(+n || 0).toFixed(c)) + "", j = (j = i.length) > 3 ? j % 3 : 0;
        return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
    },
    
    arredonda: function (num, dec) {
        var result = Math.round(num * Math.pow(10, dec)) / Math.pow(10, dec);
        return result;
    },
    
    calculaCubagem: function (largura, altura, profundidade, dec) {
        var resultado = largura * altura * profundidade;
        return this.arredonda(resultado, dec);
    },
    
    calculaArea: function (largura, profundidade) {
        var resultado = largura * profundidade;
        return this.arredonda(resultado, 3);
    },
    
    calculaNormaPaletizacao: function (lastro, camadas) {
        var resultado = lastro * camadas;
        return this.arredonda(resultado, 2);
    },
    
    '.parametro-cubagem change' : function () {
        var largura = $('#largura').val().replace('.','').replace(',','.');
        var altura = $('#altura').val().replace('.','').replace(',','.');
        var profundidade = $('#profundidade').val().replace('.','').replace(',','.');
        var cubagem = this.calculaCubagem(largura, altura, profundidade, 4);
        
        cubagem = this.formatMoney(parseFloat(cubagem.toString().replace(',', '.')).toFixed(4), 4, ',', '.');
        $('#cubagem').val(cubagem);
    },
    
    '.parametro-area change' : function () {
        var largura = $('#largura').val().replace('.','').replace(',','.');
        var profundidade = $('#profundidade').val().replace('.','').replace(',','.');
        var area = this.calculaArea(largura, profundidade);
        
        area = this.formatMoney(parseFloat(area.toString().replace(',', '.')).toFixed(3), 3, ',', '.');
        $('#area').val(area);
    }
});