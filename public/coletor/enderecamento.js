function focusInput() {
    hiddenElement("formulario-nivel");
    try {
        document.getElementById('produto').focus();
        document.getElementById('uma').focus();
    } catch(err){}
}

function hiddenElement(id) {
    document.getElementById(id).style.display= "none";
}

function showElement(id) {
    document.getElementById(id).style.display= "block";
}
