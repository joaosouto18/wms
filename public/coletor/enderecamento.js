function focusInput() {
    document.getElementById('uma').focus();
    hiddenElement("formulario-nivel");
}

function hiddenElement(id) {
    document.getElementById(id).style.display= "none";
}

function showElement(id) {
    document.getElementById(id).style.display= "block";
}