function focusInput() {
    hiddenElement("formulario-nivel");
    document.getElementById('uma').focus();
    try {
        document.getElementById('produto').focus();
    } catch(err){}
}

function hiddenElement(id) {
    document.getElementById(id).style.display= "none";
}

function showElement(id) {
    document.getElementById(id).style.display= "block";
}
